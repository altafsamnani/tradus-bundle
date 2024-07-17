<?php

namespace TradusBundle\Entity;

use Cocur\Slugify\Slugify;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation\Exclude;
use Symfony\Component\Validator\Constraints as Assert;
use TradusBundle\Service\Search\SearchService;
use TradusBundle\Service\Sitecode\SitecodeService;

/**
 * Offer.
 *
 * @ORM\Table(name="offers")
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\OfferRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Offer implements OfferInterface
{
    public const OFFER_STATUS_LIVE = 'live';
    public const DEFAULT_PRICE_TYPE = 'fixed';
    public const DEFAULT_METRICS_LENGTH = 'km';
    public const DEFAULT_IMAGE_DUPLICATED = 0;
    public const SOLR_STATUS_TO_UPDATE = 'to_update';
    public const SOLR_STATUS_IN_QUEUE = 'in_queue';
    public const SOLR_STATUS_IN_INDEX = 'in_index';
    public const SOLR_STATUS_NOT_IN_INDEX = 'not_in_index';
    public const PRICE_TYPE_FIXED = 'fixed';
    public const PRICE_TYPE_AUCTION = 'auction';
    public const PRICE_TYPE_UPON_REQUEST = 'upon-request';
    public const REDIS_NAMESPACE_OTHER_VIEWS = 'other_views:';
    public const REDIS_NAMESPACE_LAST_VIEWED = 'last_viewed:';
    public const OTHER_OFFERS_VIEWED_LIMIT_USERS = 100;
    public const OTHER_OFFERS_VIEWED_LIMIT_OFFERS = 20;
    public const OTHER_OFFERS_VIEWED_NUMBER_SLOTS = 18;
    public const OTHER_OFFERS_VIEWED_NUMBER_SLOTS_CAMPAIGN = 24;
    public const OTHER_OFFERS_VIEWED_ALL_EXPIRATION = 604800;  //7 days
    public const REDIS_FIELD_TOP_OFFERS_VIEWED = 'top_viewed';
    public const COOKIE_OFFERS_LAST_VIEWED_BY_USER = 'tradus_last_viewed';
    public const COOKIE_OFFERS_URL_LAST_VIEWED_BY_USER = 'last_url_visit';
    public const COOKIE_OFFERS_LAST_VIEWED_BY_USER_MAX_STORE = 24;
    public const DEFAULT_MAX_VALUE_ON_NULL = 10000000;
    public const OTHER_OFFERS_LAST_VIEWED_NUMBER_SLOTS = 6;
    public const CACHE_OFFER_ITEM = 300;
    public const HOMEPAGE_NUMBER_SLOTS = 6;
    public const DAY_OFFER_NUMBER_SLOTS = 1;

    public const OTHER = 'other';

    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\Column(name="id", type="integer")
     */
    private $id;

    /**
     * @ORM\Column(name="tpro_id", type="integer", nullable=true)
     * @Assert\Type("integer")
     */
    private $tproId;

    /**
     * @ORM\ManyToOne(targetEntity="TradusBundle\Entity\Seller", inversedBy="offers", cascade={"persist"}, fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="seller_id", referencedColumnName="id")
     * @Assert\NotBlank(message = OfferInterface::FIELD_SELLER_BLANK_ERROR)
     */
    private $seller;

    /**
     * @ORM\OneToMany(targetEntity="TradusBundle\Entity\OfferAnalyticsData", mappedBy="offer", cascade={"persist"}, fetch="EXTRA_LAZY")
     * @Exclude
     */
    private $analytics_data;

    /**
     * The emails send to the seller from the buyers.
     *
     * @ORM\OneToMany(targetEntity="TradusBundle\Entity\Email", mappedBy="offer", fetch="EXTRA_LAZY")
     * @Exclude
     */
    private $emails;

    /**
     * @ORM\OneToMany(targetEntity="TradusBundle\Entity\OfferAttribute", mappedBy="offer", cascade={"persist"}, fetch="EXTRA_LAZY"))
     * @Exclude
     */
    private $attributes;

    /**
     * @ORM\OneToOne(targetEntity="TradusBundle\Entity\OfferAnalytics", mappedBy="offer", cascade={"persist", "remove"}, fetch="EXTRA_LAZY")
     * @Exclude
     */
    private $analytics;

    /** @ORM\OneToMany(targetEntity="TradusBundle\Entity\OfferDescription", mappedBy="offer", cascade={"persist", "remove"}, fetch="EXTRA_LAZY") */
    private $descriptions;

    /**
     * @ORM\OneToMany(targetEntity="TradusBundle\Entity\OfferTitle", mappedBy="offer", cascade={"persist", "remove"}, fetch="EXTRA_LAZY")
     * @Exclude
     */
    private $titles;

    /**
     * @ORM\OneToMany(targetEntity="TradusBundle\Entity\OfferImage", mappedBy="offer", cascade={"persist", "remove"}, fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"sort_order" = "ASC"})
     */
    private $images;

    /**
     * @ORM\OneToMany(targetEntity="TradusBundle\Entity\OfferImage", mappedBy="offer", cascade={"persist", "remove"}, fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"sort_order_pose" = "ASC"})
     */
    private $poseImages;

    /**
     * @ORM\ManyToOne(targetEntity="TradusBundle\Entity\Make", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="make_id", referencedColumnName="id")
     * @Assert\NotBlank(message = OfferInterface::FIELD_MAKE_BLANK_ERROR)
     */
    private $make;

    /**
     * @ORM\ManyToOne(targetEntity="TradusBundle\Entity\Model", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="model_id", referencedColumnName="id")
     */
    private $modelId;

    /**
     * @ORM\ManyToOne(targetEntity="TradusBundle\Entity\Version", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="version_id", referencedColumnName="id")
     */
    private $versionId;

    /** @ORM\OneToOne(targetEntity="TradusBundle\Entity\OfferDepreciations", mappedBy="offer", cascade={"persist"}, fetch="EXTRA_LAZY") */
    private $depreciation;

    /**
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="offer", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id")
     * @Assert\NotBlank(message = OfferInterface::FIELD_CATEGORY_BLANK_ERROR)
     */
    private $category;

    /**
     *  This is the external id given by external partner.
     * @var string
     *
     * @ORM\Column(name="ad_id", type="string", length=255, unique=true, nullable=false)
     * @Assert\Type("string")
     * @Assert\NotBlank(message = OfferInterface::FIELD_AD_ID_BLANK_ERROR)
     */
    private $ad_id;

    /**
     * This is the lectura id given by external partner.
     * @var int
     *
     * @ORM\Column(name="lectura_id", type="integer", nullable=true)
     * @Assert\Type("integer")
     */
    private $lectura_id;

    /**
     * @var int
     *
     * @ORM\Column(name="v1_id", unique=true, type="integer", nullable=true)
     * @Assert\Type("integer")
     */
    private $v1_id;

    /**
     * @var string
     *
     * @ORM\Column(name="model", type="string", length=255, nullable=false)
     * @Assert\Type("string")
     * @Assert\NotBlank(message = OfferInterface::FIELD_MODEL_BLANK_ERROR)
     */
    private $model;

    /**
     * @var float
     *
     * @ORM\Column(name="price", type="float", nullable=true)
     * @Assert\Type("float")
     */
    private $price;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", length=255, nullable=true)
     * @Assert\Type("string")
     */
    private $currency;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255, nullable=true)
     * @Assert\Type("string")
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(name="video_url", type="text", nullable=true)
     * @Assert\Type("string")
     */
    private $videoUrl;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="integer", nullable=true)
     * @Assert\Type("integer")
     * @Assert\Choice(callback={"TradusBundle\Entity\Offer", "getValidStatusList"}, strict=true)
     * @Exclude
     */
    private $status = self::STATUS_ONLINE;

    /**
     * @var int
     *
     * @ORM\Column(name="pose_status", type="integer", columnDefinition="TINYINT DEFAULT -1")
     * @Assert\Type("integer")
     */
    private $poseStatus = -1;

    /**
     * @var int
     * @ORM\Column(name="image_duplicated", type="integer", columnDefinition="TINYINT DEFAULT NULL")
     * @Assert\Type("integer")
     */
    private $imageDuplicated;

    /**
     * @var DateTime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="bumped_at", type="datetime", nullable=true)
     * @Assert\DateTime()
     */
    private $bumped_at;

    /**
     * @var DateTime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime")
     * @Assert\DateTime()
     */
    private $created_at;

    /**
     * @var DateTime
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="updated_at", type="datetime")
     * @Assert\DateTime()
     */
    private $updated_at;

    /**
     * @var string
     *
     * @ORM\Column(name="price_type", type="string", length=255, nullable=true)
     * @Assert\Type("string")
     */
    private $price_type;

    /**
     * @var string
     * @ORM\Column(name="regression_data", type="text", nullable=true)
     * @Assert\Type("string")
     */
    private $regression_data;

    /**
     * @var int
     * @ORM\Column(name="price_analysis_type", type="integer", columnDefinition="TINYINT DEFAULT NULL")
     * @Assert\Type("integer")
     */
    private $priceAnalysisType;

    /**
     * @var int
     *
     * @ORM\Column(name="price_analysis_value", type="integer", nullable=true)
     * @Assert\Type("integer")
     */
    private $priceAnalysisValue;

    /**
     * @var string
     * @ORM\Column(name="price_analysis_data", type="text", nullable=true)
     * @Assert\Type("string")
     */
    private $price_analysis_data;

    /**
     * @var int
     *
     * @ORM\Column(name="sitecode", type="integer", nullable=true)
     * @Assert\Type("integer")
     */
    private $sitecode;

    /** @ORM\Column(name="solr_status", type="string") */
    private $solr_status;

    /**
     * @var OfferVas
     *
     * @ORM\OneToMany(targetEntity="TradusBundle\Entity\OfferVas", mappedBy="offerId")
     */
    private $vas;

    protected $defaultLocale;
    private $locale;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->descriptions = new ArrayCollection();
        $this->images = new ArrayCollection();
        $this->vas = new ArrayCollection();
        $sitecodeService = new SitecodeService();
        $this->defaultLocale = $sitecodeService->getDefaultLocale();
    }

    /**
     * @param DateTime|null $sortIndex
     */
    public function setSortIndex($sortIndex = null)
    {
        if ($sortIndex) {
            $this->setBumpedAt($sortIndex);
        } else {
            $this->setBumpedAt($this->generateNewSortIndex($this->getCreatedAt()));
        }
    }

    /**
     * @return DateTime|null
     */
    public function getSortIndex()
    {
        if (! $this->getBumpedAt()) {
            $this->setBumpedAt($this->generateNewSortIndex($this->getCreatedAt()));
        }

        return $this->getBumpedAt();
    }

    /**
     * @param DateTime|null $createdAt
     * @return DateTime
     */
    public function generateNewSortIndex(?DateTime $createdAt = null)
    {
        if (! $createdAt) {
            $createdAt = new DateTime();
        }
        switch ($this->getSeller()->getSellerType()) {
            case SellerInterface::SELLER_TYPE_PACKAGE_GOLD:
                // You will be on top of all for x hours
                $createdAt->modify('+12 hour');
                break;
            case SellerInterface::SELLER_TYPE_PACKAGE_SILVER:
            case SellerInterface::SELLER_TYPE_PACKAGE_BRONZE:
            case SellerInterface::SELLER_TYPE_PREMIUM:
                // All new sortindex dates are on top
                break;
            case SellerInterface::SELLER_TYPE_PACKAGE_FREE:
                $createdAt->modify('-1 week');
                break;
            case SellerInterface::SELLER_TYPE_FREE:
            default:
                $createdAt->modify('-3 month');
                break;
        }

        return $createdAt;
    }

    /**
     * @param string $modify
     */
    public function bumpUp($modify = null)
    {
        $newSortIndexDate = new DateTime();
        if ($modify) {
            $newSortIndexDate->modify($modify);
        }
        $this->setSortIndex($newSortIndexDate);
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id.
     *
     * @param int $id
     *
     * @return Offer
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set adId.
     *
     * @param string|null $adId
     *
     * @return Offer
     */
    public function setAdId($adId = null)
    {
        $this->ad_id = $adId;

        return $this;
    }

    /**
     * Get adId.
     *
     * @return string|null
     */
    public function getAdId()
    {
        return $this->ad_id;
    }

    /**
     * Set lecturaId.
     *
     * @param int|null $lecturaId
     *
     * @return Offer
     */
    public function setLecturaId($lecturaId = null)
    {
        $this->lectura_id = $lecturaId;

        return $this;
    }

    /**
     * Get lecturaId.
     *
     * @return int|null
     */
    public function getLecturaId()
    {
        return $this->lectura_id;
    }

    /**
     * Set v1Id.
     *
     * @param int|null $v1Id
     *
     * @return Offer
     */
    public function setV1Id($v1Id = null)
    {
        $this->v1_id = $v1Id;

        return $this;
    }

    /**
     * Get v1Id.
     *
     * @return int|null
     */
    public function getV1Id()
    {
        return $this->v1_id;
    }

    /**
     * Set model.
     *
     * @param string|null $model
     *
     * @return Offer
     */
    public function setModel($model = null)
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Get model.
     *
     * @return string|null
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Set price.
     *
     * @param float|null $price
     *
     * @return Offer
     */
    public function setPrice($price = null)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price.
     *
     * @return float|null
     */
    public function getPrice()
    {
        return floatval($this->price);
    }

    /**
     * Set currency.
     *
     * @param string|null $currency
     *
     * @return Offer
     */
    public function setCurrency($currency = null)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get currency.
     *
     * @return string|null
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set url.
     *
     * @param string|null $url
     *
     * @return Offer
     */
    public function setUrl($url = null)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url.
     *
     * @return string|null
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set video url.
     *
     * @param string|null $currency
     *
     * @return Offer
     */
    public function setVideoUrl($videoUrl = null)
    {
        $this->videoUrl = $videoUrl;

        return $this;
    }

    /**
     * Get video url.
     *
     * @return string|null
     */
    public function getVideoUrl()
    {
        return $this->videoUrl;
    }

    /**
     * Set status.
     *
     * @param int|null $status
     *
     * @return Offer
     */
    public function setStatus($status = null)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return int|null
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set pose status.
     *
     * @param int|null $poseStatus
     *
     * @return Offer
     */
    public function setPoseStatus($poseStatus = null)
    {
        $this->poseStatus = $poseStatus;

        return $this;
    }

    /**
     * Get pose status.
     *
     * @return int|null
     */
    public function getPoseStatus()
    {
        return $this->poseStatus;
    }

    /**
     * Set createdAt.
     *
     * @param DateTime $createdAt
     *
     * @return Offer
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;

        return $this;
    }

    /**
     * Get createdAt.
     *
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set updatedAt.
     *
     * @param DateTime $updatedAt
     *
     * @return Offer
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updated_at = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt.
     *
     * @return DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * Set seller.
     *
     * @param Seller|null $seller
     *
     * @return Offer
     */
    public function setSeller(?Seller $seller = null)
    {
        $this->seller = $seller;

        return $this;
    }

    /**
     * Get seller.
     *
     * @return Seller|null
     */
    public function getSeller()
    {
        return $this->seller;
    }

    /**
     * Add analytics_data.
     *
     * @param OfferAnalyticsData|null $analytics_data
     *
     * @return Offer
     */
    public function addAnalyticsData(?OfferAnalyticsData $analytics_data = null)
    {
        $this->analytics_data[] = $analytics_data;

        return $this;
    }

    /**
     * Remove analytics_data.
     *
     * @param OfferAnalyticsData $analytics_data
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeAnalyticsData(OfferAnalyticsData $analytics_data)
    {
        return $this->analytics_data->removeElement($analytics_data);
    }

    /**
     * Get analytics_data.
     * @param int $userId
     *
     * @return OfferAnalyticsData|null
     */
    public function getAnalyticsData()
    {
        return $this->analytics_data;
    }

    /**
     * Get offer attributes.
     * @param int $userId
     *
     * @return bool
     */
    public function hasUserVisitedOffer(int $userId)
    {
        if ($userId) {
            $criteria = Criteria::create()
                ->where(Criteria::expr()->eq('user_id', $userId))
                ->andWhere(Criteria::expr()->eq('type', 'visit'));

            $visitedCount = $this->analytics_data->matching($criteria)->count();

            return $visitedCount ? true : false;
        }

        return false;
    }

    /**
     * Add description.
     *
     * @param OfferDescription $description
     *
     * @return Offer
     */
    public function addDescription(OfferDescription $description)
    {
        $this->descriptions[] = $description;

        return $this;
    }

    /**
     * Remove description.
     *
     * @param OfferDescription $description
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeDescription(OfferDescription $description)
    {
        return $this->descriptions->removeElement($description);
    }

    /**
     * Get descriptions.
     *
     * @return Collection
     */
    public function getDescriptions()
    {
        return $this->descriptions;
    }

    /**
     * Add image.
     *
     * @param OfferImage $image
     *
     * @return Offer
     */
    public function addImage(OfferImage $image)
    {
        $this->images[] = $image;

        return $this;
    }

    /**
     * Remove image.
     *
     * @param OfferImage $image
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeImage(OfferImage $image)
    {
        return $this->images->removeElement($image);
    }

    /**
     * Remove all images.
     *
     * @return Offer
     */
    public function removeAllImage()
    {
        $this->images = new ArrayCollection();

        return $this;
    }

    /**
     * Get images.
     *
     * @return Collection
     */
    public function getImages()
    {
        return $this->images;
    }

    /**
     * Get images.
     *
     * @return Collection
     */
    public function getPoseImages()
    {
        return $this->poseImages;
    }

    /**
     * Set make.
     *
     * @param Make|null $make
     *
     * @return Offer
     */
    public function setMake(?Make $make = null)
    {
        $this->make = $make;

        return $this;
    }

    /**
     * Get make.
     *
     * @return Make|null
     */
    public function getMake()
    {
        return $this->make;
    }

    /**
     * Set depreciation.
     *
     * @param OfferDepreciations|null $depreciation
     *
     * @return Offer
     */
    public function setDepreciation(?OfferDepreciations $depreciation = null)
    {
        if ($depreciation) {
            $this->depreciation = $depreciation;
        }

        return $this;
    }

    /**
     * Get depreciation.
     *
     * @return OfferDepreciations|null
     */
    public function getDepreciation()
    {
        return $this->depreciation ? $this->depreciation : null;
    }

    /**
     * Set category.
     *
     * @param Category|null $category
     *
     * @return Offer
     */
    public function setCategory(?Category $category = null)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category.
     *
     * @return Category|null
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set analytics.
     *
     * @param OfferAnalytics|null $analytics
     *
     * @return Offer
     */
    public function setAnalytics(?OfferAnalytics $analytics = null)
    {
        $this->analytics = $analytics;

        return $this;
    }

    /**
     * Get analytics.
     *
     * @return OfferAnalytics|null
     */
    public function getAnalytics()
    {
        return $this->analytics;
    }

    /**
     * Set locale.
     *
     * @param string|null $locale
     *
     * @return Offer
     */
    public function setLocale($locale = null)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Get locale.
     *
     * @return string|null
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set bumpedAt.
     *
     * @param DateTime|null $bumpedAt
     *
     * @return Offer
     */
    public function setBumpedAt($bumpedAt = null)
    {
        $this->bumped_at = $bumpedAt;

        return $this;
    }

    /**
     * Get bumpedAt.
     *
     * @return DateTime|null
     */
    public function getBumpedAt()
    {
        return $this->bumped_at;
    }

    /**
     * Add title.
     *
     * @param OfferTitle $title
     *
     * @return Offer
     */
    public function addTitle(OfferTitle $title)
    {
        $this->titles[] = $title;

        return $this;
    }

    /**
     * Remove title.
     *
     * @param OfferTitle $title
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeTitle(OfferTitle $title)
    {
        return $this->titles->removeElement($title);
    }

    /**
     * Get titles.
     *
     * @return Collection
     */
    public function getTitles()
    {
        return $this->titles;
    }

    /**
     * Add email.
     *
     * @param Email $email
     *
     * @return Offer
     */
    public function addEmail(Email $email)
    {
        $this->emails[] = $email;

        return $this;
    }

    /**
     * Remove email.
     *
     * @param Email $email
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeEmail(Email $email)
    {
        return $this->emails->removeElement($email);
    }

    /**
     * Get emails.
     *
     * @return Collection
     */
    public function getEmails()
    {
        return $this->emails;
    }

    /**
     * Add attribute.
     *
     * @param OfferAttribute $attribute
     *
     * @return Offer
     */
    public function addAttribute(OfferAttribute $attribute)
    {
        $this->attributes[] = $attribute;

        return $this;
    }

    /**
     * Remove attribute.
     *
     * @param OfferAttribute $attribute
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeAttribute(OfferAttribute $attribute)
    {
        return $this->attributes->removeElement($attribute);
    }

    /**
     * Get attributes.
     *
     * @return Collection
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Get offer attribute.
     *
     * @return string
     */
    public function getAttribute($attribute)
    {
        $listOfAttributes = $this->getListOfAttributes([$attribute]);

        return ! empty($listOfAttributes) && $listOfAttributes[$attribute]
            ? $listOfAttributes[$attribute] : null;
    }

    /**
     * Get offer attributes.
     * @param array $listOfAttributes
     *
     * @return array
     */
    public function getListOfAttributes($listOfAttributes = [])
    {
        $offerAttributes = [];
        if (! empty($listOfAttributes) && $attributes = $this->getAttributes()) {
            foreach ($attributes as $attribute) {
                $attributeName = $attribute->getAttribute()->getName();
                if (in_array($attributeName, $listOfAttributes)) {
                    $offerAttributes[$attributeName] = $attribute->getContent();
                }
            }
        }

        return $offerAttributes;
    }

    /**
     * Set price_type.
     *
     * @param string|null $price_type
     *
     * @return Offer
     */
    public function setPriceType($price_type = self::DEFAULT_PRICE_TYPE)
    {
        $this->price_type = $price_type;

        return $this;
    }

    /**
     * Get price_type.
     *
     * @return string|null
     */
    public function getPriceType()
    {
        return $this->price_type;
    }

    /**
     * Set Duplicated Image Status.
     *
     * @param int
     *
     * @return Offer
     */
    public function setImageDuplicated($imageDuplicated = self::DEFAULT_IMAGE_DUPLICATED)
    {
        $this->imageDuplicated = $imageDuplicated;

        return $this;
    }

    /**
     * Get Duplicated Image Status.
     *
     * @return int
     */
    public function getImageDuplicated()
    {
        return $this->imageDuplicated;
    }

    /**
     * @return string|null
     */
    public function getRegressionData()
    {
        return $this->regression_data;
    }

    /**
     * @param string|null $regression_data
     * @return $this
     */
    public function setRegressionData($regression_data = null)
    {
        $this->regression_data = $regression_data;

        return $this;
    }

    /**
     * @param int|null $priceAnalysisType
     * @return $this
     */
    public function setPriceAnalysisType($priceAnalysisType = null)
    {
        $this->priceAnalysisType = $priceAnalysisType;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getPriceAnalysisType()
    {
        return $this->priceAnalysisType;
    }

    /**
     * @param int|null $priceAnalysisValue
     * @return $this
     */
    public function setPriceAnalysisValue($priceAnalysisValue = null)
    {
        $this->priceAnalysisValue = $priceAnalysisValue;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getPriceAnalysisValue()
    {
        return $this->priceAnalysisValue;
    }

    /**
     * @return string|null
     */
    public function getPriceAnalysisData()
    {
        return $this->price_analysis_data;
    }

    /**
     * @param string|null $priceAnalysisData
     * @return Offer
     */
    public function setPriceAnalysisData($price_analysis_data = null)
    {
        $this->price_analysis_data = $price_analysis_data;

        return $this;
    }

    /**
     * Get SolrStatus.
     *
     * @return string
     */
    public function getSolrStatus()
    {
        return $this->filterSolrStatus($this->solr_status);
    }

    /**
     * Set SolrStatus.
     * @param string|null $solr_status
     */
    public function setSolrStatus($solr_status = self::SOLR_STATUS_NOT_IN_INDEX)
    {
        $this->solr_status = $this->filterSolrStatus($solr_status);
    }

    /*
     * Filter solr_status
     * @return string
     * */

    private function filterSolrStatus($status)
    {
        if ($status !== self::SOLR_STATUS_TO_UPDATE &&
            $status !== self::SOLR_STATUS_IN_QUEUE &&
            $status !== self::SOLR_STATUS_IN_INDEX &&
            $status !== self::SOLR_STATUS_NOT_IN_INDEX
        ) {
            return self::SOLR_STATUS_NOT_IN_INDEX;
        }

        return $status;
    }

    public static function getValidStatusList()
    {
        return [
            self::STATUS_ONLINE,
            self::STATUS_PENDING,
            self::STATUS_DELETED,
            self::STATUS_OFFLINE,
            self::STATUS_MODERATED,
            self::STATUS_PENDING_REVIEW,
            self::STATUS_SEMI_ACTIVE,
        ];
    }

    /**
     * @param string $locale
     * @param null $titleSlug
     * @return string
     */
    public function getUrlByLocale($locale = null, $titleSlug = null)
    {
        $locale = $locale ?? $this->defaultLocale;
        $descs = $this->getDescriptions();

        if (! $titleSlug) {
            $aTitleSlug = [];
            foreach ($descs as $desc) {
                $aTitleSlug[$desc->getLocale()] = $desc->getTitleSlug();
            }

            if (! empty($aTitleSlug[$locale])) {
                $titleSlug = $aTitleSlug[$locale];
            } elseif (! empty($aTitleSlug[$this->defaultLocale])) {
                $titleSlug = $aTitleSlug[$this->defaultLocale];
            } elseif (! empty($aTitleSlug)) {
                $firstLocale = key($aTitleSlug);
                $titleSlug = $aTitleSlug[$firstLocale];
            }
        }

        if ($this->getMake()) {
            $make = $this->getMake()->getSlug();
        } else {
            $make = 'other';
        }
        $categorySlug = $this->getCategory()->getSlugUrl($locale);

        $url = '/%s/%s/%s/%s';

        return sprintf($url, $locale, $categorySlug, $make, $titleSlug);
    }

    public function getTitleByLocale($locale = null)
    {
        $locale = $locale ?? $this->defaultLocale;
        $descriptions = $this->getDescriptions();
        $title = null;
        $titleArr = [];

        foreach ($descriptions as $description) {
            $titleArr[$description->getLocale()] = $description->getTitle();
        }

        if (! empty($titleArr[$locale])) {
            $title = $titleArr[$locale];
        } elseif (! empty($titleArr[$this->defaultLocale])) {
            $title = $titleArr[$this->defaultLocale];
        } elseif (! empty($titleArr)) {
            $firstLocale = key($titleArr);
            $title = $titleArr[$firstLocale];
        }

        return $title;
    }

    /**
     * @param string $locale
     * @return OfferDescription | null
     */
    public function getDescriptionByLocale($locale = null)
    {
        $locale = $locale ?? $this->defaultLocale;
        $descs = $this->getDescriptions();
        $description = null;
        // fetching default english
        foreach ($descs as $desc) {
            if ($desc->getLocale() == $this->defaultLocale) {
                $description = $desc->getDescription();
                break;
            }
        }

        foreach ($descs as $desc) {
            if ($desc->getLocale() == $locale) {
                $description = $desc->getDescription();
                break;
            }
        }

        return $description;
    }

    /**
     * @param array|null $locales
     * @param ExchangeRate|null $rate
     * @param int $sellerHavingLead
     * @return array
     * @throws Exception
     */
    public function generateSolrPayload(?array $locales = null, ?ExchangeRate $rate = null, $sellerHavingLead = 0)
    {
        $makeId = null;
        $makeName = null;
        $modelSlug = null;
        $versionName = null;
        $versionSlug = null;
        $offer = $this;

        $isTest = intval($offer->getSeller()->getTestuser());

        if ($isTest == 1) {
            return null;
        }

        // Find all locales when not given
        if ($locales === null) {
            $locales = OfferInterface::SUPPORTED_LOCALES;
        }
        // Find the Make name for this offer
        $make = $offer->getMake();
        if ($make) {
            $makeName = $make->getName();
            $makeId = $make->getId();
        }

        /** @var Model $model */
        $model = $offer->getModelId();
        if ($model) {
            $modelSlug = $model->getModelSlug();
        }

        /** @var Version $version */
        $version = $offer->getVersionId();
        if ($version) {
            $versionSlug = $version->getVersionSlug();
            $versionName = $version->getVersionName();
        }

        // Convert price to euro's when currency is not in euro
        $exRate = 1;
        $currency = $offer->getCurrency();
        if ($currency != 'EUR' && $offer->getSitecode() != Sitecodes::SITECODE_AUTOTRADER) { // not Autotrader
            $exRate = $rate instanceof ExchangeRate ? $rate->getRate() : $exRate;
            $price = $exRate > 0 ? ceil($offer->getPrice() / $exRate) : null;
            $currency = 'EUR';
        } else {
            $price = $offer->getPrice();
        }

        if ($offer->getPriceType() == self::PRICE_TYPE_AUCTION) {
            $price = 1;
        }

        $priceAnalysisType = $offer->getPriceAnalysisType() ?: false;
        $attributePayload = $this->getAttributesPayload($offer->getCurrency(), $exRate);
        $suggester = '';
        $payload = array_merge([
            'offer_id' => $offer->getId(),
            'tpro_id_facet_int' => $offer->getTproId(),
            'ad_id_facet_string' => $offer->getAdId(),
            'create_date' => $offer->getCreatedAt()->format('Y-m-d H:i:s'),
            'sort_index' => $offer->getSortindex()->format('Y-m-d H:i:s'),
            'model' => $offer->getModel(),
            'make' => $makeName,
            'make_id_facet_int' => $makeId,
            'version' => $versionName,
            'price' => $price,
            'currency_facet_string' => $currency,
            'price_type' => $offer->getPriceType(),
            'category' => $offer->getCategory()->getCatsIds(),
            'seller_tpro_id_facet_int' => $offer->getSeller()->getUserId(),
            'seller_id' => $offer->getSeller()->getId(),
            'seller_url' => $offer->getSeller()->getSlug(),
            'seller_type' => $offer->getSeller()->getSellerType(),
            'seller_company_name' => $offer->getSeller()->getCompanyName(),
            'seller_address' => $offer->getSeller()->getAddress(),
            'seller_city' => $offer->getSeller()->getCity(),
            'seller_country' => $offer->getSeller()->getCountry(),
            'seller_created_at' => $offer->getSeller()->getCreatedAt()->format('Y-m-d\TH:i:s\Z'),
            'seller_phone_facet_string' => $offer->getSeller()->getSellerContactPhone(),
            'seller_mobile_phone_facet_string' => $offer->getSeller()->getMobilePhone(),
            'seller_offers_count' => intval($offer->getSeller()->getOffersCount()),
            'seller_options_facet_string' => json_encode($offer->getSeller()->getOptionValues()),
            'seller_whatsapp_facet_int' => $offer->getSeller()->getWhatsappEnabled(),
            'price_analysis_type_facet_string' => $priceAnalysisType,
            'image_duplicate_type_facet_int' => $offer->getImageDuplicated(),
            'seller_lead_last_month_facet_int' => $sellerHavingLead,
            'offer_highlight_facet_int' => 0,
            'offer_top_facet_int' => 0,
            'offer_bumpup_facet_int' => 0,
            'model_facet_string' => $modelSlug,
            'version_facet_string' => $versionSlug,
            'status_facet_int' => $offer->getStatus(),
            'video_url_facet_string' => $offer->getVideoUrl(),
        ], $attributePayload);

        if ($offer->getDepreciation()) {
            $data = $this->getDepreciationPayload();
            if ($data != '') {
                $payload['depreciation_facet_string'] = $data;
            }
        }

        /** Add the relevant V.A.S to the payload */
        $now = new DateTime();
        /** @var OfferVas $vas */
        foreach ($offer->getVas() as $vas) {
            if ($vas->getStartDate() < $now
                && $now < $vas->getEndDate()
                && $vas->getStatus() == OfferVas::STATUS_ONLINE) {
                switch ($vas->getVasId()) {
                    case Vas::TOP_AD:
                        $payload['offer_top_facet_int'] = 1;
                        break;
                    case Vas::HIGHLIGHTS:
                        $payload['offer_highlight_facet_int'] = 1;
                        break;
                    case Vas::BUMP_UP:
                        $payload['offer_bumpup_facet_int'] = 1;
                        break;
                }
            }
        }
        /* Add the relevant V.A.S to the payload */

        $suggester .= $makeName;
        if ($priceAnalysisType) {
            $payload[SearchService::FACET_HAS_PRICE_ANALYSIS_TYPE] = 1;
        }

        // Offer Sitecode
        $sitecodeId = $offer->getSitecode();
        $defaultLocale = Sitecodes::SITECODE_LOCALE_TRADUS;
        $sitecodeName = Sitecodes::SITECODE_KEY_TRADUS;
        if ($sitecodeId && $sitecodeId !== Sitecodes::SITECODE_TRADUS) {
            $sitecodes = SitecodeService::getSitecode($sitecodeId);
            $defaultLocale = $sitecodes['locale'];
            $sitecodeName = $sitecodes['sitecode'];
        }

        // Add locale information to the payload
        $categoryNames = $offer->getCategory()->getAllCategoryNames($locales);
        $categoryIds = $offer->getCategory()->getCatsIds();
        foreach ($locales as $locale) {
            $payload['category_name_'.$locale] = $categoryNames[$locale];
            foreach ($categoryNames[$locale] as $key => $value) {
                $payload[$locale.'_categoryname_facet_m_string'][] = $categoryIds[$key].':'.$value;
            }
            $payload['offer_url_'.$locale] = $offer->getUrlByLocale($locale);
            $payload['title_'.$locale] = $offer->getTitleByLocale($locale);
            $payload['description_'.$locale] = $offer->getDescriptionByLocale($locale);
            $suggester .= ' '.$value;
            $payload['f_suggest_wl_'.$sitecodeName.'_'.$locale] = $makeName.' '.$value.' '.$offer->getTitleByLocale($defaultLocale);
        }

        // Seller Sitecode, Not used anywhere currently
        $sellerSites = $offer->getSeller()->getSitecodes();
        foreach ($sellerSites as $sellerSiteObject) {
            $sitecodeId = $sellerSiteObject->getSitecode();

            if ((isset($payload['seller_site_facet_m_int']) && ! in_array($sitecodeId, $payload['seller_site_facet_m_int']))
            || (! isset($payload['seller_site_facet_m_int']))) {
                $payload['seller_site_facet_m_int'][] = $sitecodeId;
            }
        }

        $payload['site_facet_m_int'][] = $sitecodeId;
        $suggester .= ' '.$offer->getTitleByLocale($defaultLocale);
        $payload['f_suggest_wl_'.$sitecodeName] = $suggester;

        // Add images data to the index
        $offerImages = $offer->getImages();
        $firstImage = $offerImages->first();
        $countImages = 0;
        if ($firstImage) {
            $payload['thumbnail'] = $firstImage->getUrl();
            $countImages = count($offerImages);
        }
        $payload['images_count_facet_int'] = $countImages;

        return $payload;
    }

    /**
     * Function getAttributesPayload.
     * @return array
     */
    public function getAttributesPayload($currency = 'EUR', $rate = 1): array
    {
        $slugify = new Slugify();
        $offer = $this;
        $year = null;
        $weight = null;
        $weightNet = null;
        $mileage = null;
        $hoursRun = null;
        $mileageUnit = null;
        $steeringWheelSide = null;
        $transmission = null;
        $grossPrice = null;

        $omitAttributes = ['price_type'];
        $needProcessAttributes = [
            'construction_year',
            'weight',
            'mileage',
            'hours_run',
            'steering_wheel_side',
            'net_weight',
            'transmission',
            'gross_price',
        ];
        $dynamicAttributes = [];

        // Find the attributes for in the index
        if ($offer->getAttributes()) {
            foreach ($offer->getAttributes() as $attribute) {
                $attributeName = $attribute->getAttribute()->getName();
                if (in_array($attributeName, $needProcessAttributes)) {
                    if ($attributeName == 'construction_year') {
                        $year = $attribute->getContent();
                    }
                    if ($attributeName == 'weight') {
                        $weight = $attribute->getContent();
                    }
                    if ($attributeName == 'mileage') {
                        $mileage = (int) $attribute->getContent();
                    }
                    if ($attributeName == 'hours_run') {
                        $hoursRun = $attribute->getContent();
                    }
                    if ($attributeName == 'steering_wheel_side') {
                        $steeringWheelSide = $attribute->getContent();
                    }
                    if ($attributeName == 'net_weight') {
                        $weightNet = $attribute->getContent();
                    }
                    if ($attributeName == 'transmission') {
                        $transmission = $attribute->getContent();
                    }
                    if ($attributeName == 'gross_price') {
                        $grossPrice = $attribute->getContent();
                        if ($currency != 'EUR' && $offer->getSitecode() != Sitecodes::SITECODE_AUTOTRADER) { // not autotrader
                            $grossPrice = $rate > 0 ? ceil($grossPrice / $rate) : null;
                        }
                    }
                } elseif (! in_array($attributeName, $omitAttributes)) {
                    // Adding the region from the attributes so we do not create another column in db
                    if ($attributeName == 'item_location') {
                        $location = json_decode($attribute->getContent(), true);
                        if (isset($location['region'])) {
                            $dynamicAttributes['item_region_facet_string'] = $slugify->slugify($location['region']);
                            $dynamicAttributes['item_region_name_facet_string'] = $location['region'];
                            if (isset($location['country'])) {
                                $dynamicAttributes['item_country_facet_string'] = $location['country'];
                            }
                        }
                    }

                    if ($attribute->getAttribute()->getAttributeType() == Attribute::ATTRIBUTE_TYPE_LIST
                    && $attribute->getAttribute()->getSelectMultiple()) {
                        $dynamicAttributes[$attribute->getAttribute()->getSolrField()][]
                            = (int) $attribute->getOptionId();
                    } elseif ($attribute->getAttribute()->getAttributeType() == Attribute::ATTRIBUTE_TYPE_NUMERIC) {
                        $dynamicAttributes[$attribute->getAttribute()->getSolrField()]
                            = (int) $attribute->getContent();
                    } elseif ($attribute->getAttribute()->getAttributeType() == Attribute::ATTRIBUTE_TYPE_DECIMAL) {
                        $dynamicAttributes[$attribute->getAttribute()->getSolrField()]
                            = (float) $attribute->getContent();
                    } elseif ($attribute->getAttribute()->getAttributeType() == Attribute::ATTRIBUTE_TYPE_LIST) {
                        $dynamicAttributes[$attribute->getAttribute()->getSolrField()]
                            = (int) $attribute->getOptionId();
                    } else {
                        $dynamicAttributes[$attribute->getAttribute()->getSolrField()]
                            = $attribute->getContent();
                    }
                }
            }
        }

        $payload = [
            'year' => $year,
            'weight_facet_string' => $weight,
            'mileage_facet_string' => $mileage,
            'hours_run_facet_string' => $hoursRun,
            'steering_wheel_side_facet_string' => $steeringWheelSide,
            'weight_facet_double' => $weight,
            'weight_net_facet_double' => $weightNet,
            'mileage_facet_double' => $mileage,
            'hours_run_facet_double' => $hoursRun,
            'transmission_facet_string' => $transmission,
            'gross_price_facet_double' => $grossPrice,
        ];

        $payloadForSort = $this->getPayloadForSorts($weight, $weightNet, $mileage, $hoursRun, $year);

        return array_merge($payload, $dynamicAttributes, $payloadForSort);
    }

    /**
     * Function getPayloadForSorts.
     * @param null $weight
     * @param null $weightNet
     * @param null $mileage
     * @param null $hoursRun
     * @param null $year
     * @return array
     */
    public function getPayloadForSorts(
        $weight = null,
        $weightNet = null,
        $mileage = null,
        $hoursRun = null,
        $year = null
    ): array {
        $payload = [];
        if ($weight) {
            $payload['weight_has_facet_int'] = 1;
            if ($weight > 1) {
                $payload['weight_sort_asc_facet_double'] = $weight;
            } else {
                $payload['weight_sort_asc_facet_double'] = self::DEFAULT_MAX_VALUE_ON_NULL;
            }
        } else {
            $payload['weight_sort_asc_facet_double'] = self::DEFAULT_MAX_VALUE_ON_NULL;
        }

        if ($weightNet) {
            $payload['weight_net_has_facet_int'] = 1;
            if ($weightNet > 1) {
                $payload['weight_net_sort_asc_facet_double'] = $weightNet;
            } else {
                $payload['weight_net_sort_asc_facet_double'] = self::DEFAULT_MAX_VALUE_ON_NULL;
            }
        } else {
            $payload['weight_net_sort_asc_facet_double'] = self::DEFAULT_MAX_VALUE_ON_NULL;
        }

        if ($mileage) {
            $payload['mileage_has_facet_int'] = 1;
            if ($mileage > 1) {
                $payload['mileage_sort_asc_facet_double'] = $mileage;
            } else {
                $payload['mileage_sort_asc_facet_double'] = self::DEFAULT_MAX_VALUE_ON_NULL;
            }
        } else {
            $payload['mileage_sort_asc_facet_double'] = self::DEFAULT_MAX_VALUE_ON_NULL;
        }

        if ($hoursRun) {
            $payload['hours_run_has_facet_int'] = 1;
            if ($hoursRun > 1) {
                $payload['hours_run_sort_asc_facet_double'] = $hoursRun;
            } else {
                $payload['hours_run_sort_asc_facet_double'] = self::DEFAULT_MAX_VALUE_ON_NULL;
            }
        } else {
            $payload['hours_run_sort_asc_facet_double'] = self::DEFAULT_MAX_VALUE_ON_NULL;
        }

        if ($year) {
            $payload['year_has_facet_int'] = 1;
            if ($year > 1) {
                $payload['year_sort_asc_facet_double'] = $year;
            } else {
                $payload['year_sort_asc_facet_double'] = self::DEFAULT_MAX_VALUE_ON_NULL;
            }
        } else {
            $payload['year_sort_asc_facet_double'] = self::DEFAULT_MAX_VALUE_ON_NULL;
        }

        return $payload;
    }

    /**
     * Function getDepreciationPayload.
     * @return string
     */
    public function getDepreciationPayload(): string
    {
        $depreciation = $this->getDepreciation();
        if (! $depreciation) {
            return '';
        }
        $versionAnnualFactor = $depreciation->getVersionAnnualFactor() ? $depreciation->getVersionAnnualFactor() : 0;
        $percentage = number_format(round((abs($versionAnnualFactor) * 100), 2), 2);

        return json_encode([
            'extra_age_months' => $depreciation->getExtraAgeMonths(),
            'listing_predicted_price' => $depreciation->getListingPredictedPrice(),
            'listing_interval_factor' => $depreciation->getListingIntervalFactor(),
            'listing_annual_factor'   => $depreciation->getListingAnnualFactor(),
            'version_annual_factor'   => $versionAnnualFactor,
            'category_annual_factor'  => $depreciation->getCategoryAnnualFactor(),
            'percentage' => $percentage,
            'percentage_round' => intval($percentage),
        ]);
    }

    /**
     * @return int
     */
    public function getSitecode(): int
    {
        if (! $this->sitecode) {
            $this->sitecode = Sitecodes::SITECODE_TRADUS;
        }

        return $this->sitecode;
    }

    /**
     * @param int $sitecode
     */
    public function setSitecode(int $sitecode): void
    {
        $this->sitecode = $sitecode;
    }

    /**
     * @return mixed
     * @return ArrayCollection|Vas
     */
    public function getVas()
    {
        return $this->vas;
    }

    /**
     * @param OfferVas $vas
     */
    public function setVas(OfferVas $vas): void
    {
        $this->vas = $vas;
    }

    /**
     * @return mixed
     */
    public function getVersionId()
    {
        return $this->versionId;
    }

    /**
     * @param mixed $versionId
     */
    public function setVersionId($versionId): void
    {
        $this->versionId = $versionId;
    }

    /**
     * @return mixed
     */
    public function getModelId()
    {
        return $this->modelId;
    }

    /**
     * @param mixed $modelId
     */
    public function setModelId($modelId): void
    {
        $this->modelId = $modelId;
    }

    /**
     * @return int | null
     */
    public function getTproId()
    {
        return $this->tproId;
    }

    /**
     * @param $tproId
     */
    public function setTproId($tproId): void
    {
        $this->tproId = $tproId;
    }

    public function getOfferTitle($offer)
    {
        $offerMake = $offer->getMake();
        $label = strtolower($offerMake->getName()) != self::OTHER ? [$offerMake->getName()] : [];

        if ($offer->getModel()) {
            $label[] = $offer->getModel();
        }

        if ($offer->getVersionId()) {
            $label[] = $offer->getVersionId()->getVersionName();
        }

        if ($offer->getAttribute('construction_year') && intval($offer->getAttribute('construction_year')) > 0) {
            $label[] = '- '.$offer->getAttribute('construction_year');
        }

        return implode(' ', $label);
    }
}

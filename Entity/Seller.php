<?php

namespace TradusBundle\Entity;

use Cocur\Slugify\Slugify;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation\Exclude;
use Symfony\Component\Validator\Constraints as Assert;
use TradusBundle\Repository\SellerRepository;
use TradusBundle\Service\Sitecode\SitecodeService;
use TradusBundle\Service\Translation\TranslateByKeyService;

/**
 * Seller.
 *
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\SellerRepository")
 * @ORM\Table(name="sellers",
 *   uniqueConstraints={@ORM\UniqueConstraint(name="email_idx", columns={"email"})},
 *   uniqueConstraints={@ORM\UniqueConstraint(name="slug_idx", columns={"slug"})},
 * )
 */
class Seller implements SellerInterface
{
    public function __toString()
    {
        return $this->getSlug();
    }

    /**
     * returns possible Seller statuses list.
     *
     * @return array
     */
    public static function getValidStatusList()
    {
        return [
            self::STATUS_ONLINE,
            self::STATUS_OFFLINE,
            self::STATUS_DELETED,
        ];
    }

    /**
     * Function for obtaining the valid seller types.
     *
     * @return int[]
     */
    public static function getValidSellerTypes()
    {
        return self::SELLER_TYPES;
    }

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="integer", nullable=true)
     */
    private $userId;

    /**
     * @var Offer
     *
     * @ORM\OneToMany(targetEntity="TradusBundle\Entity\Offer", mappedBy="seller", fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"created_at" = "DESC"})
     * @Exclude
     */
    private $offers;

    /**
     * @ORM\OneToMany(targetEntity="TradusBundle\Entity\OfferAnalytics", mappedBy="seller", cascade={"persist", "remove"}, fetch="EXTRA_LAZY")
     * @Exclude
     */
    private $analytics;

    /**
     * @ORM\OneToMany(targetEntity="TradusBundle\Entity\OfferAnalyticsData", mappedBy="analytics_data", fetch="EXTRA_LAZY")
     * @Exclude
     */
    private $analytics_data;

    /**
     * @ORM\OneToMany(targetEntity="TradusBundle\Entity\Email", mappedBy="to_seller", fetch="EXTRA_LAZY")
     * @Exclude
     */
    private $emails;

    /** @ORM\OneToOne(targetEntity="SellerPreference", mappedBy="seller", cascade={"persist", "remove"}, fetch="EXTRA_LAZY") */
    private $preference;

    /** @ORM\OneToMany(targetEntity="SellerOption", mappedBy="seller", cascade={"persist", "remove"}, fetch="EXTRA_LAZY") */
    private $option;

    /**
     * @var int
     *
     * @Assert\Type("int")
     * @Assert\Choice(callback={"TradusBundle\Entity\Seller", "getValidSellerTypes"}, strict=true)
     * @Assert\NotBlank(message = SellerInterface::FIELD_TYPE_BLANK_ERROR)
     *
     * @ORM\Column(name="seller_type", type="integer", length=1)
     */
    private $seller_type = SellerInterface::SELLER_TYPE_FREE;

    /**
     * @var string
     *
     * @Assert\Email(message = "The email '{{ value }}' is not a valid email.")
     * @Assert\NotBlank(message = SellerInterface::FIELD_EMAIL_BLANK_ERROR)
     *
     * @ORM\Column(name="email", type="string", length=255, unique=true)
     */
    private $email;

    /**
     * @var string
     *
     * @Assert\Type("string")
     * @Assert\NotBlank(message = SellerInterface::FIELD_SLUG_BLANK_ERROR)
     * @Assert\Length(
     *      min = 2,
     *      max = 255,
     *      minMessage = "slug must be at least {{ limit }} characters long",
     *      maxMessage = "slug cannot be longer than {{ limit }} characters"
     * )
     *
     * @ORM\Column(name="slug", unique=true, type="string", length=255)
     */
    private $slug;

    /**
     * @var string
     *
     * @Assert\Type("string")
     * @Assert\Length(
     *      max = 255,
     *      maxMessage = "address cannot be longer than {{ limit }} characters"
     * )
     *
     * @ORM\Column(name="address", type="string", length=255, nullable=true)
     */
    private $address;

    /**
     * @var string
     *
     * @Assert\Type("string")
     * @Assert\Length(
     *      min = 2,
     *      max = 255,
     *      minMessage = "city must be at least {{ limit }} characters long",
     *      maxMessage = "city cannot be longer than {{ limit }} characters"
     * )
     *
     * @ORM\Column(name="city", type="string", length=255, nullable=true)
     */
    private $city;

    /**
     * @var string
     *
     * @Assert\Type("string")
     * @Assert\Length(
     *      min = 2,
     *      max = 255,
     *      minMessage = "country must be at least {{ limit }} characters long",
     *      maxMessage = "country cannot be longer than {{ limit }} characters"
     * )
     *
     * @ORM\Column(name="country", type="string", length=255)
     */
    private $country;

    /**
     * @var string
     *
     * @Assert\Type("string")
     * @Assert\Length(
     *      min = 2,
     *      max = 255,
     *      minMessage = "company_name must be at least {{ limit }} characters long",
     *      maxMessage = "company_name cannot be longer than {{ limit }} characters"
     * )
     *
     * @ORM\Column(name="company_name", type="string", length=255)
     */
    private $company_name;
    /**
     * @var string
     *
     * @ORM\Column(name="website", type="string", length=255)
     */
    private $website;

    /**
     * @var string
     *
     * @Assert\Url(message = "The logo '{{ value }}' is not a valid url")
     *
     * @ORM\Column(name="logo", type="string", length=255, nullable=true)
     */
    private $logo;

    /**
     * @var string
     *
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     */
    private $name;
    /**
     * @var string
     *
     *
     * @ORM\Column(name="locale", type="string", length=255, nullable=true)
     */
    private $locale;

    /**
     * @var string
     *
     *
     * @ORM\Column(name="source", type="string", length=255, nullable=true)
     */
    private $source;

    /**
     * @var string
     *
     * @Assert\Type("string")
     * @Assert\Length(
     *      min = 2,
     *      max = 25,
     *      minMessage = "phone must be at least {{ limit }} characters long",
     *      maxMessage = "phone cannot be longer than {{ limit }} characters"
     * )
     *
     * @ORM\Column(name="phone", type="string", length=255, nullable=true)
     */
    private $phone;

    /**
     * @var string
     *
     * @Assert\Type("string")
     * @ORM\Column(name="mobile_phone", type="string", length=255, nullable=true)
     */
    private $mobile_phone;

    /** @ORM\Column(columnDefinition="ENUM(0,1), nullable=false") */
    private $whatsapp_enabled;

    /**
     * @var int
     *
     * @Assert\Type("integer")
     * @Assert\Choice(callback={"TradusBundle\Entity\Seller", "getValidStatusList"}, strict=true)
     * @Assert\NotBlank(message = SellerInterface::FIELD_STATUS_BLANK_ERROR)
     *
     * @ORM\Column(name="status", type="integer")
     */
    private $status;

    /**
     * @var int
     *
     * @ORM\Column(name="invalid_email", type="integer", nullable=true)
     * @Assert\Type("integer")
     * @Exclude
     */
    protected $invalid_email = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="testuser", type="integer", nullable=false)
     */
    private $testuser;

    /**
     * @var int
     * @Assert\Type("integer")
     *
     * @ORM\Column(name="v1_id", type="integer", nullable=true)
     * @Exclude
     */
    private $v1_id;

    /**
     * @var DateTime
     * @Assert\DateTime()
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $created_at;

    /**
     * @var DateTime
     * @Assert\DateTime()
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private $updated_at;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="anonymized_at", type="datetime")
     * @Assert\DateTime()
     */
    private $anonymizedAt;

    /** @ORM\OneToMany(targetEntity="Seller", mappedBy="parent_seller") */
    protected $child_sellers;

    /**
     * @ORM\ManyToOne(targetEntity="Seller", inversedBy="child_sellers")
     * @ORM\JoinColumn(name="parent_seller", referencedColumnName="id", onDelete="CASCADE")
     */
    private $parent_seller;

    /** @ORM\Column(columnDefinition="TINYINT(4) DEFAULT NULL COMMENT 'null/0: Seller, 1: Parent Seller, 2: Both'") */
    private $point_of_contact;

    /**
     * @var int
     *
     * @ORM\Column(name="offers_count", type="integer", nullable=false)
     */
    private $offersCount;

    /** @ORM\Column(name="solr_status", type="string") */
    private $solrStatus;

    /** @ORM\Column(name="analytics_api_token", type="string", length=255, nullable=true) */
    private $analyticsApiToken;

    /** @ORM\Column(name="roles", type="string", nullable=true) */
    private $roles;

    /** @ORM\Column(name="password", type="string", length=255, nullable=true) */
    private $password;

    /**
     * @var string
     *
     * @ORM\Column(name="geo_location", type="string", length=255, nullable=true)
     */
    private $geoLocation;

    /** @var string */
    private $since = null;

    /**
     * @var SellerSitecode
     *
     * @ORM\OneToMany(targetEntity="TradusBundle\Entity\SellerSitecode", mappedBy="seller", fetch="EXTRA_LAZY")
     * @Exclude
     */
    private $sitecodes;

    /**
     * @var SellerAdditionalServices
     *
     * @ORM\OneToMany(targetEntity="TradusBundle\Entity\SellerAdditionalServices", mappedBy="seller", fetch="EXTRA_LAZY")
     * @Exclude
     */
    private $additionalService;

    /**
     * @var int
     *
     * @ORM\Column(name="badge_reply_fast", type="integer", nullable=false)
     */
    private $badgeReplyFast;

    /**
     * @var string
     *
     * @ORM\Column(name="badge_reply_fast_calc", type="string", nullable=true)
     */
    private $badgeReplyFastCalculation;

    /**
     * @var string
     *
     * @ORM\Column(name="offer_categories", type="string", nullable=true)
     */
    private $offerCategories;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="last_lead_at", type="datetime", nullable=true)
     * @Assert\DateTime()
     */
    private $lastLeadAt;

    /**
     * @return array
     */
    public function getGeoLocationObject(): array
    {
        return $this->geoLocationObject;
    }

    /**
     * @param array $geoLocationObject
     */
    public function setGeoLocationObject(array $geoLocationObject): void
    {
        $this->geoLocationObject = $geoLocationObject;
    }

    /** @var array */
    private $geoLocationObject = [];

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->offers = new ArrayCollection();
        $this->child_sellers = new ArrayCollection();
        $this->sitecodes = new ArrayCollection();
        $this->additionalService = new ArrayCollection();
    }

    /**
     * Get the Sellers Profile Url.
     *
     * @param string $locale
     * @param mixed $slug
     * @return string
     */
    public static function getSellerProfileUrl($locale = null, $slug = false)
    {
        global $kernel;
        $locale = $locale ?? $kernel->getContainer()->getParameter(ConfigServiceInterface::DEFAULT_LOCALE_CONFIG);
        if (! $slug) {
            $slug = self::getSlug();
        }

        return '/'.$locale.'/s/'.$slug.'/';
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
     * 0 = valid email
     * 1 = invalid email.
     * @param int $invalid_email
     */
    public function setInValidEmail(int $invalid_email)
    {
        $this->invalid_email = $invalid_email;
    }

    /**
     * @return int
     */
    public function getInValidEmail()
    {
        return $this->invalid_email;
    }

    /**
     * Set email.
     *
     * @param string|null $email
     *
     * @return Seller
     */
    public function setEmail($email = null)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email.
     *
     * @return string|null
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set slug.
     *
     * @param string|null $slug
     *
     * @return Seller
     */
    public function setSlug($slug = null)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug.
     *
     * @return string|null
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set address.
     *
     * @param string|null $address
     *
     * @return Seller
     */
    public function setAddress($address = null)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address.
     *
     * @return string|null
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set city.
     *
     * @param string|null $city
     *
     * @return Seller
     */
    public function setCity($city = null)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city.
     *
     * @return string|null
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set country.
     *
     * @param string|null $country
     *
     * @return Seller
     */
    public function setCountry($country = null)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country.
     *
     * @return string|null
     */
    public function getCountry()
    {
        return $this->country;
    }

    public function getLocale()
    {
        return strtolower($this->country);
    }

    /**
     * Set companyName.
     *
     * @param string|null $companyName
     *
     * @return Seller
     */
    public function setCompanyName($companyName = null)
    {
        $this->company_name = $companyName;

        return $this;
    }

    /**
     * Get companyName.
     *
     * @return string|null
     */
    public function getCompanyName()
    {
        return $this->company_name;
    }

    /**
     * @return string|null
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @param string|null $website
     *
     * @return Seller
     */
    public function setWebsite($website = null): self
    {
        $this->website = $website;

        return $this;
    }

    /**
     * Set logo.
     *
     * @param string|null $logo
     *
     * @return Seller
     */
    public function setLogo($logo = null)
    {
        $this->logo = $logo;

        return $this;
    }

    /**
     * Get logo.
     *
     * @return string|null
     */
    public function getLogo()
    {
        return $this->logo;
    }

    /**
     * Set phone.
     *
     * @param string|null $phone
     *
     * @return Seller
     */
    public function setPhone($phone = null)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get phone.
     *
     * @return string|null
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Get seller lead contact phone.
     *
     * @return string|null
     */
    public function getSellerContactPhone()
    {
        $leadPhone = $this->getOptionValue('lead_phone');

        return ! empty($leadPhone[0]) ? $leadPhone[0] : $this->getPhone();
    }

    /**
     * Get seller lead contact email.
     *
     * @return string|null
     */
    public function getSellerContactEmail()
    {
        $leadEmail = $this->getOptionValue('lead_email');

        return ! empty($leadEmail[0]) ? $leadEmail[0] : $this->getEmail();
    }

    /**
     * Set Whatsapp.
     *
     * @param int|null $flag
     *
     * @return Seller
     */
    public function setWhatsappEnabled($flag = null)
    {
        $this->whatsapp_enabled = $flag;

        return $this;
    }

    /**
     * Get Whatsapp.
     *
     * @return int|null
     */
    public function getWhatsappEnabled()
    {
        return $this->whatsapp_enabled;
    }

    /**
     * Set status.
     *
     * @param int|null $status
     *
     * @return Seller
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
     * Set testuser.
     *
     * @param int $testuser
     *
     * @return Seller
     */
    public function setTestuser($testuser = 0)
    {
        $this->testuser = $testuser;

        return $this;
    }

    /**
     * Get testuser.
     *
     * @return int
     */
    public function getTestuser()
    {
        return $this->testuser;
    }

    /**
     * Set v1Id.
     *
     * @param int|null $v1Id
     *
     * @return Seller
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
     * Set createdAt.
     *
     * @param DateTime $createdAt
     *
     * @return Seller
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
     * @return Seller
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
     * @return null | DateTime
     */
    public function getAnonymizedAt()
    {
        return $this->anonymizedAt;
    }

    /**
     * @param DateTime $anonymizedAt
     */
    public function setAnonymizedAt(DateTime $anonymizedAt): void
    {
        $this->anonymizedAt = $anonymizedAt;
    }

    /**
     * Add offer.
     *
     * @param Offer $offer
     *
     * @return Seller
     */
    public function addOffer(Offer $offer)
    {
        $this->offers[] = $offer;

        return $this;
    }

    /**
     * Remove offer.
     *
     * @param Offer $offer
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeOffer(Offer $offer)
    {
        return $this->offers->removeElement($offer);
    }

    /**
     * Get offers.
     *
     * @return Collection
     */
    public function getOffers()
    {
        return $this->offers;
    }

    /**
     * Set sellerType.
     *
     * @param int|null $sellerType
     *
     * @return Seller
     */
    public function setSellerType($sellerType)
    {
        $this->seller_type = $sellerType;

        return $this;
    }

    /**
     * Get sellerType.
     *
     * @return int|null
     */
    public function getSellerType()
    {
        return $this->seller_type;
    }

    /**
     * Add email.
     *
     * @param Email $email
     *
     * @return Seller
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
     * Set name.
     *
     * @param string|null $name
     *
     * @return Seller
     */
    public function setName($name = null)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set locale.
     *
     * @param string|null $locale
     *
     * @return Seller
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
    public function getSellerLocale()
    {
        return $this->locale;
    }

    /**
     * Set analytics.
     *
     * @param OfferAnalytics|null $analytics
     *
     * @return Seller
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
     * Set analytics_data.
     *
     * @param OfferAnalyticsData|null $analytics_data
     *
     * @return Seller
     */
    public function setAnalyticsData(?OfferAnalyticsData $analytics_data = null)
    {
        $this->analytics_data = $analytics_data;

        return $this;
    }

    /**
     * Get analytics_data.
     *
     * @return OfferAnalyticsData|null
     */
    public function getAnalyticsData()
    {
        return $this->analytics_data;
    }

    /**
     * Set preference.
     *
     * @param SellerPreference|null $preference
     *
     * @return Seller
     */
    public function setPreference(?SellerPreference $preference = null)
    {
        $preference->setSeller($this);
        $this->preference = $preference;

        return $this;
    }

    /**
     * Get preference.
     *
     * @return SellerPreference|null
     */
    public function getPreference()
    {
        return $this->preference;
    }

    /**
     * Set option.
     *
     * @param SellerOption|null $option
     *
     * @return Seller
     */
    public function addOption(?SellerOption $option = null)
    {
        $option->setSeller($this);
        $this->option[] = $option;

        return $this;
    }

    /**
     * Remove option.
     *
     * @param SellerOption $option
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeOption(SellerOption $option)
    {
        $this->option->removeElement($option);
    }

    /**
     * Get Option.
     *
     * @return SellerOption|null
     */
    public function getOption()
    {
        return $this->option;
    }

    /**
     * Get Option values.
     *
     * @return array
     */
    public function getOptionValues()
    {
        $optionList = [];
        $option = $this->option;
        if (is_null($option)) {
            return $optionList;
        }
        foreach ($option as $sellerOption) {
            $optionList[$sellerOption->getValueType()][] = $sellerOption->getValue();
        }

        return $optionList;
    }

    /**
     * Get specific seller option value.
     *
     * @param string $optionType
     * @return array|mixed
     */
    public function getOptionValue($optionType = 'phone')
    {
        $options = $this->getOptionValues();

        return ! empty($options[$optionType]) ? $options[$optionType] : [];
    }

    /**
     * Defines how old/often an offer needs to be to get a bump, add/change bump selection here.
     *
     * @param $sellerType
     * @return string
     */
    public static function getBumpModifierForSellerType($sellerType)
    {
        $bumpModifier = false;

        switch ($sellerType) {
            case self::SELLER_TYPE_PACKAGE_GOLD:
                $bumpModifier = '-2 week';
                break;
            case self::SELLER_TYPE_PACKAGE_SILVER:
                $bumpModifier = '-3 week';
                break;
            case self::SELLER_TYPE_PACKAGE_BRONZE:
                $bumpModifier = '-4 week';
                break;
            case self::SELLER_TYPE_SPARE_PARTS:
                $bumpModifier = '-3 week';
                break;
            case self::SELLER_TYPE_SELF_SERVE:
                $bumpModifier = '-4 week';
                break;
            case self::SELLER_TYPE_PACKAGE_PREMIUM:
                $bumpModifier = '-3 week';
                break;
            case self::SELLER_TYPE_PACKAGE_PREMIUM_PLUS:
                $bumpModifier = '-2 week';
                break;
            case self::SELLER_TYPE_PACKAGE_THREE_MONTHS_TRIAL:
            case self::SELLER_TYPE_CSV_UPLOAD:
                $bumpModifier = '-4 week';
                break;

            default:
                // We don't bump other then above.
                break;
        }

        return $bumpModifier;
    }

    /**
     * Set source.
     *
     * @param string|null $source
     *
     * @return Seller
     */
    public function setSource($source = null)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source.
     *
     * @return string|null
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Add Child Seller.
     *
     * @param seller $seller
     *
     * @return Seller
     */
    public function addChildSeller(self $seller)
    {
        $this->child_sellers[] = $seller;
        $seller->setParentSellerId($this);

        return $this;
    }

    /**
     * Get Child Sellers.
     *
     * @return Collection
     */
    public function getChildSellers()
    {
        return $this->child_sellers;
    }

    /**
     * Set Parent seller id.
     *
     * @param Seller $seller
     *
     * @return Seller
     */
    public function setParentSellerId(self $seller)
    {
        $this->parent_seller = $seller;

        return $this;
    }

    /**
     * Get Parent seller id.
     *
     * @return Seller|null
     */
    public function getParentSellerId()
    {
        return $this->parent_seller;
    }

    /**
     * Set Point Of Contact.
     *
     * @param int|null $flag
     *
     * @return Seller
     */
    public function setPointOfContact($flag = null)
    {
        $this->point_of_contact = ceil($flag);

        return $this;
    }

    /**
     * Get Point Of Contact.
     *
     * @return int|null
     */
    public function getPointOfContact()
    {
        return $this->point_of_contact;
    }

    /**
     * @return int
     */
    public function getOffersCount(): int
    {
        return $this->offersCount ? $this->offersCount : 0;
    }

    /**
     * @param int $offersCount
     */
    public function setOffersCount(int $offersCount): void
    {
        $this->offersCount = $offersCount;
    }

    /**
     * @return null | string
     */
    public function getOfferCategories()
    {
        return $this->offerCategories;
    }

    /**
     * @param string $offerCat
     * @return Seller
     */
    public function setOfferCategories(string $offerCat): self
    {
        $this->offerCategories = $offerCat;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getLastLeadAt()
    {
        return $this->lastLeadAt;
    }

    /**
     * @param DateTime $lastLeadAt
     * @return Seller
     */
    public function setLastLeadAt(DateTime $lastLeadAt): self
    {
        $this->lastLeadAt = $lastLeadAt;

        return $this;
    }

    public function getSellerLeadLastMonth()
    {
        $startDate = new DateTime();

        return ! empty($this->getLastLeadAt()) ?
            (int) ($startDate->diff($this->getLastLeadAt())->format('%R%a') > -30) : 0;
    }

    /**
     * Get solrStatus.
     *
     * @return string
     */
    public function getSolrStatus()
    {
        return $this->filterSolrStatus($this->solrStatus);
    }

    /**
     * @param string $solrStatus
     */
    public function setSolrStatus($solrStatus = self::SOLR_STATUS_NOT_IN_INDEX)
    {
        $this->solrStatus = $this->filterSolrStatus($solrStatus);
    }

    /**
     * @return string|null
     */
    public function getGeoLocation()
    {
        return $this->geoLocation;
    }

    /**
     * @param string $geoLocation
     */
    public function setGeoLocation(string $geoLocation): void
    {
        $this->geoLocation = $geoLocation;
    }

    /**
     * @return string
     */
    public function getSince(): ?string
    {
        return $this->since;
    }

    /**
     * @param $translator
     * @throws Exception
     */
    public function setSince($translator): void
    {
        $createdDate = $this->getCreatedAt();
        $today = date('Y-m-d H:i:s');
        $dateDiff = $createdDate->diff(new DateTime($today));
        $sitecodeService = new SitecodeService();
        $site = $sitecodeService->getSitecodeTitle();

        switch ($dateDiff->y) {
            case 0:
                $sellerSince = $translator->trans('Registered on @site less than a year ago', ['@site' => $site]);
                break;
            case 1:
                $sellerSince = $translator->trans('Registered on @site for a year', ['@site' => $site]);
                break;
            default:
                $sellerSince = $translator->trans(
                    'Registered on @site for @duration years',
                    ['@site' => $site, '@duration' => $dateDiff->y]
                );
                break;
        }

        $this->since = $sellerSince;
    }

    /**
     * @param $solrStatus
     * @return string
     */
    private function filterSolrStatus($solrStatus)
    {
        if ($solrStatus !== self::SOLR_STATUS_TO_UPDATE &&
            $solrStatus !== self::SOLR_STATUS_IN_QUEUE &&
            $solrStatus !== self::SOLR_STATUS_IN_INDEX &&
            $solrStatus !== self::SOLR_STATUS_NOT_IN_INDEX
        ) {
            return self::SOLR_STATUS_NOT_IN_INDEX;
        }

        return $solrStatus;
    }

    /**
     * @return array
     */
    public function generateSolrPayload()
    {
        $slugify = new Slugify();
        $parentID = 0;
        $payload = [];
        try {
            if ($this->getParentSellerId()) {
                $parentID = $this->getParentSellerId()->getId();
            }

            $payload = [
                self::SOLR_FIELD_SELLER_ID => $this->getId(),
                self::SOLR_FIELD_INT_ID => $this->getId(),
                self::SOLR_FIELD_USER_ID => $this->getUserId(),
                self::SOLR_FIELD_SELLER_PARENT => intval($parentID),
                self::SOLR_FIELD_V1_ID => $this->getV1Id(),
                self::SOLR_FIELD_SELLER_CATEGORY => [],
                self::SOLR_FIELD_SELLER_TYPE => intval($this->getSellerType()),
                self::SOLR_FIELD_SELLER_EMAIL => $this->getSellerContactEmail(),
                self::SOLR_FIELD_SELLER_NAME => $this->getName(),
                self::SOLR_FIELD_SELLER_URL => '/s/'.$this->getSlug().'/',
                self::SOLR_FIELD_SELLER_LOCALE => $this->getLocale(),
                self::SOLR_FIELD_SELLER_SOURCE => $this->getSource(),
                self::SOLR_FIELD_SELLER_ADDRESS => $this->getAddress(),
                self::SOLR_FIELD_SELLER_CITY => $this->getCity(),
                self::SOLR_FIELD_SELLER_COUNTRY => $this->getCountry(),
                self::SOLR_FIELD_SELLER_COMPANY_NAME => $this->getCompanyName(),
                self::SOLR_FIELD_SELLER_SLUG => $this->getSlug(),
                self::SOLR_FIELD_SELLER_LOGO => $this->getLogo(),
                self::SOLR_FIELD_SELLER_PHONE => $this->getSellerContactPhone(),
                self::SOLR_FIELD_SELLER_MOBILE_PHONE => $this->getMobilePhone(),
                self::SOLR_FIELD_SELLER_OFFERS_COUNT => $this->getOffersCount(),
                self::SOLR_FIELD_SELLER_WHATSAPP_FACET_INT => intval($this->getWhatsappEnabled()),
                self::SOLR_FIELD_SELLER_POINT_OF_CONTACT => intval($this->getPointOfContact()),
                self::SOLR_FIELD_SELLER_GEO_LOCATION => $this->getGeoLocation(),
                self::SOLR_FIELD_SELLER_CREATED_AT => $this->getCreatedAt()->format('Y-m-d H:i:s'),
                self::SOLR_FIELD_SELLER_HAS_LEAD_LAST_MONTH_FACET_INT => intval($this->getSellerLeadLastMonth()),
                self::SOLR_FIELD_SELLER_CATEGORY => (! empty($this->getOfferCategories()) ? explode(',', $this->getOfferCategories()) : []),
                self::SOLR_FIELD_SELLER_HAS_IMAGE_FACET_INT => intval(! empty($this->getLogo())),
                self::SOLR_FIELD_SELLER_GEO_LOCATION_OBJECT => $this->getGeoLocationObject(),
                self::SOLR_FIELD_SELLER_ANALYTICS_API_TOKEN => $this->getAnalyticsApiToken(),
                self::SOLR_FIELD_SELLER_ROLES => $this->getRoles(),
                self::SOLR_FIELD_SELLER_PASSWORD => $this->getPassword(),
                ];

            $preferences = $this->getPreference();
            if ($preferences) {
                foreach ($preferences->getLanguageOptions() as $p) {
                    $payload[self::SOLR_FIELD_SELLER_PREFERENCES][] = $p;
                }
            }

            if ($this->getGeoLocation() != '') {
                $location = json_decode($this->getGeoLocation(), true);
                $payload[self::SOLR_FIELD_SELLER_GEO_LOCATION_LATLON] =
                    round(floatval($location['lat']), 8).','.round(floatval($location['lng']), 8);
                if (isset($location['region'])) {
                    $payload['item_region_facet_string'] = $slugify->slugify($location['region']);
                    $payload['item_region_name_facet_string'] = $location['region'];
                }
            }

            $payload['site_facet_m_int'][] = SellerRepository::getSitecodesFromOffersBySeller($this->getId());
            $payload['services_facet_string'] = $this->getAdditionalServicesPayload();
        } catch (Exception $e) {
            /* TODO - handle exception if required */
        }

        return $payload;
    }

    /**
     * @return ArrayCollection|SellerSitecode
     */
    public function getSitecodes()
    {
        return $this->sitecodes;
    }

    /**
     * @param SellerSitecode $sitecodes
     */
    public function setSitecodes(SellerSitecode $sitecodes): void
    {
        $this->sitecodes = $sitecodes;
    }

    /**
     * @return mixed
     */
    public function getAnalyticsApiToken()
    {
        return $this->analyticsApiToken;
    }

    /**
     * @param mixed | null $analyticsApiToken
     */
    public function setAnalyticsApiToken($analyticsApiToken = null): void
    {
        $this->analyticsApiToken = $analyticsApiToken;
    }

    /**
     * @return mixed
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * @param mixed |null $roles
     */
    public function setRoles($roles = null): void
    {
        $this->roles = $roles;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param mixed $password
     */
    public function setPassword($password = null): void
    {
        $this->password = $password;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * Set mobile phone.
     *
     * @param string|null $mobilePhone
     *
     * @return Seller
     */
    public function setMobilePhone(?string $mobilePhone = null)
    {
        $this->mobile_phone = $mobilePhone;

        return $this;
    }

    /**
     * Get mobile phone.
     *
     * @return string|null
     */
    public function getMobilePhone()
    {
        return $this->mobile_phone;
    }

    /**
     * @return ArrayCollection|SellerAdditionalServices
     */
    public function getAdditionalService()
    {
        return $this->additionalService;
    }

    /**
     * @param SellerAdditionalServices $additionalService
     */
    public function setAdditionalService(SellerAdditionalServices $additionalService)
    {
        if (! in_array($additionalService, $this->additionalService)) {
            $this->additionalService[] = $additionalService;
        }
    }

    /**
     * @return int
     */
    public function getBadgeReplyFast()
    {
        return $this->badgeReplyFast;
    }

    /**
     * @param int $badgeReplyFast
     */
    public function setBadgeReplyFast($badgeReplyFast)
    {
        $this->badgeReplyFast = $badgeReplyFast;
    }

    public function getServicesIds()
    {
        $servicesIds = [];
        $additionalServices = $this->additionalService;
        foreach ($additionalServices as $service) {
            $servicesIds[] = $service->getServiceId();
        }

        return $servicesIds;
    }

    public function getAdditionalServicesPayload()
    {
        $additionalServices = [];
        $additionalServiceObject = $this->getAdditionalService();
        if ($additionalServiceObject) {
            foreach ($additionalServiceObject as $serviceObject) {
                $additionalServices[] = [
                    'id' => $serviceObject->getService()->getService()->getOwner()->getId(),
                    'title' => $serviceObject->getService()->getService()->getOwner()->getTitle(),
                    'description' => $serviceObject->getService()->getService()->getOwner()->getDescription(),
                ];
            }
        }

        return json_encode($additionalServices);
    }

    /**
     * @return string
     */
    public function getBadgeReplyFastCalculation()
    {
        return $this->badgeReplyFastCalculation;
    }

    /**
     * @param string $badgeReplyFastCalculation
     */
    public function setBadgeReplyFastCalculation($badgeReplyFastCalculation)
    {
        $this->badgeReplyFastCalculation = $badgeReplyFastCalculation;
    }

    /**
     * @param string $locale
     * @return string
     */
    public function getLabelReplyFast(?string $locale = null): string
    {
        if ($this->getBadgeReplyFast() == 1) {
            $translator = new TranslateByKeyService($locale);

            return $translator->translateByKey('seller.details.quick_reply');
        }

        return '';
    }

    /**
     * Get the Seller Url.
     *
     * @param string $locale
     * @return string
     */
    public function getSellerUrl($locale = null)
    {
        $sitecodeService = new SitecodeService();
        $locale = empty($locale) ? $sitecodeService->getDefaultLocale() : $locale;

        return $sitecodeService->getSitecodeDomain().$locale.'/s/'.$this->getSlug().'/';
    }
}

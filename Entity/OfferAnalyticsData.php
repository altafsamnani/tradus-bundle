<?php

namespace TradusBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * OfferAnalytics.
 *
 * @ORM\Table(name="offer_analytics_data")
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\OfferAnalyticsDataRepository")
 */
class OfferAnalyticsData
{
    public const TYPE_VISIT = 'visit';
    public const TYPE_EMAIL = 'email';
    public const TYPE_PHONE_CLICK = 'phone_click';
    public const TYPE_PHONE_CALL = 'phone_call';
    public const TYPE_PHONE_CALLBACK = 'phone_callback';

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="TradusBundle\Entity\OfferAnalytics", inversedBy="analytics_data")
     * @ORM\JoinColumn(name="analytics_id", referencedColumnName="id")
     */
    private $analytics;

    /**
     * @ORM\ManyToOne(targetEntity="TradusBundle\Entity\Seller", inversedBy="analytics_data")
     * @ORM\JoinColumn(name="seller_id", referencedColumnName="id")
     */
    private $seller;

    /**
     * @ORM\ManyToOne(targetEntity="TradusBundle\Entity\Offer", inversedBy="analytics_data")
     * @ORM\JoinColumn(name="offer_id", referencedColumnName="id")
     */
    private $offer;

    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="integer", nullable=true)
     */
    private $user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="ip", type="string", length=255, nullable=true)
     */
    private $ip;

    /**
     * @var string
     *
     * @ORM\Column(name="country", type="string", length=255, nullable=true)
     */
    private $country;

    /**
     * @var string
     *
     * @ORM\Column(name="locale", type="string", length=255, nullable=true)
     */
    private $locale;

    /**
     * @var string
     *
     * @ORM\Column(name="user_agent", type="string", length=255, nullable=true)
     */
    private $user_agent;

    /**
     * @var string
     *
     * @ORM\Column(name="site_version", type="string", length=255, nullable=true)
     */
    private $site_version;

    /**
     * @var int
     *
     * @ORM\Column(name="category_id", type="integer", nullable=true)
     */
    private $category_id;

    /**
     * @var int
     *
     * @ORM\Column(name="offer_id", type="integer", nullable=true)
     */
    private $offer_id;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255, nullable=true)
     */
    private $type;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $created_at;

    /**
     * @var datetime
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private $updated_at;

    /**
     * @var string
     *
     * @ORM\Column(name="user_index", type="string", length=255, nullable=true)
     */
    private $userIndex;

    /**
     * @var int
     *
     * @ORM\Column(name="sitecode_id", type="integer", nullable=true)
     */
    private $sitecode_id;

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
     * Set ip.
     *
     * @param string|null $ip
     *
     * @return OfferAnalyticsData
     */
    public function setIp($ip = null)
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * Get ip.
     *
     * @return string|null
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * Set country.
     *
     * @param string|null $country
     *
     * @return OfferAnalyticsData
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

    /**
     * @param int $userId
     */
    public function setUserId(int $userId)
    {
        $this->user_id = $userId;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set locale.
     *
     * @param string|null $locale
     *
     * @return OfferAnalyticsData
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
     * Set type.
     *
     * @param string|null $type
     *
     * @return OfferAnalyticsData
     */
    public function setType($type = null)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return string|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set seller.
     *
     * @param Seller|null $seller
     *
     * @return OfferAnalyticsData
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
     * Set offer.
     *
     * @param Offer|null $offer
     *
     * @return Offer
     */
    public function setOffer(?Offer $offer = null)
    {
        $this->offer = $offer;

        return $this;
    }

    /**
     * Get offer.
     *
     * @return Offer|null
     */
    public function getOffer()
    {
        return $this->offer;
    }

    /**
     * Set analytics.
     *
     * @param OfferAnalytics|null $analytics
     *
     * @return OfferAnalyticsData
     */
    public function setOfferAnalytics(?OfferAnalytics $analytics = null)
    {
        $this->analytics = $analytics;

        return $this;
    }

    /**
     * Get analytics.
     *
     * @return OfferAnalytics|null
     */
    public function getOfferAnalytics()
    {
        return $this->analytics;
    }

    /**
     * Set createdAt.
     *
     * @param DateTime $createdAt
     *
     * @return OfferAnalyticsData
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
     * @return OfferAnalyticsData
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
     * Set userAgent.
     *
     * @param string|null $userAgent
     *
     * @return OfferAnalyticsData
     */
    public function setUserAgent($userAgent = null)
    {
        $this->user_agent = $userAgent;

        return $this;
    }

    /**
     * Get userAgent.
     *
     * @return string|null
     */
    public function getUserAgent()
    {
        return $this->user_agent;
    }

    /**
     * Set siteVersion.
     *
     * @param string|null $siteVersion
     *
     * @return OfferAnalyticsData
     */
    public function setSiteVersion($siteVersion = null)
    {
        $this->site_version = $siteVersion;

        return $this;
    }

    /**
     * Get siteVersion.
     *
     * @return string|null
     */
    public function getSiteVersion()
    {
        return $this->site_version;
    }

    /**
     * Set categoryId.
     *
     * @param int|null $categoryId
     *
     * @return OfferAnalyticsData
     */
    public function setCategoryId($categoryId = null)
    {
        $this->category_id = $categoryId;

        return $this;
    }

    /**
     * Get categoryId.
     *
     * @return int|null
     */
    public function getCategoryId()
    {
        return $this->category_id;
    }

    /**
     * Get offerId.
     *
     * @return int|null
     */
    public function getOfferId()
    {
        return $this->offer_id;
    }

    /**
     * Set offerId.
     *
     * @param int|null offerId
     *
     * @return OfferAnalyticsData
     */
    public function setOfferId($offerId = null)
    {
        $this->offer_id = $offerId;

        return $this;
    }

    /**
     * Set analytics.
     *
     * @param OfferAnalytics|null $analytics
     *
     * @return OfferAnalyticsData
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
     * @return string
     */
    public function getUserIndex(): string
    {
        if ($this->getUserId()) {
            return $this->getIp().$this->getUserId();
        }

        return $this->getIp();
    }

    public function setUserIndex(): void
    {
        if ($this->getUserId()) {
            $this->userIndex = $this->getIp().$this->getUserId();
        } else {
            $this->userIndex = $this->getIp();
        }
    }

    /**
     * @return int
     */
    public function getSitecodeId(): int
    {
        return $this->sitecode_id ? $this->sitecode_id : Sitecodes::SITECODE_TRADUS;
    }

    /**
     * @param int $sitecode_id | null
     */
    public function setSitecodeId($sitecode_id): void
    {
        $sitecode_id = $sitecode_id ? $sitecode_id : Sitecodes::SITECODE_TRADUS;
        $this->sitecode_id = $sitecode_id;
    }
}

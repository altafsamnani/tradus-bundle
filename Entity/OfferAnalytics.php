<?php

namespace TradusBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * OfferAnalytics.
 *
 * @ORM\Table(name="offer_analytics")
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\OfferAnalyticsRepository")
 */
class OfferAnalytics
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity="TradusBundle\Entity\Offer", inversedBy="analytics")
     * @ORM\JoinColumn(name="offer_id", referencedColumnName="id")
     */
    private $offer;

    /** @ORM\OneToMany(targetEntity="TradusBundle\Entity\OfferAnalyticsData", mappedBy="analytics") */
    private $analytics_data;

    /**
     * @ORM\ManyToOne(targetEntity="TradusBundle\Entity\Seller", inversedBy="analytics", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="seller_id", referencedColumnName="id")
     */
    private $seller;

    /**
     * @var int
     *
     * @ORM\Column(name="visits", type="integer", nullable=true)
     */
    private $visits;

    /**
     * @var int
     *
     * @ORM\Column(name="emails", type="integer", nullable=true)
     */
    private $emails;

    /**
     * @var int
     *
     * @ORM\Column(name="phone_clicks", type="integer", nullable=true)
     */
    private $phone_clicks;

    /**
     * @var int
     *
     * @ORM\Column(name="phone_calls", type="integer", nullable=true)
     */
    private $phone_calls;

    /**
     * @var int
     *
     * @ORM\Column(name="phone_callbacks", type="integer", nullable=true)
     */
    private $phone_callbacks;

    /**
     * @var int
     *
     * @ORM\Column(name="search_results", type="integer", nullable=true)
     */
    private $search_results;

    /**
     * @var int
     *
     * @ORM\Column(name="auction", type="integer", nullable=true)
     */
    private $auction;

    /**
     * @var int
     *
     * @ORM\Column(name="whatsapp_message", type="integer", nullable=true)
     */
    private $whatsapp_message;

    /**
     * @var int
     *
     * @ORM\Column(name="sitecode_id", type="integer", nullable=true)
     */
    private $sitecode_id;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->analytics_data = new ArrayCollection();
        $this->setVisits(0);
        $this->setEmails(0);
        $this->setPhoneClicks(0);
        $this->setPhoneCalls(0);
        $this->setPhoneCallbacks(0);
        $this->setSearchResults(0);
        $this->setAuction(0);
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
     * Set visits.
     *
     * @param int|null $visits
     *
     * @return OfferAnalytics
     */
    public function setVisits($visits = null)
    {
        $this->visits = $visits;

        return $this;
    }

    /**
     * Get visits.
     *
     * @return int|null
     */
    public function getVisits()
    {
        return $this->visits;
    }

    /**
     * Set emails.
     *
     * @param int|null $emails
     *
     * @return OfferAnalytics
     */
    public function setEmails($emails = null)
    {
        $this->emails = $emails;

        return $this;
    }

    /**
     * Get emails.
     *
     * @return int|null
     */
    public function getEmails()
    {
        return $this->emails;
    }

    /**
     * Set phoneClicks.
     *
     * @param int|null $phoneClicks
     *
     * @return OfferAnalytics
     */
    public function setPhoneClicks($phoneClicks = null)
    {
        $this->phone_clicks = $phoneClicks;

        return $this;
    }

    /**
     * Get phoneClicks.
     *
     * @return int|null
     */
    public function getPhoneClicks()
    {
        return $this->phone_clicks;
    }

    /**
     * Set phoneCalls.
     *
     * @param int|null $phoneCalls
     *
     * @return OfferAnalytics
     */
    public function setPhoneCalls($phoneCalls = null)
    {
        $this->phone_calls = $phoneCalls;

        return $this;
    }

    /**
     * Get phoneCalls.
     *
     * @return int|null
     */
    public function getPhoneCalls()
    {
        return $this->phone_calls;
    }

    /**
     * Set phoneCallbacks.
     *
     * @param int|null $phoneCallbacks
     *
     * @return OfferAnalytics
     */
    public function setPhoneCallbacks($phoneCallbacks = null)
    {
        $this->phone_callbacks = $phoneCallbacks;

        return $this;
    }

    /**
     * Get phoneCallbacks.
     *
     * @return int|null
     */
    public function getPhoneCallbacks()
    {
        return $this->phone_callbacks;
    }

    /**
     * Set searchResults.
     *
     * @param int|null $searchResults
     *
     * @return OfferAnalytics
     */
    public function setSearchResults($searchResults = null)
    {
        $this->search_results = $searchResults;

        return $this;
    }

    /**
     * Get searchResults.
     *
     * @return int|null
     */
    public function getSearchResults()
    {
        return $this->search_results;
    }

    /**
     * Set auction.
     *
     * @param int|null $auction
     *
     * @return $this
     */
    public function setAuction($auction = null)
    {
        $this->auction = $auction;

        return $this;
    }

    /**
     * Get auction.
     *
     * @return int|null
     */
    public function getAuction()
    {
        return $this->auction;
    }

    /**
     * Set whatsappMessage.
     *
     * @param int|null $whatsappMessage
     *
     * @return OfferAnalytics
     */
    public function setWhatsappMessage($whatsappMessage = null)
    {
        $this->whatsapp_message = $whatsappMessage;

        return $this;
    }

    /**
     * Get whatsappMessage.
     *
     * @return int|null
     */
    public function getWhatsappMessage()
    {
        return $this->whatsapp_message;
    }

    /**
     * Set offer.
     *
     * @param Offer|null $offer
     *
     * @return OfferAnalytics
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
     * Set seller.
     *
     * @param Seller|null $seller
     *
     * @return OfferAnalytics
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
     * Add analyticsDatum.
     *
     * @param OfferAnalyticsData $analyticsDatum
     *
     * @return OfferAnalytics
     */
    public function addOfferAnalyticsDatum(OfferAnalyticsData $analyticsDatum)
    {
        $this->analytics_data[] = $analyticsDatum;

        return $this;
    }

    /**
     * Remove analyticsDatum.
     *
     * @param OfferAnalyticsData $analyticsDatum
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeOfferAnalyticsDatum(OfferAnalyticsData $analyticsDatum)
    {
        return $this->analytics_data->removeElement($analyticsDatum);
    }

    /**
     * Get analyticsData.
     *
     * @return Collection
     */
    public function getOfferAnalyticsData()
    {
        return $this->analytics_data;
    }

    /**
     * Add analyticsDatum.
     *
     * @param OfferAnalyticsData $analyticsDatum
     *
     * @return OfferAnalytics
     */
    public function addAnalyticsDatum(OfferAnalyticsData $analyticsDatum)
    {
        $this->analytics_data[] = $analyticsDatum;

        return $this;
    }

    /**
     * Remove analyticsDatum.
     *
     * @param OfferAnalyticsData $analyticsDatum
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeAnalyticsDatum(OfferAnalyticsData $analyticsDatum)
    {
        return $this->analytics_data->removeElement($analyticsDatum);
    }

    /**
     * Get analyticsData.
     *
     * @return Collection
     */
    public function getAnalyticsData()
    {
        return $this->analytics_data;
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

<?php

namespace TradusBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OfferAnalytics.
 *
 * @ORM\Table(name="offer_analytics_daily")
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\OfferAnalyticsDataRepository")
 */
class OfferAnalyticsDaily
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="day", type="date", nullable=false)
     */
    private $day;

    /**
     * @var int
     *
     * @ORM\Column(name="offer_id", type="integer", nullable=false)
     */
    private $offer_id;

    /**
     * @var int
     *
     * @ORM\Column(name="seller_id", type="integer", nullable=true)
     */
    private $seller_id;

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
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set Day.
     *
     * @return Day
     */
    public function setDay($day)
    {
        $this->day = $day;

        return $this;
    }

    /**
     * Get Day.
     *
     * @return Day
     */
    public function getDay()
    {
        return $this->day;
    }

    /**
     * Set offer id.
     *
     * @return OfferAnalyticsDaily
     */
    public function setOfferId($offerid)
    {
        $this->offer_id = $offerid;

        return $this;
    }

    /**
     * Get offer_id.
     *
     * @return offer_id
     */
    public function getOfferId()
    {
        return $this->offer_id;
    }

    /**
     * Set seller id.
     *
     * @return OfferAnalyticsDaily
     */
    public function setSellerId($sellerid)
    {
        $this->seller_id = $sellerid;

        return $this;
    }

    /**
     * Get seller id.
     *
     * @return seller_id
     */
    public function getSellerId()
    {
        return $this->seller_id;
    }

    /**
     * Set visits.
     *
     * @param int|null $visits
     *
     * @return OfferAnalyticsDaily
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
     * @return OfferAnalyticsDaily
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
     * @return OfferAnalyticsDaily
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
     * @return OfferAnalyticsDaily
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
     * @return OfferAnalyticsDaily
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
     * @return OfferAnalyticsDaily
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
     * @return OfferAnalyticsDaily
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

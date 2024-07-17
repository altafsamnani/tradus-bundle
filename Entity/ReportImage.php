<?php

namespace TradusBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Report Image.
 *
 * @ORM\Table(name="report_image")
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\ReportImageRepository")
 */
class ReportImage
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="offer_id", type="integer", nullable=true)
     */
    private $offer;

    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="integer", nullable=true)
     */
    private $userId;

    /**
     * @var int
     *
     * @ORM\Column(name="offer_image_id", type="integer", nullable=true)
     */
    private $offerImageId;

    /**
     * @var string
     *
     * @ORM\Column(name="ip", type="string", length=255, nullable=true)
     */
    private $ip;

    /**
     * @var string
     *
     * @ORM\Column(name="session_token", type="string", length=255, nullable=true)
     */
    private $sessionToken;

    /**
     * @var string
     *
     * @ORM\Column(name="user_agent", type="string", length=255, nullable=true)
     */
    private $userAgent;

    /**
     * @var string
     *
     * @ORM\Column(name="langcode", type="string", length=2)
     */
    private $locale;

    /**
     * @var string
     *
     * @ORM\Column(name="reported_reason", type="string", length=500)
     */
    private $reportedReason;

    /**
     * @var int
     *
     * @ORM\Column(name="sitecode_id", type="integer", nullable=false, options={"default"="1"})
     * @Assert\Type("integer")
     */
    private $sitecodeId;

    /**
     * @var DateTime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return ReportImage
     */
    public function setId(int $id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getOffer()
    {
        return $this->offer;
    }

    /**
     * @param $offer
     * @return ReportImage
     */
    public function setOffer($offer)
    {
        $this->offer = $offer;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param $userId
     * @return ReportImage
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getOfferImageId()
    {
        return $this->offerImageId;
    }

    /**
     * @param $offerImageId
     * @return ReportImage
     */
    public function setOfferImageId($offerImageId)
    {
        $this->offerImageId = $offerImageId;

        return $this;
    }

    /**
     * @return string
     */
    public function getSessionToken(): string
    {
        return $this->sessionToken;
    }

    /**
     * @param string $sessionToken
     * @return ReportImage
     */
    public function setSessionToken(string $sessionToken)
    {
        $this->sessionToken = $sessionToken;

        return $this;
    }

    /**
     * @return string
     */
    public function getIp(): string
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     * @return ReportImage
     */
    public function setIp(string $ip)
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * @return string
     */
    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    /**
     * @param string $userAgent
     * @return ReportImage
     */
    public function setUserAgent(string $userAgent)
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    /**
     * @return string
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * @param string $locale
     * @return ReportImage
     */
    public function setLocale(string $locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * @return string
     */
    public function getReportedReason(): string
    {
        return $this->reportedReason;
    }

    /**
     * @param string $reportedReason
     * @return ReportImage
     */
    public function setReportedReason(string $reportedReason)
    {
        $this->reportedReason = $reportedReason;

        return $this;
    }

    /**
     * @return int
     */
    public function getSitecodeId(): int
    {
        return $this->sitecodeId;
    }

    /**
     * @param int $sitecodeId
     * @return ReportImage
     */
    public function setSitecodeId(int $sitecodeId)
    {
        $this->sitecodeId = $sitecodeId;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     * @return $this
     */
    public function setCreatedAt(DateTime $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}

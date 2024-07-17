<?php

namespace TradusBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Report Abuse.
 *
 * @ORM\Table(name="report_abuse")
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\ReportAbuseRepository")
 */
class ReportAbuse
{
    public const CATEGORY = 1;
    public const PRICE = 2;
    public const SOLD = 3;
    public const SPAM = 4;

    public const ABUSE_REASONS = [
        self::CATEGORY  => 'Inappropriate category',
        self::PRICE     => 'Wrong price',
        self::SOLD      => 'The item is sold',
        self::SPAM      => 'Spam',
    ];

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
     * @var string
     *
     * @ORM\Column(name="offer_url", type="string", length=255, nullable=true)
     */
    private $offerUrl;

    /**
     * @var int
     *
     * @ORM\Column(name="reason", type="integer", nullable=true)
     */
    private $reason;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="string", length=255, nullable=true)
     */
    private $message;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="integer", nullable=true)
     */
    private $userId;

    /**
     * @var string
     *
     * @ORM\Column(name="ip", type="string", length=255, nullable=true)
     */
    private $ip;

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
     * @var DateTime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="sent_at", type="datetime", nullable=true)
     */
    private $sentAt;

    /**
     * @return DateTime
     */
    public function getSentAt(): DateTime
    {
        return $this->sentAt;
    }

    /**
     * @var int
     *
     * @ORM\Column(name="sitecode_id", type="integer", nullable=false, options={"default"="1"})
     * @Assert\Type("integer")
     */
    private $sitecodeId;

    /**
     * @param DateTime $sentAt
     */
    public function setSentAt(DateTime $sentAt): void
    {
        $this->sentAt = $sentAt;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getOffer()
    {
        return $this->offer;
    }

    /**
     * @param mixed $offer
     */
    public function setOffer($offer): void
    {
        $this->offer = $offer;
    }

    /**
     * @return string
     */
    public function getOfferUrl(): string
    {
        return $this->offerUrl;
    }

    /**
     * @param string $offerUrl
     */
    public function setOfferUrl(string $offerUrl): void
    {
        $this->offerUrl = $offerUrl;
    }

    /**
     * @return int
     */
    public function getReason(): int
    {
        return $this->reason;
    }

    /**
     * @param int $reason
     */
    public function setReason(int $reason): void
    {
        $this->reason = $reason;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param mixed $userId
     */
    public function setUserId($userId): void
    {
        $this->userId = $userId;
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
     */
    public function setIp(string $ip): void
    {
        $this->ip = $ip;
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
     */
    public function setUserAgent(string $userAgent): void
    {
        $this->userAgent = $userAgent;
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
     */
    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
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
     */
    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
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
     */
    public function setSitecodeId(int $sitecodeId): void
    {
        $this->sitecodeId = $sitecodeId;
    }
}

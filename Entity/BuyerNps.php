<?php

namespace TradusBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="buyer_nps")
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\BuyerNpsRepository")
 */
class BuyerNps
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="nps_results", type="text", nullable=false)
     */
    private $npsResults;

    /**
     * @var string
     * @ORM\Column(name="locale", type="text", nullable=true)
     */
    private $locale;

    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    private $userId;

    /**
     * @var int
     *
     * @ORM\Column(name="sitecode_id", type="integer", nullable=false, options={"default"="1"})
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
     * @var DateTime
     *
     * @ORM\Column(name="completed_at", type="datetime", nullable=true)
     */
    private $completedAt;

    /**
     * @return string
     */
    public function getNpsResults(): string
    {
        return $this->npsResults;
    }

    /**
     * @param string $npsResults
     */
    public function setNpsResults(string $npsResults): void
    {
        $this->npsResults = $npsResults;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     */
    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
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
     * @return DateTime
     */
    public function getCompletedAt(): ?DateTime
    {
        return $this->completedAt;
    }

    /**
     * @param DateTime $completedAt
     */
    public function setCompletedAt(DateTime $completedAt): void
    {
        $this->completedAt = $completedAt;
    }

    public function toArray()
    {
        return [
            'user_id' => $this->getUserId(),
            'nps_results' => json_decode($this->getNpsResults(), true),
            'locale' => $this->getLocale(),
            'created_at' => $this->getCreatedAt(),
            'completed_at' => $this->getCompletedAt(),
            'sitecode_id' => $this->getSitecodeId(),
        ];
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
}

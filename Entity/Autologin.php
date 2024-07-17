<?php

namespace TradusBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * Autologin.
 *
 * @ORM\Table(name="autologin")
 * @ORM\Entity
 */
class Autologin
{
    /* Autologin from a similar offer alert in the email */
    const OFFER = 1;

    /* Autologin token expires after so many days */
    const EXPIRE_AFTER = 3;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false, options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    private $userId;

    /**
     * @var int
     *
     * @ORM\Column(name="type", type="integer", nullable=false, options={"default"="1","comment"="1 - Offer;"})
     */
    private $type = '1';

    /**
     * @var string|null
     *
     * @ORM\Column(name="token", type="string", length=255, nullable=true)
     */
    private $token;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(name="added_date", type="datetime", nullable=true)
     */
    private $addedDate;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(name="used_date", type="datetime", nullable=true)
     */
    private $usedDate;

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
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @param int $type
     */
    public function setType(int $type): void
    {
        $this->type = $type;
    }

    /**
     * @return null|string
     */
    public function getToken(): ?string
    {
        return $this->token;
    }

    /**
     * @param null|string $token
     */
    public function setToken(?string $token): void
    {
        $this->token = $token;
    }

    /**
     * @return DateTime|null
     */
    public function getAddedDate(): ?DateTime
    {
        return $this->addedDate;
    }

    /**
     * @param DateTime|null $addedDate
     */
    public function setAddedDate(?DateTime $addedDate): void
    {
        $this->addedDate = $addedDate;
    }

    /**
     * @return DateTime|null
     */
    public function getUsedDate(): ?DateTime
    {
        return $this->usedDate;
    }

    /**
     * @param DateTime|null $usedDate
     */
    public function setUsedDate(?DateTime $usedDate): void
    {
        $this->usedDate = $usedDate;
    }
}

<?php

namespace TradusBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;

/**
 * OfferVas.
 *
 * @ORM\Table(
 *     name="offer_vas",
 *     indexes={@ORM\Index(name="offer_id", columns={"offer_id"}), @ORM\Index(name="vas_id", columns={"vas_id"})}
 * )
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\OfferVasRepository")
 */
class OfferVas
{
    public const STATUS_ONLINE = 100;
    public const STATUS_OFFLINE = -100;
    public const REDIS_NAMESPACE_VAS_HOME = 'vas_homepage:';

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="TradusBundle\Entity\Offer", inversedBy="vas")
     * @ORM\JoinColumn(name="offer_id", referencedColumnName="id", nullable=false)
     * @Exclude
     */
    private $offerId;

    /**
     * @var int
     *
     * @ORM\OneToOne(targetEntity="TradusBundle\Entity\Vas")
     * @ORM\Column(name="vas_id", type="integer", nullable=false)
     */
    private $vasId;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(name="start_date", type="datetime", nullable=true)
     */
    private $startDate;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(name="end_date", type="datetime", nullable=true)
     */
    private $endDate;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="integer", nullable=false, options={"default"="100"})
     */
    private $status = self::STATUS_ONLINE;

    /**
     * @var int
     *
     * @ORM\Column(name="sitecode_id", type="integer", nullable=false, options={"default"="1"})
     */
    private $sitecodeId = Sitecodes::SITECODE_TRADUS;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return OfferVas
     */
    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return Offer
     */
    public function getOfferId()
    {
        return $this->offerId;
    }

    /**
     * @param Offer $offerId
     * @return OfferVas
     */
    public function setOfferId(Offer $offerId): self
    {
        $this->offerId = $offerId;

        return $this;
    }

    /**
     * @return int
     */
    public function getVasId(): int
    {
        return $this->vasId;
    }

    /**
     * @param int $vasId
     * @return OfferVas
     */
    public function setVasId(int $vasId): self
    {
        $this->vasId = $vasId;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getStartDate(): ?DateTime
    {
        return $this->startDate;
    }

    /**
     * @param DateTime|null $startDate
     * @return OfferVas
     */
    public function setStartDate(?DateTime $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getEndDate(): ?DateTime
    {
        return $this->endDate;
    }

    /**
     * @param DateTime|null $endDate
     * @return OfferVas
     */
    public function setEndDate(?DateTime $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @param int $status
     * @return OfferVas
     */
    public function setStatus(int $status): self
    {
        $this->status = $status;

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
     * @return OfferVas
     */
    public function setSitecodeId(int $sitecodeId): self
    {
        $this->sitecodeId = $sitecodeId;

        return $this;
    }

    public function toArray()
    {
        return [
            'vas' => $this->vasId,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'status' => $this->status,
            'sitecodeId' => $this->getSitecodeId(),
        ];
    }
}

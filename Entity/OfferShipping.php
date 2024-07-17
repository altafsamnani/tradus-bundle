<?php

namespace TradusBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * OfferShipping.
 *
 * @ORM\Table(name="offer_shipping")
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\OfferShippingRepository")
 */
class OfferShipping
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\ManyToOne(targetEntity="TradusBundle\Entity\Offer", inversedBy="shipping_quote")
     * @ORM\JoinColumn(name="offer_id", referencedColumnName="id")
     */
    private $offer;

    /**
     * @var int
     * @ORM\ManyToOne(targetEntity="TradusBundle\Entity\TradusUser", inversedBy="shipping_quote")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;

    /**
     * @var string
     *
     * @ORM\Column(name="from_country_iso", type="string", length=2, nullable=false)
     */
    private $fromCountryIso;

    /**
     * @var string
     *
     * @ORM\Column(name="from_city", type="string", length=50, nullable=false)
     */
    private $fromCity;

    /**
     * @var string
     *
     * @ORM\Column(name="destination_country_iso", type="string", length=2, nullable=false)
     */
    private $destinationCountryIso;

    /**
     * @var string
     *
     * @ORM\Column(name="destination_city", type="string", length=50, nullable=false)
     */
    private $destinationCity;

    /**
     * @var int
     *
     * @ORM\Column(name="tw_vehicle_type", type="integer", nullable=false)
     */
    private $twVehicleType;
    /**
     * @var decimal
     *
     * @ORM\Column(name="total", type="decimal", precision=10, scale=2, nullable=false)
     */
    private $total;

    /**
     * @var \DateTime
     * @Assert\DateTime()
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime
     * @Assert\DateTime()
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private $updatedAt;

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
     * Set offerId.
     *
     * @param int $offer
     *
     * @return OfferShipping
     */
    public function setOffer($offer)
    {
        $this->offer = $offer;

        return $this;
    }

    /**
     * Get offer.
     *
     * @return int
     */
    public function getOffer()
    {
        return $this->offer;
    }

    /**
     * Set user.
     *
     * @param int $user
     *
     * @return OfferShipping
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     *
     * @return int
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set fromCountryIso.
     *
     * @param string $fromCountryIso
     *
     * @return OfferShipping
     */
    public function setFromCountryIso($fromCountryIso)
    {
        $this->fromCountryIso = $fromCountryIso;

        return $this;
    }

    /**
     * Get fromCountryIso.
     *
     * @return string
     */
    public function getFromCountryIso()
    {
        return $this->fromCountryIso;
    }

    /**
     * Set fromCity.
     *
     * @param string $fromCity
     *
     * @return OfferShipping
     */
    public function setFromCity($fromCity)
    {
        $this->fromCity = $fromCity;

        return $this;
    }

    /**
     * Get fromCity.
     *
     * @return string
     */
    public function getFromCity()
    {
        return $this->fromCity;
    }

    /**
     * Set destinationCountryIso.
     *
     * @param string $destinationCountryIso
     *
     * @return OfferShipping
     */
    public function setDestinationCountryIso($destinationCountryIso)
    {
        $this->destinationCountryIso = $destinationCountryIso;

        return $this;
    }

    /**
     * Get destinationCountryIso.
     *
     * @return string
     */
    public function getDestinationCountryIso()
    {
        return $this->destinationCountryIso;
    }

    /**
     * Set destinationCity.
     *
     * @param string $destinationCity
     *
     * @return OfferShipping
     */
    public function setDestinationCity($destinationCity)
    {
        $this->destinationCity = $destinationCity;

        return $this;
    }

    /**
     * Get destinationCity.
     *
     * @return string
     */
    public function getDestinationCity()
    {
        return $this->destinationCity;
    }

    /**
     * Set twVehicleType.
     *
     * @param int $twVehicleType
     *
     * @return OfferShipping
     */
    public function setTwVehicleType($twVehicleType)
    {
        $this->twVehicleType = $twVehicleType;

        return $this;
    }

    /**
     * Get twVehicleType.
     *
     * @return int
     */
    public function getTwVehicleType()
    {
        return $this->twVehicleType;
    }

    /**
     * Set total.
     *
     * @param decimal $total
     *
     * @return OfferShipping
     */
    public function setTotal($total)
    {
        $this->total = $total;

        return $this;
    }

    /**
     * Get total.
     *
     * @return decimal
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * Set createdAt.
     *
     * @param \DateTime $createdAt
     *
     * @return OfferShipping
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt.
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set updatedAt.
     *
     * @param \DateTime $updatedAt
     *
     * @return OfferShipping
     */
    public function setUpdatedAt($updatedAt = null)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt.
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }
}

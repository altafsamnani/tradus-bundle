<?php

namespace TradusBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Ip2location.
 *
 * @ORM\Table(name="ip2location")
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\Ip2locationRepository")
 */
class Ip2location
{
    /**
     * @var float
     * @ORM\Id
     * @ORM\Column(name="ip_from", type="float")
     */
    private $ipFrom;

    /**
     * @var float
     * @ORM\Id
     * @ORM\Column(name="ip_to", type="float")
     */
    private $ipTo;

    /**
     * @var string
     *
     * @ORM\Column(name="country_code", type="string", length=2, nullable=true)
     */
    private $countryCode;

    /**
     * @var string
     *
     * @ORM\Column(name="country_name", type="string", length=64, nullable=true)
     */
    private $countryName;

    /**
     * @var string
     *
     * @ORM\Column(name="region", type="string", length=128, nullable=true)
     */
    private $region;

    /**
     * @var string
     *
     * @ORM\Column(name="city", type="string", length=128, nullable=true)
     */
    private $city;

    /**
     * @var float
     *
     * @ORM\Column(name="latitude", type="float")
     */
    private $latitude;

    /**
     * @var float
     *
     * @ORM\Column(name="longitude", type="float")
     */
    private $longitude;

    /**
     * @var string
     *
     * @ORM\Column(name="zipcode", type="string", length=30, nullable=true)
     */
    private $zipcode;

    /**
     * @return float
     */
    public function getIpFrom(): float
    {
        return $this->ipFrom;
    }

    /**
     * @return float
     */
    public function getIpTo(): float
    {
        return $this->ipTo;
    }

    /**
     * @return string
     */
    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    /**
     * @return string
     */
    public function getCountryName(): string
    {
        return $this->countryName;
    }

    /**
     * @return string
     */
    public function getRegion(): string
    {
        return $this->region;
    }

    /**
     * @return string
     */
    public function getCity(): string
    {
        return $this->city;
    }

    /**
     * @return float
     */
    public function getLatitude(): float
    {
        return $this->latitude;
    }

    /**
     * @return float
     */
    public function getLongitude(): float
    {
        return $this->longitude;
    }

    /**
     * @return string
     */
    public function getZipcode(): string
    {
        return $this->zipcode;
    }
}

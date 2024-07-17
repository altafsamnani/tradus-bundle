<?php

namespace TradusBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Country.
 *
 * @ORM\Table(name="countries")
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\CountryRepository")
 */
class Country
{
    const IS_TOP_COUNTRY = 1;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="iso_code", type="string", length=2, nullable=true)
     */
    private $isoCode;

    /**
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @ORM\Column(name="top_country", type="integer", nullable=true)
     */
    private $topCountry;

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
     * @param string|null $name
     * @return $this
     */
    public function setName($name = null)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string|null $isoCode
     * @return $this
     */
    public function setIsoCode($isoCode = null)
    {
        $this->isoCode = $isoCode;

        return $this;
    }

    /**
     * Get isoCode.
     *
     * @return string|null
     */
    public function getIsoCode()
    {
        return $this->isoCode;
    }

    /**
     * @param string|null $topCountry
     * @return $this
     */
    public function setTopCountry($topCountry = null)
    {
        $this->topCountry = $topCountry;

        return $this;
    }

    /**
     * Get topCountry.
     *
     * @return string|null
     */
    public function getTopCountry()
    {
        return $this->topCountry;
    }
}

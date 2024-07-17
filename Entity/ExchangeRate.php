<?php

namespace TradusBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\ExchangeRateRepository")
 * @ORM\Table(name="exchange_rate")
 */
class ExchangeRate
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /** @ORM\Column(name="currency", type="string", length=8, nullable=false) */
    private $currency;

    /** @ORM\Column(name="rate", type="decimal", precision=12, scale=6) */
    private $rate;

    /** @ORM\Column(name="updated_at", type="datetime") */
    private $updated_at;

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
     * Set currency.
     *
     * @param string|null $currency
     *
     * @return ExchangeRate
     */
    public function setCurrency($currency = null)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get currency.
     *
     * @return string|null
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set rate.
     *
     * @param float|null $rate
     *
     * @return ExchangeRate
     */
    public function setRate($rate = null)
    {
        $this->rate = $rate;

        return $this;
    }

    /**
     * Get rate.
     *
     * @return float|null
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * Set updatedAt.
     *
     * @param DateTime|null $updatedAt
     *
     * @return ExchangeRate
     */
    public function setUpdatedAt($updatedAt = null)
    {
        $this->updated_at = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt.
     *
     * @return DateTime|null
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }
}

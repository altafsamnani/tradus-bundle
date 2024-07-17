<?php

namespace TradusBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation\Exclude;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * OfferDepreciations.
 * @ORM\Table(name="offer_depreciations")
 * @ORM\Entity()
 */
class OfferDepreciations
{
    const STATUS_ONLINE = 100;
    const STATUS_OFFLINE = -100;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity="TradusBundle\Entity\Offer", inversedBy="depreciation", cascade={"persist"})
     * @ORM\JoinColumn(name="offer_id", referencedColumnName="id")
     */
    private $offer;

    /**
     * @var int
     *
     * @ORM\Column(name="extra_age_months", type="integer", nullable=true)
     * @Assert\Type("int")
     */
    private $extraAgeMonths;

    /**
     * @var float
     *
     * @ORM\Column(name="listing_predicted_price", type="float", nullable=true)
     * @Assert\Type("float")
     */
    private $listingPredictedPrice;

    /**
     * @var float
     *
     * @ORM\Column(name="listing_interval_factor", type="float", nullable=true)
     * @Assert\Type("float")
     */
    private $listingIntervalFactor;

    /**
     * @var float
     *
     * @ORM\Column(name="listing_annual_factor", type="float", nullable=true)
     * @Assert\Type("float")
     */
    private $listingAnnualFactor;

    /**
     * @var float
     *
     * @ORM\Column(name="version_annual_factor", type="float", nullable=true)
     * @Assert\Type("float")
     */
    private $versionAnnualFactor;

    /**
     * @var float
     *
     * @ORM\Column(name="category_annual_factor", type="float", nullable=true)
     * @Assert\Type("float")
     */
    private $categoryAnnualFactor;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="integer", nullable=true)
     * @Assert\Type("integer")
     * @Assert\Choice(callback={"TradusBundle\Entity\Offer", "getValidStatusList"}, strict=true)
     * @Exclude
     */
    private $status = self::STATUS_ONLINE;

    /**
     * @var DateTime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime")
     * @Assert\DateTime()
     */
    private $createdAt;

    /**
     * @var DateTime
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="updated_at", type="datetime")
     * @Assert\DateTime()
     */
    private $updatedAt;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Offer
     */
    public function getOffer()
    {
        return $this->offer;
    }

    /**
     * @param Offer $offer
     *
     * @return OfferDepreciations
     */
    public function setOffer(Offer $offer)
    {
        $this->offer = $offer;

        return $this;
    }

    /**
     * @return int
     */
    public function getExtraAgeMonths(): int
    {
        return $this->extraAgeMonths;
    }

    /**
     * @param int|null $extraAgeMonths
     */
    public function setExtraAgeMonths(int $extraAgeMonths = null): void
    {
        $this->extraAgeMonths = $extraAgeMonths;
    }

    /**
     * @return float
     */
    public function getListingPredictedPrice(): float
    {
        return $this->listingPredictedPrice;
    }

    /**
     * @param float|null $listingPredictedPrice
     */
    public function setListingPredictedPrice(float $listingPredictedPrice = null): void
    {
        $this->listingPredictedPrice = $listingPredictedPrice;
    }

    /**
     * @return float
     */
    public function getListingIntervalFactor(): float
    {
        return $this->listingIntervalFactor;
    }

    /**
     * @param float|null $listingIntervalFactor
     */
    public function setListingIntervalFactor(float $listingIntervalFactor = null): void
    {
        $this->listingIntervalFactor = $listingIntervalFactor;
    }

    /**
     * @return float
     */
    public function getListingAnnualFactor(): float
    {
        return $this->listingAnnualFactor;
    }

    /**
     * @param float|null $listingAnnualFactor
     */
    public function setListingAnnualFactor(float $listingAnnualFactor = null): void
    {
        $this->listingAnnualFactor = $listingAnnualFactor;
    }

    /**
     * @return float
     */
    public function getVersionAnnualFactor(): float
    {
        return $this->versionAnnualFactor;
    }

    /**
     * @param float|null $versionAnnualFactor
     */
    public function setVersionAnnualFactor(float $versionAnnualFactor = null): void
    {
        $this->versionAnnualFactor = $versionAnnualFactor;
    }

    /**
     * @return float
     */
    public function getCategoryAnnualFactor(): float
    {
        return $this->categoryAnnualFactor;
    }

    /**
     * @param float|null $categoryAnnualFactor
     */
    public function setCategoryAnnualFactor(float $categoryAnnualFactor = null): void
    {
        $this->categoryAnnualFactor = $categoryAnnualFactor;
    }

    /**
     * Set status.
     *
     * @param int|null $status
     *
     * @return Offer
     */
    public function setStatus($status = null)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return int|null
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set createdAt.
     *
     * @param DateTime $createdAt
     *
     * @return Offer
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt.
     *
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set updatedAt.
     *
     * @param DateTime $updatedAt
     *
     * @return Offer
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt.
     *
     * @return DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }
}

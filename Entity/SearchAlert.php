<?php

namespace TradusBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation\Exclude;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * SearchAlert.
 *
 * @ORM\Table(name="search_alerts")
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\SearchAlertRepository")
 */
class SearchAlert
{
    public const STATUS_SUBSCRIBED = 100;
    public const STATUS_UNSUBSCRIBED = -10;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="integer", nullable=true)
     * @Assert\Type("integer")
     */
    private $status = self::STATUS_SUBSCRIBED;

    /**
     * @ORM\ManyToOne(targetEntity="TradusBundle\Entity\TradusUser", inversedBy="search_alerts")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * @Exclude
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="Category")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id")
     * @Exclude
     */
    private $category;

    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="integer", nullable=true)
     * @Assert\Type("integer")
     */
    private $userId;

    /**
     * @var int
     *
     * @ORM\Column(name="category_id", type="integer", nullable=true)
     * @Assert\Type("integer")
     */
    private $categoryId;

    /**
     * @var string
     *
     * @ORM\Column(name="makes", type="string", length=255, nullable=true)
     * @Assert\Type("string")
     */
    private $makes;

    /**
     * @var string
     *
     * @ORM\Column(name="countries", type="string", length=255, nullable=true)
     * @Assert\Type("string")
     */
    private $countries;

    /**
     * @var string
     *
     * @ORM\Column(name="query_string", type="string", length=255, nullable=true)
     * @Assert\Type("string")
     */
    private $query_string;

    /**
     * @var string
     *
     * @ORM\Column(name="sort_by", type="string", length=255, nullable=true)
     * @Assert\Type("string")
     */
    private $sort_by;

    /**
     * @var float
     *
     * @ORM\Column(name="price_from", type="float", nullable=true)
     * @Assert\Type("float")
     */
    private $price_from;

    /**
     * @var float
     *
     * @ORM\Column(name="price_to", type="float", nullable=true)
     * @Assert\Type("float")
     */
    private $price_to;

    /**
     * @var int
     *
     * @ORM\Column(name="year_from", type="integer", nullable=true)
     * @Assert\Type("integer")
     */
    private $year_from;

    /**
     * @var int
     *
     * @ORM\Column(name="year_to", type="integer", nullable=true)
     * @Assert\Type("integer")
     */
    private $year_to;

    /**
     * @var int
     *
     * @ORM\Column(name="weight_from", type="integer", nullable=true)
     * @Assert\Type("integer")
     */
    private $weight_from;

    /**
     * @var int
     *
     * @ORM\Column(name="weight_to", type="integer", nullable=true)
     * @Assert\Type("integer")
     */
    private $weight_to;

    /**
     * @var int
     *
     * @ORM\Column(name="mileage_from", type="integer", nullable=true)
     * @Assert\Type("integer")
     */
    private $mileage_from;

    /**
     * @var int
     *
     * @ORM\Column(name="mileage_to", type="integer", nullable=true)
     * @Assert\Type("integer")
     */
    private $mileage_to;

    /**
     * @var DateTime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime")
     * @Assert\DateTime()
     */
    private $created_at;

    /**
     * @var DateTime
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="updated_at", type="datetime")
     * @Assert\DateTime()
     */
    private $updated_at;

    /**
     * @var string
     *
     * @ORM\Column(name="price_type", type="string", length=255, nullable=true)
     * @Assert\Type("string")
     */
    private $price_type;

    /**
     * @var string
     *
     * @ORM\Column(name="price_rating", type="string", length=255, nullable=true)
     * @Assert\Type("string")
     */
    private $price_rating;

    /**
     * @var string
     *
     * @ORM\Column(name="transmission", type="string", length=255, nullable=true)
     * @Assert\Type("string")
     */
    private $transmission;

    /**
     * @var int
     *
     * @ORM\Column(name="sitecode_id", type="integer", nullable=true)
     */
    private $sitecodeId;

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
     * Set user.
     *
     * @param TradusUser $user
     *
     * @return SearchAlert
     */
    public function setUser(TradusUser $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     *
     * @return TradusUser
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set Category Id.
     *
     * @param $categoryId
     *
     * @return SearchAlert
     */
    public function setCategoryId($categoryId)
    {
        $this->categoryId = $categoryId;

        return $this;
    }

    /**
     * Get Category Id.
     *
     * @return int
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     * Set User Id.
     *
     * @param $userId
     *
     * @return SearchAlert
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get User Id.
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set status.
     *
     * @param $status
     *
     * @return SearchAlert
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set category.
     *
     * @param Category $category
     *
     * @return SearchAlert
     */
    public function setCategory(Category $category)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category.
     *
     * @return Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set makes.
     *
     * @param $makes
     *
     * @return SearchAlert
     */
    public function setMakes($makes)
    {
        $this->makes = $makes;

        return $this;
    }

    /**
     * Get makes.
     *
     * @return string
     */
    public function getMakes()
    {
        return $this->makes;
    }

    /**
     * Set countries.
     *
     * @param $countries
     *
     * @return SearchAlert
     */
    public function setCountries($countries)
    {
        $this->countries = $countries;

        return $this;
    }

    /**
     * Get countries.
     *
     * @return string
     */
    public function getCountries()
    {
        return $this->countries;
    }

    /**
     * Set query_string.
     *
     * @param $query_string
     *
     * @return SearchAlert
     */
    public function setQueryString($query_string)
    {
        $this->query_string = $query_string;

        return $this;
    }

    /**
     * Get query_string.
     *
     * @return string
     */
    public function getQueryString()
    {
        return $this->query_string;
    }

    /**
     * Set sort_by.
     *
     * @param $sort_by
     *
     * @return SearchAlert
     */
    public function setSortBy($sort_by)
    {
        $this->sort_by = $sort_by;

        return $this;
    }

    /**
     * Get sort_by.
     *
     * @return string
     */
    public function getSortBy()
    {
        return $this->sort_by;
    }

    /**
     * Set price_from.
     *
     * @param $price_from
     *
     * @return SearchAlert
     */
    public function setPriceFrom($price_from)
    {
        $this->price_from = $price_from;

        return $this;
    }

    /**
     * Get price_from.
     *
     * @return int
     */
    public function getPriceFrom()
    {
        return $this->price_from;
    }

    /**
     * Set price_to.
     *
     * @param $price_to
     *
     * @return SearchAlert
     */
    public function setPriceTo($price_to)
    {
        $this->price_to = $price_to;

        return $this;
    }

    /**
     * Get price_to.
     *
     * @return int
     */
    public function getPriceTo()
    {
        return $this->price_to;
    }

    /**
     * Set year_from.
     *
     * @param $year_from
     *
     * @return SearchAlert
     */
    public function setYearFrom($year_from)
    {
        $this->year_from = $year_from;

        return $this;
    }

    /**
     * Get year_from.
     *
     * @return int
     */
    public function getYearFrom()
    {
        return $this->year_from;
    }

    /**
     * Set year_to.
     *
     * @param $year_to
     *
     * @return SearchAlert
     */
    public function setYearTo($year_to)
    {
        $this->year_to = $year_to;

        return $this;
    }

    /**
     * Get year_to.
     *
     * @return int
     */
    public function getYearTo()
    {
        return $this->year_to;
    }

    /**
     * Set createdAt.
     *
     * @param DateTime $createdAt
     *
     * @return SearchAlert
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;

        return $this;
    }

    /**
     * Get createdAt.
     *
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set updatedAt.
     *
     * @param DateTime $updatedAt
     *
     * @return SearchAlert
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updated_at = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt.
     *
     * @return DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * @return string
     */
    public function getPriceType()
    {
        return $this->price_type;
    }

    /**
     * @param string $priceType
     *
     * @return SearchAlert;
     */
    public function setPriceType($priceType): self
    {
        $this->price_type = $priceType;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPriceRating()
    {
        return $this->price_rating;
    }

    /**
     * @param $priceRating
     */
    public function setPriceRating($priceRating): void
    {
        $this->price_rating = $priceRating;
    }

    /**
     * @return int|null
     */
    public function getWeightFrom()
    {
        return $this->weight_from;
    }

    /**
     * @param $weightFrom
     */
    public function setWeightFrom($weightFrom): void
    {
        $this->weight_from = $weightFrom;
    }

    /**
     * @return int|null
     */
    public function getWeightTo()
    {
        return $this->weight_to;
    }

    /**
     * @param $weightTo
     */
    public function setWeightTo($weightTo): void
    {
        $this->weight_to = $weightTo;
    }

    /**
     * @return int|null
     */
    public function getMileageFrom()
    {
        return $this->mileage_from;
    }

    /**
     * @param $mileageFrom
     */
    public function setMileageFrom($mileageFrom): void
    {
        $this->mileage_from = $mileageFrom;
    }

    /**
     * @return int|null
     */
    public function getMileageTo()
    {
        return $this->mileage_to;
    }

    /**
     * @param $mileageTo
     */
    public function setMileageTo($mileageTo): void
    {
        $this->mileage_to = $mileageTo;
    }

    /**
     * @return string|null
     */
    public function getTransmission()
    {
        return $this->transmission;
    }

    /**
     * @param $transmission
     */
    public function setTransmission($transmission): void
    {
        $this->transmission = $transmission;
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

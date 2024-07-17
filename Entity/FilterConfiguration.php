<?php

namespace TradusBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * FilterConfiguration.
 *
 * @ORM\Table(name="filter_configuration", indexes={@ORM\Index(name="idx_status", columns={"status"}), @ORM\Index(name="idx_filter_for", columns={"filter_for"})})
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\FilterConfigurationRepository")
 */
class FilterConfiguration implements FilterConfigurationInterface
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
     *
     * @ORM\Column(name="filter_name", type="string", length=200, nullable=false)
     */
    private $filterName;

    /**
     * @var string
     *
     * @ORM\Column(name="filter_title", type="string", length=200, nullable=false)
     */
    private $filterTitle;

    /**
     * @var string|null
     *
     * @ORM\Column(name="filter_type", type="string", length=100, nullable=true)
     */
    private $filterType;

    /**
     * @var string|null
     *
     * @ORM\Column(name="filter_style", type="string", length=100, nullable=true)
     */
    private $filterStyle;

    /**
     * @var string
     *
     * @ORM\Column(name="searchkey", type="string", length=200, nullable=false)
     */
    private $searchKey;

    /**
     * @var int|null
     *
     * @ORM\Column(name="filter_group", type="integer", nullable=true)
     */
    private $filterGroup;

    /**
     * @var int|null
     *
     * @ORM\Column(name="filter_for", type="integer", nullable=true, options={"default"="1"})
     */
    private $filterFor = '1';

    /**
     * @var int|null
     *
     * @ORM\Column(name="filter_sort", type="integer", nullable=true)
     */
    private $filterSort;

    /**
     * @var int|null
     *
     * @ORM\Column(name="attribute_id", type="integer", nullable=true)
     */
    private $attributeId;

    /**
     * @var string|null
     *
     * @ORM\Column(name="filter_options", type="text", length=0, nullable=true)
     */
    private $filterOptions;

    /**
     * @var string|null
     *
     * @ORM\Column(name="filter_extra_options", type="text", length=0, nullable=true)
     */
    private $filterExtraOptions;

    /**
     * @var string|null
     *
     * @ORM\Column(name="solr_key", type="string", length=100, nullable=true)
     */
    private $solrKey;

    /**
     * @var int|null
     *
     * @ORM\Column(name="status", type="integer", nullable=true, options={"default"="1"})
     */
    private $status = '1';

    /**
     * @var int|null
     *
     * @ORM\Column(name="created_by", type="integer", nullable=true)
     */
    private $createdBy;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     */
    private $updatedAt;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(name="deleted_at", type="datetime", nullable=true)
     */
    private $deletedAt;

    /**
     * @ORM\Column(name="percentage", type="decimal")
     */
    private $percentage;

    /**
     * @var int|null
     *
     * @ORM\Column(name="show_filter", type="integer", nullable=true)
     */
    private $showFilter;

    /**
     * @return string
     */
    public function getFilterName(): string
    {
        return $this->filterName;
    }

    /**
     * @param string $filterName
     * @return FilterConfiguration
     */
    public function setFilterName(string $filterName): self
    {
        $this->filterName = $filterName;

        return $this;
    }

    /**
     * @return string
     */
    public function getFilterTitle(): string
    {
        return $this->filterTitle;
    }

    /**
     * @param string $filterTitle
     * @return FilterConfiguration
     */
    public function setFilterTitle(string $filterTitle): self
    {
        $this->filterTitle = $filterTitle;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFilterType(): ?string
    {
        return $this->filterType;
    }

    /**
     * @param string|null $filterType
     * @return FilterConfiguration
     */
    public function setFilterType(?string $filterType): self
    {
        $this->filterType = $filterType;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFilterStyle(): ?string
    {
        return $this->filterStyle;
    }

    /**
     * @param string|null $filterStyle
     * @return FilterConfiguration
     */
    public function setFilterStyle(?string $filterStyle): self
    {
        $this->filterStyle = $filterStyle;

        return $this;
    }

    /**
     * @return string
     */
    public function getSearchKey(): string
    {
        return $this->searchKey;
    }

    /**
     * @param string $searchKey
     * @return FilterConfiguration
     */
    public function setSearchKey(string $searchKey): self
    {
        $this->searchKey = $searchKey;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getFilterGroup(): ?int
    {
        return $this->filterGroup;
    }

    /**
     * @param int|null $filterGroup
     * @return FilterConfiguration
     */
    public function setFilterGroup(?int $filterGroup): self
    {
        $this->filterGroup = $filterGroup;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getFilterFor(): ?int
    {
        return $this->filterFor;
    }

    /**
     * @param int|null $filterFor
     * @return FilterConfiguration
     */
    public function setFilterFor(?int $filterFor): self
    {
        $this->filterFor = $filterFor;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getAttributeId(): ?int
    {
        return $this->attributeId;
    }

    /**
     * @param int|null $attributeId
     * @return FilterConfiguration
     */
    public function setAttributeId(?int $attributeId): self
    {
        $this->attributeId = $attributeId;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFilterOptions(): ?string
    {
        return $this->filterOptions;
    }

    /**
     * @param string|null $filterOptions
     * @return FilterConfiguration
     */
    public function setFilterOptions(?string $filterOptions): self
    {
        $this->filterOptions = $filterOptions;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFilterExtraOptions(): ?string
    {
        return $this->filterExtraOptions;
    }

    /**
     * @param string|null $filterExtraOptions
     * @return FilterConfiguration
     */
    public function setFilterExtraOptions(?string $filterExtraOptions): self
    {
        $this->filterExtraOptions = $filterExtraOptions;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSolrKey(): ?string
    {
        return $this->solrKey;
    }

    /**
     * @param string|null $solrKey
     * @return FilterConfiguration
     */
    public function setSolrKey(?string $solrKey): self
    {
        $this->solrKey = $solrKey;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getStatus(): ?int
    {
        return $this->status;
    }

    /**
     * @param int|null $status
     * @return FilterConfiguration
     */
    public function setStatus(?int $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getCreatedBy(): ?int
    {
        return $this->createdBy;
    }

    /**
     * @param int|null $createdBy
     * @return FilterConfiguration
     */
    public function setCreatedBy(?int $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime|null $createdAt
     * @return FilterConfiguration
     */
    public function setCreatedAt(?DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @param DateTime|null $updatedAt
     * @return FilterConfiguration
     */
    public function setUpdatedAt(?DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getDeletedAt(): ?DateTime
    {
        return $this->deletedAt;
    }

    /**
     * @param DateTime|null $deletedAt
     * @return FilterConfiguration
     */
    public function setDeletedAt(?DateTime $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function toArray()
    {
        return get_object_vars($this);
    }

    /**
     * @return int|null
     */
    public function getFilterSort(): ?int
    {
        return $this->filterSort;
    }

    /**
     * @param int|null $filterSort
     */
    public function setFilterSort(?int $filterSort): void
    {
        $this->filterSort = $filterSort;
    }

    /**
     * Get percentage.
     *
     * @return float|null
     */
    public function getPercentage()
    {
        return $this->percentage;
    }

    /**
     * Set percentage.
     *
     * @param float|null $percentage
     * @return $this
     */
    public function setPercentage($percentage = null)
    {
        $this->percentage = $percentage;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getShowFilter(): ?int
    {
        return $this->showFilter;
    }

    /**
     * @param int|null $showFilter
     * @return $this
     */
    public function SetShowFilter(?int $showFilter)
    {
        $this->showFilter = $showFilter;

        return $this;
    }
}

<?php

namespace TradusBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * FilterConfigurationCategories.
 *
 * @ORM\Table(name="filter_configuration_categories", indexes={@ORM\Index(name="idx_filter_id", columns={"filter_id"}), @ORM\Index(name="idx_category_id", columns={"category_id"})})
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\FilterConfigurationCategoriesRepository")
 */
class FilterConfigurationCategories
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
     * @var int
     *
     * @ORM\Column(name="filter_id", type="integer", nullable=true)
     */
    private $filter;

    /**
     * @var int|null
     *
     * @ORM\Column(name="category_id", type="integer", nullable=true)
     */
    private $category;

    /**
     * @return int
     */
    public function getFilterId(): int
    {
        return $this->filter;
    }

    /**
     * @param int $filterId
     * @return FilterConfigurationCategories
     */
    public function setFilterId(int $filterId): self
    {
        $this->filter = $filterId;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getCategoryId(): ?int
    {
        return $this->category;
    }

    /**
     * @param int|null $categoryId
     * @return FilterConfigurationCategories
     */
    public function setCategoryId(?int $categoryId): self
    {
        $this->category = $categoryId;

        return $this;
    }

    public function toArray()
    {
        return get_object_vars($this);
    }
}

<?php

namespace TradusBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;

/**
 * Attribute.
 *
 * @ORM\Table(name="attributes")
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\AttributeRepository")
 */
class Attribute
{
    public const REDIS_NAMESPACE_ATTRIBUTES = 'attributes_definition:';

    public const STATUS_ONLINE = 100;
    public const STATUS_OFFLINE = -10;
    public const STATUS_DELETED = -200;

    public const ATTRIBUTE_TYPE_LIST = 'list';
    public const ATTRIBUTE_TYPE_NUMERIC = 'numeric';
    public const ATTRIBUTE_TYPE_TEXT = 'text';
    public const ATTRIBUTE_TYPE_DECIMAL = 'decimal';
    public const ATTRIBUTE_TYPE_BOOLEAN = 'boolean';

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->options = new ArrayCollection();
        $this->translations = new ArrayCollection();
        $this->categoryAttributes = new ArrayCollection();
    }

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Exclude
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity="AttributeTranslation", mappedBy="attribute")
     * @Exclude
     */
    private $translations;

    /**
     * @ORM\OneToMany(targetEntity="AttributeOption", mappedBy="attribute")
     * @ORM\OrderBy({"sortOrder" = "ASC"})
     */
    private $options;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="string", length=255, nullable=true)
     */
    private $content;

    /**
     * @var string
     *
     * @ORM\Column(name="v1_id", type="string", length=255, nullable=true)
     */
    private $v1_id;

    /**
     * @var string
     *
     * @ORM\Column(length=128, unique=false, nullable=true)
     */
    private $slug;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="integer", nullable=true)
     * @Exclude
     */
    private $status;

    /**
     * @var int
     * @ORM\Column(name="parent_id", type="integer", nullable=true)
     */
    private $parentId;

    /**
     * @var string
     *
     * @ORM\Column(name="attribute_type", type="string", length=45, nullable=true)
     */
    private $attributeType;

    /**
     * @var int
     * @ORM\Column(name="select_multiple", type="integer", nullable=true)
     */
    private $selectMultiple;

    /**
     * @var string
     *
     * @ORM\Column(name="solr_field", type="string", length=100, nullable=true)
     */
    private $solrField;

    /**
     * @ORM\ManyToOne(targetEntity="TradusBundle\Entity\AttributeGroup", inversedBy="attributes")
     * @ORM\JoinColumn(name="group_id", referencedColumnName="id")
     */
    private $group;

    /**
     * @var string
     *
     * @ORM\Column(name="translation_key", type="string", length=255, nullable=true)
     */
    private $translationKey;

    /**
     * @var string
     *
     * @ORM\Column(name="translation_text", type="string", length=255, nullable=true)
     */
    private $translationText;

    /**
     * @var string
     *
     * @ORM\Column(name="measure_unit", type="string", length=45, nullable=true)
     */
    private $measureUnit;

    /**
     * @var int
     * @ORM\Column(name="sort_order", type="integer", nullable=true)
     */
    private $sortOrder;

    /**
     * @var int
     * @ORM\Column(name="allow_filter", type="integer", nullable=true)
     */
    private $allowFilter;

    /**
     * @ORM\ManyToMany(targetEntity="TradusBundle\Entity\Category", inversedBy="categoryAttributes")
     * @ORM\JoinTable(name="category_attributes")
     */
    private $categoryAttributes;

    /**
     * @var int
     * @ORM\Column(name="search_sort_order", type="integer", nullable=true)
     */
    private $searchSortOrder;

    /**
     * @var string
     *
     * @ORM\Column(name="html_component_type", type="string", length=45, nullable=true)
     */
    private $htmlComponentType;

    /**
     * @ORM\ManyToOne(targetEntity="SearchGroup", inversedBy="attributes")
     * @ORM\JoinColumn(name="search_group_id", referencedColumnName="id")
     */
    private $searchGroup;

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
     * Set name.
     *
     * @param string $name
     *
     * @return DynamicContent
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set content.
     *
     * @param string $content
     *
     * @return DynamicContent
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set slug.
     *
     * @param string $slug
     *
     * @return DynamicContent
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug.
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set status.
     *
     * @param int $status
     *
     * @return DynamicContent
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
     * Set v1Id.
     *
     * @param string|null $v1Id
     *
     * @return Attribute
     */
    public function setV1Id($v1Id = null)
    {
        $this->v1_id = $v1Id;

        return $this;
    }

    /**
     * Get v1Id.
     *
     * @return string|null
     */
    public function getV1Id()
    {
        return $this->v1_id;
    }

    /**
     * Add translation.
     *
     * @param AttributeTranslation $translation
     *
     * @return Attribute
     */
    public function addTranslation(AttributeTranslation $translation)
    {
        $this->translations[] = $translation;

        return $this;
    }

    /**
     * Remove translation.
     *
     * @param AttributeTranslation $translation
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeTranslation(AttributeTranslation $translation)
    {
        return $this->translations->removeElement($translation);
    }

    /**
     * Get translations.
     *
     * @return Collection
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * Add option.
     *
     * @param AttributeOption $option
     *
     * @return Attribute
     */
    public function addOption(AttributeOption $option)
    {
        $this->options[] = $option;

        return $this;
    }

    /**
     * Remove option.
     *
     * @param AttributeOption $option
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeOption(AttributeOption $option)
    {
        return $this->options->removeElement($option);
    }

    /**
     * Get options.
     *
     * @return Collection
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Add category.
     *
     * @param Category $category
     *
     * @return Category
     */
    public function addCategory(Category $category)
    {
        $this->categoryAttributes[] = $category;

        return $this;
    }

    /**
     * Remove category.
     *
     * @param Category $category
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeCategory(Category $category)
    {
        return $this->categoryAttributes->removeElement($category);
    }

    /**
     * Get categories.
     *
     * @return Collection
     */
    public function getCategories()
    {
        return $this->categoryAttributes;
    }

    /**
     * @return int|null
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * @param $parentId
     */
    public function setParentId($parentId = null)
    {
        $this->parentId = $parentId;
    }

    /**
     * @return string|null
     */
    public function getAttributeType()
    {
        return $this->attributeType;
    }

    /**
     * @param $attributeType
     */
    public function setAttributeType($attributeType = null)
    {
        $this->attributeType = $attributeType;
    }

    /**
     * @return int|null
     */
    public function getAllowFilter()
    {
        return $this->allowFilter;
    }

    /**
     * @param $allowFilter
     */
    public function setAllowFilter($allowFilter = null)
    {
        $this->allowFilter = $allowFilter;
    }

    /**
     * @return int|null
     */
    public function getSelectMultiple()
    {
        return $this->selectMultiple;
    }

    /**
     * @param $selectMultiple
     */
    public function setSelectMultiple($selectMultiple = null)
    {
        $this->selectMultiple = $selectMultiple;
    }

    /**
     * @return string|null
     */
    public function getSolrField()
    {
        return $this->solrField;
    }

    /**
     * @param $solrField
     */
    public function setSolrField($solrField = null)
    {
        $this->solrField = $solrField;
    }

    /**
     * @return string|null
     */
    public function getTranslationKey()
    {
        return $this->translationKey;
    }

    /**
     * @param $translationKey
     */
    public function setTranslationKey($translationKey = null)
    {
        $this->translationKey = $translationKey;
    }

    /**
     * @return string|null
     */
    public function getTranslationText()
    {
        return $this->translationText;
    }

    /**
     * @param $translationText
     */
    public function setTranslationText($translationText = null)
    {
        $this->translationText = $translationText;
    }

    /**
     * @return string|null
     */
    public function getMeasureUnit()
    {
        return $this->measureUnit;
    }

    /**
     * @param $measureUnit
     */
    public function setMeasureUnit($measureUnit = null)
    {
        $this->measureUnit = $measureUnit;
    }

    /**
     * @return int|null
     */
    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    /**
     * @param $sortOrder
     */
    public function setSortOrder($sortOrder = null)
    {
        $this->sortOrder = $sortOrder;
    }

    /**
     * Set group.
     *
     * @param AttributeGroup|null $group
     *
     * @return Attribute
     */
    public function setGroup(?AttributeGroup $group = null)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Get group.
     *
     * @return AttributeGroup|null
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @return int|null
     */
    public function getSearchSortOrder()
    {
        return $this->searchSortOrder;
    }

    /**
     * @param $searchSortOrder
     */
    public function setSearchSortOrder($searchSortOrder = null)
    {
        $this->searchSortOrder = $searchSortOrder;
    }

    /**
     * @return string|null
     */
    public function getHtmlComponentType()
    {
        return $this->htmlComponentType;
    }

    /**
     * @param $htmlComponentType
     */
    public function setHtmlComponentType($htmlComponentType = null)
    {
        $this->htmlComponentType = $htmlComponentType;
    }

    /**
     * Set search group.
     *
     * @param SearchGroup|null $group
     *
     * @return Attribute
     */
    public function setSearchGroup(?SearchGroup $group = null)
    {
        $this->searchGroup = $group;

        return $this;
    }

    /**
     * Get search group.
     *
     * @return SearchGroup|null
     */
    public function getSearchGroup()
    {
        return $this->searchGroup;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getName().' - '.$this->getContent();
    }
}

<?php

namespace TradusBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;

/**
 * AttributeGroup.
 *
 * @ORM\Table(name="attribute_groups")
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\AttributeGroupRepository")
 */
class AttributeGroup
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->attributes = new ArrayCollection();
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
     * @ORM\Column(name="name", type="string", length=45, nullable=false)
     */
    private $name;

    /**
     * @var int
     * @ORM\Column(name="sort_order", type="integer", nullable=false)
     */
    private $sortOrder;

    /**
     * @var int
     * @ORM\Column(name="collapsable", type="integer", columnDefinition="TINYINT DEFAULT 1")
     */
    private $collapsable;

    /**
     * @var int
     * @ORM\Column(name="status", type="integer", columnDefinition="Status 100")
     */
    private $status;

    /**
     * @ORM\OneToMany(targetEntity="Attribute", mappedBy="group")
     * @Exclude
     */
    private $attributes;

    /**
     * @var string
     *
     * @ORM\Column(name="translation_key", type="string", length=255, nullable=false)
     */
    private $translationKey;

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
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $collapsable
     */
    public function setCollapsableOption($collapsable)
    {
        $this->collapsable = $collapsable;
    }

    /**
     * @return int
     */
    public function getCollapsableOption()
    {
        return $this->collapsable;
    }

    /**
     * @param $sortOrder
     */
    public function setSortOrder($sortOrder)
    {
        $this->sortOrder = $sortOrder;
    }

    /**
     * @return int
     */
    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    /**
     * @param $sortOrder
     */
    public function setTranslationKey($translationKey)
    {
        $this->translationKey = $translationKey;
    }

    /**
     * @return int
     */
    public function getTranslationKey()
    {
        return $this->translationKey;
    }

    /**
     * Add attribute.
     *
     * @param Attribute $attribute
     *
     * @return AttributeGroup
     */
    public function addAttribute(Attribute $attribute)
    {
        $this->attributes[] = $attribute;

        return $this;
    }

    /**
     * Remove attribute.
     *
     * @param Attribute $attribute
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeAttribute(Attribute $attribute)
    {
        return $this->attributes->removeElement($attribute);
    }

    /**
     * Get attributes.
     *
     * @return Collection
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Set status.
     *
     * @param int|null $status
     *
     * @return AttributeGroup
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
}

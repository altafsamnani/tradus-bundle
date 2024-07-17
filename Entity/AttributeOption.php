<?php

namespace TradusBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;

/**
 * AttributeOption.
 *
 * @ORM\Table(name="attribute_options")
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\AttributeOptionRepository")
 */
class AttributeOption
{
    public const STATUS_ONLINE = 100;
    public const STATUS_OFFLINE = -10;
    public const STATUS_DELETED = -200;
    public const PRICE_OPTIONS_DISPLAY_SEARCH = [299, 300];

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->translations = new ArrayCollection();
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
     * @ORM\ManyToOne(targetEntity="TradusBundle\Entity\Attribute", inversedBy="options")
     * @ORM\JoinColumn(name="attribute_id", referencedColumnName="id")
     */
    private $attribute;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="string", length=255, nullable=true)
     */
    private $content;

    /**
     * @var string
     *
     * @ORM\Column(name="slug", type="string", length=255, nullable=true)
     */
    private $slug;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="integer", nullable=true)
     * @Exclude
     */
    private $status;

    /**
     * @var int
     * @ORM\Column(name="sort_order", type="integer", nullable=true)
     */
    private $sortOrder;

    /**
     * @ORM\OneToMany(targetEntity="AttributeTranslation", mappedBy="option")
     * @Exclude
     */
    private $translations;

    /**
     * @var string
     *
     * @ORM\Column(name="translation_key", type="string", length=255, nullable=true)
     */
    private $translationKey;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set attribute.
     *
     * @param Attribute|null $attribute
     *
     * @return AttributeOption
     */
    public function setAttribute(?Attribute $attribute = null)
    {
        $this->attribute = $attribute;

        return $this;
    }

    /**
     * Get attribute.
     *
     * @return Attribute|null
     */
    public function getAttribute()
    {
        return $this->attribute;
    }

    /**
     * Set content.
     *
     * @param string $content
     *
     * @return AttributeOption
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
     * Set status.
     *
     * @param int $status
     *
     * @return AttributeOption
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
     * Add translation.
     *
     * @param AttributeTranslation $translation
     *
     * @return AttributeOption
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
     * @param $translationKey
     */
    public function setTranslationKey($translationKey = null)
    {
        $this->translationKey = $translationKey;
    }

    /**
     * @return string|null
     */
    public function getTranslationKey()
    {
        return $this->translationKey;
    }

    /**
     * @return string | null
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @param string $slug
     * @return AttributeOption
     */
    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'label' => $this->getContent(),
            'slug' => $this->getSlug(),
            'translationKey' => $this->getTranslationKey(),
        ];
    }
}

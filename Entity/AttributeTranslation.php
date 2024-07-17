<?php

namespace TradusBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AttributeTranslation.
 *
 * @ORM\Table(name="attribute_translations")
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\AttributeTranslationRepository")
 */
class AttributeTranslation
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="TradusBundle\Entity\Attribute", inversedBy="translations")
     * @ORM\JoinColumn(name="attribute_id", referencedColumnName="id")
     */
    private $attribute;

    /**
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @ORM\Column(name="slug", type="string", length=255, nullable=true)
     */
    private $slug;

    /**
     * @ORM\Column(name="locale", type="string", length=255, nullable=true)
     */
    private $locale;

    /**
     * @ORM\ManyToOne(targetEntity="TradusBundle\Entity\AttributeOption", inversedBy="translations")
     * @ORM\JoinColumn(name="option_id", referencedColumnName="id")
     */
    private $option;

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
     * @param string|null $name
     *
     * @return AttributeTranslation
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
     * Set slug.
     *
     * @param string|null $slug
     *
     * @return AttributeTranslation
     */
    public function setSlug($slug = null)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug.
     *
     * @return string|null
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set locale.
     *
     * @param string|null $locale
     *
     * @return AttributeTranslation
     */
    public function setLocale($locale = null)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Get locale.
     *
     * @return string|null
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set attribute.
     *
     * @param \TradusBundle\Entity\Attribute|null $attribute
     *
     * @return AttributeTranslation
     */
    public function setAttribute(\TradusBundle\Entity\Attribute $attribute = null)
    {
        $this->attribute = $attribute;

        return $this;
    }

    /**
     * Get attribute.
     *
     * @return \TradusBundle\Entity\Attribute|null
     */
    public function getAttribute()
    {
        return $this->attribute;
    }

    /**
     * Set option.
     *
     * @param \TradusBundle\Entity\AttributeOption|null $option
     *
     * @return AttributeTranslation
     */
    public function setOption(\TradusBundle\Entity\AttributeOption $option = null)
    {
        $this->option = $option;

        return $this;
    }

    /**
     * Get option.
     *
     * @return \TradusBundle\Entity\AttributeOption|null
     */
    public function getOption()
    {
        return $this->option;
    }
}

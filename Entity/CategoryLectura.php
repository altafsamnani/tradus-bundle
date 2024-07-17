<?php

namespace TradusBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CategoryLectura.
 *
 * @ORM\Table(name="category_lectura")
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\CategoryLecturaRepository")
 */
class CategoryLectura
{
    public const REDIS_NAMESPACE_LECTURA_RSS = 'lectura_rss:';
    public const REDIS_LIFE_SPAN = 21600; //6 hours
    public const LECTURA_RSS_MAX_RESULTS_PER_CATEGORY = 3;

    public const SOLR_FIELDS_CREATE_DATE = 'create_date';
    public const SOLR_FIELDS_QUERY = 'query';
    public const SOLR_FIELDS_LOCALE = 'locale';
    public const SOLR_FIELDS_TITLE = 'title';
    public const SOLR_FIELDS_DESCRIPTION = 'description';
    public const SOLR_FIELDS_LINK = 'link';
    public const SOLR_FIELDS_CATEGORY = 'category';
    public const SOLR_FIELDS_ID = 'rss_id';

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
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="categoryLectura", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id")
     * @Assert\NotBlank(message = OfferInterface::FIELD_CATEGORY_BLANK_ERROR)
     */
    private $category;

    /**
     * @var string
     *
     * @ORM\Column(name="locale", type="string", length=10, nullable=false)
     */
    private $locale;

    /**
     * @var string
     *
     * @ORM\Column(name="lectura_url", type="string", length=255, nullable=false)
     */
    private $lecturaUrl;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set category.
     *
     * @param Category|null $category
     *
     * @return Offer
     */
    public function setCategory(?Category $category = null)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category.
     *
     * @return Category|null
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @return string
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * @param string $locale
     */
    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    /**
     * @return string
     */
    public function getLecturaUrl(): string
    {
        return $this->lecturaUrl;
    }

    /**
     * @param string $lecturaUrl
     */
    public function setLecturaUrl(string $lecturaUrl): void
    {
        $this->lecturaUrl = $lecturaUrl;
    }
}

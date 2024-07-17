<?php

namespace TradusBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation\AccessorOrder;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\SerializedName;
use TradusBundle\Service\Sitecode\SitecodeService;

/**
 * Category.
 *
 * @ORM\Table(name="categories")
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\CategoryRepository")
 * @AccessorOrder("custom", custom = {"id", "name", "slug"})
 */
class Category
{
    public const CATEGORY_TRANSPORT_ID = 1;
    public const REDIS_NAMESPACE_CATEGORY_SORT = 'categories_sort:';
    public const REDIS_NAMESPACE_CATEGORY_TREE = 'categories_tree:';
    public const REDIS_NAMESPACE_CATEGORY_MAKES = 'categories_makes:';
    public const REDIS_NAMESPACE_CATEGORY_MAKES_EXPIRATION = 600; //10 min
    public const REDIS_NAMESPACE_CATEGORY_ATTRIBUTE = 'categories_attributes:';
    public const REDIS_NAMESPACE_CATEGORIES_FILTERS = 'categories_filters:';
    public const REDIS_NAMESPACE_CATEGORY_CHILDREN = 'category_children:';
    public const REDIS_NAMESPACE_CATEGORY_NAME = 'category_name:';
    public const STATUS_ONLINE = 100;
    public const STATUS_OFFLINE = -10;
    public const STATUS_DELETED = -200;
    public const CATEGORY_FIRST_LEVEL = 1;
    public const CATEGORY_DEEPEST_LEVEL = 3;
    public const CATEGORY_SORT_MIN_PERCENTAGE = 20;
    public const CATEGORY_FILTER_MIN_PERCENTAGE = 5;

    protected $defaultLocale;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->offer = new ArrayCollection();
        $this->categoryLectura = new ArrayCollection();
        $this->categoryAttributes = new ArrayCollection();
        $sitecodeService = new SitecodeService();
        $this->defaultLocale = $sitecodeService->getDefaultLocale();
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
     * @ORM\OneToMany(targetEntity="CategoryTranslation", mappedBy="category")
     * @Exclude
     */
    private $translations;

    /**
     * @ORM\OneToMany(targetEntity="Category", mappedBy="parent")
     * @SerializedName("items")
     * @Exclude
     */
    private $children;

    /**
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $parent;

    /**
     * @var string
     *
     * @ORM\Column(name="url", length=255, nullable=true)
     * @Exclude
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(name="depth", type="integer", nullable=true)
     * @Exclude
     */
    public $depth;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="integer", nullable=true)
     * @Exclude
     */
    private $status;

    /**
     * @var DateTime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $created_at;

    /**
     * @var DateTime
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private $updated_at;

    /**
     * @var int
     *
     * @ORM\Column(name="v1_id", type="integer")
     * @Exclude
     */
    private $v1_id;

    /**
     * @var int
     *
     * @ORM\Column(name="is_other_category", type="integer", nullable=true)
     * @Exclude
     */
    private $isOtherCategory;

    /**
     * @ORM\OneToMany(targetEntity="Offer", mappedBy="category")
     * @Exclude
     */
    private $offer;

    /**
     * @ORM\OneToMany(targetEntity="CategoryLectura", mappedBy="category")
     * @Exclude
     */
    private $categoryLectura;

    /**
     * @ORM\ManyToMany(targetEntity="TradusBundle\Entity\Attribute", mappedBy="categoryAttributes")
     * @ORM\JoinTable(name="category_attributes")
     */
    private $categoryAttributes;

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
     * set id.
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Set slug.
     *
     * @param string $slug
     *
     * @return Category
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
     * Set url.
     *
     * @param string $url
     *
     * @return Category
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set depth.
     *
     * @param int $depth
     *
     * @return Category
     */
    public function setDepth($depth)
    {
        $this->depth = $depth;

        return $this;
    }

    /**
     * Get depth.
     *
     * @return int
     */
    public function getDepth()
    {
        return $this->depth;
    }

    /**
     * Set status.
     *
     * @param int $status
     *
     * @return Category
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
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * @param DateTime $created_at
     * @return Category
     */
    public function setCreatedAt(DateTime $created_at): self
    {
        $this->created_at = $created_at;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * @param DateTime $updated_at
     * @return Category
     */
    public function setUpdatedAt(DateTime $updated_at): self
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    /**
     * Add child.
     *
     * @param Category $child
     *
     * @return Category
     */
    public function addChild(self $child)
    {
        $this->children[] = $child;

        return $this;
    }

    /**
     * Remove child.
     *
     * @param Category $child
     */
    public function removeChild(self $child)
    {
        $this->children->removeElement($child);
    }

    /**
     * Get children.
     *
     * @return Collection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Set parent.
     *
     * @param Category $parent
     *
     * @return Category
     */
    public function setParent(?self $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent.
     *
     * @return Category
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set v1Id.
     *
     * @param int $v1Id
     *
     * @return Category
     */
    public function setV1Id($v1Id)
    {
        $this->v1_id = $v1Id;

        return $this;
    }

    /**
     * Get v1Id.
     *
     * @return int
     */
    public function getV1Id()
    {
        return $this->v1_id;
    }

    /**
     * Set other category flag.
     *
     * @param int $isOtherCategory
     *
     * @return Category
     */
    public function setIsOtherCategory($isOtherCategory = 0)
    {
        $this->isOtherCategory = $isOtherCategory;

        return $this;
    }

    /**
     * Get other category flag.
     *
     * @return int
     */
    public function getIsOtherCategory()
    {
        return $this->isOtherCategory;
    }

    /**
     * Set locale.
     *
     * @param string|null $locale
     *
     * @return Category
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
     * @param Attribute|null $attribute
     *
     * @return Category
     */
    public function setAttribute(?Attribute $attribute = null)
    {
        if (! $this->categoryAttributes->contains($attribute)) {
            $this->categoryAttributes[] = $attribute;
        }

        return $this;
    }

    /**
     * Get attributes.
     *
     * @return Attribute|null
     */
    public function getAttributes()
    {
        return $this->categoryAttributes;
    }

    /**
     * Add translation.
     *
     * @param CategoryTranslation $translation
     *
     * @return Category
     */
    public function addTranslation(CategoryTranslation $translation)
    {
        $this->translations[] = $translation;

        return $this;
    }

    /**
     * Remove translation.
     *
     * @param CategoryTranslation $translation
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeTranslation(CategoryTranslation $translation)
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
     * Get offers.
     *
     * @return Collection
     */
    public function getOffer()
    {
        return $this->offer;
    }

    /**
     * Get CategoryLectura.
     *
     * @return Collection
     */
    public function getCategoryLectura()
    {
        return $this->categoryLectura;
    }

    public function __toString()
    {
        $trans = $this->getTranslations();
        foreach ($trans as $t) {
            if ($t->getLocale() == $this->defaultLocale) {
                return $t->getName();
            }
        }

        return 'CATEGORY WITHOUT TRANSLATION';
    }

    /**
     * @param $locales
     * @return array
     */
    public function getAllCategoryNames($locales)
    {
        $cats = [];
        foreach ($locales as $locale) {
            $cats[$locale] = $this->getCatsNames($locale);
        }

        return $cats;
    }

    /**
     * @param string $locale
     * @return array
     */
    public function getCatsArray($locale = null)
    {
        $locale = $locale ?? $this->defaultLocale;
        $cats = [];
        $category = $this;
        $depth = $category->getDepth();
        for ($i = 1; $i <= $depth; $i++) {
            $cats[$i]['id'] = $category->getId();
            $cats[$i]['label'] = $category->getNameTranslation($locale);
            $cats[$i]['slug'] = $category->getSlugTranslation($locale);
            $cats[$i]['url'] = '/'.$locale.'/search/'.$category->getSearchSlugUrl($locale, false);
            $category = $category->getParent();
        }
        $cats = array_reverse($cats);

        return $cats;
    }

    /**
     * @param string $locale
     * @return string
     */
    public function getCats($locale = null)
    {
        $locale = $locale ?? $this->defaultLocale;
        $category = $this;
        $depth = $category->getDepth();
        for ($i = 1; $i <= $depth; $i++) {
            $cats[] = $category->getSlugTranslation($locale);
            $category = $category->getParent();
        }
        $cats = array_reverse($cats);

        return implode('|', $cats);
    }

    /**
     * @param string $locale
     * @return array
     */
    public function getCatsIds($locale = null)
    {
        $locale = $locale ?? $this->defaultLocale;
        $cats = [];
        $category = $this;
        $depth = $category->getDepth();
        for ($i = 1; $i <= $depth; $i++) {
            $cats[$i] = $category->getId();

            $category = $category->getParent();
        }

        $cats = array_reverse($cats);

        return $cats;
    }

    /**
     * @param string $locale
     * @return array
     */
    public function getCatsNames($locale = null)
    {
        $locale = $locale ?? $this->defaultLocale;
        $category = $this;
        $depth = $category->getDepth();
        $cats = [];
        for ($i = 1; $i <= $depth; $i++) {
            $cats[] = $category->getNameTranslation($locale);
            $category = $category->getParent();
        }
        $cats = array_reverse($cats);

        return $cats;
    }

    /**
     * @param string $locale
     * @return array
     */
    public function getChildrenCats($locale = null)
    {
        $locale = $locale ?? $this->defaultLocale;
        $children = $this->getChildren();

        if (! count($children)) {
            return false;
        }

        foreach ($children as $child) {
            $cats[] = ['slug' => $child->getSlugTranslation($locale), 'name' => $child->getNameTranslation($locale)];
        }

        return $cats;
    }

    public function getSlugUrl($locale = null)
    {
        $locale = $locale ?? $this->defaultLocale;
        $category = $this;
        $cats = [];
        $depth = $category->getDepth();
        for ($i = 1; $i <= $depth; $i++) {
            $cats[] = $category->getSlugTranslation($locale);
            $category = $category->getParent();
        }
        $cats = array_reverse($cats);

        return implode('/', $cats);
    }

    public function getSearchSlugUrl($locale = null, $parent_start = false)
    {
        $locale = $locale ?? $this->defaultLocale;
        if ($parent_start) {
            $category = $this->getParent();
        } else {
            $category = $this;
        }

        if (! $category) {
            return;
        }

        $cats = [];
        $depth = $category->getDepth();
        for ($i = 1; $i <= $depth; $i++) {
            if ($category) {
                if ($category->getDepth() == 3) {
                    $concat = '-s'.$category->getId();
                } elseif ($category->getDepth() == 2) {
                    $concat = '-t'.$category->getId();
                } elseif ($category->getDepth() == 1) {
                    $concat = '-c'.$category->getId();
                }
                $cats[] = $category->getSlugTranslation($locale).$concat;
                $category = $category->getParent();
            }
        }
        $cats = array_reverse($cats);

        return implode('/', $cats).'/';
    }

    public function getSlugTranslation($locale = null)
    {
        $locale = $locale ?? $this->defaultLocale;
        /** @var CategoryTranslation $trans */
        $trans = $this->getTranslations();

        foreach ($trans as $translation) {
            if ($translation->getLocale() == $locale) {
                return $translation->getSlug();
            }
        }
    }

    public function getNameTranslation($locale = null)
    {
        $locale = $locale ?? $this->defaultLocale;
        $trans = $this->getTranslations();
        foreach ($trans as $translation) {
            if ($translation->getLocale() == $locale) {
                return $translation->getName();
            }
        }
    }

    /**
     * @return |null
     */
    public function getL1ParentId()
    {
        if (! $this->getParent()) {
            return;
        } elseif (! $this->getParent()->getParent()) {
            return $this->getParent();
        } else {
            return $this->getParent()->getParent();
        }
    }
}

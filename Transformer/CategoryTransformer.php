<?php

namespace TradusBundle\Transformer;

use TradusBundle\Entity\Category;
use TradusBundle\Entity\CategoryInterface;
use TradusBundle\Service\Sitecode\SitecodeService;

/**
 * Class CategoryTransformer.
 */
class CategoryTransformer implements CategoryInterface
{
    /** @var array */
    private $categories;

    /** @var string */
    private $locale;

    /** @var bool */
    private $add_count;

    /** @var int */
    private $categoryL1;

    /**
     * CategoryTransformer constructor.
     *
     * @param array $categories
     * @param string $locale
     * @param bool $add_count
     *   Whether a count is required for this transform.
     */
    public function __construct(array $categories, ?string $locale = null, $add_count = false)
    {
        $this->categories = $categories;
        $this->add_count = $add_count;
        $sitecodeService = new SitecodeService();
        $this->locale = $locale ?? $sitecodeService->getDefaultLocale();
    }

    /**
     * {@inheritdoc}
     */
    public function transform(): array
    {
        $categories = [];

        foreach ($this->categories as $category) {
            $this->categoryL1 = $category->getId();
            $this->traverseCategory($category, $categories);
        }

        return $categories;
    }

    /**
     * Recursive.
     * @param Category $category
     * @param array $categories
     */
    private function traverseCategory(Category $category, array &$categories)
    {
        $ssc = new SitecodeService();
        $id = $category->getId();
        $locale = $this->locale;
        $slug_translation = $category->getSlugTranslation($locale);
        $name_translation = $category->getNameTranslation($locale);
        $depth = $category->getDepth();
        $character = isset(self::LEVELS[$depth - 1]) ? self::LEVELS[$depth - 1] : '';
        $icon = $svgIcon = '';
        if ($depth < 3) {
            $imageName = self::ICON_BASE_URL."category-$id";
            $icon = $imageName.'.png';
            $svgIcon = $imageName.'.svg';
        }

        $categories[$id] = [
            self::ID => $id,
            self::NAME => $slug_translation,
            self::LABEL => $name_translation,
            self::SLUG => $slug_translation,
            self::SEARCH => $slug_translation.'-'.$character.$id,
            // TODO add count via SOLR this should not be queried from the database.
            self::COUNT => $this->add_count ? 0 : 0,
            self::DEPTH => $depth,
            self::CATEGORY_L1 => $this->categoryL1,
            self::ICON => $ssc->getAssetIcon($icon),
            self::SVG_ICON => $ssc->getAssetIcon($svgIcon),
            self::OTHER_CATEGORY => $category->getIsOtherCategory(),
        ];

        /** @var Category $child_category */
        foreach ($category->getChildren() as $child_category) {
            if (! isset($categories[$id][self::CHILDREN])) {
                $categories[$id][self::CHILDREN] = [];
            }
            $this->traverseCategory($child_category, $categories[$id][self::CHILDREN]);
        }
    }
}

<?php

namespace TradusBundle\Entity;

/**
 * Interface CategoryInterface.
 */
interface CategoryInterface
{
    const ID = 'id';
    const LABEL = 'label';
    const NAME = 'name';
    const SLUG = 'slug';
    const SEARCH = 'search';
    const COUNT = 'count';
    const CHILDREN = 'children';
    const DEPTH = 'depth';
    const CATEGORY_L1 = 'cat_l1';
    const ICON = 'icon';
    const SVG_ICON = 'svg_icon';
    const ICON_BASE_URL = 'https://www.tradus.com/category-assets/';
    const OTHER_CATEGORY = 'isOtherCategory';

    // TODO shouldn't these be the same thing, lets fix this sometime soon?
    const HREF = 'href';
    const URL = 'url';

    /**
     * Constants used for categories in search.
     */
    const CATEGORY_CHARACTER = 'c';
    const TYPE_CHARACTER = 't';
    const SUBTYPE_CHARACTER = 's';
    const LEVELS = [
        self::CATEGORY_CHARACTER,
        self::TYPE_CHARACTER,
        self::SUBTYPE_CHARACTER,
    ];

    /* Constant for Main Categories */
    const TRANSPORT_ID = 1;
    const FARM_ID = 50;
    const CONSTRUCTION_ID = 83;
    const SPARE_PARTS_ID = 118;
    const MATERIAL_HANDLING_EQUIPMENT_ID = 4014;
    const PROCESSING_EQUIPMENT_ID = 4244;
    const TRANSPORT_SLUG = 'transport';
    const FARM_SLUG = 'farm';
    const CONSTRUCTION_SLUG = 'construction';
    const SPARE_PARTS_SLUG = 'spare-parts';
    const MATERIAL_HANDLING_EQUIPMENT_SLUG = 'material-handling-equipment';
    const PROCESSING_EQUIPMENT_SLUG = 'processing-equipment-and-machine-tools';

    /* Constant for Others Id List */
    const OTHERS_ID_LIST = [24, 37, 47, 59, 67, 72, 75, 78, 82, 95, 106, 112, 129, 3696, 3701, 3721, 3726];

    /**
     * @return array
     */
    public function transform(): array;
}

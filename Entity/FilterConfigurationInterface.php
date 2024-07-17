<?php

namespace TradusBundle\Entity;

/**
 * Interface FilterConfigurationInterface.
 */
interface FilterConfigurationInterface
{
    public const STATUS_ACTIVE = 1;
    public const CATEGORY_DEPTH = 3;
    public const PAGE_SEARCH = 1;
    public const PAGE_HOME = 2;

    public const FILTER_TYPE_CHECKBOX = 'checkbox';
    public const FILTER_TYPE_RADIO = 'radio';
    public const FILTER_TYPE_SELECT = 'select';
    public const FILTER_TYPE_RANGE = 'range';

    /**
     * We use this name until we have the dynamic filter groups.
     * @todo Remove this when we introduce filter groups
     */
    public const FILTER_DEFAULT_GROUP = 'Default';

    /**
     * We have use this for filters-mngm-tool specify placement of the filter (left side navigation / detailed search page only).
     */
    public const SEARCH_PAGE = 'search_page';
    public const OFFER_SEARCH = 'offer_search';
    public const DETAILED_SEARCH = 'detailed_search';

    public const FILTER_FOR_ALL = '0';
    public const FILTER_FOR_OFFER_SEARCH = '1';
    public const FILTER_FOR_DETAILED_SEARCH = '2';
}

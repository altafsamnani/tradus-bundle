<?php

namespace TradusBundle\Service\Seller;

use Cocur\Slugify\Slugify;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Intl\Locale;
use Symfony\Component\Translation\Translator;
use TradusBundle\Entity\Category;
use TradusBundle\Entity\CategoryInterface;
use TradusBundle\Entity\OfferInterface;
use TradusBundle\Entity\Regions;
use TradusBundle\Entity\Seller;
use TradusBundle\Entity\SellerInterface;
use TradusBundle\Repository\CategoryRepository;
use TradusBundle\Repository\RegionsRepository;
use TradusBundle\Service\Config\ConfigService;
use TradusBundle\Service\Helper\OfferServiceHelper;
use TradusBundle\Service\Redis\RedisService;
use TradusBundle\Service\Search\Client;
use TradusBundle\Service\Search\Query;
use TradusBundle\Service\Search\Result;
use TradusBundle\Service\Sitecode\SitecodeService;
use TradusBundle\Service\Utils\PagerService;

/**
 * Class SellerSearchService.
 */
class SellerSearchService
{
    public const RANDOM_CASE_1 = 1;
    public const RANDOM_CASE_2 = 2;
    public const REQUEST_FIELD_SORT = 'sort';
    public const REQUEST_FIELD_LIMIT = 'limit';
    public const REQUEST_FIELD_QUERY = 'q';
    public const REQUEST_FIELD_QUERY_FRONTEND = 'query';
    public const REQUEST_FIELD_PAGE = 'page';
    public const REQUEST_FIELD_COUNTRY = 'country';
    public const REQUEST_FIELD_REGION = 'region';
    public const REQUEST_FIELD_CAT_L1 = 'cat_l1';
    public const REQUEST_FIELD_CAT_L2 = 'cat_l2';
    public const REQUEST_FIELD_CAT_L3 = 'cat_l3';
    public const REQUEST_FIELD_LOCALE = 'locale';
    public const REQUEST_FIELD_SELLER_SLUG = 'seller_slug';
    public const REQUEST_FIELD_SELLER_TYPES = 'seller_type';
    public const REQUEST_FIELD_DEBUG = 'debug';
    public const REQUEST_FIELD_SELLER_ID = 'id';
    public const REQUEST_FIELD_SERVICES = 'services_facet_string';

    public const REQUEST_VALUE_SORT_RELEVANCY = 'relevancy';
    public const REQUEST_VALUE_SORT_RELEVANCY_LABEL = 'Best match';
    public const REQUEST_VALUE_SORT_OFFERS_DESC = 'offer-desc';
    public const REQUEST_VALUE_SORT_OFFERS_DESC_LABEL = 'Offers Count: highest first';
    public const REQUEST_VALUE_SORT_OFFERS_ASC = 'offer-asc';
    public const REQUEST_VALUE_SORT_OFFERS_ASC_LABEL = 'Offers Count: lowest first';
    public const REQUEST_VALUE_SORT_DATE_DESC = 'date-desc';
    public const REQUEST_VALUE_SORT_DATE_DESC_LABEL = 'Date Registered: newest first';
    public const REQUEST_VALUE_SORT_DATE_ASC = 'date-asc';
    public const REQUEST_VALUE_SORT_DATE_ASC_LABEL = 'Date Registered: oldest first';

    public const REQUEST_VALUE_DEFAULT_PAGE = 1;
    public const REQUEST_VALUE_DEFAULT_SORT = self::REQUEST_VALUE_SORT_RELEVANCY;
    public const REQUEST_VALUE_DEFAULT_LIMIT = Query::DEFAULT_ROWS;
    public const REQUEST_VALUE_MAX_LIMIT = 100;

    public const DELIMITER_MULTI_VALUE = '+';
    public const DELIMITER_QUERY_TEXT = ' ';

    public const FIELD_TYPE_SIMPLE = 'simple';
    public const FIELD_TYPE_MULTIPLE = 'multiple';

    public const SEARCH_FIELDS_COUNTRY = 'country';
    public const SEARCH_FIELDS_SELLER_COUNTRY = 'seller_country';
    public const SEARCH_FIELDS_REGION = 'item_region_facet_string';
    public const SEARCH_FIELDS_CATEGORY = 'category';
    public const SEARCH_FIELDS_LOGO = 'logo';
    public const SEARCH_FIELDS_SELLER_CREATED = 'created_at';
    public const SEARCH_FIELDS_SELLER_OFFERS_COUNT = 'offers_count';
    public const SEARCH_FIELDS_TYPE = 'type';
    public const SEARCH_FIELDS_SUBTYPE = 'subtype';
    public const SEARCH_ALL_CATEGORIES = ['category', 'type', 'subtype'];
    public const SEARCH_FIELDS_QUERY = 'query';
    public const SEARCH_FIELDS_CATEGORY_MAX_COUNT_VALUE = 1000;
    public const SEARCH_FIELDS_SORT_INDEX = 'sort_index';

    public const REDIS_NAMESPACE_FACET_CATEGORIES_DATA = 'FacSellerCatData:';

    public const ALL_SORT_VALUES = [
        self::REQUEST_VALUE_SORT_RELEVANCY,
        self::REQUEST_VALUE_SORT_OFFERS_DESC,
        self::REQUEST_VALUE_SORT_OFFERS_ASC,
        self::REQUEST_VALUE_SORT_DATE_DESC,
        self::REQUEST_VALUE_SORT_DATE_ASC,
    ];

    /** @var Request $request */
    protected $request;

    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var Slugify */
    protected $slugify;

    /** @var PagerService */
    protected $pager;

    /** @var array */
    protected $params = [];

    /** @var Client */
    protected $client;

    /** @var Result */
    protected $result;

    /** @var Query */
    protected $query;

    /** @var Translator */
    protected $translator;

    /**
     * Boost all sellers except free.
     * @var float
     */
    protected $relevancyBoostSellerTypesScore;

    /**
     * Boost sellers with images.
     * @var float
     */
    protected $relevancyBoostHasImageScore;

    /**
     * Boost country score.
     * @var float
     */
    protected $relevancyBoostCountryScore;

    /**
     * List of (sellers) countries to boost in search.
     * @var array
     */
    protected $relevancyBoostCountryList;

    /**
     * Boost offers where buyer and seller match their country.
     * @var float
     */
    protected $relevancyBoostCountryMatchScore;

    /**
     * Boost new sellers.
     * @var int
     */
    protected $relevancyBoostFreshSeller;

    /**
     * Boost new sellers.
     * @var float
     */
    protected $relevancyBoostFreshSellerScore;

    /**
     * Used to calculate sort-index into a score.
     * @var float
     */
    protected $relevancyBoostTimeA;

    /**
     * Used to calculate sort-index into a score
     * Lower to give older documents less value.
     * @var float
     */
    protected $relevancyBoostTimeB;

    /**
     * Used to compare the buyer country to the seller's.
     * @var string
     */
    protected $relevancyBoostBuyerCountry;

    /**
     * Enable/Disable Solr debug output.
     * @var bool
     */
    protected $searchDebug = false;

    /** @var @var string */
    protected $defaultLocale;

    /** @var int */
    protected $sitecodeId;

    /** @var string */
    protected $sitecodeKey;

    protected $sitecodeService;

    protected $solr;

    protected $solrCores;

    /**
     * SellerSearchService constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct($options = null, ?EntityManagerInterface $entityManager = null)
    {
        $endpoint = null;
        if ($options && isset($options['seller_endpoint'])) {
            $endpoint = $options['seller_endpoint'];
        }

        $this->setClient(new Client($endpoint));
        $this->entityManager = $entityManager;
        $this->slugify = new Slugify();
        $this->loadConfiguration();

        $ssc = new SitecodeService();
        $this->sitecodeService = $ssc;
        $this->sitecodeId = $ssc->getSitecodeId();
        $this->sitecodeKey = $ssc->getSitecodeKey();
        $this->defaultLocale = $ssc->getDefaultLocale();
    }

    protected function loadConfiguration()
    {
        global $kernel;
        /** @var ConfigService $config */
        $config = $kernel->getContainer()->get('tradus.config');
        $this->translator = $kernel->getContainer()->get('translator');
        $this->pager = $kernel->getContainer()->get('tradus.pager');
        $this->solr = $kernel->getContainer()->getParameter('solr');
        $this->solrCores = $this->getSolrCores($this->solr);
        $this->relevancyBoostHasImageScore = $config->getSettingValue('relevancy.boostHasImageScore');
        $this->relevancyBoostSellerTypesScore = $config->getSettingValue('relevancy.boostSellerTypesScore');
        $this->relevancyBoostCountryScore = $config->getSettingValue('relevancy.boostCountryScore');
        $this->relevancyBoostCountryList = $config->getSettingValue('relevancy.boostCountryList');
        $this->relevancyBoostCountryMatchScore = $config->getSettingValue('relevancy.boostCountryMatchScore');
        $this->relevancyBoostFreshSeller = $config->getSettingValue('relevancy.boostFreshSeller');
        $this->relevancyBoostFreshSellerScore = $config->getSettingValue('relevancy.boostFreshSellerScore');

        $this->relevancyBoostTimeA = $config->getSettingValue('relevancy.boostTimeA');
        $this->relevancyBoostTimeB = $config->getSettingValue('relevancy.boostTimeB');
    }

    /**
     * @param Request $request
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @param string $string
     * @return string
     */
    protected function slugify($string = '')
    {
        return ! empty($string) ? $this->slugify->slugify($string) : $string;
    }

    protected function getSolrCores($solr)
    {
        $cores = [];
        foreach ($solr as $key => $core) {
            $urlParts = explode('/', $core);
            $cores[$key] = ! empty($urlParts) ? array_pop($urlParts) : '';
        }

        return $cores;
    }

    /**
     * @param string $locale
     * @param array|null $countries
     * @param int $page
     * @return array
     */
    public function getSellerSearchUrls(
        int $page,
        $locale = null,
        ?array $countries = null
    ) {
        $locale = $locale ?? $this->defaultLocale;
        $alternates = [];
        $selectedCategory = $this->getSelectedCategory();
        $searchUrl = '';
        foreach (OfferInterface::SUPPORTED_LOCALES as $supportedLocale) {
            if ($page > 1) {
                $alternates[$supportedLocale] =
                    $this->getSearchUrl($supportedLocale, $selectedCategory).'?page='.$page;
            } else {
                $alternates[$supportedLocale] =
                    $this->getSearchUrl($supportedLocale, $selectedCategory);
            }
            if ($locale == $supportedLocale) {
                $searchUrl = $this->getSearchUrl($supportedLocale, $selectedCategory);
            }
        }

        return [
            'resetFilters' => "/$locale/seller/",
            'searchUrl' => $searchUrl,
            'filterCount' => 0 + ! empty($countries)
                + $this->requestHas(self::REQUEST_FIELD_CAT_L1)
                + $this->requestHas(self::REQUEST_FIELD_CAT_L2)
                + $this->requestHas(self::REQUEST_FIELD_CAT_L3),
            'alternates' => $alternates,
        ];
    }

    public function getSelectedCountry()
    {
        $countries = [];
        if ($this->requestHas(self::REQUEST_FIELD_COUNTRY)) {
            if (! is_array($this->requestGet(self::REQUEST_FIELD_COUNTRY))) {
                $countries = explode(self::DELIMITER_MULTI_VALUE, $this->requestGet(self::REQUEST_FIELD_COUNTRY));
            } else {
                $countries = $this->requestGet(self::REQUEST_FIELD_COUNTRY);
            }
        }

        return $countries;
    }

    public function getSelectedRegion()
    {
        $regions = [];
        if ($this->requestHas(self::REQUEST_FIELD_REGION)) {
            if (! is_array($this->requestGet(self::REQUEST_FIELD_REGION))) {
                $regions = explode(self::DELIMITER_MULTI_VALUE, $this->requestGet(self::REQUEST_FIELD_REGION));
            } else {
                $regions = $this->requestGet(self::REQUEST_FIELD_REGION);
            }
        }

        return $regions;
    }

    /**
     * Function getSelectedCategory.
     * @return int | null
     */
    public function getSelectedCategory()
    {
        $catL1 = $this->requestHas(self::REQUEST_FIELD_CAT_L1) ?
            $this->requestGet(self::REQUEST_FIELD_CAT_L1) : null;
        $catL2 = $this->requestHas(self::REQUEST_FIELD_CAT_L2) ?
            $this->requestGet(self::REQUEST_FIELD_CAT_L2) : null;
        $catL3 = $this->requestHas(self::REQUEST_FIELD_CAT_L3) ?
            $this->requestGet(self::REQUEST_FIELD_CAT_L3) : null;

        if (! empty($catL3)) {
            return $catL3;
        }

        if (! empty($catL2)) {
            return $catL2;
        }

        if (! empty($catL1)) {
            return $catL1;
        }
    }

    public function getSelectedPage()
    {
        return $this->requestHas(self::REQUEST_FIELD_PAGE) ?
            intval($this->requestGet(self::REQUEST_FIELD_PAGE)) : 1;
    }

    public function getSelectedLimit()
    {
        return $this->requestHas(self::REQUEST_FIELD_LIMIT) ?
            intval($this->requestGet(self::REQUEST_FIELD_LIMIT)) : 30;
    }

    public function getSelectedLocale()
    {
        return $this->requestHas(self::REQUEST_FIELD_LOCALE) ?
            $this->requestGet(self::REQUEST_FIELD_LOCALE) : $this->defaultLocale;
    }

    public function getSearchResult()
    {
        $locale = $this->getSelectedLocale();
        $category = $this->getSelectedCategory() ? [$this->getSelectedCategory()] : null;
        $country = $this->getSelectedCountry() ?: null;
        $page = $this->getSelectedPage();
        $limit = $this->getSelectedLimit();
        $sellersRepo = $this->entityManager->getRepository('TradusBundle:Seller');
        $result = $sellersRepo->getActiveSellersWithActiveOffers($locale, $page, $limit, $category, $country);
        $result['resultCount'] = (int) $result['resultCount'];
        $result['facet'] = $this->generateFacets($locale, $result['facet']);

        return $result;
    }

    protected function generateFacets($locale = null, ?array $data = null)
    {
        $locale = $locale ?? $this->defaultLocale;

        $filters = ['country', 'category'];

        $filteredData = [];
        if (! empty($filters)) {
            foreach ($filters as $filterName) {
                if (! empty($data[$filterName])) {
                    $data[$filterName] = array_combine(
                        array_column($data[$filterName], $filterName),
                        array_column($data[$filterName], 'resultCount')
                    );
                }
                switch ($filterName) {
                    case 'country':
                        $filterArray = $data[$filterName];
                        if (! empty($filterArray)) {
                            $countriesSearch = [];
                            $selectedCountry = $this->getSelectedCountry() ?: [];
                            $countriesList = Intl::getRegionBundle()->getCountryNames($locale);
                            arsort($filterArray);
                            foreach ($filterArray as $countryI18n => $countryCount) {
                                if (! empty($countriesList[$countryI18n])) {
                                    $countriesSearch[] = [
                                        'label' => $countriesList[$countryI18n],
                                        'value' => $countryI18n,
                                        'id' => $countryI18n,
                                        'url' => '',
                                        'resetLink' => null,
                                        'extra' => null,
                                        'resultCount' => (int) $countryCount,
                                        'checked' => in_array($countryI18n, $selectedCountry),
                                    ];
                                }
                            }

                            if (! empty($selectedCountry)) {
                                $checked = array_column($countriesSearch, 'checked');
                                $counts = array_column($countriesSearch, 'resultCount');
                                array_multisort($checked, SORT_DESC, $counts, SORT_DESC, $countriesSearch);
                            }

                            $filteredData[$filterName] = [
                                'name' => $filterName,
                                'type' => 'multiple',
                                'search' => $filterName,
                                'items' => $countriesSearch,
                            ];
                        }
                        break;
                    case 'category':
                        // todo as moving the seller data to solr, below code is temporary
                        $categoryRepo = $this->entityManager->getRepository('TradusBundle:Category');
                        $catL1 = $this->requestHas(self::REQUEST_FIELD_CAT_L1) ?
                            $this->requestGet(self::REQUEST_FIELD_CAT_L1) : null;
                        $catL2 = $this->requestHas(self::REQUEST_FIELD_CAT_L2) ?
                            $this->requestGet(self::REQUEST_FIELD_CAT_L2) : null;
                        $catL3 = $this->requestHas(self::REQUEST_FIELD_CAT_L3) ?
                            $this->requestGet(self::REQUEST_FIELD_CAT_L3) : null;
                        $allL1Categories = $categoryRepo->getL1Categories();

                        $L1CategoryData = [];
                        $L2CategoryData = [];
                        $L3CategoryData = [];
                        $selectedL1 = [];
                        $selectedL2 = [];
                        $selectedL3 = [];
                        $allL2Categories = [];
                        $allL3Categories = [];
                        $allResultCounts = $data[$filterName];
                        $allCatIds = [];

                        foreach ($allL1Categories as $item) {
                            $allResultCounts[$item->getId()] = ! empty($allResultCounts[$item->getId()]) ?
                                (int) $allResultCounts[$item->getId()] : 0;
                            $checked = ! empty($catL1) && $item->getId() == $catL1;
                            if ($checked || empty($catL1)) {
                                $allCatIds['L1'][$item->getId()] = $categoryRepo->getAllChildrenIds($item);
                            }
                            $facetData = [
                                'label' => $item->getNameTranslation($locale),
                                'value' => $item->getId(),
                                'id' => $item->getId(),
                                'url' => $this->getSearchUrl($locale, $item->getId()),
                                'resetLink' => $this->getSearchUrl($locale),
                                'extra' => null,
                                'resultCount' => &$allResultCounts[$item->getId()],
                                'checked' => $checked,
                            ];
                            if ($checked) {
                                $selectedL1 = $facetData;
                                $allL2Categories = $item->getChildren();
                            }
                            $L1CategoryData[] = $facetData;
                        }

                        foreach ($allL2Categories as $item) {
                            $checked = ! empty($catL2) && $item->getId() == $catL2;
                            if ($checked || empty($catL2)) {
                                $allCatIds['L2'][$item->getId()] = $categoryRepo->getAllChildrenIds($item);
                            }
                            $allResultCounts[$item->getId()] = ! empty($allResultCounts[$item->getId()]) ?
                                (int) $allResultCounts[$item->getId()] : 0;
                            $facetData = [
                                'label' => $item->getNameTranslation($locale),
                                'value' => $item->getId(),
                                'id' => $item->getId(),
                                'url' => $this->getSearchUrl($locale, $item->getId()),
                                'resetLink' => $this->getSearchUrl($locale, $catL1),
                                'extra' => null,
                                'resultCount' => &$allResultCounts[$item->getId()],
                                'checked' => $checked,
                            ];
                            if ($checked) {
                                $selectedL2 = $facetData;
                                $allL3Categories = $item->getChildren();
                            }
                            $L2CategoryData[] = $facetData;
                        }

                        foreach ($allL3Categories as $item) {
                            $checked = ! empty($catL3) && $item->getId() == $catL3;
                            $allResultCounts[$item->getId()] = ! empty($allResultCounts[$item->getId()]) ?
                                (int) $allResultCounts[$item->getId()] : 0;
                            $facetData = [
                                'label' => $item->getNameTranslation($locale),
                                'value' => $item->getId(),
                                'id' => $item->getId(),
                                'url' => $this->getSearchUrl($locale, $item->getId()),
                                'resetLink' => $this->getSearchUrl($locale, $catL2),
                                'extra' => null,
                                'resultCount' => &$allResultCounts[$item->getId()],
                                'checked' => $checked,
                            ];
                            if ($checked) {
                                $selectedL3 = $facetData;
                            }
                            $L3CategoryData[] = $facetData;
                        }

                        foreach ($allCatIds as $catIds) {
                            foreach ($catIds as $parent => $cats) {
                                foreach ($cats as $cat) {
                                    if (! empty($allResultCounts[$cat])) {
                                        $allResultCounts[$parent] += $allResultCounts[$cat];
                                    }
                                }
                            }
                        }

                        $filteredData[$filterName] = empty($L1CategoryData) ? [] : [
                            'name' => $filterName,
                            'selectedOption' => &$selectedL1,
                            'type' => 'simple',
                            'search' => 'cat_l1',
                            'items' => &$L1CategoryData,
                        ];

                        $filterName = 'type';
                        $filteredData[$filterName] = empty($L2CategoryData) ? [] : [
                            'name' => $filterName,
                            'selectedOption' => &$selectedL2,
                            'type' => 'simple',
                            'search' => 'cat_l2',
                            'items' => &$L2CategoryData,
                        ];

                        $filterName = 'subtype';
                        $filteredData[$filterName] = empty($L3CategoryData) ? [] : [
                            'name' => $filterName,
                            'selectedOption' => &$selectedL3,
                            'type' => 'simple',
                            'search' => 'cat_l3',
                            'items' => &$L3CategoryData,
                        ];
                        break;
                }
            }
        }

        return $filteredData;
    }

    /**
     * @param string $locale
     * @param array|null $category
     * @return array
     */
    public function getRandomSellers($locale = null, ?array $result = null)
    {
        $locale = $locale ?? $this->defaultLocale;
        $response = [];
        if ($this->requestGet('random') == self::RANDOM_CASE_2) {
            $response['category_id'] = $this->getSelectedCategory();
            $response['category_url'] = $this->getSearchUrl($locale, $this->getSelectedCategory());
        }
        $response['sellers'] = array_map(function ($seller) {
            return $this->transformRandomSellers($seller);
        }, $result['response']['docs']);

        return $response;
    }

    /**
     * @param string $locale
     * @param array $seller
     * @return array
     */
    protected function getRandomSellerSlug(string $locale, array $seller)
    {
        return array_merge($seller, [
            'id' => (int) $seller['id'],
            'url' => Seller::getSellerProfileUrl($locale, $seller['slug']),
            'logo' => ! empty($seller['logo']) ? $seller['logo'].SellerInterface::IMAGE_SIZE_SMALL : $seller['logo'],
            'geo_location' => ! empty($seller['geo_location']) ?
                json_decode($seller['geo_location'], true, JSON_UNESCAPED_SLASHES) : $seller['geo_location'],
        ]);
    }

    /**
     * @param string $locale
     * @param int|bool $categoryId
     * @return string
     */
    public function getSearchUrl($locale = null, $categoryId = false)
    {
        $locale = $locale ?? $this->defaultLocale;
        $basePath = $this->getSearchBaseUrl($locale);
        $categoryPath = $this->getCategoryPath($locale, $categoryId);
        $countryPath = $this->getCountryPath($locale);

        return "{$basePath}{$categoryPath}{$countryPath}";
    }

    /**
     * @param string $locale
     * @param bool $categoryId
     * @return mixed|string
     */
    public function getCategoryPath($locale = null, $categoryId = false)
    {
        $locale = $locale ?? $this->defaultLocale;
        static $redis = false;

        if (! $redis) {
            $redis = new RedisService('CatPath:');
        }

        $categoryPath = '';

        if (! $categoryId) {
            $categoryId = $this->getSelectedCategory();
        }

        if (! empty($categoryId)) {
            $myKey = $locale.':'.(int) $categoryId;
            $categoryPath = $redis->getParameter($myKey);

            if (empty($categoryPath)) {
                $categoryPath = '';
                $categoryRepo = $this->entityManager->getRepository('TradusBundle:Category');
                $category = $categoryRepo->find($categoryId);
                if ($category) {
                    $categoryPath = $category->getSearchSlugUrl($locale);
                }

                if (! empty($categoryPath)) {
                    $redis->setParameter($myKey, $categoryPath);
                }
            }
        }

        return $categoryPath;
    }

    /**
     * @param $locale
     * @return string
     */
    public function getCountryPath($locale)
    {
        // COUNTRY PATH
        $countryPath = '';
        if ($this->requestHas(self::REQUEST_FIELD_COUNTRY)) {
            $country = $this->getSelectedCountry();
            $countries_search = [];

            Locale::setDefault($locale);
            $countries = Intl::getRegionBundle()->getCountryNames();

            foreach ($country as $shortCode) {
                if ($shortCode == 'EN') {
                    continue;
                }
                if ($shortCode == 'DA') {
                    $shortCode = 'DK';
                } // Fix Denmark

                if (isset($countries[$shortCode])) {
                    $countries_search[] = strtolower($this->slugify($countries[$shortCode]));
                }
            }
            if (count($countries_search) >= 1) {
                $location_str = implode('+', $countries_search);
                $countryPath = OfferServiceHelper::localizedLocation($locale).$location_str.'/';
            }
        }

        return $countryPath;
    }

    /**
     * @param string $locale
     * @return string
     */
    public function getSearchBaseUrl($locale = null)
    {
        $locale = $locale ?? $this->defaultLocale;
        // BASE PATH
        $basePath = 'seller';
        if ($this->requestHas(self::REQUEST_FIELD_SELLER_SLUG)) {
            $basePath = 's/'.$this->requestGet(self::REQUEST_FIELD_SELLER_SLUG);
        }

        return "/{$locale}/{$basePath}/";
    }

    /**
     * @param $key
     * @return bool
     */
    protected function requestHas($key)
    {
        if (! $this->request) {
            return false;
        }

        return $this->request->query->has($key)
            && (! empty($this->request->query->get($key))
                || $this->request->query->get($key) === 0);
    }

    /**
     * @param string $key
     * @return mixed|string
     */
    public function requestFetch(string $key)
    {
        $paramValue = $this->request->query->get($key);
        if (is_string($paramValue)) {
            $paramValue = trim($paramValue);
        }

        return $paramValue;
    }

    /**
     * @param $key
     * @return mixed|string
     */
    protected function requestGet($key)
    {
        $paramValue = $this->requestFetch($key);
        if (! empty($paramValue)) {
            $this->addParam($key, $paramValue);
        }

        return $paramValue;
    }

    /**
     * @param $key
     * @param $value
     * @param bool $overwrite
     */
    public function addParam($key, $value, $overwrite = true)
    {
        if ($value !== null) {
            if (! $overwrite && isset($this->params[$key])) {
                if (! is_array($this->params[$key])) {
                    $this->params[$key] = [$this->params[$key]];
                }
                $this->params[$key][] = $value;
            } else {
                $this->params[$key] = $value;
            }
        }
    }

    /**
     * Usefull when you do a new search.
     */
    public function resetParams()
    {
        $this->params = [];
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param string $paramName
     *
     * @return bool|mixed
     */
    public function getParam(string $paramName)
    {
        if (isset($this->params[$paramName])) {
            return $this->params[$paramName];
        }

        return false;
    }

    /**
     * Function evalSelectedSort.
     * @param array $arraySorts
     * @return array
     */
    public function evalSelectedSort(array $arraySorts): array
    {
        $isSelected = false;
        foreach ($arraySorts as $sort) {
            if ($sort['selected']) {
                $isSelected = true;
            }
        }
        if (! $isSelected) {
            $arraySorts[0]['selected'] = true;
        }

        return $arraySorts;
    }

    /**
     * @return int
     */
    public function getFilterCount()
    {
        return 0 + ! empty($this->getSelectedCountry())
            + ! empty($this->getSelectedRegion())
            + $this->requestHas(self::REQUEST_FIELD_CAT_L1)
            + $this->requestHas(self::REQUEST_FIELD_CAT_L2)
            + $this->requestHas(self::REQUEST_FIELD_CAT_L3);
    }

    /**
     * @param string $locale
     *
     * @return array
     */
    public function getFacetCountriesData($locale = null)
    {
        $locale = $locale ?? $this->defaultLocale;
        Locale::setDefault($locale);
        $countries = Intl::getRegionBundle()->getCountryNames();
        $facetLookup = [];
        $translatedLocation = $this->translator->trans('location');
        foreach ($countries as $shortCode => $country) {
            $facetLookup[$shortCode] = [
                'name' => $country,
                'id' => $shortCode,
                'search_url' => '/'.$locale.'/seller/'.$translatedLocation.'-'.$this->slugify($country).'/',
                'reset_link' => null,
            ];
        }

        return $facetLookup;
    }

    /**
     * @todo Add this to Redis so we do not do a query every time
     *
     * @param string $locale
     *
     * @return array
     */
    public function getFacetRegionsData($locale = null)
    {
        /** @var RegionsRepository $regionsRepo */
        $regionsRepo = $this->entityManager->getRepository('TradusBundle:Regions');
        $regions = $regionsRepo->findBy(['locale' => $locale]);
        $facetLookup = [];
        /** @var Regions $region */
        foreach ($regions as $region) {
            $facetLookup[$region->getSlug()] = [
                'name' => $region->getName(),
                'id' => $region->getSlug(),
                'search_url' => null,
                'reset_link' => null,
            ];
        }

        return $facetLookup;
    }

    /**
     * Will find facet values for current search without query on given field.
     *
     * @param array $facetValues
     * @param string $field
     *
     * @return mixed
     */
    public function mergeFacetValuesForNotSelectedValues(array $facetValues, string $field)
    {
        $query = clone $this->query;
        $query->setRows(0)->disableStats()->clearStatsFields()->clearFacetFields()->addFacetFields($field);
        $query->replaceRawQueryField($field, '*');

        $searchResult = $this->client->execute($query);

        $additionalFacetData = $searchResult->getFacetFields();
        if (isset($additionalFacetData[$field]) && count($additionalFacetData[$field])) {
            // Do a foreach because array_merge reorders the results to alphabetic
            foreach ($additionalFacetData[$field] as $key => $value) {
                if (isset($facetValues[$key]) && $facetValues[$key] == 0) {
                    unset($facetValues[$key]);
                }
                $facetValues[$key] = $value;
            }
        }

        return $facetValues;
    }

    private function buildFacet($id, string $value, array $facetLookup, $facetCount = 0, $checked = false): array
    {
        return [
            'label' => isset($facetLookup[$id]['name']) ? $facetLookup[$id]['name'] : '',
            'value' => $value,
            'id' => $id,
            'url' => isset($facetLookup[$id]['search_url']) ? $facetLookup[$id]['search_url'] : '',
            'resetLink' => @$facetLookup[$id]['reset_link'],
            'extra' => @$facetLookup[$id]['extra'],
            'resultCount' => $facetCount,
            'checked' => $checked,
        ];
    }

    /**
     * Customized Facet Rendering, Transport at the Top & Others always at the bottom.
     *
     * @param string $facetName
     * @param array  $items
     * @param array  $item
     * @param array  $otherFacetList
     * @param mixed  $transportFacet
     * @param mixed  $isOtherCategory
     *
     * @return void
     */
    private function addFacetItem(
        string $facetName,
        array &$items,
        array $item,
        array &$otherFacetList,
        &$transportFacet = false,
        $isOtherCategory = false
    ) {
        switch (true) {
            case $facetName == self::SEARCH_FIELDS_CATEGORY &&
                $item['id'] == CategoryInterface::TRANSPORT_ID:
                $transportFacet = $item;

                break;
            case in_array($facetName, self::SEARCH_ALL_CATEGORIES) && $isOtherCategory:
                $otherFacetList[] = $item;

                break;
            default:
                $items[] = $item;
        }
    }

    /**
     * @param       $facetName
     * @param       $selected
     * @param       $facetLookup
     * @param       $facetValues
     * @param array $aFacetCategories
     * @param null  $source
     * @param null  $type
     *
     * @return array
     */
    public function transformFacetData(
        $facetName,
        $selected,
        $facetLookup,
        $facetValues,
        $aFacetCategories = [],
        $source = null,
        $type = null
    ) {
        $selectedData = [];
        $selectedOption = null;

        if ($selected) {
            if (! is_array($selected)) {
                $selectedData = explode(self::DELIMITER_MULTI_VALUE, $selected);
            } else {
                $selectedData = $selected;
            }

            if (! empty($selectedData) && $facetName == self::SEARCH_FIELDS_CATEGORY
                || $facetName == self::SEARCH_FIELDS_TYPE
                || $facetName == self::SEARCH_FIELDS_SUBTYPE) {
                $selectedOption = $this->buildFacet($selectedData[0], $selectedData[0], $facetLookup, 0, true);
            }
        }

        $result = [
            'name' => $facetName,
            'selectedOption' => $selectedOption,
            'type' => $type,
            'search' => $source,
            'items' => [],
        ];

        $selectedItem = false;
        $transportFacet = false;
        $otherFacetList = [];
        foreach ($facetValues as $facetId => $facetCount) {
            if (isset($facetLookup[$facetId]) && $facetCount > 0) {
                $value = $facetLookup[$facetId]['id'];
                $item = $this->buildFacet($facetId, $value, $facetLookup, $facetCount);
                if (! empty($selectedData)) {
                    foreach ($selectedData as $selectedValue) {
                        if (strtolower($selectedValue) == strtolower($value)) {
                            $item['checked'] = true;
                            if ($facetName == self::SEARCH_FIELDS_CATEGORY || $facetName == self::SEARCH_FIELDS_TYPE
                                || $facetName == self::SEARCH_FIELDS_SUBTYPE) {
                                $result['selectedOption'] = $item;
                                $selectedItem = $selectedValue;
                            }
                        }
                    }
                }

                if ($facetName != self::SEARCH_FIELDS_CATEGORY && $facetName != self::SEARCH_FIELDS_TYPE
                    && $facetName != self::SEARCH_FIELDS_SUBTYPE) {
                    $this->addFacetItem($facetName, $result['items'], $item, $otherFacetList);
                }
            }
        }

        if ($facetName == self::SEARCH_FIELDS_CATEGORY || $facetName == self::SEARCH_FIELDS_TYPE
            || $facetName == self::SEARCH_FIELDS_SUBTYPE) {
            $parentId = 0;
            if ($facetName == self::SEARCH_FIELDS_TYPE) {
                $parentId = $this->requestGet(self::REQUEST_FIELD_CAT_L1);
            } elseif ($facetName == self::SEARCH_FIELDS_SUBTYPE) {
                $parentId = $this->requestGet(self::REQUEST_FIELD_CAT_L2);
            }

            foreach ($aFacetCategories as $facetCatId => $facetCatCount) {
                if (isset($facetLookup[$facetCatId]) && $facetCatCount > 0) {
                    if ($facetName == self::SEARCH_FIELDS_CATEGORY
                        || (($facetLookup[$facetCatId]['parent_category'] == $parentId)
                            && isset($facetValues[$parentId])
                            && ($facetValues[$parentId] > 0)
                        )
                    ) {
                        $value = $facetLookup[$facetCatId]['id'];
                        $isOtherCategory = isset($facetLookup[$facetCatId]['is_other_category']) ?
                            $facetLookup[$facetCatId]['is_other_category'] : false;

                        $aFacetCount = isset($facetValues[$facetCatId]) ? $facetValues[$facetCatId] : 0;
                        $item = $this->buildFacet($facetCatId, $value, $facetLookup, $aFacetCount);
                        $item['checked'] = ($selectedItem == $facetCatId) ? true : false;
                        $this->addFacetItem(
                            $facetName,
                            $result['items'],
                            $item,
                            $otherFacetList,
                            $transportFacet,
                            $isOtherCategory
                        );
                    }
                }
            }
            /* Transport facet always at the top */
            if ($transportFacet) {
                array_unshift($result['items'], $transportFacet);
            }
        }

        /* Other facet always at the bottom */
        if (! empty($otherFacetList)) {
            $result['items'] = array_merge($result['items'], $otherFacetList);
        }

        return $result;
    }

    /**
     * Get sidewide search Facetdata.
     *
     * @return Result
     */
    public function getCategoryFacetDataSideWide()
    {
        $query = $this->client->getQuerySelect();
        $query->setRows(0)->addSort(SellerInterface::SOLR_FIELD_SELLER_OFFERS_COUNT);
        $query->enableFacet()->addFacetField(self::SEARCH_FIELDS_CATEGORY);
        $query->setFacetLimit(self::SEARCH_FIELDS_CATEGORY, self::SEARCH_FIELDS_CATEGORY_MAX_COUNT_VALUE);
        $this->result = $this->client->execute($query);

        return $this->result;
    }

    public function getQueryPath()
    {
        $queryPath = '';
        if ($this->requestHas(self::REQUEST_FIELD_QUERY) && ! empty($this->requestGet(self::REQUEST_FIELD_QUERY))) {
            $queryPath = 'q/'.strtolower($this->slugify($this->requestGet(self::REQUEST_FIELD_QUERY))).'/';
        }

        return $queryPath;
    }

    /**
     * @param string $locale
     * @param string $cat
     *
     * @return array
     */
    public function getFacetCategoriesData($locale = null, $cat = 'l1')
    {
        $locale = $locale ?? $this->defaultLocale;
        $categories = [];
        $urlParameters = $this->getSearchUrlParametersString([
            self::REQUEST_FIELD_PAGE,
            self::REQUEST_FIELD_QUERY,
        ]);
        if ($urlParameters) {
            $urlParameters = '?'.$urlParameters;
        }

        $myKey = $locale.':'.$cat;
        $redis = new RedisService(self::REDIS_NAMESPACE_FACET_CATEGORIES_DATA);
        $ret = $redis->getParameter($myKey);
        if (! empty($ret)) {
            $ret = unserialize($ret);
            if (! empty($ret)) {
                $basePath = $this->getSearchBaseUrl($locale);
                $queryPath = $this->getQueryPath();
                $countryPath = $this->getCountryPath($locale);
                $prefixPath = "{$basePath}{$queryPath}";
                $sufixPath = "{$countryPath}{$urlParameters}";
                $result = [];
                foreach ($ret as $k => $v) {
                    //$v['search_url'] = "{$prefixPath}".$this->getCategoryPath($locale, $v['id'])."{$sufixPath}";
                    $v['search_url'] = "{$prefixPath}".$v['search_url']."{$sufixPath}";
                    $v['reset_link'] = "{$prefixPath}".$v['reset_link']."{$sufixPath}";
                    $result[$k] = $v;
                }

                return $result;
            }
        }

        /** @var CategoryRepository $categoryRepo */
        $categoryRepo = $this->entityManager->getRepository('TradusBundle:Category');

        if ($cat == 'l1') {
            $categories = $categoryRepo->getL1Categories();
        } elseif ($cat == 'l2') {
            $categories = $categoryRepo->getL2Categories();
        } elseif ($cat == 'l3') {
            $categories = $categoryRepo->getL3Categories();
        }

        $result = [];
        $cache = [];
        $ret = [];
        if ($categories) {

            /** @var Category $category */
            foreach ($categories as $category) {
                $urlParameters = $this->getSearchUrlParametersString([
                    self::REQUEST_FIELD_PAGE,
                    self::REQUEST_FIELD_QUERY,
                ]);
                if ($urlParameters) {
                    $urlParameters = '?'.$urlParameters;
                }

                $ret['id'] = $category->getId();
                $ret['name'] = $category->getNameTranslation($locale);
                $ret['search_url'] = $this->getSearchUrl($locale, $category->getId()).$urlParameters;
                $ret['is_other_category'] = $category->getIsOtherCategory();
                $catPath = $this->getCategoryPath($locale, $category->getId());

                $parentCategory = $category->getParent();
                if ($parentCategory) {
                    $ret['parent_category'] = $parentCategory->getId();
                    $ret['reset_link'] = $this->getSearchUrl($locale, $parentCategory->getId()).$urlParameters;
                    $parentPath = $this->getCategoryPath($locale, $parentCategory->getId());
                } else {
                    $ret['parent_category'] = 0;
                    $ret['reset_link'] = $this->getSearchUrl($locale, 99999999).$urlParameters;
                    $parentPath = $this->getCategoryPath($locale, 99999999);
                }
                $result[$category->getId()] = $ret;
                $ret['search_url'] = $catPath;
                $ret['reset_link'] = $parentPath;
                $cache[$category->getId()] = $ret;
            }
        }

        $redis->setParameter($myKey, serialize($cache));

        return $result;
    }

    /**
     * @return array
     */
    public function getFacetDataResults()
    {
        $result = [];
        $locale = $this->request->query->get('locale') ?: $this->defaultLocale;
        $facetFields = $this->result->getFacetFields();

        foreach ($facetFields as $facetName => $facetValues) {
            if ($facetName == self::SEARCH_FIELDS_COUNTRY) {
                $facetName = self::REQUEST_FIELD_COUNTRY;
                $facetLookup = $this->getFacetCountriesData($locale);
                // Get the additional values if the search was done without  country
                if ($this->requestHas(self::REQUEST_FIELD_COUNTRY)) {
                    $facetValues = $this->mergeFacetValuesForNotSelectedValues(
                        $facetValues,
                        self::SEARCH_FIELDS_COUNTRY
                    );
                }

                $selected = $this->requestGet(self::REQUEST_FIELD_COUNTRY);
                $result[$facetName] = $this->transformFacetData(
                    $facetName,
                    $selected,
                    $facetLookup,
                    $facetValues,
                    [],
                    $facetName,
                    self::FIELD_TYPE_MULTIPLE
                );
            }

            /* Copy paste from country and transformed to region */
            if ($facetName == self::SEARCH_FIELDS_REGION) {
                $facetName = self::REQUEST_FIELD_REGION;
                $facetLookup = $this->getFacetRegionsData($this->defaultLocale);
                // Get the additional values if the search was done without region
                if ($this->requestHas(self::REQUEST_FIELD_REGION)) {
                    $facetValues = $this->mergeFacetValuesForNotSelectedValues(
                        $facetValues,
                        self::SEARCH_FIELDS_REGION
                    );
                }

                $selected = $this->requestGet(self::REQUEST_FIELD_REGION);
                $result[$facetName] = $this->transformFacetData(
                    $facetName,
                    $selected,
                    $facetLookup,
                    $facetValues,
                    [],
                    $facetName,
                    self::FIELD_TYPE_MULTIPLE
                );
            }

            if ($facetName == 'category') {
                $facetCategories = $this->getCategoryFacetDataSideWide();
                $aFacetCategories = $facetCategories->getFacetField('category');

                $selected1 = $this->requestGet(self::REQUEST_FIELD_CAT_L1);

                $facetLookup = $this->getFacetCategoriesData($locale, 'l1');

                $result[$facetName] = $this->transformFacetData(
                    $facetName,
                    $selected1,
                    $facetLookup,
                    $facetValues,
                    $aFacetCategories,
                    self::REQUEST_FIELD_CAT_L1,
                    self::FIELD_TYPE_SIMPLE
                );

                $facetName = 'type';
                $result[$facetName] = [];
                if (! empty($selected1)) {
                    $selected2 = $this->requestGet(self::REQUEST_FIELD_CAT_L2);
                    $facetLookup = $this->getFacetCategoriesData($locale, 'l2');
                    $result[$facetName] = $this->transformFacetData(
                        $facetName,
                        $selected2,
                        $facetLookup,
                        $facetValues,
                        $aFacetCategories,
                        self::REQUEST_FIELD_CAT_L2,
                        self::FIELD_TYPE_SIMPLE
                    );
                }

                $facetName = 'subtype';
                $result[$facetName] = [];
                if (! empty($selected2)) {
                    $selected3 = $this->requestGet(self::REQUEST_FIELD_CAT_L3);
                    $facetLookup = $this->getFacetCategoriesData($locale, 'l3');
                    $result[$facetName] = $this->transformFacetData(
                        $facetName,
                        $selected3,
                        $facetLookup,
                        $facetValues,
                        $aFacetCategories,
                        self::REQUEST_FIELD_CAT_L3,
                        self::FIELD_TYPE_SIMPLE
                    );
                }
            }
        }

        return $result;
    }

    /**
     * @param array $seller
     * @return array
     */
    protected function transformSellers(array $seller)
    {
        $largeLogo = $smallLogo = 'https://www.tradus.com/assets/'.$this->sitecodeKey.'/offer-result/transport.png';
        if (! empty($seller['logo'])) {
            $largeLogo = $seller['logo'];
            $smallLogo = $seller['logo'].SellerInterface::IMAGE_SIZE_SMALL;
        }

        if (isset($seller['url'])) {
            $locale = $this->getSelectedLocale();
            $filterPath = $this->getCategoryPath($locale).$this->getCountryPath($locale);
            if ($this->requestHas(self::REQUEST_FIELD_REGION)) {
                $filterPath .= '?'.http_build_query(
                    [self::REQUEST_FIELD_REGION => $this->requestGet(self::REQUEST_FIELD_REGION)]
                );
            }
            $seller['url'] = '/'.$this->getSelectedLocale().$seller['url'].$filterPath;
        }

        if (! empty($seller['geo_location'])) {
            $seller['geo_location_object'] = json_decode($seller['geo_location'], true);
        }

        return array_merge($seller, [
            'id' => (int) $seller['seller_id'],
            'logo' => [
                'large' => $largeLogo,
                'small' => $smallLogo,
            ],
        ]);
    }

    /**
     * @param array $seller
     * @return array
     */
    protected function transformRandomSellers(array $seller)
    {
        $smallLogo = $url = '';
        if (! empty($seller['logo'])) {
            $smallLogo = $seller['logo'].SellerInterface::IMAGE_SIZE_SMALL;
        }

        if (isset($seller['url'])) {
            $url = '/'.$this->getSelectedLocale().$seller['url'];
        }

        return [
            'id' => (int) $seller['seller_id'],
            'name' => $seller['name'] ?? $seller['company_name'] ?? null,
            'slug' => $seller['slug'] ?? null,
            'logo' => $smallLogo,
            'geo_location' => $seller['geo_location'] ?? null,
            'url' => $url,
        ];
    }

    public function getOfferSellers(Request $request)
    {
        $offerClient = new Client($this->solr['endpoint']);
        $query = $offerClient->getQuerySelect();
        $query = $this->createOfferQueryFromRequest($query, $request);
        $query->addQuery('site_facet_m_int', $this->sitecodeId);
        $query->setRows(0);
        $query->enableFacet()->setFacetFields(['seller_id']);
        $query->setFacetLimit('seller_id', -1);
        $query->setFacetMincount('seller_id', 1);
        $query->setFacetSort('seller_id', 'count');
        $sellersCore = $this->solrCores['seller_endpoint'] ?: 'tradus_sellers';

        $query->setFQ("{!join from=id to=seller_id fromIndex=$sellersCore}seller_has_image_facet_int:1");
        $ret = $offerClient->execute($query);
        $sellersWithLogo = $ret->getFacetField('seller_id');

        $query->setFQ("{!join from=id to=seller_id fromIndex=$sellersCore}seller_has_image_facet_int:0");
        $ret = $offerClient->execute($query);
        $sellersWithoutLogo = $ret->getFacetField('seller_id');

        return $sellersWithLogo + $sellersWithoutLogo;
    }

    public function getSolrSellerResult(Request $request, $overrideLimit = false)
    {
        $locale = $this->getSelectedLocale();
        $searchLimit = self::REQUEST_VALUE_DEFAULT_LIMIT;

        $sellers = $this->getOfferSellers($request);
        $sellerPages = array_chunk($sellers, $searchLimit, true);

        $page = $start = 0;
        if ($this->requestHas(self::REQUEST_FIELD_PAGE)
            && $this->requestGet(self::REQUEST_FIELD_PAGE) > self::REQUEST_VALUE_DEFAULT_PAGE) {
            $page = ($this->requestGet(self::REQUEST_FIELD_PAGE) - 1);
            if (empty($sellerPages[$page])) {
                $page = 0;
            }
            $start = $page * $searchLimit;
        }

        $currPageSellers = $sellerPages[$page];
        $sellerIds = array_keys($currPageSellers);

        $query = $this->client->getQuerySelect();
        $this->query = $this->createQueryFromRequest($query, $request, $overrideLimit);
        $this->query->addQuery('id', $sellerIds);
        $this->query->setStart(0);
        $searchResult = $this->execute($this->query);
        $sellerResult = $this->getTradusResult($searchResult);
        foreach ($sellerResult['response']['docs'] as $doc) {
            $doc['offers_count'] = $currPageSellers[$doc['id']];
            $currPageSellers[$doc['id']] = $doc;
        }
        $sellersDoc = [];
        foreach ($currPageSellers as $seller) {
            if (! empty($seller['seller_id'])) {
                $sellersDoc[] = $seller;
            }
        }

        $query = $this->client->getQuerySelect();
        $this->query = $this->createQueryFromRequest($query, $request, $overrideLimit);
        $tradusCore = $this->solrCores['endpoint'] ?: 'tradus';
        $query->setFQ("{!join from=seller_id to=id fromIndex=$tradusCore}site_facet_m_int:".$this->sitecodeId.' AND seller_site_facet_m_int:'.$this->sitecodeId);
        $searchResult = $this->execute($this->query);
        $result = $this->getTradusResult($searchResult);
        $result['seller_ids'] = isset($sellerResult['seller_ids']) ? $sellerResult['seller_ids'] : '';
        $result['response']['start'] = $start;
        $result['response']['numFound'] = $result['result_count'];
        $result['response']['docs'] = $sellersDoc;

        $result['sellers'] = array_map(function ($sellerDoc) {
            return $this->transformSellers($sellerDoc);
        }, $result['response']['docs']);
        $result['query'] = $request->query->get('q');
        $result['searchQuery'] = $this->getSearchQueryText($locale);
        $sortsOptions = $this->getResultSorts();
        $sortedOptions = array_values($sortsOptions);
        $result['sorts'] = $this->evalSelectedSort($sortedOptions);
        $result['filterOptions'] = [
            'filterCount' => $this->getFilterCount(),
            'resetFilters' => $this->getSearchBaseUrl($locale),
        ];

        // Filters
        $result['filters'] = [];

        // PAGINATION
        $page += 1;
        $alternateSearchParameters = null;
        $searchUrlParameters = $this->getSearchUrlParametersString(
            [self::REQUEST_FIELD_PAGE, self::REQUEST_FIELD_QUERY]
        );

        if ($searchUrlParameters) {
            $alternateSearchParameters = $searchUrlParameters;
            $searchUrlParameters = '&'.$searchUrlParameters;
            $searchUrlParameters = str_replace('%', '%%', $searchUrlParameters);
        }

        $searchUrl = $this->getSearchUrl($locale).'?'.'page=%s'.$searchUrlParameters;
        $result['pager'] = $this->pager->generatePager($page, $searchUrl, $result['result_count']);

        // alternates links
        $alternates = [];
        foreach (OfferInterface::SUPPORTED_LOCALES as $supportedLocale) {
            if ($alternateSearchParameters) {
                if ($page > 1) {
                    $alternates[$supportedLocale] =
                        $this->getSearchUrl($supportedLocale).'?page='.$page.'&'.$alternateSearchParameters;
                } else {
                    $alternates[$supportedLocale] =
                        $this->getSearchUrl($supportedLocale).'?'.$alternateSearchParameters;
                }
            } else {
                if ($page > 1) {
                    $alternates[$supportedLocale] =
                        $this->getSearchUrl($supportedLocale).'?page='.$page;
                } else {
                    $alternates[$supportedLocale] =
                        $this->getSearchUrl($supportedLocale);
                }
            }
        }
        $result['alternates'] = $alternates;
        //FACETS
        $result['facet'] = $this->getFacetDataResults();

        $result['seo_no_follow'] = false;

        if (! empty($result['query'])) {
            $searchQuery = $this->slugify($result['query']);

            $category = $this->entityManager->getRepository('TradusBundle:CategoryTranslation')
                ->findBy(['slug' => $searchQuery]);
            if ($category) {
                $result['seo_no_follow'] = true;
            }
        }

        //REMOVE DATA THAT IS CURRENTLY NOT NEEDED:
        unset($result['response']);
        unset($result['facet_counts']);

        return $result;
    }

    public function getSolrSearchResult(Request $request, $overrideLimit = false)
    {
        $locale = $this->getSelectedLocale();
        $query = $this->client->getQuerySelect();
        $this->query = $this->createQueryFromRequest($query, $request, $overrideLimit);
        $searchResult = $this->execute($this->query);
        $result = $this->getTradusResult($searchResult);
        if ($this->requestHas('random')) {
            return $this->getRandomSellers($locale, $result);
        }
        $result['sellers'] = array_map(function ($seller) {
            return $this->transformSellers($seller);
        }, $result['response']['docs']);
        $result['query'] = $request->query->get('q');
        $result['searchQuery'] = $this->getSearchQueryText($locale);
        $sortsOptions = $this->getResultSorts();
        $sortedOptions = array_values($sortsOptions);
        $result['sorts'] = $this->evalSelectedSort($sortedOptions);
        $result['filterOptions'] = [
            'filterCount' => $this->getFilterCount(),
            'resetFilters' => $this->getSearchBaseUrl($locale),
        ];

        // Filters
        $result['filters'] = [];

        // PAGINATION
        $page = $request->query->get('page') ?: 1;
        $alternateSearchParameters = null;
        $searchUrlParameters = $this->getSearchUrlParametersString(
            [self::REQUEST_FIELD_PAGE, self::REQUEST_FIELD_QUERY]
        );

        if ($searchUrlParameters) {
            $alternateSearchParameters = $searchUrlParameters;
            $searchUrlParameters = '&'.$searchUrlParameters;
            $searchUrlParameters = str_replace('%', '%%', $searchUrlParameters);
        }

        $searchUrl = $this->getSearchUrl($locale).'?'.'page=%s'.$searchUrlParameters;
        $result['pager'] = $this->pager->generatePager($page, $searchUrl, $searchResult->getNumberFound());

        // alternates links
        $alternates = [];
        foreach (OfferInterface::SUPPORTED_LOCALES as $supportedLocale) {
            if ($alternateSearchParameters) {
                if ($page > 1) {
                    $alternates[$supportedLocale] =
                        $this->getSearchUrl($supportedLocale).'?page='.$page.'&'.$alternateSearchParameters;
                } else {
                    $alternates[$supportedLocale] =
                        $this->getSearchUrl($supportedLocale).'?'.$alternateSearchParameters;
                }
            } else {
                if ($page > 1) {
                    $alternates[$supportedLocale] =
                        $this->getSearchUrl($supportedLocale).'?page='.$page;
                } else {
                    $alternates[$supportedLocale] =
                        $this->getSearchUrl($supportedLocale);
                }
            }
        }
        $result['alternates'] = $alternates;
        //FACETS
        $result['facet'] = $this->getFacetDataResults();

        $result['seo_no_follow'] = false;

        if (! empty($result['query'])) {
            $searchQuery = $this->slugify($result['query']);

            $category = $this->entityManager->getRepository('TradusBundle:CategoryTranslation')
                ->findBy(['slug' => $searchQuery]);
            if ($category) {
                $result['seo_no_follow'] = true;
            }
        }

        //REMOVE DATA THAT IS CURRENTLY NOT NEEDED:
        unset($result['response']);
        unset($result['facet_counts']);

        return $result;
    }

    /**
     * @param array $excludeParams
     *
     * @return string
     */
    public function getSearchUrlParametersString($excludeParams = [])
    {
        $parameterForInUrl = [
            self::REQUEST_FIELD_PAGE,
            self::REQUEST_FIELD_QUERY, // FRONT_END USES ANOTHER FIELD
            self::REQUEST_FIELD_SORT,
            self::REQUEST_FIELD_REGION,
        ];

        $urlParameters = [];
        foreach ($parameterForInUrl as $parameterName) {
            $parameterValue = $this->getParam($parameterName);
            if ($parameterValue && ! in_array($parameterName, $excludeParams)) {
                // Exclude defaults
                if ($parameterName == self::REQUEST_FIELD_PAGE && $parameterValue == self::REQUEST_VALUE_DEFAULT_PAGE) {
                    continue;
                }

                if ($parameterName == self::REQUEST_FIELD_SORT && $parameterValue == self::REQUEST_VALUE_DEFAULT_SORT) {
                    continue;
                }

                if ($parameterName == self::REQUEST_FIELD_QUERY) {
                    $parameterName = self::REQUEST_FIELD_QUERY_FRONTEND; // FE uses another parameter
                }

                if (! empty($parameterValue) || $parameterValue === 0) {
                    $urlParameters[$parameterName] = $parameterValue;
                }
            }
        }

        return http_build_query($urlParameters);
    }

    /**
     * Function buildSortEntry.
     *
     * @param string $key
     * @param string $label
     * @param bool   $selected
     * @param string $value
     *
     * @return array
     */
    public function buildSortEntry(string $key, string $label, bool $selected, string $value): array
    {
        // tradus-front the radio-filter.html.twig has a condition to only show
        // the sorting options on mobile screens if this condition is true
        // {% if option.resultCount > 0 %}
        // so we set resultCount = 1 to pass that condition but it has no other meaning

        return [
            'key' => $key,
            'label' => $label,
            'selected' => $selected,
            'value' => $value,
            'resultCount' => 1,
        ];
    }

    /**
     * @return array
     */
    public function getResultSorts()
    {
        $sort = $this->getParam(self::REQUEST_FIELD_SORT);
        if (empty($sort)) {
            $sort = $this->sitecodeService->getSitecodeParameter('default_sort');
        }
        $urlParameters = $this->getSearchUrlParametersString(
            [self::REQUEST_FIELD_SORT, self::REQUEST_FIELD_PAGE]
        );
        if ($urlParameters) {
            $urlParameters = '&'.$urlParameters;
        }

        $keysForOrdering = array_flip(self::ALL_SORT_VALUES);

        return [
            $keysForOrdering[self::REQUEST_VALUE_SORT_RELEVANCY] => $this->buildSortEntry(
                self::REQUEST_VALUE_SORT_RELEVANCY,
                $this->translator->trans(self::REQUEST_VALUE_SORT_RELEVANCY_LABEL),
                $sort == self::REQUEST_VALUE_SORT_RELEVANCY ?: false,
                self::REQUEST_VALUE_SORT_RELEVANCY.$urlParameters
            ),
            $keysForOrdering[self::REQUEST_VALUE_SORT_OFFERS_DESC] => $this->buildSortEntry(
                self::REQUEST_VALUE_SORT_OFFERS_DESC,
                $this->translator->trans(self::REQUEST_VALUE_SORT_OFFERS_DESC_LABEL),
                $sort == self::REQUEST_VALUE_SORT_OFFERS_DESC ?: false,
                self::REQUEST_VALUE_SORT_OFFERS_DESC.$urlParameters
            ),
            $keysForOrdering[self::REQUEST_VALUE_SORT_OFFERS_ASC] => $this->buildSortEntry(
                self::REQUEST_VALUE_SORT_OFFERS_ASC,
                $this->translator->trans(self::REQUEST_VALUE_SORT_OFFERS_ASC_LABEL),
                $sort == self::REQUEST_VALUE_SORT_OFFERS_ASC ?: false,
                self::REQUEST_VALUE_SORT_OFFERS_ASC.$urlParameters
            ),
            $keysForOrdering[self::REQUEST_VALUE_SORT_DATE_DESC] => $this->buildSortEntry(
                self::REQUEST_VALUE_SORT_DATE_DESC,
                $this->translator->trans(self::REQUEST_VALUE_SORT_DATE_DESC_LABEL),
                $sort == self::REQUEST_VALUE_SORT_DATE_DESC ?: false,
                self::REQUEST_VALUE_SORT_DATE_DESC.$urlParameters
            ),
            $keysForOrdering[self::REQUEST_VALUE_SORT_DATE_ASC] => $this->buildSortEntry(
                self::REQUEST_VALUE_SORT_DATE_ASC,
                $this->translator->trans(self::REQUEST_VALUE_SORT_DATE_ASC_LABEL),
                $sort == self::REQUEST_VALUE_SORT_DATE_ASC ?: false,
                self::REQUEST_VALUE_SORT_DATE_ASC.$urlParameters
            ),
        ];
    }

    /**
     * @param string $locale
     *
     * @return string
     */
    public function getSearchQueryText($locale = null)
    {
        $locale = $locale ?? $this->defaultLocale;
        $categoryRepo = $this->entityManager->getRepository('TradusBundle:Category');
        $searchQueryText = '';
        $this->translator->setLocale($locale);

        if ($this->requestHas(self::REQUEST_FIELD_QUERY)) {
            $searchQueryText .= $this->requestGet(self::REQUEST_FIELD_QUERY);
        }

        if ($this->requestHas(self::REQUEST_FIELD_CAT_L1)) {
            if (($category = $categoryRepo->find($this->requestGet(self::REQUEST_FIELD_CAT_L1)))) {
                if ($searchQueryText != '') {
                    $searchQueryText .= ', ';
                }
                $searchQueryText .= $category->getNameTranslation($locale);
            }
        }

        if ($this->requestHas(self::REQUEST_FIELD_CAT_L2)) {
            if (($category = $categoryRepo->find($this->requestGet(self::REQUEST_FIELD_CAT_L2)))) {
                if ($searchQueryText != '') {
                    $searchQueryText .= ', ';
                }
                $searchQueryText .= $category->getNameTranslation($locale);
            }
        }
        if ($this->requestHas(self::REQUEST_FIELD_CAT_L3)) {
            if (($category = $categoryRepo->find($this->requestGet(self::REQUEST_FIELD_CAT_L3)))) {
                if ($searchQueryText != '') {
                    $searchQueryText .= ', ';
                }
                $searchQueryText .= $category->getNameTranslation($locale);
            }
        }

        if ($this->requestHas(self::REQUEST_FIELD_COUNTRY)) {
            $searchQueryText .= implode(', ', $this->getSelectedCountry());
        }

        if ($searchQueryText == '') {
            $searchQueryText = $this->translator->trans('All categories');
        }

        return $searchQueryText;
    }

    /**
     * @param $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * Set sorting based on Sort String.
     *
     * @param string $sort
     */
    public function setQuerySort(string $sort)
    {
        switch ($sort) {
            case self::REQUEST_VALUE_SORT_DATE_DESC:
                $this->query->addSort(self::SEARCH_FIELDS_SELLER_CREATED, Query::SORT_DESC);
                break;
            case self::REQUEST_VALUE_SORT_DATE_ASC:
                $this->query->addSort(self::SEARCH_FIELDS_SELLER_CREATED, Query::SORT_ASC);
                break;
            case self::REQUEST_VALUE_SORT_OFFERS_ASC:
                $this->query->addSort(self::SEARCH_FIELDS_SELLER_OFFERS_COUNT, Query::SORT_ASC);
                break;
            case self::REQUEST_VALUE_SORT_OFFERS_DESC:
                $this->query->addSort(self::SEARCH_FIELDS_SELLER_OFFERS_COUNT, Query::SORT_DESC);
                break;

            case self::REQUEST_VALUE_SORT_RELEVANCY:
            default:
                $this->setRelevancy();
                break;
        }
    }

    protected function setRelevancy()
    {
        $this->query->addSort(SellerInterface::SOLR_FIELD_SELLER_HAS_IMAGE_FACET_INT, Query::SORT_DESC);

        /*
         * Add random case query
         */
        if ($this->requestHas('random')) {
            $searchSort = mt_rand().'_random';
            $this->query->addSort($searchSort, Query::SORT_DESC);
            $this->query->addQuery(SellerInterface::SOLR_FIELD_SELLER_HAS_IMAGE_FACET_INT, 1);

            return;
        }
        $this->query->addSort(self::SEARCH_FIELDS_SELLER_OFFERS_COUNT, Query::SORT_DESC);

        $this->query->enableEdismax();

        /*
         * Default Boost seller types excluding free sellers (so free sellers get lower in the results)
         */
        if ($this->relevancyBoostSellerTypesScore > 0) {
            $this->query->addRawEdismaxBoostQuery(
                SellerInterface::SOLR_FIELD_SELLER_TYPE,
                '[1 TO *]',
                $this->relevancyBoostSellerTypesScore
            );
        }

        /*
         * Boost seller with country on list
         */
        if ($this->relevancyBoostCountryScore > 0 && count($this->relevancyBoostCountryList)) {
            $this->query->addRawEdismaxBoostQuery(
                SellerInterface::SOLR_FIELD_SELLER_COUNTRY,
                $this->relevancyBoostCountryList,
                $this->relevancyBoostCountryScore
            );
        }

        /*
         * Boost sellers that match with buyer country
         */
        if ($this->relevancyBoostBuyerCountry && $this->relevancyBoostBuyerCountry != '-') {
            $this->query->addRawEdismaxBoostQuery(
                SellerInterface::SOLR_FIELD_SELLER_COUNTRY,
                $this->relevancyBoostBuyerCountry,
                $this->relevancyBoostCountryMatchScore
            );
        }

        /**
         * Freshness of the seller, based on substracting the factor to current date.
         */
        $startDateTime = new DateTime();
        $interval = '-'.$this->relevancyBoostFreshSeller;
        $startDateTime->modify($interval);
        $this->query->addRawEdismaxBoostQuery(
            SellerInterface::SOLR_FIELD_SELLER_CREATED_AT,
            '['.$startDateTime->format('Y-m-d\TH:i:s\Z').' TO *]',
            $this->relevancyBoostFreshSellerScore
        );
    }

    /**
     * @return array|mixed
     */
    public function getParsedSearchQuery()
    {
        $searchQuery = $this->requestGet(self::REQUEST_FIELD_QUERY);
        $searchQuery = str_replace('-', self::DELIMITER_QUERY_TEXT, $searchQuery);
        if (! is_array($searchQuery)) {
            $searchQuery = explode(self::DELIMITER_QUERY_TEXT, $searchQuery);
        }

        return $searchQuery;
    }

    /**
     * @param Query   $query
     * @param Request $request
     *
     * @return Query
     */
    public function createQueryFromRequest(Query $query, Request $request, $overrideLimit = false)
    {
        $this->resetParams();
        $this->query = $query;
        $this->request = $request;

        /*
         * Enable/Disble debug information
         */
        if ($this->searchDebug === true || $this->requestHas(self::REQUEST_FIELD_DEBUG)) {
            $this->query->enableDebug();
        }

        /**
         * Set the sorting.
         */
        $searchSort = $this->sitecodeService->getSitecodeParameter('default_sort');
        if ($this->requestHas(self::REQUEST_FIELD_SORT)) {
            $searchSort = $this->requestGet(self::REQUEST_FIELD_SORT);
            // TODO: remove the following when it's removed from front-end
            if ($searchSort == 'sort_index-desc') {
                $searchSort = self::REQUEST_VALUE_SORT_RELEVANCY;
                $this->params[self::REQUEST_FIELD_SORT] = $searchSort;
            }
        }
        $this->setQuerySort($searchSort);

        /**
         * What is the LIMIT of number of results.
         */
        $searchLimit = self::REQUEST_VALUE_DEFAULT_LIMIT;
        if ($this->requestHas(self::REQUEST_FIELD_LIMIT)) {
            $searchLimit = $this->requestGet(self::REQUEST_FIELD_LIMIT);

            // Limit the maximum results to retrieve for performance.
            if ($searchLimit > self::REQUEST_VALUE_MAX_LIMIT && ! $overrideLimit) {
                $searchLimit = self::REQUEST_VALUE_MAX_LIMIT;
            }
            $this->query->setRows($searchLimit);
        }

        /*
         * Add the PAGE NUMBER to the search
         */
        if ($this->requestHas(self::REQUEST_FIELD_PAGE)
            && $this->requestGet(self::REQUEST_FIELD_PAGE) > self::REQUEST_VALUE_DEFAULT_PAGE) {
            $start = ($this->requestGet(self::REQUEST_FIELD_PAGE) - 1) * $searchLimit;
            $this->query->setStart($start);
        }

        if ($this->requestHas('random')) {
            $this->query->addRawQuery(self::SEARCH_FIELDS_LOGO, '[* TO *]');
        }

        /*
         * Add CATEGORY to the search
         */
        if ($this->requestHas(self::REQUEST_FIELD_CAT_L1)) {
            $this->query->addQuery(self::SEARCH_FIELDS_CATEGORY, $this->requestGet(self::REQUEST_FIELD_CAT_L1));
        }

        /*
         * Add CATEGORY TYPE to the search
         */
        if ($this->requestHas(self::REQUEST_FIELD_CAT_L2)) {
            $this->query->addQuery(self::SEARCH_FIELDS_CATEGORY, $this->requestGet(self::REQUEST_FIELD_CAT_L2));
        }

        /*
         * Add CATEGORY SUBTYPE to the search
         */
        if ($this->requestHas(self::REQUEST_FIELD_CAT_L3)) {
            $this->query->addQuery(self::SEARCH_FIELDS_CATEGORY, $this->requestGet(self::REQUEST_FIELD_CAT_L3));
        }

        /*
         * Add QUERY to the search
         */
        if ($this->requestHas(self::REQUEST_FIELD_QUERY)) {
            $this->query->addRawQuery(
                self::SEARCH_FIELDS_QUERY,
                $this->getParsedSearchQuery(),
                Query::OPERATOR_AND,
                Query::OPERATOR_AND
            );
        }

        /* END: We are adding the dynamic filters here for the search */

        /*
         * Add Attribute COUNTRY to the search
         */
        if ($this->requestHas(self::REQUEST_FIELD_COUNTRY)) {
            if (! is_array($this->requestGet(self::REQUEST_FIELD_COUNTRY))) {
                $country = explode(self::DELIMITER_MULTI_VALUE, $this->requestGet(self::REQUEST_FIELD_COUNTRY));
            } else {
                $country = $this->requestGet(self::REQUEST_FIELD_COUNTRY);
            }
            $this->query->addQuery(SellerInterface::SOLR_FIELD_SELLER_COUNTRY, $country);
        }

        /*
         * Add REGION to the search
         */
        if ($this->requestHas(self::REQUEST_FIELD_REGION)) {
            if (! is_array($this->requestGet(self::REQUEST_FIELD_REGION))) {
                $region = explode(self::DELIMITER_MULTI_VALUE, $this->requestGet(self::REQUEST_FIELD_REGION));
            } else {
                $region = $this->requestGet(self::REQUEST_FIELD_REGION);
            }
            $this->query->addQuery(self::SEARCH_FIELDS_REGION, $region);
        }

        /*
         * Add filter on seller types
         */
        if ($this->requestHas(self::REQUEST_FIELD_SELLER_TYPES)) {
            $this->query->addQuery(
                SellerInterface::SOLR_FIELD_SELLER_TYPE,
                $this->requestGet(self::REQUEST_FIELD_SELLER_TYPES)
            );
        }

        // default facets fields
        $facetFieldsArray = [
            self::SEARCH_FIELDS_CATEGORY,
            self::SEARCH_FIELDS_COUNTRY,
            self::SEARCH_FIELDS_REGION,
        ];

        // default stats fields
        $statsFieldsArray = [];

        /* Enable facet data retrieval in search */
        $this->query->enableFacet()->setFacetFields($facetFieldsArray);

        /* Enable stats data retrieval in search */
        $this->query->enableStats()->setStatsFields($statsFieldsArray);

        return $this->query;
    }

    /**
     * @param Query   $query
     * @param Request $request
     *
     * @return Query
     */
    public function createOfferQueryFromRequest(Query $query, Request $request, $overrideLimit = false)
    {
        $this->resetParams();
        $this->query = $query;
        $this->request = $request;

        /*
         * Enable/Disble debug information
         */
        if ($this->searchDebug === true || $this->requestHas(self::REQUEST_FIELD_DEBUG)) {
            $this->query->enableDebug();
        }

        /*
         * Add CATEGORY to the search
         */
        if ($this->requestHas(self::REQUEST_FIELD_CAT_L1)) {
            $this->query->addQuery(self::SEARCH_FIELDS_CATEGORY, $this->requestGet(self::REQUEST_FIELD_CAT_L1));
        }

        /*
         * Add CATEGORY TYPE to the search
         */
        if ($this->requestHas(self::REQUEST_FIELD_CAT_L2)) {
            $this->query->addQuery(self::SEARCH_FIELDS_CATEGORY, $this->requestGet(self::REQUEST_FIELD_CAT_L2));
        }

        /*
         * Add CATEGORY SUBTYPE to the search
         */
        if ($this->requestHas(self::REQUEST_FIELD_CAT_L3)) {
            $this->query->addQuery(self::SEARCH_FIELDS_CATEGORY, $this->requestGet(self::REQUEST_FIELD_CAT_L3));
        }

        /*
         * Add QUERY to the search
         */
        if ($this->requestHas(self::REQUEST_FIELD_QUERY)) {
            $this->query->addRawQuery(
                self::SEARCH_FIELDS_QUERY,
                $this->getParsedSearchQuery(),
                Query::OPERATOR_AND,
                Query::OPERATOR_AND
            );
        }

        /* END: We are adding the dynamic filters here for the search */

        /*
         * Add Attribute COUNTRY to the search
         */
        if ($this->requestHas(self::REQUEST_FIELD_COUNTRY)) {
            if (! is_array($this->requestGet(self::REQUEST_FIELD_COUNTRY))) {
                $country = explode(self::DELIMITER_MULTI_VALUE, $this->requestGet(self::REQUEST_FIELD_COUNTRY));
            } else {
                $country = $this->requestGet(self::REQUEST_FIELD_COUNTRY);
            }
            $this->query->addQuery(self::SEARCH_FIELDS_SELLER_COUNTRY, $country);
        }

        /*
         * Add REGION to the search
         */
        if ($this->requestHas(self::REQUEST_FIELD_REGION)) {
            if (! is_array($this->requestGet(self::REQUEST_FIELD_REGION))) {
                $region = explode(self::DELIMITER_MULTI_VALUE, $this->requestGet(self::REQUEST_FIELD_REGION));
            } else {
                $region = $this->requestGet(self::REQUEST_FIELD_REGION);
            }
            $this->query->addQuery(self::SEARCH_FIELDS_REGION, $region);
        }

        // default facets fields
        $facetFieldsArray = [
            'seller_id',
        ];

        /* Enable facet data retrieval in search */
        $this->query->enableFacet()->setFacetFields($facetFieldsArray);

        return $this->query;
    }

    /**
     * @param Query $query
     *
     * @return Result
     */
    public function execute(?Query $query = null)
    {
        if ($query) {
            $this->query = $query;
        }

        //if doesnt contain the sitecode then add it...
        $currentQuery = $this->query->getQuery();
        $eval = explode('site_facet_m_int', $currentQuery);

        if (count($eval) <= 1) {
            $this->query->addQuery('site_facet_m_int', $this->sitecodeId);
        }
        //end whitelabel

        $this->result = $this->client->execute($this->query);

        return $this->result;
    }

    /**
     * Make the result compatible with current application expects.
     *
     * @param Result $result
     *
     * @return array
     */
    public function getTradusResult(Result $result)
    {
        $data['result_count'] = $result->getNumberFound();

        if (count($result->getDocuments())) {
            foreach ($result->getDocuments() as $doc) {
                $data['seller_ids'][] = $doc['seller_id'];
            }
        }
        if (isset($data['facet_counts'])) {
            $data['facet'] = $data['facet_counts'];
        }

        $data = array_merge($data, $result->getData());

        return $data;
    }

    /**
     * Function getSolrSearchByProximity.
     * @param float $latitude
     * @param float $longitude
     * @param float $radius
     * @return Result
     */
    public function getSolrSearchByProximity(
        float $latitude,
        float $longitude,
        float $radius,
        ?int $category = null,
        ?array $country = null,
        ?string $city = null,
        ?string $limit = null
    ) {
        $this->query = $this->client->getQuerySelect();
        $this->resetParams();
        $this->query->addQuery('site_facet_m_int', $this->sitecodeId);

        if ($category) {
            $this->query->addQuery(Seller::SOLR_FIELD_SELLER_CATEGORY, $category);
        }

        if ($country) {
            $this->query->addQuery(Seller::SOLR_FIELD_SELLER_COUNTRY, $country);
        }

        if ($city) {
            $this->query->addQuery(Seller::SOLR_FIELD_SELLER_CITY, $city);
        }

        $this->query->setOption('fq', '{!geofilt pt='.$latitude.','.$longitude.' sfield=latlon d='.$radius.'}');
        $this->query->setOption('fl', '*,_dist_:geodist(latlon,'.$latitude.','.$longitude.')');

        if ($limit) {
            $this->query->setRows($limit);
        }

        $this->query->addSort('geodist(latlon,'.$latitude.','.$longitude.')', Query::SORT_ASC);

        /** Enable facet data retrieval in search */
        $facetFieldsArray = [
            self::SEARCH_FIELDS_CATEGORY,
            self::SEARCH_FIELDS_COUNTRY,
        ];
        $this->query->enableFacet()->setFacetFields($facetFieldsArray);

        $this->result = $this->client->execute($this->query);

        return [
            'results_count' => $this->result->getNumberFound(),
            'documents' => $this->result->getDocuments(),
            'facets' => $this->getFacetDataResults(),
            'filterOptions' => [
                'filterCount' => $this->getFilterCount(),
            ],
        ];
    }

    /**
     * Function getAdditionalServicesFromIds.
     * @param array $sellersIds
     * @return array
     */
    public function getAdditionalServicesFromIds(array $sellersIds, $locale = ''): array
    {
        global $kernel;
        $this->translator = $kernel->getContainer()->get('translator');
        $locale = $locale ?: $this->defaultLocale;
        $this->translator->setLocale($locale);

        $mappedServices = [];
        if ($sellersIds) {
            $this->query = $this->client->getQuerySelect();
            $this->query->addRawQuery(self::REQUEST_FIELD_SELLER_ID, '('.implode(' ', $sellersIds).')');
            $this->result = $this->execute($this->query);
            $results = $this->getTradusResult($this->result);
            /*foreach ($results['response']['docs'] as $doc) {
                $serviceTranslate = [];
                $sellerAdditionalServices = json_decode($doc[self::REQUEST_FIELD_SERVICES], true);
                if (! empty($sellerAdditionalServices)) {
                    foreach ($sellerAdditionalServices as $service) {
                        $service['title'] = $this->translator->trans($service['title']);
                        $service['description'] = $this->translator->trans($service['description']);
                        $serviceTranslate[] = $service;
                    }
                }
                $mappedServices[$doc[self::REQUEST_FIELD_SELLER_ID]] = $serviceTranslate;
            } */
            foreach($results['response']['docs'] as $doc) {
                try {
                    // $mappedServices[$doc[self::REQUEST_FIELD_SELLER_ID]] =
                    json_decode($doc[self::REQUEST_FIELD_SERVICES], true);
                } catch (\Exception $exception) {
                    dump($exception->getMessage(), $doc);exit;
                }
            }
        }

        return $mappedServices;
    }
}

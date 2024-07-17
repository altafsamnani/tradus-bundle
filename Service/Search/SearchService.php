<?php

namespace TradusBundle\Service\Search;

use Cocur\Slugify\Slugify;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Locale;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Intl\Intl;
use TradusBundle\Entity\Attribute;
use TradusBundle\Entity\Category;
use TradusBundle\Entity\CategoryInterface;
use TradusBundle\Entity\CategoryLectura;
use TradusBundle\Entity\FilterConfigurationInterface;
use TradusBundle\Entity\Make;
use TradusBundle\Entity\Model;
use TradusBundle\Entity\Offer;
use TradusBundle\Entity\PriceAnalysisType;
use TradusBundle\Entity\Seller;
use TradusBundle\Entity\SellerInterface;
use TradusBundle\Entity\Sitecodes;
use TradusBundle\Entity\Version;
use TradusBundle\Repository\CategoryRepository;
use TradusBundle\Repository\FilterConfigurationRepository;
use TradusBundle\Repository\MakeRepository;
use TradusBundle\Repository\PriceAnalysisTypeRepository;
use TradusBundle\Service\Brand\BrandService;
use TradusBundle\Service\Config\ConfigService;
use TradusBundle\Service\Helper\OfferServiceHelper;
use TradusBundle\Service\Offer\OfferService;
use TradusBundle\Service\Redis\RedisService;
use TradusBundle\Service\Sitecode\SitecodeService;
use TradusBundle\Utils\CurrencyExchange\CurrencyExchange;
use TradusBundle\Utils\CurrencyExchange\CurrencyExchangeException;
use TradusBundle\Utils\MysqlHelper\MysqlHelper;

/**
 * Class SearchService.
 */
class SearchService
{
    public const SEARCH_FIELDS_CREATE_DATE = 'create_date';
    public const SEARCH_FIELDS_SORT_INDEX = 'sort_index';
    public const SEARCH_FIELDS_CATEGORY = 'category';

    public const SEARCH_FIELDS_PRICE_WL = '_price_facet_double';
    public const SEARCH_FIELDS_GROSS_PRICE_WL = '_gross_price_facet_double';
    public const SEARCH_FIELDS_CURRENCY_WL = '_currency_facet_string';
    public const SEARCH_FIELDS_PRICE_ANALYSIS_TYPE_WL = '_price_analysis_type_facet_string';

    public const SEARCH_FIELDS_PRICE = 'price';
    public const SEARCH_FIELDS_THUMBNAIL = 'thumbnail';
    public const SEARCH_FIELDS_MAKE = 'make';
    public const SEARCH_FIELDS_MAKE_ID = 'make_id_facet_int';
    public const SEARCH_FIELDS_MODEL = 'model_facet_string';
    public const SEARCH_FIELDS_VERSION = 'version_facet_string';
    public const SEARCH_FIELDS_SELLER_TYPE = 'seller_type';
    public const SEARCH_FIELDS_TITLE = 'title_en';
    public const SEARCH_FIELDS_QUERY = 'query';
    public const SEARCH_FIELDS_COUNTRY = 'seller_country';
    public const SEARCH_FIELDS_REGION = 'item_region_facet_string';
    public const SEARCH_SELLER_ID = 'seller_id';
    public const SEARCH_FIELDS_OFFER_ID = 'offer_id';
    public const SEARCH_FIELDS_IMAGE_COUNT = 'images_count_facet_int';
    public const SEARCH_FIELDS_PRICE_TYPE = 'price_type';
    public const SEARCH_FIELDS_CATEGORY_MAX_COUNT_VALUE = 1000;
    public const SEARCH_FIELDS_SELLER_CREATED = 'seller_created_at';
    public const SEARCH_FIELDS_SELLER_OFFERS_COUNT = 'seller_offers_count';
    public const SEARCH_FIELDS_TYPE = 'type';
    public const SEARCH_FIELDS_SUBTYPE = 'subtype';
    public const SEARCH_ALL_CATEGORIES = ['category', 'type', 'subtype'];

    public const SEARCH_FIELDS_MILEAGE = 'mileage_facet_double';
    public const SEARCH_FIELDS_MILEAGE_STRING = 'mileage_facet_string';
    public const SEARCH_FIELDS_WEIGHT = 'weight_facet_double';
    public const SEARCH_FIELDS_HOURS_RUN = 'hours_run_facet_double';
    public const SEARCH_FIELDS_YEAR = 'year';
    public const SEARCH_FIELDS_WEIGHT_NET = 'weight_net_facet_double';
    public const SEARCH_FIELDS_MILEAGE_ASC = 'mileage_sort_asc_facet_double';
    public const SEARCH_FIELDS_WEIGHT_ASC = 'weight_sort_asc_facet_double';
    public const SEARCH_FIELDS_WEIGHT_NET_ASC = 'weight_net_sort_asc_facet_double';
    public const SEARCH_FIELDS_HOURS_RUN_ASC = 'hours_run_sort_asc_facet_double';
    public const SEARCH_FIELDS_YEAR_ASC = 'year_sort_asc_facet_double';
    public const SEARCH_FIELDS_PRICE_ANALYSIS_TYPE = 'price_analysis_type_facet_string';
    public const SEARCH_FIELDS_IMAGE_DUPLICATED = 'image_duplicate_type_facet_int';
    public const SEARCH_FIELDS_TRANSMISSION = 'transmission_facet_string';
    public const SEARCH_FIELDS_SELLER_HAS_LEAD = 'seller_lead_last_month_facet_int';

    public const REQUEST_FIELD_SORT = 'sort';
    public const REQUEST_FIELD_LIMIT = 'limit';
    public const REQUEST_FIELD_QUERY = 'q';
    public const REQUEST_FIELD_QUERY_FRONTEND = 'query';
    public const REQUEST_FIELD_PAGE = 'page';
    public const REQUEST_FIELD_MAKE = 'make';
    public const REQUEST_FIELD_COUNTRY = 'country';
    public const REQUEST_FIELD_REGION = 'region';
    public const REQUEST_FIELD_CATEGORY_ID = 'category_id';
    public const REQUEST_FIELD_CAT_L1 = 'cat_l1';
    public const REQUEST_FIELD_CAT_L2 = 'cat_l2';
    public const REQUEST_FIELD_CAT_L3 = 'cat_l3';
    public const REQUEST_FIELD_PRICE_FROM = 'price_from';
    public const REQUEST_FIELD_CURRENCY = 'currency';
    public const REQUEST_FIELD_PRICE_TO = 'price_to';
    public const REQUEST_FIELD_MILEAGE_FROM = 'mileage_from';
    public const REQUEST_FIELD_MILEAGE_TO = 'mileage_to';
    public const REQUEST_FIELD_WEIGHT_FROM = 'weight_from';
    public const REQUEST_FIELD_WEIGHT_TO = 'weight_to';
    public const REQUEST_FIELD_WEIGHT_NET_FROM = 'weight_net_from';
    public const REQUEST_FIELD_WEIGHT_NET_TO = 'weight_net_to';
    public const REQUEST_FIELD_YEAR_FROM = 'year_from';
    public const REQUEST_FIELD_YEAR_TO = 'year_to';
    public const REQUEST_DYNAMIC_YEAR_FROM = 'construction_year_from';
    public const REQUEST_FIELD_SELLER = 'seller_id';
    public const REQUEST_FIELD_SELLER_SLUG = 'seller_slug';
    public const REQUEST_FIELD_DEBUG = 'debug';
    public const REQUEST_FIELD_OFFER = 'offer_id';
    public const REQUEST_FIELD_FROM_CREATE_DATE = 'create_date';
    public const REQUEST_FIELD_SELLER_TYPES = 'seller_types';
    public const REQUEST_FIELD_HAS_IMAGE_COUNT = 'has_image_count';
    public const REQUEST_FIELD_PRICE_TYPE = 'price_type';
    public const REQUEST_FIELD_PRICE_ANALYSIS_TYPE = 'price_analysis_type';
    public const REQUEST_FIELD_TRANSMISSION = 'transmission';
    public const REQUEST_FIELD_USER_ID = 'user_id';
    public const REQUEST_FIELD_BY_ID = 'search_by_id';
    public const REQUEST_FIELD_BUMPED_BY_VAS = 'offer_bumpup_facet_int';
    public const REQUEST_FIELD_MODEL = 'model';
    public const REQUEST_FIELD_VERSION = 'version';

    public const REQUEST_BUYER_COUNTRY = 'buyerCountry';

    public const REQUEST_VALUE_SORT_SORT_INDEX = 'score-desc';
    public const REQUEST_VALUE_SORT_RELEVANCY = 'relevancy';
    public const REQUEST_VALUE_SORT_RELEVANCY_LABEL = 'Best match';
    public const REQUEST_VALUE_SORT_PRICE_ASC = 'price-asc';
    public const REQUEST_VALUE_SORT_PRICE_ASC_LABEL = 'Price: lowest first';
    public const REQUEST_VALUE_SORT_PRICE_DESC = 'price-desc';
    public const REQUEST_VALUE_SORT_PRICE_DESC_LABEL = 'Price: highest first';
    public const REQUEST_VALUE_SORT_DATE_DESC = 'date-desc';
    public const REQUEST_VALUE_SORT_DATE_DESC_LABEL = 'Date Published';
    public const REQUEST_VALUE_SORT_DATE_ASC = 'date-asc';
    public const REQUEST_VALUE_SORT_MILEAGE_ASC = 'mileage-asc';
    public const REQUEST_VALUE_SORT_MILEAGE_ASC_LABEL = 'Mileage: lowest first';
    public const REQUEST_VALUE_SORT_MILEAGE_DESC = 'mileage-desc';
    public const REQUEST_VALUE_SORT_MILEAGE_DESC_LABEL = 'Mileage: highest first';

    public const REQUEST_VALUE_SORT_WEIGHT_ASC = 'weight-asc';
    public const REQUEST_VALUE_SORT_WEIGHT_ASC_LABEL = 'Weight: lowest first';
    public const REQUEST_VALUE_SORT_WEIGHT_DESC = 'weight-desc';
    public const REQUEST_VALUE_SORT_WEIGHT_DESC_LABEL = 'Weight: highest first';

    public const REQUEST_VALUE_SORT_WEIGHT_NET_ASC = 'weight-net-asc';
    public const REQUEST_VALUE_SORT_WEIGHT_NET_ASC_LABEL = 'Net Weight: lowest first';
    public const REQUEST_VALUE_SORT_WEIGHT_NET_DESC = 'weight-net-desc';
    public const REQUEST_VALUE_SORT_WEIGHT_NET_DESC_LABEL = 'Net Weight: highest first';

    public const REQUEST_VALUE_SORT_HOURS_ASC = 'hours-asc';
    public const REQUEST_VALUE_SORT_HOURS_ASC_LABEL = 'Hours run: lowest first';
    public const REQUEST_VALUE_SORT_HOURS_DESC = 'hours-desc';
    public const REQUEST_VALUE_SORT_HOURS_DESC_LABEL = 'Hours run: highest first';
    public const REQUEST_VALUE_SORT_YEAR_ASC = 'year-asc';
    public const REQUEST_VALUE_SORT_YEAR_DESC = 'year-desc';
    public const REQUEST_VALUE_SORT_YEAR_ASC_LABEL = 'Year: oldest first';
    public const REQUEST_VALUE_SORT_YEAR_DESC_LABEL = 'Year: newest first';

    public const REQUEST_FIELD_MILEAGE_LABEL = 'mileage';
    public const REQUEST_FIELD_WEIGHT_LABEL = 'weight';
    public const REQUEST_FIELD_WEIGHT_NET_LABEL = 'netWeight';
    public const REQUEST_FIELD_PRICE_RATING_LABEL = 'priceRating';
    public const REQUEST_FIELD_PRICE_ANALYSIS_TYPE_LABEL = 'priceAnalysisType';
    public const REQUEST_FIELD_TRANSMISSION_LABEL = 'transmission';

    public const REQUEST_VALUE_DEFAULT_PAGE = 1;
    public const REQUEST_VALUE_DEFAULT_SORT = self::REQUEST_VALUE_SORT_RELEVANCY;
    public const REQUEST_VALUE_DEFAULT_LIMIT = Query::DEFAULT_ROWS;
    public const REQUEST_VALUE_MAX_LIMIT = 100;

    public const DELIMITER_LIST = ',';
    public const DELIMITER_MULTI_VALUE = '+';
    public const DELIMITER_QUERY_TEXT = ' ';

    public const FIELD_TYPE_SIMPLE = 'simple';
    public const FIELD_TYPE_MULTIPLE = 'multiple';
    public const FIELD_TYPE_RANGE = 'range';

    public const SIMILAR_OFFER_PRICE_RANGE = 15; // + - 15 percent against selected offer
    public const LIMIT_RELATED = 12;

    public const FACET_HAS_WEIGHT = 'weight_has_facet_int';
    public const FACET_HAS_MILEAGE = 'mileage_has_facet_int';
    public const FACET_HAS_HOURS_RUN = 'hours_run_has_facet_int';
    public const FACET_HAS_YEARS = 'year_has_facet_int';
    public const FACET_HAS_WEIGHT_NET = 'weight_net_has_facet_int';
    public const FACET_HAS_PRICE_ANALYSIS_TYPE = 'price_analysis_type_has_facet_int';
    public const FACET_HAS_TRANSMISSION = 'transmission_has_facet_int';

    public const EXTRA_FILTERS_FACETS = [
        [
            'name' => self::REQUEST_FIELD_MILEAGE_LABEL,
            'facet' => self::FACET_HAS_MILEAGE,
            'filters' => [self::REQUEST_FIELD_MILEAGE_FROM, self::REQUEST_FIELD_MILEAGE_TO],
        ],
        [
            'name' => self::REQUEST_FIELD_WEIGHT_LABEL,
            'facet' => self::FACET_HAS_WEIGHT,
            'filters' => [self::REQUEST_FIELD_WEIGHT_FROM, self::REQUEST_FIELD_WEIGHT_TO],
        ],
        [
            'name' => self::REQUEST_FIELD_WEIGHT_NET_LABEL,
            'facet' => self::FACET_HAS_WEIGHT_NET,
            'filters' => [self::REQUEST_FIELD_WEIGHT_NET_FROM, self::REQUEST_FIELD_WEIGHT_NET_TO],
        ],
        [
            'name' => self::REQUEST_FIELD_PRICE_RATING_LABEL,
            'facet' => self::FACET_HAS_PRICE_ANALYSIS_TYPE,
            'filters' => [
                OfferService::GREAT_PRICE_VALUE => OfferService::GREAT_PRICE,
                OfferService::FAIR_PRICE_VALUE => OfferService::FAIR_PRICE,
                OfferService::OVERPRICE_VALUE => OfferService::OVERPRICE,
                OfferService::NO_PRICE_VALUE => OfferService::NO_PRICE,
            ],
        ],
    ];

    public const EXTRA_SORT_FACETS = [
        [
            'facet' => self::FACET_HAS_MILEAGE,
            'sorts' => [self::REQUEST_VALUE_SORT_MILEAGE_ASC, self::REQUEST_VALUE_SORT_MILEAGE_DESC],
        ],
        [
            'facet' => self::FACET_HAS_YEARS,
            'sorts' => [self::REQUEST_VALUE_SORT_YEAR_ASC, self::REQUEST_VALUE_SORT_YEAR_DESC],
        ],
        [
            'facet' => self::FACET_HAS_WEIGHT,
            'sorts' => [self::REQUEST_VALUE_SORT_WEIGHT_ASC, self::REQUEST_VALUE_SORT_WEIGHT_DESC],
        ],
        /*//Uncomment this code in case we need to sort by this weight
        [
            'facet' => self::FACET_HAS_WEIGHT_NET,
            'sorts' => [self::REQUEST_VALUE_SORT_WEIGHT_NET_ASC, self::REQUEST_VALUE_SORT_WEIGHT_NET_DESC]
        ],*/
        [
            'facet' => self::FACET_HAS_HOURS_RUN,
            'sorts' => [self::REQUEST_VALUE_SORT_HOURS_ASC, self::REQUEST_VALUE_SORT_HOURS_DESC],
        ],
    ];

    public const ALL_SORT_VALUES = [
        self::REQUEST_VALUE_SORT_PRICE_ASC,
        self::REQUEST_VALUE_SORT_PRICE_DESC,
        self::REQUEST_VALUE_SORT_MILEAGE_ASC,
        self::REQUEST_VALUE_SORT_MILEAGE_DESC,
        self::REQUEST_VALUE_SORT_HOURS_ASC,
        self::REQUEST_VALUE_SORT_HOURS_DESC,
        self::REQUEST_VALUE_SORT_YEAR_ASC,
        self::REQUEST_VALUE_SORT_YEAR_DESC,
        self::REQUEST_VALUE_SORT_WEIGHT_ASC,
        self::REQUEST_VALUE_SORT_WEIGHT_DESC,
        /*//Uncomment this code in case we need to sort by this weight
        self::REQUEST_VALUE_SORT_WEIGHT_NET_ASC,
        self::REQUEST_VALUE_SORT_WEIGHT_NET_DESC,
        */
        self::REQUEST_VALUE_SORT_SORT_INDEX,
        self::REQUEST_VALUE_SORT_RELEVANCY,
        self::REQUEST_VALUE_SORT_DATE_DESC,
        self::REQUEST_VALUE_SORT_DATE_ASC,
    ];

    public const REDIS_NAMESPACE_FACET_CATEGORIES_DATA = 'FacCatData:';

    public const TOP_MANUFACTURERS_MAX_COUNT = 1000;

    /** @var Client */
    protected $client;

    /** @var Client */
    protected $sellerClient;

    /** @var Result */
    protected $result;

    /** @var Query */
    protected $query;

    /** @var Request */
    protected $request;

    /** @var array */
    protected $params = [];

    /** @var string */
    protected $translator;

    /**
     * Boost offers with images.
     * @var float
     */
    protected $relevancyBoostHasImageScore;

    /**
     * Boost offers with a proper price.
     * @var float
     */
    protected $relevancyBoostPriceScore;

    /**
     * Boost all sellers except free.
     * @var float
     */
    protected $relevancyBoostSellerTypesScore;
    /**
     * Used to boost the words appearing in title
     * Higher value scores documents higher with the words in the title.
     * @var float
     */
    protected $relevancyBoostTitleScore;

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
     * List of (sellers) countries to show in the latest offer from homepage.
     * @var array
     */
    protected $relevancyHomeCountryList;

    /**
     * Used to reference the age of the offer in sort-index sorting by score.
     * @var string
     */
    protected $relevancyTimeBoostReferenceTime = '6.33e-11'; // 6 months

    /**
     * Boost offers where buyer and seller match their country.
     * @var float
     */
    protected $relevancyBoostCountryMatchScore;

    /**
     * Limit value to boost images greater and equal to this value.
     * @var int
     */
    protected $relevancyBoostLimitImages;

    /**
     * Boost offers that have more images than previous $relevancyBoostLimitImages limit.
     * @var float
     */
    protected $relevancyBoostLimitImagesScore;

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
     * Set of rules in json formed by range and value.
     * @var object
     */
    protected $relevancyBoostSellerInventoryRules;

    /**
     * Used to separate the legacy bumpUp from the bumpUp by VAS.
     * @var float
     */
    protected $relevancyBoostOfferVAS;

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
     * Boost fixed price.
     * @var float
     */
    protected $relevancyBoostFixedPriceScore;

    /**
     * Used to enable ab testing, set it to negative value to disable, the value will point to test A.
     * @var float
     */
    protected $abTestingForAScore;

    /**
     * Used to switch between ON/OFF the 20% calculation for displaying the new sorting options.
     * @var int
     */
    protected $sortOptionsActivateFillOnTheFly;

    /**
     * Used to get category ids for price analysis type.
     * @var array
     */
    protected $priceAnalysisCategories;

    /**
     * Enable/Disable Solr debug output.
     * @var bool
     */
    protected $searchDebug = false;

    /** @var EntityManagerInterface */
    protected $entity_manager;

    public $searchFilters = [];

    /** @var int */
    protected $sitecodeId;

    /** @var string */
    protected $sitecodeKey;

    protected $defaultLocale;

    protected $sitecodeService;

    protected $tracking = [];

    protected $selectedFilters = [];

    protected $locale = false;

    protected $dynamicFilters = [];

    /**
     * SearchService constructor.
     *
     * @param null                   $options
     * @param EntityManagerInterface $entityManager
     */
    public function __construct($options = null, ?EntityManagerInterface $entityManager = null)
    {
        $endpoint = null;
        if ($options && isset($options['endpoint'])) {
            $endpoint = $options['endpoint'];
        }

        if ($options && isset($options['seller_endpoint'])) {
            $sellerEndpoint = $options['seller_endpoint'];
            $this->setSellerClient(new Client($sellerEndpoint));
        }

        $this->setClient(new Client($endpoint));
        $this->entity_manager = $entityManager;
        $this->loadConfiguration();

        $ssc = new SitecodeService();
        $this->sitecodeService = $ssc;
        $this->sitecodeId = $ssc->getSitecodeId();
        $this->sitecodeKey = $ssc->getSitecodeKey();
        $this->defaultLocale = $ssc->getDefaultLocale();
    }

    /**
     * Loads configuration from database.
     */
    protected function loadConfiguration()
    {
        global $kernel;
        /** @var ConfigService $config */
        $config = $kernel->getContainer()->get('tradus.config');
        $this->translator = $kernel->getContainer()->get('translator');
        $this->relevancyBoostHasImageScore = $config->getSettingValue('relevancy.boostHasImageScore');
        $this->relevancyBoostPriceScore = $config->getSettingValue('relevancy.boostPriceScore');
        $this->relevancyBoostSellerTypesScore = $config->getSettingValue('relevancy.boostSellerTypesScore');
        $this->relevancyBoostTitleScore = $config->getSettingValue('relevancy.boostTitleScore');
        $this->relevancyBoostCountryScore = $config->getSettingValue('relevancy.boostCountryScore');
        $this->relevancyBoostCountryList = $config->getSettingValue('relevancy.boostCountryList');
        $this->relevancyBoostCountryMatchScore = $config->getSettingValue('relevancy.boostCountryMatchScore');
        $this->relevancyBoostLimitImages = $config->getSettingValue('relevancy.boostLimitImages');
        $this->relevancyBoostLimitImagesScore = $config->getSettingValue('relevancy.boostLimitImagesScore');
        $this->relevancyBoostFreshSeller = $config->getSettingValue('relevancy.boostFreshSeller');
        $this->relevancyBoostFreshSellerScore = $config->getSettingValue('relevancy.boostFreshSellerScore');
        $this->relevancyBoostSellerInventoryRules = $config->getSettingValue('relevancy.boostSellerInventoryRules');
        $this->abTestingForAScore = $config->getSettingValue('config.abTestingForAPercentageScore');
        $this->relevancyBoostFixedPriceScore = $config->getSettingValue('relevancy.boostFixedPriceScore');
        $this->relevancyHomeCountryList = $config->getSettingValue('relevancy.homeCountryList');

        $this->relevancyBoostTimeA = $config->getSettingValue('relevancy.boostTimeA');
        $this->relevancyBoostTimeB = $config->getSettingValue('relevancy.boostTimeB');

        $this->sortOptionsActivateFillOnTheFly = $config->getSettingValue('sort.options.activateFillOnTheFly');
        $this->priceAnalysisCategories = $config->getSettingValue('priceAnalysis.categories');

        $this->relevancyBoostOfferVAS = $config->getSettingValue('relevancy.boostOfferVAS');
    }

    /**
     * Returns the value of the configuration priceAnalysisCategories.
     */
    public function getPriceAnalysisCategories()
    {
        return $this->priceAnalysisCategories;
    }

    /**
     * @return Query
     */
    public function getQuerySelect()
    {
        return $this->client->getQuerySelect();
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
            /*      // there's an escape and pharse inside addQuery it might get confussed with adding the ( ) let's trust addQuery to do it right
                        if ($currentQuery != "*:*") {
                            $this->query->setQuery('('. $currentQuery .')');
                        } else {
                            $this->query->setQuery('');
                        }*/

            if ($currentQuery == '*:*') {
                $this->query->setQuery('');
            }

            $this->query->addQuery('site_facet_m_int', 1);
        }
        $this->query->addQuery('status_facet_int', Offer::STATUS_ONLINE);

        //end whitelabel
        $this->result = $this->client->execute($this->query);

        return $this->result;
    }

    /**
     * Get Autocomplete suggestions.
     *
     * @param string $query
     * @param string $locale
     * @param        $cache
     * @param bool   $rebuildSuggestionDictionary
     *
     * @return array
     */
    public function getAutoCompleteSuggestions(
        string $query,
        string $locale,
        $cache,
        bool $rebuildSuggestionDictionary = false
    ): array {
        $sitecodeName = strtolower($this->sitecodeService->getSitecodeKey());
        $dictionary = 'suggestions'.ucfirst($sitecodeName).strtoupper($locale);
        $this->query = $this->client->getQuerySuggest();
        $this->query->setQuery($query);
        $this->query->setDictionary($dictionary);
        $this->query->setBuild($rebuildSuggestionDictionary);
        $this->result = $this->client->execute($this->query);

        // Get category for the first suggestion
        $suggestions = $this->result->getSuggestions();
        if (empty($suggestions)) {
            return $suggestions;
        }
        $categorySuggestions = $this->getCategorySuggestions(reset($suggestions), $locale, $cache);

        return array_merge($categorySuggestions, $suggestions);
    }

    /**
     * Returns the first 3 categories for the suggested term.
     *
     * @param array  $suggestion
     * @param string $locale
     * @param        $cache
     *
     * @return array
     */
    public function getCategorySuggestions(array $suggestion, string $locale, $cache): array
    {
        $this->query = $this->client->getQuerySelect();
        $this->query->setRows(0)
            ->addQuery('suggestion_text', $suggestion['term'])
            ->enableFacet()
            ->addFacetFields($locale.'_categoryname_facet_m_string')
            ->setFacetLimit($locale.'_categoryname_facet_m_string', 3);
        /* Just add this to the query when we want to show suggestions from all categories
         * ->setFacetLimit($locale.'_categoryname_facet_m_string', 3) */

        $this->result = $this->client->execute($this->query);
        $facets = $this->result->getFacetFields();

        // Get categories from cache to exclude the non transport ones - remove this when we go back to regular flow
        // I am not proud of this shit but it should be just temporary until we want to show all categories
        // `Please remove me in the future!`
        // LATER EDIT: Now we should enable this for every category but let's wait to see if they change their minds
        /*$categoryCached = $cache->getItem($locale . '_categoryname_facet_m_string');
        if (!$categoryCached->isHit()) {
            $repo = $this->entity_manager->getRepository('TradusBundle:Category');
            $categoryTransport = [CategoryInterface::TRANSPORT_ID];
            $transport = $repo->findOneBy(['id' => CategoryInterface::TRANSPORT_ID]);
            $transportChildren = $repo->getChildrenIds($transport);
            $transportCategory = array_merge($categoryTransport, $transportChildren);

            $categoryCached->set($transportCategory);
            $categoryCached->expiresAfter(5000);
            $cache->save($categoryCached);
        }
        $transportCategories = $categoryCached->get();
        $categories = [];

        $counter = 0;
        foreach (array_keys($facets[$locale.'_categoryname_facet_m_string']) as $key => $value) {
            $arr = explode(':', $value);
            if (in_array($arr[0], $transportCategories)) {
                $categories[$arr[0]] = $arr[1];
                $counter++;
            }
            if ($counter == 3) {
                break;
            }
        }

        if ($counter >= 2 && !empty($categories[CategoryInterface::TRANSPORT_ID])) {
            unset($categories[CategoryInterface::TRANSPORT_ID]);
        }*/
        /** This is the end of the madness. Until this point the shitty code can be removed */
        $categories = [];
        foreach (array_keys($facets[$locale.'_categoryname_facet_m_string']) as $key => $value) {
            $arr = explode(':', $value);
            $categories[$arr[0]] = $arr[1];
        }

        $result = [];
        foreach ($categories as $categoryId => $name) {
            $suggestion['category'] = $name;
            $suggestion['categoryId'] = $categoryId;
            $result[] = $suggestion;
        }

        return $result;
    }

    /**
     * @param int  $categoryId
     * @param int  $minPrice
     * @param null $source
     *
     * @return array
     */
    public function findLatestPremiumOffersBy(int $categoryId, $minPrice = 1000, $source = null)
    {
        static $redis = false;

        if (! $redis) {
            $redis = new RedisService('Premium:', 90);
        }

        $myKey = $categoryId.':'.$minPrice.':'.$source;
        $res = $redis->getParameter($myKey);

        if (! empty($res)) {
            $result = unserialize($res);
            if (! empty($result)) {
                return $result;
            }
        }

        $this->query = $this->client->getQuerySelect();

        $this->query->setRows(100)
            ->addSort(self::SEARCH_FIELDS_CREATE_DATE, 'DESC')
            ->addQuery(self::SEARCH_FIELDS_CATEGORY, $categoryId)
            ->addRangeQuery($this->sitecodeKey.self::SEARCH_FIELDS_PRICE_WL, $minPrice)
            ->addRangeQuery(self::SEARCH_FIELDS_IMAGE_COUNT, 1);

        if ($source && $source == 'home') {
            $this->query->addQuery(self::SEARCH_FIELDS_COUNTRY, $this->relevancyHomeCountryList)
                ->addQuery(self::SEARCH_FIELDS_PRICE_TYPE, Offer::PRICE_TYPE_FIXED)
                ->addRawQuery(self::SEARCH_FIELDS_IMAGE_DUPLICATED, 0)
                ->addRawQuery(self::SEARCH_FIELDS_SELLER_HAS_LEAD, 0);
        }
        $this->result = $this->execute($this->query);
        // We only need 6 and we prefer some results above (boost sellers)
        $this->result->shuffleDocuments()->boostSellerTypesDocuments(false)->limitDocuments(self::LIMIT_RELATED);
        $result = $this->getTradusResult($this->result);

        $redis->setParameter($myKey, serialize($result)); // cache

        return $result;
    }

    /**
     * Finds Similar offers based on a category and make.
     *
     * @param int      $categoryId
     * @param string   $makeName
     * @param int|bool $excludeOfferId
     * @param bool     $title
     * @param bool     $price
     * @param int      $limit
     *
     * @return array
     */
    public function findSimilarOffersBy(
        int $categoryId,
        string $makeName,
        $excludeOfferId = false,
        $title = false,
        $price = false,
        $limit = self::LIMIT_RELATED
    ) {
        $this->query = $this->client->getQuerySelect();

        if ($price) {
            $this->query
                ->addSort($this->sitecodeKey.self::SEARCH_FIELDS_PRICE_WL)
                ->setRows(100)
                ->addQuery(self::SEARCH_FIELDS_CATEGORY, $categoryId)
                ->addQuery(self::SEARCH_FIELDS_MAKE, $makeName)
                ->addRangeQuery(
                    $this->sitecodeKey.self::SEARCH_FIELDS_PRICE_WL,
                    $price * (1 - (self::SIMILAR_OFFER_PRICE_RANGE / 100)),
                    $price * (1 + (self::SIMILAR_OFFER_PRICE_RANGE / 100))
                );
            $this->result = $this->execute($this->query);
            $this->result->filterDocuments(self::SEARCH_FIELDS_OFFER_ID, $excludeOfferId);

            return $this->getTradusResult($this->result);
        }

        $this->query->addSort(self::SEARCH_FIELDS_SORT_INDEX)
            ->setRows(100)
            ->addQuery(self::SEARCH_FIELDS_CATEGORY, $categoryId)
            ->addQuery(self::SEARCH_FIELDS_MAKE, $makeName);

        $this->result = $this->execute($this->query);

        // Filter the free sellers out of the result set, but keep in the total count/numFound
        $filterSellerType = false;
        if ($this->result->getNumberFound() > $limit) {
            $filterSellerType = true;

            // Don't show same listing
            if ($excludeOfferId) {
                $this->result->filterDocuments(self::SEARCH_FIELDS_OFFER_ID, $excludeOfferId);
            }

            // Remove offers without images
            $this->result->filterDocuments(self::SEARCH_FIELDS_IMAGE_COUNT, 0);
            if (! $title) {
                $this->result
                    ->shuffleDocuments()
                    ->boostSellerTypesDocuments($filterSellerType)
                    ->limitDocuments($limit);
            } else {
                $this->result->orderBySimilarity($title)
                    ->limitDocuments(30)
                    ->boostSellerTypesDocuments($filterSellerType)
                    ->limitDocuments($limit);
            }
        } else {
            /*
             * This will do a MoreLikeThis search based on offerid, will be more accurated, but can only return offers,
             * no faccets etc.
             */
            $this->query->enableMlt();
            $this->query->addQuery(self::SEARCH_FIELDS_OFFER_ID, $excludeOfferId);
            $this->result = $this->execute($this->query);
            $this->result->replaceDocumentsWithMoreLikeThis($excludeOfferId)->limitDocuments($limit);
        }

        return $this->getTradusResult($this->result);
    }

    /**
     * Finds offers based on their IDs.
     *
     * @param array $offers
     *
     * @return array
     */
    public function findOffersByIds(array $offers): array
    {
        $this->query = $this->client->getQuerySelect();
        $this->query->setRows(Offer::OTHER_OFFERS_VIEWED_LIMIT_OFFERS);
        $this->query->addRawQuery(self::SEARCH_FIELDS_OFFER_ID, '('.implode(' ', $offers).')');
        $this->query->addSort(self::SEARCH_FIELDS_OFFER_ID);
        $this->result = $this->execute($this->query);

        return $this->getTradusResult($this->result);
    }

    /**
     * Get sidewide search Facetdata.
     *
     * @return Result
     */
    public function getCategoryFacetDataSideWide()
    {
        $query = $this->client->getQuerySelect();
        $query->addQuery('site_facet_m_int', $this->sitecodeId);
        $query->setRows(0)->addSort(self::SEARCH_FIELDS_SORT_INDEX);
        $query->enableFacet()->addFacetField(self::SEARCH_FIELDS_CATEGORY);
        $query->setFacetLimit(self::SEARCH_FIELDS_CATEGORY, self::SEARCH_FIELDS_CATEGORY_MAX_COUNT_VALUE);
        $this->result = $this->client->execute($query);

        return $this->result;
    }

    /**
     * Get Facets for categories sorts.
     *
     * @param int $categoryId
     *
     * @return Result
     */
    public function getCategoryFacetsForSort(int $categoryId)
    {
        $this->query = $this->client->getQuerySelect();
        $this->query->setRows(0);
        $this->query->addQuery(self::SEARCH_FIELDS_CATEGORY, $categoryId);
        $this->query->enableFacet()->addFacetField(self::FACET_HAS_WEIGHT);
        $this->query->addFacetField(self::FACET_HAS_WEIGHT_NET);
        $this->query->addFacetField(self::FACET_HAS_PRICE_ANALYSIS_TYPE);
        $this->query->addFacetField(self::FACET_HAS_MILEAGE);
        $this->query->addFacetField(self::FACET_HAS_HOURS_RUN);
        $this->query->addFacetField(self::FACET_HAS_YEARS);
        $this->result = $this->execute($this->query);

        return $this->result;
    }

    /**
     * @param Request $request
     *
     * @param bool $topAd
     * @param int $topAdsNumber
     * @return Result
     * @throws CurrencyExchangeException
     * @throws NonUniqueResultException
     */
    public function findByRequest(Request $request, bool $topAd = false, int $topAdsNumber = 0)
    {
        $query = $this->client->getQuerySelect();
        $this->query = $this->createQueryFromRequest($query, $request, $topAd, $topAdsNumber);

        return $this->execute($this->query);
    }

    /**
     * @param Request $request
     * @param int     $offerId
     *
     * @return Result
     * @throws NonUniqueResultException
     */
    public function findByOfferIdRequest(Request $request, int $offerId)
    {
        $query = $this->client->getQuerySelect();
        $this->resetParams();
        $this->query = $query;
        $this->request = $request;
        $this->query->setRows(1);
        $this->query->addQuery(self::REQUEST_FIELD_OFFER, $offerId);

        return $this->execute($this->query);
    }

    /**
     * @param Query $query
     * @param Request $request
     *
     * @param bool $topAd
     * @param int $topAdsNumber
     * @return Query
     * @throws CurrencyExchangeException
     * @throws NonUniqueResultException
     */
    public function createQueryFromRequest(Query $query, Request $request, bool $topAd = false, int $topAdsNumber = 0)
    {
        $this->resetParams();
        $this->query = $query;
        $this->request = $request;
        /*
         * Enable/Disable debug information
         */
        if ($this->searchDebug === true || $this->requestHas(self::REQUEST_FIELD_DEBUG)) {
            $this->query->enableDebug();
        }

        // Make the same query with top ads enabled first
        if ($topAd) {
            $searchLimit = $topAdsNumber;
            $sortArray = ['price-asc', 'price-desc', 'date-asc', 'date-desc', 'relevancy', 'make_str-asc', 'make_str-desc', 'make_id_facet_int-asc', 'make_id_facet_int-desc', 'seller_id-asc', 'seller_id-desc'];
            $rand = mt_rand(0, 10);
            $searchSort = $sortArray[$rand];
            if ($rand < 5) {
                $this->setQuerySort($searchSort);
            } else {
                $searchSort = explode('-', $searchSort);
                $this->query->addSort($searchSort[0], $searchSort[1]);
            }
            $this->query->setRows($searchLimit);
            $this->query->addQuery($this->sitecodeKey.'_offer_top_facet_int', 1);
        } else {

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
                if ($searchLimit > self::REQUEST_VALUE_MAX_LIMIT) {
                    $searchLimit = self::REQUEST_VALUE_MAX_LIMIT;
                }
                $this->query->setRows($searchLimit);
            }
        }

        /*
         * Add the PAGE NUMBER to the search
         */
        if ($this->requestHas(self::REQUEST_FIELD_PAGE)
            && $this->requestGet(self::REQUEST_FIELD_PAGE) > self::REQUEST_VALUE_DEFAULT_PAGE) {
            $start = ($this->requestGet(self::REQUEST_FIELD_PAGE) - 1) * $searchLimit;
            $this->query->setStart($start);
        }

        $this->createDynamicFiltersList();

        /*
         * Add CATEGORY to the search
         */
        if ($this->requestHas(self::REQUEST_FIELD_CAT_L1)) {
            $this->query->addQuery(self::SEARCH_FIELDS_CATEGORY, intval($this->requestGet(self::REQUEST_FIELD_CAT_L1)));
            $this->createResetLink(self::REQUEST_FIELD_CAT_L1);
        }

        /*
         * Add CATEGORY TYPE to the search
         */
        if ($this->requestHas(self::REQUEST_FIELD_CAT_L2)) {
            $this->query->addQuery(self::SEARCH_FIELDS_CATEGORY, intval($this->requestGet(self::REQUEST_FIELD_CAT_L2)));
            $this->createResetLink(self::REQUEST_FIELD_CAT_L2);
        }

        /*
         * Add CATEGORY SUBTYPE to the search
         */
        if ($this->requestHas(self::REQUEST_FIELD_CAT_L3)) {
            $this->query->addQuery(self::SEARCH_FIELDS_CATEGORY, intval($this->requestGet(self::REQUEST_FIELD_CAT_L3)));
            $this->createResetLink(self::REQUEST_FIELD_CAT_L3);
        }

        /*
         * Add CATEGORY IMAGE_COUNT to the search
         */
        if ($this->requestHas(self::REQUEST_FIELD_HAS_IMAGE_COUNT)) {
            $this->query->addRangeQuery(
                self::SEARCH_FIELDS_IMAGE_COUNT,
                $this->requestGet(self::REQUEST_FIELD_HAS_IMAGE_COUNT)
            );
        }

        /*
         * Add offer id to the search
         */
        if ($this->requestHas(self::REQUEST_FIELD_OFFER)) {
            $this->query->addRawQuery(
                self::REQUEST_FIELD_OFFER,
                $this->requestGet(self::REQUEST_FIELD_OFFER),
                Query::OPERATOR_AND,
                Query::OPERATOR_SPACE
            );
        }

        /*
         * Add QUERY to the search
         */
        if ($this->requestHas(self::REQUEST_FIELD_QUERY)) {
            $q = urldecode($this->request->query->get(self::REQUEST_FIELD_QUERY));
            $this->request->query->set(self::REQUEST_FIELD_QUERY, $q);
            $this->createResetLink(self::REQUEST_FIELD_QUERY);
            $this->query->addRawQuery(
                self::SEARCH_FIELDS_QUERY,
                $this->getParsedSearchQuery(),
                Query::OPERATOR_AND,
                Query::OPERATOR_AND
            );
        }

        /*
         * Add create date filter to the search
         */
        if ($this->requestHas(self::REQUEST_FIELD_FROM_CREATE_DATE)) {
            $this->query->addRangeQuery(
                self::SEARCH_FIELDS_CREATE_DATE,
                $this->requestGet(self::REQUEST_FIELD_FROM_CREATE_DATE)
            );
        }

        /*
         * START: We are adding the dynamic filters here for the search
         */
        foreach ($this->searchFilters as $filter) {
            $trackDynamicFilter = false;
            switch ($filter['filterType']) {
                case FilterConfigurationInterface::FILTER_TYPE_RANGE:
                    if ($this->requestHas($filter['filterOptions']['from']['name'])
                        || $this->requestHas($filter['filterOptions']['to']['name'])
                    ) {
                        $from = is_numeric($this->requestGet($filter['filterOptions']['from']['name']))
                            ? (int) $this->requestGet($filter['filterOptions']['from']['name']) : 0;
                        $to = is_numeric($this->requestGet($filter['filterOptions']['to']['name']))
                            ? (int) $this->requestGet($filter['filterOptions']['to']['name']) : null;
                        $trackDynamicFilter[$filter['solrKey']] = $from.'-'.$to;
                        $this->query->addRangeQuery(
                            $filter['solrKey'],
                            $from,
                            $to
                        );

                        if ($this->requestHas($filter['filterOptions']['from']['name'])) {
                            $this->createResetLink($filter['filterOptions']['from']['name']);
                        }
                        if ($this->requestHas($filter['filterOptions']['to']['name'])) {
                            $this->createResetLink($filter['filterOptions']['to']['name']);
                        }
                    }

                    break;

                default:
                    if ($this->requestHas($filter['searchKey'])) {
                        if (! is_array($this->requestGet($filter['searchKey']))) {
                            $values = explode(self::DELIMITER_MULTI_VALUE, $this->requestGet($filter['searchKey']));
                        } else {
                            $values = $this->requestGet($filter['searchKey']);
                        }
                        foreach ($values as $exclude) {
                            $this->createResetLink($filter['searchKey'], true, $exclude);
                        }
                        $values = $trackValues = $this->getValuesIds($values, $filter);
                        if (is_array($values) && ! empty($values)) {
                            $trackValues = implode(',', $values);
                        }
                        $trackDynamicFilter[$filter['solrKey']] = $trackValues;
                        $this->query->addQuery($filter['solrKey'], $values);
                    }

                    break;
            }
            if ($trackDynamicFilter) {
                $this->tracking = array_merge($this->tracking, $trackDynamicFilter);
            }
        }
        /* END: We are adding the dynamic filters here for the search */

        /* Add Attribute MODEL to the search */

        if ($this->requestHas(self::REQUEST_FIELD_MODEL)) {
            if (! is_array($this->requestGet(self::REQUEST_FIELD_MODEL))) {
                $model = explode(self::DELIMITER_MULTI_VALUE, $this->requestGet(self::REQUEST_FIELD_MODEL));
            } else {
                $model = $this->requestGet(self::REQUEST_FIELD_MODEL);
            }
            $this->createResetLink(self::REQUEST_FIELD_MODEL);
            $this->tracking['filter_locations'] = implode(',', $model);
            $this->query->addQuery(self::SEARCH_FIELDS_MODEL, $model);
        }
        /* Add Attribute MODEL to the search */

        /* Add Attribute VERSION to the search */
        if ($this->requestHas(self::REQUEST_FIELD_VERSION)) {
            if (! is_array($this->requestGet(self::REQUEST_FIELD_VERSION))) {
                $version = explode(self::DELIMITER_MULTI_VALUE, $this->requestGet(self::REQUEST_FIELD_VERSION));
            } else {
                $version = $this->requestGet(self::REQUEST_FIELD_VERSION);
            }
            $this->createResetLink(self::REQUEST_FIELD_VERSION);
            $this->tracking['filter_locations'] = implode(',', $version);
            $this->query->addQuery(self::SEARCH_FIELDS_VERSION, $version);
        }
        /* Add Attribute VERSION to the search */

        /*
         * Add Attribute MAKE to the search
         */
        if ($this->requestHas(self::REQUEST_FIELD_MAKE)) {
            $this->tracking['filter_makes'] = $this->requestGet(self::REQUEST_FIELD_MAKE);
            $makesRequest = $this->requestGet(self::REQUEST_FIELD_MAKE);
            $searchMakes = $this->getMakeValuesAsIndexed($makesRequest);
            $this->createResetLink(self::REQUEST_FIELD_MAKE);
            $this->query->addQuery(self::SEARCH_FIELDS_MAKE, $searchMakes);
        }

        /*
         * Add Attribute COUNTRY to the search
         */
        if ($this->requestHas(self::REQUEST_FIELD_COUNTRY)) {
            if (! is_array($this->requestGet(self::REQUEST_FIELD_COUNTRY))) {
                $country = explode(self::DELIMITER_MULTI_VALUE, $this->requestGet(self::REQUEST_FIELD_COUNTRY));
            } else {
                $country = $this->requestGet(self::REQUEST_FIELD_COUNTRY);
            }
            $this->tracking['filter_locations'] = implode(',', $country);
            foreach ($country as $countryCode) {
                $this->createResetLink(self::REQUEST_FIELD_COUNTRY, true, $countryCode);
            }
            $this->query->addQuery(self::SEARCH_FIELDS_COUNTRY, $country);
        }

        /*
         * Add Attribute REGION to the search
         */
        if ($this->requestHas(self::REQUEST_FIELD_REGION)) {
            if (! is_array($this->requestGet(self::REQUEST_FIELD_REGION))) {
                $region = explode(self::DELIMITER_MULTI_VALUE, $this->requestGet(self::REQUEST_FIELD_REGION));
            } else {
                $region = $this->requestGet(self::REQUEST_FIELD_REGION);
            }
            $this->tracking['filter_locations'] = implode(',', $region);
            foreach ($region as $regionSlug) {
                $this->createResetLink(self::REQUEST_FIELD_REGION, true, $regionSlug);
            }
            $this->query->addQuery(self::SEARCH_FIELDS_REGION, $region);
        }

        /*
         * Add Attribute PRICETYPE to the search
         */
        if ($this->requestHas(self::REQUEST_FIELD_PRICE_TYPE)) {
            if (! is_array($this->requestGet(self::REQUEST_FIELD_PRICE_TYPE))) {
                $priceType = explode(self::DELIMITER_MULTI_VALUE, $this->requestGet(self::REQUEST_FIELD_PRICE_TYPE));
            } else {
                $priceType = $this->requestGet(self::REQUEST_FIELD_PRICE_TYPE);
            }
            $this->tracking['filter_locations'] = implode(',', $priceType);
            foreach ($priceType as $priceTypeSlug) {
                $this->createResetLink(self::REQUEST_FIELD_PRICE_TYPE, true, $priceTypeSlug);
            }
            $this->query->addQuery(self::REQUEST_FIELD_PRICE_TYPE, $priceType);
        }

        /*
         * Add Attribute TRANSMISSION to the search
         */
        if ($this->requestHas(self::REQUEST_FIELD_TRANSMISSION)) {
            if (! is_array($this->requestGet(self::REQUEST_FIELD_TRANSMISSION))) {
                $trans = explode(self::DELIMITER_MULTI_VALUE, $this->requestGet(self::REQUEST_FIELD_TRANSMISSION));
            } else {
                $trans = $this->requestGet(self::REQUEST_FIELD_TRANSMISSION);
            }
            foreach ($trans as $transmission) {
                $this->createResetLink(self::REQUEST_FIELD_TRANSMISSION, true, $transmission);
            }
            $trans = $this->getTransmissionBySlug($trans);
            $this->tracking['filter_locations'] = implode(',', $trans);
            $this->query->addQuery(self::SEARCH_FIELDS_TRANSMISSION, $trans);
        }

        $categoryFilters = $this->getCategoryExtraSorts();

        /*
         * Add Attribute PRICE ANALYSIS TYPE to the search
         */

        if ($this->requestHas(self::REQUEST_FIELD_PRICE_ANALYSIS_TYPE)
            && isset($categoryFilters['filters'][self::REQUEST_FIELD_PRICE_RATING_LABEL])
        ) {
            if (! is_array($this->requestGet(self::REQUEST_FIELD_PRICE_ANALYSIS_TYPE))) {
                $priceAnalysisType = explode(self::DELIMITER_MULTI_VALUE, $this->requestGet(self::REQUEST_FIELD_PRICE_ANALYSIS_TYPE));
            } else {
                $priceAnalysisType = $this->requestGet(self::REQUEST_FIELD_PRICE_ANALYSIS_TYPE);
            }
            $this->tracking['filter_locations'] = implode(',', $priceAnalysisType);

            /** @var PriceAnalysisTypeRepository $priceAnalysisTypeRepository */
            $priceAnalysisTypeRepository = $this->entity_manager->getRepository('TradusBundle:PriceAnalysisType');

            $priceAnalysisType = $priceAnalysisTypeRepository
                ->getValuesBySlug($priceAnalysisType);
            $this->query->addSort($this->sitecodeKey.self::SEARCH_FIELDS_PRICE_ANALYSIS_TYPE_WL, Query::SORT_ASC);
            $this->query->removeSort('score');
            $this->query->addQuery($this->sitecodeKey.self::SEARCH_FIELDS_PRICE_ANALYSIS_TYPE_WL, $priceAnalysisType);
            foreach ($priceAnalysisType as $priceAnalysis) {
                $this->createResetLink(self::REQUEST_FIELD_PRICE_ANALYSIS_TYPE, true, $priceAnalysis);
            }
            // I've commented this because is messes the results by adding to conditions where is only one (try 1 just to see if this fixes the problem)
            //$this->query->addQuery(self::REQUEST_FIELD_PRICE_TYPE, Offer::PRICE_TYPE_FIXED);
            $this->query->addQuery(self::SEARCH_FIELDS_CATEGORY, $this->priceAnalysisCategories);
        }

        /*
         * Add filter on seller types
         */
        if ($this->requestHas(self::REQUEST_FIELD_SELLER_TYPES)) {
            $this->query->addQuery(
                self::SEARCH_FIELDS_SELLER_TYPE,
                $this->requestGet(self::REQUEST_FIELD_SELLER_TYPES)
            );
        }

        /*
         * Add PRICE FILTER to the search
         */
        if ($this->requestHas(self::REQUEST_FIELD_PRICE_FROM)
            || $this->requestHas(self::REQUEST_FIELD_PRICE_TO)
        ) {
            $priceFrom = is_numeric($this->requestGet(self::REQUEST_FIELD_PRICE_FROM))
                ? floatval($this->requestGet(self::REQUEST_FIELD_PRICE_FROM)) : 0;

            $priceTo = is_numeric($this->requestGet(self::REQUEST_FIELD_PRICE_TO))
                ? floatval($this->requestGet(self::REQUEST_FIELD_PRICE_TO)) : 1000000000;

            if ($this->requestHas(self::REQUEST_FIELD_PRICE_FROM)) {
                $this->createResetLink(self::REQUEST_FIELD_PRICE_FROM);
            }
            if ($this->requestHas(self::REQUEST_FIELD_PRICE_TO)) {
                $this->createResetLink(self::REQUEST_FIELD_PRICE_TO);
            }
            $this->tracking['filter_price_range'] = $priceFrom.','.$priceTo;
            $this->setCurrencyPrice($priceFrom, $priceTo);
            $this->query->addRangeQuery(
                $this->sitecodeKey.self::SEARCH_FIELDS_PRICE_WL,
                $priceFrom,
                $priceTo
            );
        }

        if (($this->requestHas(self::REQUEST_FIELD_WEIGHT_FROM)
             || $this->requestHas(self::REQUEST_FIELD_WEIGHT_TO)
            )
            && isset($categoryFilters['filters'][self::REQUEST_FIELD_WEIGHT_LABEL])
        ) {
            $weightFrom = is_numeric($this->requestGet(self::REQUEST_FIELD_WEIGHT_FROM))
                ? floatval($this->requestGet(self::REQUEST_FIELD_WEIGHT_FROM)) : 0;
            $weightTo = is_numeric($this->requestGet(self::REQUEST_FIELD_WEIGHT_TO))
                ? floatval($this->requestGet(self::REQUEST_FIELD_WEIGHT_TO)) : 1000000000;
            if ($this->requestHas(self::REQUEST_FIELD_WEIGHT_FROM)) {
                $this->createResetLink(self::REQUEST_FIELD_WEIGHT_FROM);
            }
            if ($this->requestHas(self::REQUEST_FIELD_WEIGHT_TO)) {
                $this->createResetLink(self::REQUEST_FIELD_WEIGHT_TO);
            }
            $this->query->addRangeQuery(
                self::SEARCH_FIELDS_WEIGHT,
                $weightFrom,
                $weightTo
            );
        }

        if (($this->requestHas(self::REQUEST_FIELD_WEIGHT_NET_FROM)
             || $this->requestHas(self::REQUEST_FIELD_WEIGHT_NET_TO)
            )
            && isset($categoryFilters['filters'][self::REQUEST_FIELD_WEIGHT_NET_LABEL])
        ) {
            $weightNetFrom = is_numeric($this->requestGet(self::REQUEST_FIELD_WEIGHT_NET_FROM))
                ? floatval($this->requestGet(self::REQUEST_FIELD_WEIGHT_NET_FROM)) : 0;
            $weightNetTo = is_numeric($this->requestGet(self::REQUEST_FIELD_WEIGHT_NET_TO))
                ? floatval($this->requestGet(self::REQUEST_FIELD_WEIGHT_NET_TO)) : 1000000000;
            if ($this->requestHas(self::REQUEST_FIELD_WEIGHT_NET_FROM)) {
                $this->createResetLink(self::REQUEST_FIELD_WEIGHT_NET_FROM);
            }
            if ($this->requestHas(self::REQUEST_FIELD_WEIGHT_NET_TO)) {
                $this->createResetLink(self::REQUEST_FIELD_WEIGHT_NET_TO);
            }
            $this->query->addRangeQuery(
                self::SEARCH_FIELDS_WEIGHT_NET,
                $weightNetFrom,
                $weightNetTo
            );
        }

        /*
         * Add YEAR Filter to the search
         */
        if ($this->requestHas(self::REQUEST_FIELD_YEAR_FROM) || $this->requestHas(self::REQUEST_FIELD_YEAR_TO)) {
            if ($this->requestHas(self::REQUEST_FIELD_YEAR_FROM)) {
                $this->createResetLink(self::REQUEST_FIELD_YEAR_FROM);
            }
            if ($this->requestHas(self::REQUEST_FIELD_YEAR_TO)) {
                $this->createResetLink(self::REQUEST_FIELD_YEAR_TO);
            }
            $this->query->addRangeQuery(
                self::SEARCH_FIELDS_YEAR,
                $this->requestGet(self::REQUEST_FIELD_YEAR_FROM),
                $this->requestGet(self::REQUEST_FIELD_YEAR_TO)
            );
        }

        /*
         * SELLER PAGE Search
         */
        if ($this->requestHas(self::REQUEST_FIELD_SELLER)) {
            $this->createResetLink(self::REQUEST_FIELD_SELLER);
            $this->query->addQuery(self::SEARCH_SELLER_ID, $this->requestGet(self::REQUEST_FIELD_SELLER));
        }

        // default facets fields
        $facetFieldsArray = [
            self::SEARCH_FIELDS_MAKE,
            self::SEARCH_FIELDS_CATEGORY,
            self::SEARCH_FIELDS_COUNTRY,
            self::SEARCH_FIELDS_REGION,
            self::SEARCH_FIELDS_PRICE_TYPE,
            self::SEARCH_FIELDS_MILEAGE,
            self::SEARCH_FIELDS_WEIGHT,
            self::SEARCH_FIELDS_WEIGHT_NET,
            $this->sitecodeKey.self::SEARCH_FIELDS_PRICE_ANALYSIS_TYPE_WL,
            self::SEARCH_FIELDS_TRANSMISSION,
            self::SEARCH_FIELDS_MODEL,
            self::SEARCH_FIELDS_VERSION,
            self::SEARCH_SELLER_ID,
        ];

        // default stats fields
        $statsFieldsArray = [
            $this->sitecodeKey.self::SEARCH_FIELDS_PRICE_WL,
            self::SEARCH_FIELDS_YEAR,
            self::SEARCH_FIELDS_MILEAGE,
            self::SEARCH_FIELDS_WEIGHT,
            self::SEARCH_FIELDS_WEIGHT_NET,
            $this->sitecodeKey.self::SEARCH_FIELDS_PRICE_ANALYSIS_TYPE_WL,
        ];

        // Add the dynamic facets
        foreach ($this->searchFilters as $filter) {
            $facetFieldsArray[] = $filter['solrKey'];
            $statsFieldsArray[] = $filter['solrKey'];
        }

        /* Enable facet data retrieval in search */
        $this->query->enableFacet()->setFacetFields($facetFieldsArray);

        /* Enable stats data retrieval in search */
        $this->query->enableStats()->setStatsFields($statsFieldsArray);

        return $this->query;
    }

    /**
     * Make is indexed as Make Name, and we need to search as exact match
     * This function transform slugs into these make names.
     *
     * @param $makesRequest
     *
     * @return array
     * @throws NonUniqueResultException
     */
    public function getMakeValuesAsIndexed($slugValues)
    {
        /** @var MakeRepository $makesRepo */
        $makesRepo = $this->entity_manager->getRepository('TradusBundle:Make');

        if (! is_array($slugValues)) {
            $slugValues = explode(self::DELIMITER_MULTI_VALUE, $this->requestGet(self::REQUEST_FIELD_MAKE));
        }

        // Filter on numeric make request (transfer in make slug), this should not be happening, but somewhere it did
        foreach ($slugValues as $key => $makeValue) {
            if (is_numeric($makeValue)) {
                /** @var Make $make */
                $make = $makesRepo->getMakeById($makeValue);
                if ($make) {
                    $slugValues[$key] = $make->getSlug();
                    // Also fix the Request Object
                    if (isset($this->request) &&
                        $this->request->query->has(self::REQUEST_FIELD_MAKE)
                        && is_string($this->request->query->get(self::REQUEST_FIELD_MAKE))) {
                        $newRequestValue = str_replace(
                            $makeValue,
                            $make->getSlug(),
                            $this->request->query->get(self::REQUEST_FIELD_MAKE)
                        );
                        $this->request->query->set(self::REQUEST_FIELD_MAKE, $newRequestValue);
                    }
                }
            }
        }

        // Makes are indexed as Exact Match
        $makes = $makesRepo->getMakesBySlug($slugValues);
        $searchMakes = [];
        if ($makes) {
            foreach ($makes as $make) {
                /* @var Make $make */
                array_push($searchMakes, $make->getName());
            }
        }

        return $searchMakes;
    }

    /**
     * @return array|mixed
     */
    public function getParsedSearchQuery()
    {
        $searchQuery = $this->requestGet(self::REQUEST_FIELD_QUERY);
        if (! is_array($searchQuery)) {
            $searchQuery = explode(self::DELIMITER_QUERY_TEXT, $searchQuery);
        }

        return $searchQuery;
    }

    /**
     * Set sorting based on Sort String.
     *
     * @param string $sort
     */
    public function setQuerySort(String $sort)
    {
        switch ($sort) {
            case self::REQUEST_VALUE_SORT_PRICE_ASC:
                $this->query->addSort(
                    'if(eq('.$this->sitecodeKey.self::SEARCH_FIELDS_PRICE_WL.',0.0),0,div(1,field('.$this->sitecodeKey.self::SEARCH_FIELDS_PRICE_WL.')))',
                    Query::SORT_DESC
                );
                break;
            case self::REQUEST_VALUE_SORT_PRICE_DESC:
                $this->query->addSort($this->sitecodeKey.self::SEARCH_FIELDS_PRICE_WL, Query::SORT_DESC);
                break;
            case self::REQUEST_VALUE_SORT_DATE_DESC:
                $this->query->addSort(self::SEARCH_FIELDS_CREATE_DATE, Query::SORT_DESC);
                break;
            case self::REQUEST_VALUE_SORT_DATE_ASC:
                $this->query->addSort(self::SEARCH_FIELDS_CREATE_DATE, Query::SORT_ASC);
                break;
            case self::REQUEST_VALUE_SORT_WEIGHT_DESC:
                $this->query->addSort(self::SEARCH_FIELDS_WEIGHT, Query::SORT_DESC);
                break;
            case self::REQUEST_VALUE_SORT_WEIGHT_ASC:
                $this->query->addSort(self::SEARCH_FIELDS_WEIGHT_ASC, Query::SORT_ASC);
                break;
            /* //Uncomment this code in case we need to sort by this weight
            case self::REQUEST_VALUE_SORT_WEIGHT_NET_DESC:
                $this->query->addSort(self::SEARCH_FIELDS_WEIGHT_NET, Query::SORT_DESC);
                break;
            case self::REQUEST_VALUE_SORT_WEIGHT_NET_ASC:
                $this->query->addSort(self::SEARCH_FIELDS_WEIGHT_NET_ASC, Query::SORT_ASC);
                break;
            */
            case self::REQUEST_VALUE_SORT_MILEAGE_DESC:
                $this->query->addSort(self::SEARCH_FIELDS_MILEAGE, Query::SORT_DESC);
                break;
            case self::REQUEST_VALUE_SORT_MILEAGE_ASC:
                $this->query->addSort(self::SEARCH_FIELDS_MILEAGE_ASC, Query::SORT_ASC);
                break;
            case self::REQUEST_VALUE_SORT_HOURS_DESC:
                $this->query->addSort(self::SEARCH_FIELDS_HOURS_RUN, Query::SORT_DESC);
                break;
            case self::REQUEST_VALUE_SORT_HOURS_ASC:
                $this->query->addSort(self::SEARCH_FIELDS_HOURS_RUN_ASC, Query::SORT_ASC);
                break;
            case self::REQUEST_VALUE_SORT_YEAR_DESC:
                $this->query->addSort(self::SEARCH_FIELDS_YEAR, Query::SORT_DESC);
                break;
            case self::REQUEST_VALUE_SORT_YEAR_ASC:
                $this->query->addSort(self::SEARCH_FIELDS_YEAR_ASC, Query::SORT_ASC);
                break;

            case self::REQUEST_VALUE_SORT_RELEVANCY:
            case self::REQUEST_VALUE_SORT_SORT_INDEX:
            default:
                $this->setRelevancy();
                break;
        }
    }

    /**
     * Set solr sorting on score and converts the sort_index age into a score.
     */
    public function setRelevancy()
    {
        $this->setRelevancyB();
        /*
        // DO NOT REMOVE PLEASE
        $random = (int) mt_rand(1, 100);
        if ($random <= $this->abTestingScoreForA) {
            $this->setRelevancyA();
        } else {
            $this->setRelevancyB();
        }*/
    }

    /**
     * Legacy's Algorithm.
     */
    public function setRelevancyA()
    {
        // Enable Relevancy with Edismax
        $this->query->enableEdismax();

        // Sort on score
        $this->query->addSort('score', Query::SORT_DESC);

        /*
         * Boost keywords in title
         */
        if ($this->relevancyBoostTitleScore > 0 && $this->requestHas(self::REQUEST_FIELD_QUERY)) {
            $this->query->addRawEdismaxBoostQuery(
                self::SEARCH_FIELDS_TITLE,
                $this->getParsedSearchQuery(),
                $this->relevancyBoostTitleScore,
                Query::OPERATOR_AND
            );
        }

        /*
         * Default Boost seller types excluding free sellers (so free sellers get lower in the results)
         */
        if ($this->relevancyBoostSellerTypesScore > 0) {
            $this->query->addRawEdismaxBoostQuery(
                self::SEARCH_FIELDS_SELLER_TYPE,
                '[1 TO *]',
                0.5
            );
        }

        /*
         * Boost offers with a price above 100 to 1000 and bigger boost for >1000
         */
        if ($this->relevancyBoostPriceScore > 0) {
            $this->query->addRawEdismaxBoostQuery(
                self::SEARCH_FIELDS_PRICE,
                '[1000.0 TO *]',
                4
            );
            $this->query->addRawEdismaxBoostQuery(
                self::SEARCH_FIELDS_PRICE,
                '[100.0 TO 999.0]',
                2
            );
        }

        /*
         * Boost offers with images
         */
        if ($this->relevancyBoostHasImageScore > 0) {
            $this->query->addRawEdismaxBoostQuery(
                self::SEARCH_FIELDS_IMAGE_COUNT,
                '[1 TO *]',
                10
            );
        }

        /*
         * Boost offers with seller country
         */
        if ($this->relevancyBoostCountryScore > 0 && count($this->relevancyBoostCountryList)) {
            $this->query->addRawEdismaxBoostQuery(
                self::SEARCH_FIELDS_COUNTRY,
                '["NL", "DE", "BE", "AT", "ES", "IT", "FR", "DA"]',
                0.1
            );
        }

        /**
         * Boost newer offers (based on sort-index).
         */
        $referenceTime = $this->relevancyTimeBoostReferenceTime;
        $multiplierA = $this->relevancyBoostTimeA;
        $multiplierB = $this->relevancyBoostTimeB;
        // With above settings we get a very linear decrease of score based on age (sort-index) of the offer.
        // Lower B to for example 0.8 to decrease score quicker with aging of the offer.

        // We use NOW/HOUR+1HOUR to stabilize the result set for one hour.
        $this->query->setEdismaxBoost(
            'recip(ms(NOW/HOUR+1HOUR,'.self::SEARCH_FIELDS_SORT_INDEX.'),'.
            $referenceTime.','.$multiplierA.','.$multiplierB.')'
        );
        /*
         * Explanation:
         * recip(x, m, a, b) implements f(x) = a/(xm+b) with :
            x : the document age in ms, defined as ms(NOW,<datefield>).
            m : a constant that defines a time scale which is used to apply boost. It should be relative to what
        you consider an old document age (a reference_time) in milliseconds. For example, choosing a reference_time of
        1 year (3.16e10ms) implies to use its inverse : 3.16e-11 (1/3.16e10 rounded).
            a and b are constants (defined arbitrarily).
            xm = 1 when the document is 1 reference_time old (multiplier = a/(1+b)).
            xm ≈ 0 when the document is new, resulting in a value close to a/b.
            Using the same value for a and b ensures the multiplier doesn't exceed 1 with recent documents.
            With a = b = 1, a 1 reference_time old document has a multiplier of about 1/2, a 2 reference_time old
        document has a multiplier of about 1/3, and so on.

            Make Boosting Stronger:
            Increase m : choose a lower reference_time for example 6 months, that gives us m = 6.33e-11. Comparing to
        a 1 year reference, the multiplier decreases 2x faster as the document age increases.
            Decreasing a and b expands the response curve of the function. This can be very agressive.
        */
    }

    /**
     * New Algorithm introduced on December 2018.
     */
    public function setRelevancyB()
    {
        // Enable Relevancy with Edismax
        $this->query->enableEdismax();

        // Sort on score
        $this->query->addSort('score', Query::SORT_DESC);

        /*
         * Boost keywords in title
         */
        if ($this->relevancyBoostTitleScore > 0 && $this->requestHas(self::REQUEST_FIELD_QUERY)) {
            $this->query->addRawEdismaxBoostQuery(
                self::SEARCH_FIELDS_TITLE,
                $this->getParsedSearchQuery(),
                $this->relevancyBoostTitleScore,
                Query::OPERATOR_AND
            );
        }

        /*
         * Default Boost seller types excluding free sellers (so free sellers get lower in the results)
         */
        if ($this->relevancyBoostSellerTypesScore > 0) {
            $this->query->addRawEdismaxBoostQuery(
                self::SEARCH_FIELDS_SELLER_TYPE,
                '[1 TO *]',
                $this->relevancyBoostSellerTypesScore
            );
        }

        /*
         * Boost offers with a price above 100 to 1000 and bigger boost for >1000
         */

        if ($this->relevancyBoostPriceScore > 0) {
            $this->query->addRawEdismaxBoostQuery(
                $this->sitecodeKey.self::SEARCH_FIELDS_PRICE_WL,
                '[1000.0 TO *]',
                $this->relevancyBoostPriceScore
            );
            $this->query->addRawEdismaxBoostQuery(
                $this->sitecodeKey.self::SEARCH_FIELDS_PRICE_WL,
                '[100.0 TO 999.0]',
                ($this->relevancyBoostPriceScore / 2)
            );
        }

        /*
         * Boost offers with images
         */
        if ($this->relevancyBoostHasImageScore > 0) {
            $this->query->addRawEdismaxBoostQuery(
                self::SEARCH_FIELDS_IMAGE_COUNT,
                '[1 TO *]',
                $this->relevancyBoostHasImageScore
            );

            /*
             * Boost offers that have more or equal to the limit
             */
            if ($this->relevancyBoostHasImageScore >= $this->relevancyBoostHasImageScore) {
                $this->query->addRawEdismaxBoostQuery(
                    self::SEARCH_FIELDS_IMAGE_COUNT,
                    '['.$this->relevancyBoostLimitImages.' TO *]',
                    $this->relevancyBoostLimitImagesScore
                );
            }
        }

        /*
         * Boost offers with seller country on list
         */
        if ($this->relevancyBoostCountryScore > 0 && count($this->relevancyBoostCountryList)) {
            $this->query->addRawEdismaxBoostQuery(
                self::SEARCH_FIELDS_COUNTRY,
                $this->relevancyBoostCountryList,
                $this->relevancyBoostCountryScore
            );
        }

        /*
         * Boost offers that match with buyer country
         */
        if ($this->relevancyBoostBuyerCountry && $this->relevancyBoostBuyerCountry != '-') {
            $this->query->addRawEdismaxBoostQuery(
                self::SEARCH_FIELDS_COUNTRY,
                $this->relevancyBoostBuyerCountry,
                $this->relevancyBoostCountryMatchScore
            );
        }

        /*
         * Boost offers bumpedUp by VAS
         */
        if ($this->relevancyBoostOfferVAS > 0) {
            $this->query->addRawEdismaxBoostQuery(
                $this->sitecodeKey.'_offer_bumpup_facet_int',
                '[1 TO 1]',
                $this->relevancyBoostOfferVAS
            );
        }

        /**
         * Freshness of the seller, based on substracting the factor to current date.
         */
        $startDateTime = new DateTime();
        $interval = '-'.$this->relevancyBoostFreshSeller;
        $startDateTime->modify($interval);
        $this->query->addRawEdismaxBoostQuery(
            self::SEARCH_FIELDS_SELLER_CREATED,
            '['.$startDateTime->format('Y-m-d\TH:i:s\Z').' TO *]',
            $this->relevancyBoostFreshSellerScore
        );

        $jsonInventoryLimits = $this->relevancyBoostSellerInventoryRules;

        foreach ($jsonInventoryLimits as $limit) {
            $this->query->addRawEdismaxBoostQuery(
                self::SEARCH_FIELDS_SELLER_OFFERS_COUNT,
                $limit['range'],
                $limit['value']
            );
        }

        /*
         * Boost fixed price on offers
         */
        if (is_numeric($this->relevancyBoostFixedPriceScore)) {
            $this->query->addRawEdismaxBoostQuery(
                self::SEARCH_FIELDS_PRICE_TYPE,
                '['.Offer::PRICE_TYPE_FIXED.' TO '.Offer::PRICE_TYPE_FIXED.']',
                $this->relevancyBoostFixedPriceScore
            );
        }
        //****************************************************************************
        /**
         * Boost newer offers (based on sort-index).
         */
        $referenceTime = $this->relevancyTimeBoostReferenceTime;
        $multiplierA = $this->relevancyBoostTimeA;
        $multiplierB = $this->relevancyBoostTimeB;
        // With above settings we get a very linear decrease of score based on age (sort-index) of the offer.
        // Lower B to for example 0.8 to decrease score quicker with aging of the offer.

        // We use NOW/HOUR+1HOUR to stabilize the result set for one hour.
        $this->query->setEdismaxBoost(
            'recip(ms(NOW/HOUR+1HOUR,'.
            self::SEARCH_FIELDS_SORT_INDEX.'),'.
            $referenceTime.','.$multiplierA.','.$multiplierB.')'
        );
        /*
         * Explanation:
         * recip(x, m, a, b) implements f(x) = a/(xm+b) with :
            x : the document age in ms, defined as ms(NOW,<datefield>).
            m : a constant that defines a time scale which is used to apply boost. It should be relative to
            what you consider an old document age (a reference_time) in milliseconds. For example,
            choosing a reference_time of 1 year (3.16e10ms) implies to use its inverse : 3.16e-11 (1/3.16e10 rounded).
            a and b are constants (defined arbitrarily).
            xm = 1 when the document is 1 reference_time old (multiplier = a/(1+b)).
            xm ≈ 0 when the document is new, resulting in a value close to a/b.
            Using the same value for a and b ensures the multiplier doesn't exceed 1 with recent documents.
            With a = b = 1, a 1 reference_time old document has a multiplier of about 1/2, a 2 reference_time old
        document has a multiplier of about 1/3, and so on.

            Make Boosting Stronger:
            Increase m : choose a lower reference_time for example 6 months, that gives us m = 6.33e-11.
            Comparing to a 1 year reference, the multiplier decreases 2x faster as the document age increases.
            Decreasing a and b expands the response curve of the function. This can be very agressive.
        */
    }

    /**
     * @param string $locale
     *
     * @return string
     */
    public function getSearchBaseUrl($locale = null)
    {
        $locale = $locale ?? $this->defaultLocale;
        // BASE PATH
        $basePath = 'search';
        if ($this->requestHas(self::REQUEST_FIELD_SELLER_SLUG)) {
            $basePath = 's/'.$this->requestGet(self::REQUEST_FIELD_SELLER_SLUG);
        }

        return "/{$locale}/{$basePath}/";
    }

    /**
     * Gets the full search url with params.
     *
     * @param string $locale
     *
     * @return string
     */
    public function getSearchUrlFull($locale = null)
    {
        $locale = $locale ?? $this->defaultLocale;
        $searchUrl = $this->getSearchUrl($locale);
        $searchParams = $this->getSearchUrlParametersString();
        if ($searchParams) {
            $searchUrl = $searchUrl.'?'.$searchParams;
        }

        return $searchUrl;
    }

    public function getQueryPath()
    {
        $queryPath = '';
        if ($this->requestHas(self::REQUEST_FIELD_QUERY) && ! empty($this->requestGet(self::REQUEST_FIELD_QUERY))) {
            $queryPath = 'q/'.strtolower(urlencode($this->requestGet(self::REQUEST_FIELD_QUERY))).'/';
        }

        return $queryPath;
    }

    public function getCategoryPath($locale = null, $categoryId = false)
    {
        static $redis = false;
        $locale = $locale ?? $this->defaultLocale;

        if (! $redis) {
            $redis = new RedisService('CatPath:');
        }

        $categoryPath = '';

        if (! $categoryId) {
            if ($this->requestHas(self::REQUEST_FIELD_CAT_L3)) {
                $categoryId = $this->requestGet(self::REQUEST_FIELD_CAT_L3);
            } elseif ($this->requestHas(self::REQUEST_FIELD_CAT_L2)) {
                $categoryId = $this->requestGet(self::REQUEST_FIELD_CAT_L2);
            } elseif ($this->requestHas(self::REQUEST_FIELD_CAT_L1)) {
                $categoryId = $this->requestGet(self::REQUEST_FIELD_CAT_L1);
            }
        }

        if ($categoryId !== false) {
            $myKey = $locale.':'.(int) $categoryId;
            $categoryPath = $redis->getParameter($myKey);

            if (empty($categoryPath)) {
                $categoryPath = '';
                $categoryRepo = $this->entity_manager->getRepository('TradusBundle:Category');
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

    public function getMakePath($locale)
    {
        // MAKE PATH
        $makePath = '';
        if ($this->requestHas(self::REQUEST_FIELD_MAKE)) {
            $makeId = $this->requestGet(self::REQUEST_FIELD_MAKE);
            if (is_array($makeId)) {
                $makeId = implode('+', $makeId);
            }
            $makePath = OfferServiceHelper::localizedMake($locale).$makeId.'/';
        }

        return $makePath;
    }

    public function getCountryPath($locale, $slugify = false, $excluded = false)
    {
        if (! $slugify) {
            $slugify = new Slugify();
        }

        // COUNTRY PATH
        $countryPath = '';
        if ($this->requestHas(self::REQUEST_FIELD_COUNTRY)) {
            $country = $this->requestGet(self::REQUEST_FIELD_COUNTRY);
            if (! is_array($country)) {
                $country = explode(self::DELIMITER_MULTI_VALUE, $country);
            }
            if ($excluded !== false) {
                $country = array_diff($country, [$excluded]);
                if (empty($country)) {
                    return '';
                }
            }
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
                    $countries_search[] = strtolower($slugify->slugify($countries[$shortCode]));
                }
            }
            if (count($countries_search) >= 1) {
                $location_str = implode('+', $countries_search);
                $countryPath = OfferServiceHelper::localizedLocation($locale).$location_str.'/';
            }
        }

        return $countryPath;
    }

    public function getPriceTypePath($locale, $excluded = false)
    {
        /**
         * Add Attribute PRICETYPE to the search.
         */
        $priceTypePath = '';
        if ($this->requestHas(self::REQUEST_FIELD_PRICE_TYPE)) {
            if (! is_array($this->requestGet(self::REQUEST_FIELD_PRICE_TYPE))) {
                $priceType = explode(self::DELIMITER_MULTI_VALUE, $this->requestGet(self::REQUEST_FIELD_PRICE_TYPE));
            } else {
                $priceType = $this->requestGet(self::REQUEST_FIELD_PRICE_TYPE);
            }
            if ($excluded !== false) {
                $priceType = array_diff($priceType, [$excluded]);
                if (empty($priceType)) {
                    return '';
                }
            }
            if ($priceType) {
                $priceTypeSlug = implode('+', $priceType);
                $priceTypePath = OfferServiceHelper::localizedPriceType($locale).$priceTypeSlug.'/';
            }
        }

        return $priceTypePath;
    }

    public function getRegionPath($locale, $excluded = false)
    {
        /**
         * Add Attribute REGION to the search.
         */
        $regionPath = '';
        if ($this->requestHas(self::REQUEST_FIELD_REGION)) {
            if (! is_array($this->requestGet(self::REQUEST_FIELD_REGION))) {
                $region = explode(self::DELIMITER_MULTI_VALUE, $this->requestGet(self::REQUEST_FIELD_REGION));
            } else {
                $region = $this->requestGet(self::REQUEST_FIELD_REGION);
            }
            if ($excluded !== false) {
                $region = array_diff($region, [$excluded]);
                if (empty($region)) {
                    return '';
                }
            }
            if ($region) {
                $regionSlug = implode('+', $region);
                $regionPath = OfferServiceHelper::localizedRegion($locale).$regionSlug.'/';
            }
        }

        return $regionPath;
    }

    public function getTransmissionPath($locale, $excluded = false)
    {
        /**
         * Add Attribute TRANSMISSION to the search.
         */
        $transPath = '';
        if ($this->requestHas(self::REQUEST_FIELD_TRANSMISSION)) {
            if (! is_array($this->requestGet(self::REQUEST_FIELD_TRANSMISSION))) {
                $trans = explode(self::DELIMITER_MULTI_VALUE, $this->requestGet(self::REQUEST_FIELD_TRANSMISSION));
            } else {
                $trans = $this->requestGet(self::REQUEST_FIELD_TRANSMISSION);
            }
            if ($excluded !== false) {
                $trans = array_diff($trans, [$excluded]);
                if (empty($trans)) {
                    return '';
                }
            }
            if ($trans) {
                $transSlug = implode('+', $trans);
                $transPath = OfferServiceHelper::localizedTransmission($locale).$transSlug.'/';
            }
        }

        return $transPath;
    }

    public function getPriceAnalysisTypePath($locale, $excluded = false)
    {
        /**
         * Add Attribute Price Analysis Type to the search.
         */
        $priceAnalysisTypePath = '';
        if ($this->requestHas(self::REQUEST_FIELD_PRICE_ANALYSIS_TYPE)) {
            if (! is_array($this->requestGet(self::REQUEST_FIELD_PRICE_ANALYSIS_TYPE))) {
                $priceAnalysisTypePath = explode(
                    self::DELIMITER_MULTI_VALUE,
                    $this->requestGet(self::REQUEST_FIELD_PRICE_ANALYSIS_TYPE)
                );
            } else {
                $priceAnalysisTypePath = $this->requestGet(self::REQUEST_FIELD_PRICE_ANALYSIS_TYPE);
            }

            if ($excluded !== false) {
                $priceAnalysisTypePath = array_diff($priceAnalysisTypePath, [$excluded]);
                if (empty($priceAnalysisTypePath)) {
                    return '';
                }
            }

            if ($priceAnalysisTypePath) {
                $priceRatingSlug = $this->translator->trans('pricerating').'-';
                $priceAnalysisTypeSlug = implode('+', $priceAnalysisTypePath);
                $priceAnalysisTypePath = $priceRatingSlug.$priceAnalysisTypeSlug.'/';
            }
        }

        return $priceAnalysisTypePath;
    }

    /**
     * @param string   $locale
     * @param int|bool $categoryId
     *
     * @return string
     */
    public function getSearchUrl($locale = null, $categoryId = false)
    {
        $slugify = new Slugify();
        $locale = $locale ?? $this->defaultLocale;

        $basePath = $this->getSearchBaseUrl($locale);
        $queryPath = ! empty($this->getQueryPath()) ? urldecode($this->getQueryPath()) : '';
        $categoryPath = $this->getCategoryPath($locale, $categoryId);
        $makePath = $this->getMakePath($locale);
        $countryPath = $this->getCountryPath($locale, $slugify);
        $priceTypePath = $this->getPriceTypePath($locale);
        $priceAnalysisTypePath = $this->getPriceAnalysisTypePath($locale);
        $transmissionPath = $this->getTransmissionPath($locale);
        $regionPath = $this->getRegionPath($locale);

        return "{$basePath}{$queryPath}{$categoryPath}{$makePath}{$countryPath}{$regionPath}{$priceTypePath}{$priceAnalysisTypePath}{$transmissionPath}";
    }

    /**
     * @param string   $locale
     * @param int|bool $categoryId
     *
     * @return array
     */
    public function getSearchUrlPaths($locale = null, $categoryId = false)
    {
        $slugify = new Slugify();
        $locale = $locale ?? $this->defaultLocale;

        return [
            'basePath' => $this->getSearchBaseUrl($locale),
            'queryPath' => ! empty($this->getQueryPath()) ? urldecode($this->getQueryPath()) : '',
            'categoryPath' => $this->getCategoryPath($locale, $categoryId),
            'makePath' => $this->getMakePath($locale),
            'countryPath' => $this->getCountryPath($locale, $slugify),
            'priceTypePath' => $this->getPriceTypePath($locale),
            'priceAnalysisTypePath' => $this->getPriceAnalysisTypePath($locale),
            'transmissionPath' => $this->getTransmissionPath($locale),
            'regionPath' => $this->getRegionPath($locale),
        ];
    }

    /**
     * @param array $searchFilters
     * @return string
     */
    public function getFormattedFacetUrl($searchFilters = [])
    {
        $basePath = $queryPath = $categoryPath = $makePath = $countryPath = $regionPath = $priceTypePath = $priceAnalysisTypePath = $transmissionPath = '';
        extract($searchFilters);

        return "{$basePath}{$queryPath}{$categoryPath}{$makePath}{$countryPath}{$regionPath}{$priceTypePath}{$priceAnalysisTypePath}{$transmissionPath}";
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
            self::REQUEST_FIELD_YEAR_FROM,
            self::REQUEST_DYNAMIC_YEAR_FROM,
            self::REQUEST_FIELD_YEAR_TO,
            self::REQUEST_FIELD_PRICE_FROM,
            self::REQUEST_FIELD_PRICE_TO,
            self::REQUEST_FIELD_WEIGHT_FROM,
            self::REQUEST_FIELD_WEIGHT_TO,
            self::REQUEST_FIELD_WEIGHT_NET_FROM,
            self::REQUEST_FIELD_WEIGHT_NET_TO,
            self::REQUEST_FIELD_MILEAGE_FROM,
            self::REQUEST_FIELD_MILEAGE_TO,
            self::REQUEST_FIELD_MODEL,
            self::REQUEST_FIELD_VERSION,
        ];

        /*
         * Add the new filters to the Search params
         * This is being used for pagination and change locale
         */
        foreach ($this->searchFilters as $filter) {
            switch ($filter['filterType']) {
                case FilterConfigurationInterface::FILTER_TYPE_RANGE:
                    if ($this->requestHas($filter['filterOptions']['from']['name'])) {
                        $parameterForInUrl[] = $filter['filterOptions']['from']['name'];
                    }
                    if ($this->requestHas($filter['filterOptions']['to']['name'])) {
                        $parameterForInUrl[] = $filter['filterOptions']['to']['name'];
                    }
                    break;

                default:
                    if ($this->requestHas($filter['searchKey'])) {
                        $parameterForInUrl[] = $filter['searchKey'];
                    }

                    break;
            }
        }

        $urlParameters = [];
        foreach ($parameterForInUrl as $parameterName) {
            $parameterValue = $this->requestGet($parameterName);
            if (! $parameterValue) {
                $parameterValue = $this->getParam($parameterName);
            }
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

            /* For multiple choices filters we also want to exclude only one choice
             * So inside the excludedParams we send another key => value pair with 'excluded' as key and the choice we want to exclude as value
             */
            if ($parameterValue && in_array($parameterName, $excludeParams) && isset($excludeParams['excluded'])) {
                $filters = explode(self::DELIMITER_MULTI_VALUE, $parameterValue);
                $filters = array_diff($filters, [$excludeParams['excluded']]);
                $filters = implode(self::DELIMITER_MULTI_VALUE, $filters);
                $urlParameters[$parameterName] = $filters;
            }
        }

        return http_build_query($urlParameters);
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
                $makePath = $this->getMakePath($locale);
                $countryPath = $this->getCountryPath($locale);
                $priceTypePath = $this->getPriceTypePath($locale);
                $transmissionPath = $this->getTransmissionPath($locale);
                $priceAnalysisTypePath = $this->getPriceAnalysisTypePath($locale);
                $regionPath = $this->getRegionPath($locale);
                $prefixPath = "{$basePath}{$queryPath}";
                $sufixPath = "{$makePath}{$countryPath}{$regionPath}{$priceTypePath}{$priceAnalysisTypePath}{$transmissionPath}{$urlParameters}";
                $result = [];
                foreach ($ret as $k => $v) {
                    //$v['search_url'] = "{$prefixPath}".$this->getCategoryPath($locale, $v['id'])."{$sufixPath}";
                    $v['search_url'] = "{$prefixPath}".$v['search_url']."{$sufixPath}";
                    $v['reset_link'] = "{$prefixPath}".$v['reset_link']."{$sufixPath}";
                    /*if($v['parent_category'])
                    $ret['reset_link']
                        = "{$prefixPath}".$this->getCategoryPath($locale, $v['parent_category'])."{$sufixPath}";
                    else
                    $ret['reset_link'] = "{$prefixPath}".$this->getCategoryPath($locale, 99999999) . "{$sufixPath}";*/
                    $result[$k] = $v;
                }

                return $result;
            }
        }

        /** @var CategoryRepository $categoryRepo */
        $categoryRepo = $this->entity_manager->getRepository('TradusBundle:Category');

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
     * @param string $locale
     *
     * @return array
     */
    public function getFacetMakesData(?string $locale = null)
    {
        $urlParameters = $this->getSearchUrlParametersString([
            self::REQUEST_FIELD_PAGE,
            self::REQUEST_FIELD_QUERY,
            self::REQUEST_FIELD_MODEL,
            self::REQUEST_FIELD_VERSION,
        ]);
        if ($urlParameters) {
            $urlParameters = '?'.$urlParameters;
        }
        $locale = $locale ?? $this->defaultLocale;
        $this->translator->setLocale($locale);
        $makesRepo = $this->entity_manager->getRepository('TradusBundle:Make');
        $makes = $makesRepo->findAll();
        $filterUrls = $this->getSearchUrlPaths($locale);
        $facetLookup = [];
        $translatedMake = $this->translator->trans('make');
        /** @var Make $make */
        foreach ($makes as $make) {
            $filterUrls['makePath'] = $translatedMake.'-'.$make->getSlug().'/';
            $search_url = $this->getFormattedFacetUrl($filterUrls);
            $facetLookup[$make->getName()] = [
                'name' => $make->getName(),
                'id' => $make->getSlug(),
                'search_url' => $search_url.$urlParameters,
                'reset_link' => null,
            ];
        }

        return $facetLookup;
    }

    /**
     * @param $make
     * @param $facetsModels
     * @return array
     */
    public function getFacetModelsData($make, $facetsModels = null, ?string $locale = null)
    {
        if (empty($make)) {
            return [];
        }

        /** @var BrandService $brandService */
        $brandService = new BrandService($this->entity_manager);
        $models = $brandService->getModelsByMakes([$make]);

        if (! $models) {
            return [];
        }
        $facetLookup = [];
        $url = $this->getSearchUrl($locale);
        $translatedModel = $this->translator->trans('model');
        foreach ($models[$make] as $slug => $name) {
            if (($facetsModels && isset($facetsModels[$slug]) && $facetsModels[$slug] > 0) || ! isset($facetsModels[$slug])) {
                $facetLookup[$slug] = [
                    'name' => $name,
                    'id' => $slug,
                    'search_url' => $url.'?'.$translatedModel.'='.$slug,
                    'reset_link' => null,
                ];
            }
        }

        return $facetLookup;
    }

    /**
     * @param $model
     * @return array
     */
    public function getFacetVersionsData($model, ?string $locale = null)
    {
        if (empty($model)) {
            return [];
        }
        /** @var BrandService $brandService */
        $brandService = new BrandService($this->entity_manager);
        $versions = $brandService->getVersionsByModels([$model]);

        $facetLookup = [];
        $url = $this->getSearchUrl($locale);
        $translatedModel = $this->translator->trans('model');
        $translatedVersion = $this->translator->trans('version');
        foreach ($versions[$model] as $slug => $name) {
            $facetLookup[$slug] = [
                'name' => $name,
                'id' => $slug,
                'search_url' => $url.'?'.$translatedModel.'='.$model.'&'.$translatedVersion.'='.$slug,
                'reset_link' => null,
            ];
        }

        return $facetLookup;
    }

    /**
     * @param string $locale
     *
     * @return array
     */
    public function getFacetCountriesData($locale = null)
    {
        $urlParameters = $this->getSearchUrlParametersString([
            self::REQUEST_FIELD_PAGE,
            self::REQUEST_FIELD_QUERY,
        ]);
        if ($urlParameters) {
            $urlParameters = '?'.$urlParameters;
        }
        $locale = $locale ?? $this->defaultLocale;
        Locale::setDefault($locale);
        $countries = Intl::getRegionBundle()->getCountryNames();
        $facetLookup = [];
        $translatedLocation = $this->translator->trans('location');
        $filterUrls = $this->getSearchUrlPaths($locale);
        $slugify = new Slugify();
        foreach ($countries as $shortCode => $country) {
            $filterUrls['countryPath'] = $translatedLocation.'-'.$slugify->slugify($country).'/';
            $search_url = $this->getFormattedFacetUrl($filterUrls);
            $facetLookup[$shortCode] = [
                'name' => $country,
                'id' => $shortCode,
                'search_url' => $search_url.$urlParameters,
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
        $urlParameters = $this->getSearchUrlParametersString([
            self::REQUEST_FIELD_PAGE,
            self::REQUEST_FIELD_QUERY,
        ]);
        if ($urlParameters) {
            $urlParameters = '?'.$urlParameters;
        }

        $locale = $locale ?? $this->defaultLocale;
        Locale::setDefault($locale);
        $siteCountry = strtoupper($this->sitecodeService->getSitecodeParameter('default_phone_code'));
        $regionsRepo = $this->entity_manager->getRepository('TradusBundle:Regions');
        $regions = $regionsRepo->findBy(['countryCode' => $siteCountry]);
        $facetLookup = [];
        $translatedRegion = $this->translator->trans('region');
        $filterUrls = $this->getSearchUrlPaths($locale);
        $slugify = new Slugify();
        foreach ($regions as $region) {
            $filterUrls['regionPath'] = $translatedRegion.'-'.$slugify->slugify($region->getName()).'/';
            $search_url = $this->getFormattedFacetUrl($filterUrls);
            $facetLookup[$region->getSlug()] = [
                'name' => $region->getName(),
                'id' => $region->getSlug(),
                'search_url' => $search_url.$urlParameters,
                'reset_link' => null,
            ];
        }

        return $facetLookup;
    }

    /**
     * @param string $locale
     *
     * @return array
     */
    public function getFacetPriceTypeData()
    {
        $priceTypeRepo = $this->entity_manager->getRepository('TradusBundle:PriceType');
        $priceTypes = $priceTypeRepo->findAll();
        $facetLookup = [];
        foreach ($priceTypes as $priceType) {
            $facetLookup[$priceType->getSlug()] = [
                'name' => $priceType->getName(),
                'id' => $priceType->getSlug(),
                'search_url' => null,
                'reset_link' => null,
            ];
        }

        return $facetLookup;
    }

    /**
     * @param string $locale
     *
     * @return array
     */
    public function getFacetTransmissionData()
    {
        $attributeRepo = $this->entity_manager->getRepository('TradusBundle:Attribute');
        $attributeTypes = $attributeRepo->findBy(
            ['name' => self::REQUEST_FIELD_TRANSMISSION]
        );
        $facetLookup = [];
        foreach ($attributeTypes as $attributeType) {
            $facetLookup[$attributeType->getContent()] = [
                'name' => $this->translator->trans($attributeType->getContent()),
                'id' => strtolower($attributeType->getContent()),
                'search_url' => null,
                'reset_link' => null,
            ];
        }

        return $facetLookup;
    }

    /**
     * It's a mess in the code and in my mind.
     * @return array
     */
    public function getFacetPriceAnalysisTypeData()
    {
        $ptLabels = $this->sitecodeService->getSitecodeParameter('pt_labels');
        $priceAnalysisTypeRepo = $this->entity_manager->getRepository('TradusBundle:PriceAnalysisType');
        $priceAnalysisTypes = $priceAnalysisTypeRepo->findBy([], ['order' => 'ASC']);
        $facetLookup = [];
        foreach ($priceAnalysisTypes as $priceAnalysisType) {
            if ($this->sitecodeService->getSitecodeKey() == Sitecodes::SITECODE_KEY_TRADUS ||
                $this->sitecodeService->getSitecodeKey() == Sitecodes::SITECODE_KEY_AUTOTRADER ||
                ($this->sitecodeService->getSitecodeKey() == Sitecodes::SITECODE_KEY_OTOMOTOPROFI &&
                    in_array($priceAnalysisType->getValue(), $this->sitecodeService->getSitecodeParameter('price_analysis_type_array')))) {
                $ptLabel = $ptLabels[$priceAnalysisType->getValue()];
                $facetLookup[$priceAnalysisType->getValue()] = [
                        'name' => $this->translator->trans($ptLabel),
                        'id' => $priceAnalysisType->getSlug(),
                        'extra' => $priceAnalysisType->getSlug(),
                        'value' => $priceAnalysisType->getValue(),
                        'order' => $priceAnalysisType->getOrder(),
                        'search_url' => null,
                        'reset_link' => null,
                    ];
            }
        }

        return $facetLookup;
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
            $keysForOrdering[self::REQUEST_VALUE_SORT_PRICE_ASC] => $this->buildSortEntry(
                self::REQUEST_VALUE_SORT_PRICE_ASC,
                $this->translator->trans(self::REQUEST_VALUE_SORT_PRICE_ASC_LABEL),
                $sort == self::REQUEST_VALUE_SORT_PRICE_ASC ?: false,
                self::REQUEST_VALUE_SORT_PRICE_ASC.$urlParameters
            ),
            $keysForOrdering[self::REQUEST_VALUE_SORT_PRICE_DESC] => $this->buildSortEntry(
                self::REQUEST_VALUE_SORT_PRICE_DESC,
                $this->translator->trans(self::REQUEST_VALUE_SORT_PRICE_DESC_LABEL),
                $sort == self::REQUEST_VALUE_SORT_PRICE_DESC ?: false,
                self::REQUEST_VALUE_SORT_PRICE_DESC.$urlParameters
            ),
            $keysForOrdering[self::REQUEST_VALUE_SORT_RELEVANCY] => $this->buildSortEntry(
                self::REQUEST_VALUE_SORT_RELEVANCY,
                $this->translator->trans(self::REQUEST_VALUE_SORT_RELEVANCY_LABEL),
                $sort == self::REQUEST_VALUE_SORT_RELEVANCY ?: false,
                self::REQUEST_VALUE_SORT_RELEVANCY.$urlParameters
            ),
            $keysForOrdering[self::REQUEST_VALUE_SORT_DATE_DESC] => $this->buildSortEntry(
                self::REQUEST_VALUE_SORT_DATE_DESC,
                $this->translator->trans(self::REQUEST_VALUE_SORT_DATE_DESC_LABEL),
                $sort == self::REQUEST_VALUE_SORT_DATE_DESC ?: false,
                self::REQUEST_VALUE_SORT_DATE_DESC.$urlParameters
            ),
        ];
    }

    /**
     * Function getSelectedCategory.
     * @param null $filter
     * @return mixed|string|null
     */
    public function getSelectedCategory($filter = null)
    {
        if ($this->requestHas(self::REQUEST_FIELD_CATEGORY_ID)) {
            $this->setSearchCategory($this->requestGet(self::REQUEST_FIELD_CATEGORY_ID));
        }

        $catL1 = $this->requestHas(self::REQUEST_FIELD_CAT_L1) ?
            $this->requestGet(self::REQUEST_FIELD_CAT_L1) : null;
        $catL2 = $this->requestHas(self::REQUEST_FIELD_CAT_L2) ?
            $this->requestGet(self::REQUEST_FIELD_CAT_L2) : null;
        $catL3 = $this->requestHas(self::REQUEST_FIELD_CAT_L3) ?
            $this->requestGet(self::REQUEST_FIELD_CAT_L3) : null;

        if (! $catL1) {
            return;
        }

        if (! $catL2) {
            return $catL1;
        }

        if (! $catL3) {
            return ! empty($filter) ? $catL1.','.$catL2 : $catL2;
        }

        return ! empty($filter) ? $catL1.','.$catL2.','.$catL3 : $catL3;
    }

    /**
     * to handle category_id in request and set cat_l1, cat_l2, cat_l3 accordingly.
     * @param int $catId
     */
    public function setSearchCategory(int $catId)
    {
        if (! empty($catId)) {
            $categoryRepo = $this->entity_manager->getRepository('TradusBundle:Category');
            $catsArray = [
                self::REQUEST_FIELD_CAT_L1,
                self::REQUEST_FIELD_CAT_L2,
                self::REQUEST_FIELD_CAT_L3,
            ];
            $category = $categoryRepo->find($catId);
            $categories = $category->getCatsArray();
            if (! empty($categories)) {
                foreach ($categories as $key => $cat) {
                    $this->request->query->set($catsArray[$key], $cat['id']);
                }
            }
        }
        $this->request->query->remove(self::REQUEST_FIELD_CATEGORY_ID);
    }

    /**
     * Function getCategoryExtraSorts.
     *
     * @param array $searchSortOptions search sort options
     *
     * @return array
     */
    public function getCategoryExtraSorts(): array
    {
        $results = [];
        $category = self::getSelectedCategory();
        if (! $category) {
            return $results;
        }
        $redis = new RedisService(Category::REDIS_NAMESPACE_CATEGORY_SORT);
        $sortsAvailable = $redis->getParameter($category);
        if (! $sortsAvailable && $this->sortOptionsActivateFillOnTheFly === 1) {
            $sortsAvailable = $this->buildCategoriesSortAndFilter($redis, $category);
        }

        if ($sortsAvailable) {
            $sortsAvailable = json_decode($sortsAvailable, true);
            if (isset($sortsAvailable['sorts'])) {
                $results['sorts'] = $this->buildSortEntryForExtras($sortsAvailable['sorts']);
                $results['filters'] = isset($sortsAvailable['filters']) ? $sortsAvailable['filters'] : [];
            }
        }

        if (! $results) {
            return [];
        }

        return $results;
    }

    /*
     * function buildCategoriesSortAndFilter
     * @param RedisService $redis
     * @param $category
     * @return mixed
     */
    public function buildCategoriesSortAndFilter(RedisService $redis, $category)
    {
        $categoryRepository = $this->entity_manager->getRepository('TradusBundle:Category');
        $payload = $categoryRepository->getCategorySortPayload($category);
        $newPayload[$category] = json_encode($payload);
        $categoryRepository->setCategorySortRedis($newPayload);

        return $redis->getParameter($category);
    }

    /**
     * Function buildSortEntryForExtras.
     *
     * @param array $sortOptions
     * @param array $searchSortOptions
     *
     * @return array
     */
    public function buildSortEntryForExtras(array $sortOptions): array
    {
        $result = [];
        $sortRequest = $this->getParam(self::REQUEST_FIELD_SORT);
        if (empty($sortRequest)) {
            $sort = $this->sitecodeService->getSitecodeParameter('default_sort');
        }
        $urlParameters = $this->getSearchUrlParametersString(
            [self::REQUEST_FIELD_SORT, self::REQUEST_FIELD_PAGE]
        );
        if ($urlParameters) {
            $urlParameters = '&'.$urlParameters;
        }

        foreach ($sortOptions as $sort) {
            $orderOfSort = array_search($sort, self::ALL_SORT_VALUES);
            switch ($sort) {
                case self::REQUEST_VALUE_SORT_MILEAGE_ASC:
                    $result[$orderOfSort] = $this->buildSortEntry(
                        self::REQUEST_VALUE_SORT_MILEAGE_ASC,
                        $this->translator->trans(self::REQUEST_VALUE_SORT_MILEAGE_ASC_LABEL),
                        $sortRequest == self::REQUEST_VALUE_SORT_MILEAGE_ASC ?: false,
                        self::REQUEST_VALUE_SORT_MILEAGE_ASC.$urlParameters
                    );
                    break;

                case self::REQUEST_VALUE_SORT_MILEAGE_DESC:
                    $result[$orderOfSort] = $this->buildSortEntry(
                        self::REQUEST_VALUE_SORT_MILEAGE_DESC,
                        $this->translator->trans(self::REQUEST_VALUE_SORT_MILEAGE_DESC_LABEL),
                        $sortRequest == self::REQUEST_VALUE_SORT_MILEAGE_DESC ?: false,
                        self::REQUEST_VALUE_SORT_MILEAGE_DESC.$urlParameters
                    );
                    break;

                case self::REQUEST_VALUE_SORT_YEAR_ASC:
                    $result[$orderOfSort] = $this->buildSortEntry(
                        self::REQUEST_VALUE_SORT_YEAR_ASC,
                        $this->translator->trans(self::REQUEST_VALUE_SORT_YEAR_ASC_LABEL),
                        $sortRequest == self::REQUEST_VALUE_SORT_YEAR_ASC ?: false,
                        self::REQUEST_VALUE_SORT_YEAR_ASC.$urlParameters
                    );
                    break;

                case self::REQUEST_VALUE_SORT_YEAR_DESC:
                    $result[$orderOfSort] = $this->buildSortEntry(
                        self::REQUEST_VALUE_SORT_YEAR_DESC,
                        $this->translator->trans(self::REQUEST_VALUE_SORT_YEAR_DESC_LABEL),
                        $sortRequest == self::REQUEST_VALUE_SORT_YEAR_DESC ?: false,
                        self::REQUEST_VALUE_SORT_YEAR_DESC.$urlParameters
                    );
                    break;

                case self::REQUEST_VALUE_SORT_WEIGHT_ASC:
                    $result[$orderOfSort] = $this->buildSortEntry(
                        self::REQUEST_VALUE_SORT_WEIGHT_ASC,
                        $this->translator->trans(self::REQUEST_VALUE_SORT_WEIGHT_ASC_LABEL),
                        $sortRequest == self::REQUEST_VALUE_SORT_WEIGHT_ASC ?: false,
                        self::REQUEST_VALUE_SORT_WEIGHT_ASC.$urlParameters
                    );
                    break;

                case self::REQUEST_VALUE_SORT_WEIGHT_DESC:
                    $result[$orderOfSort] = $this->buildSortEntry(
                        self::REQUEST_VALUE_SORT_WEIGHT_DESC,
                        $this->translator->trans(self::REQUEST_VALUE_SORT_WEIGHT_DESC_LABEL),
                        $sortRequest == self::REQUEST_VALUE_SORT_WEIGHT_DESC ?: false,
                        self::REQUEST_VALUE_SORT_WEIGHT_DESC.$urlParameters
                    );
                    break;

                /* //Uncomment this code in case we need to sort by this weight
                 * case self::REQUEST_VALUE_SORT_WEIGHT_NET_ASC:
                    $result[$orderOfSort] = $this->buildSortEntry(
                        self::REQUEST_VALUE_SORT_WEIGHT_NET_ASC,
                        $this->translator->trans(self::REQUEST_VALUE_SORT_WEIGHT_NET_ASC_LABEL),
                        $sortRequest == self::REQUEST_VALUE_SORT_WEIGHT_NET_ASC ?: false,
                        self::REQUEST_VALUE_SORT_WEIGHT_NET_ASC.$urlParameters
                    );
                    break;

                case self::REQUEST_VALUE_SORT_WEIGHT_NET_DESC:
                    $result[$orderOfSort] = $this->buildSortEntry(
                        self::REQUEST_VALUE_SORT_WEIGHT_NET_DESC,
                        $this->translator->trans(self::REQUEST_VALUE_SORT_WEIGHT_NET_DESC_LABEL),
                        $sortRequest == self::REQUEST_VALUE_SORT_WEIGHT_NET_DESC ?: false,
                        self::REQUEST_VALUE_SORT_WEIGHT_NET_DESC.$urlParameters
                    );
                    break;*/

                case self::REQUEST_VALUE_SORT_HOURS_ASC:
                    $result[$orderOfSort] = $this->buildSortEntry(
                        self::REQUEST_VALUE_SORT_HOURS_ASC,
                        $this->translator->trans(self::REQUEST_VALUE_SORT_HOURS_ASC_LABEL),
                        $sortRequest == self::REQUEST_VALUE_SORT_HOURS_ASC ?: false,
                        self::REQUEST_VALUE_SORT_HOURS_ASC.$urlParameters
                    );
                    break;

                case self::REQUEST_VALUE_SORT_HOURS_DESC:
                    $result[$orderOfSort] = $this->buildSortEntry(
                        self::REQUEST_VALUE_SORT_HOURS_DESC,
                        $this->translator->trans(self::REQUEST_VALUE_SORT_HOURS_DESC_LABEL),
                        $sortRequest == self::REQUEST_VALUE_SORT_HOURS_DESC ?: false,
                        self::REQUEST_VALUE_SORT_HOURS_DESC.$urlParameters
                    );
                    break;
            }
        }

        return $result;
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
     * @param string $locale
     *
     * @return string
     */
    public function getSearchQueryText($locale = null)
    {
        $locale = $locale ?? $this->defaultLocale;
        $categoryRepo = $this->entity_manager->getRepository('TradusBundle:Category');
        $searchQueryText = '';
        $this->translator->setLocale($locale);

        if ($this->requestHas(self::REQUEST_FIELD_QUERY)) {
            $searchQueryText .= $this->requestGet(self::REQUEST_FIELD_QUERY);
        }

        if ($this->requestHas(self::REQUEST_FIELD_CAT_L1)) {
            /** @var Category $category */
            $category = $categoryRepo->find($this->requestGet(self::REQUEST_FIELD_CAT_L1));
            if ($category) {
                if ($searchQueryText != '') {
                    $searchQueryText .= ', ';
                }
                $searchQueryText .= $category->getNameTranslation($locale);
            }
        }

        if ($this->requestHas(self::REQUEST_FIELD_CAT_L2)) {
            /** @var Category $category */
            $category = $categoryRepo->find($this->requestGet(self::REQUEST_FIELD_CAT_L2));
            if ($category) {
                if ($searchQueryText != '') {
                    $searchQueryText .= ', ';
                }
                $searchQueryText .= $category->getNameTranslation($locale);
            }
        }
        if ($this->requestHas(self::REQUEST_FIELD_CAT_L3)) {
            /** @var Category $category */
            $category = $categoryRepo->find($this->requestGet(self::REQUEST_FIELD_CAT_L3));
            if ($category) {
                if ($searchQueryText != '') {
                    $searchQueryText .= ', ';
                }
                $searchQueryText .= $category->getNameTranslation($locale);
            }
        }

        if ($this->requestHas(self::REQUEST_FIELD_MAKE)) {
            if ($searchQueryText != '') {
                $searchQueryText .= ', ';
            }
            $makes = $this->requestGet(self::REQUEST_FIELD_MAKE);
            if (! is_array($makes)) {
                $makes = explode('+', $makes);
            }
            foreach ($makes as $make) {
                $searchQueryText .= ucfirst($make).', ';
            }
            $searchQueryText = trim($searchQueryText);
            $searchQueryText = rtrim($searchQueryText, ',');
        }

        if ($this->requestHas(self::REQUEST_FIELD_COUNTRY)) {
            $countries_list = Intl::getRegionBundle()->getCountryNames($locale);
            foreach ($this->requestGet(self::REQUEST_FIELD_COUNTRY) as $country) {
                if ($searchQueryText != '') {
                    $searchQueryText .= ', ';
                }
                $searchQueryText .= $countries_list[$country];
            }
        }

        if ($searchQueryText == '') {
            if ($this->requestHas(self::REQUEST_FIELD_SELLER) && $this->requestHas(self::REQUEST_FIELD_SELLER_SLUG)) {
                $sellerName = str_replace('-', ' ', $this->request->query->get(self::REQUEST_FIELD_SELLER_SLUG));
                $searchQueryText = ucwords($sellerName);
            } else {
                $searchQueryText = $this->translator->trans('All categories');
            }
        }

        return $searchQueryText;
    }

    /**
     * Get the official result object.
     *
     * @return Result
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Make the result compatible with current application expects.
     *
     * @param Result $result
     * @param $topAds
     * @return array
     */
    public function getTradusResult(Result $result, $topAds = [])
    {
        $count = 0;
        $topAdsDocs = [];
        // Add the top ads to the list of results
        $data['top_offer_ids'] = [];
        $top_offer_ids = [];
        $data['offer_ids'] = [];
        $sellers = [];
        if ($topAds instanceof Result) {
            if (count($topAds->getDocuments())) {
                $topAdsDocs = $topAds->getData()['response']['docs'];
                foreach ($topAdsDocs as $topKey => $doc) {
                    $top_offer_ids[] = $doc['offer_id'];
                    $topAdsDocs[$topKey]['seller'] = &$sellers[$doc['seller_id']];
                }
                $top_offer_ids_keys = array_keys($top_offer_ids);
                foreach ($top_offer_ids_keys as $key) {
                    $data['top_offer_ids'][] = $top_offer_ids[$key];
                }
            }
        }

        $data['result_count'] = $result->getNumberFound();

        $searchResult = $result->getData();
        $docs = $searchResult['response']['docs'];
        if (count($result->getDocuments())) {
            foreach ($docs as $docKey => $doc) {
                if (in_array($doc['offer_id'], $data['top_offer_ids'])) {
                    continue;
                }
                $data['offer_ids'][] = $doc['offer_id'];
                $docs[$docKey]['seller'] = &$sellers[$doc['seller_id']];
            }
        }
        if (isset($data['facet_counts'])) {
            $data['facet'] = $data['facet_counts'];
        }

        $searchResult['response']['docs'] = array_merge($topAdsDocs, $docs);
        $this->getOfferSellers($sellers);
        $data = array_merge($data, $searchResult);

        return $data;
    }

    public function getOfferSellers(&$sellers)
    {
        if (! empty($sellers) && ! empty($this->sellerClient)) {
            $query = $this->sellerClient->getQuerySelect();
            $sellerIds = array_keys($sellers);
            $query->addQuery(SellerInterface::SOLR_FIELD_SELLER_ID, $sellerIds);
            $ret = $this->sellerClient->execute($query);
            if (! empty($ret->getNumberFound())) {
                foreach ($ret->getDocuments() as $doc) {
                    $sellers[$doc[SellerInterface::SOLR_FIELD_SELLER_ID]] = $doc;
                }
            }
        }
    }

    public function getSeoContents($result)
    {
        return [
            'seller' => $this->getSeoSellerContents($result),
            'price' => $this->getFixedOffersPriceRange(),
            ];
    }

    public function getSeoSellerContents($result)
    {
        $response = [];
        if (! empty($result['facet_counts']['facet_fields']['seller_id'])) {
            $facetSellerFields = array_filter($this->result->convertToKeyValueArray($result['facet_counts']['facet_fields']['seller_id']));
            if (! empty($facetSellerFields)) {
                $sellersCount = count($facetSellerFields);
                $topSellers = (array_slice($facetSellerFields, 0, 5, true));
                $this->getOfferSellers($topSellers);

                foreach ($topSellers as $sKey => $seller) {
                    $topSellers[$sKey] = [
                        'id' => $seller['id'],
                        'label' => $seller['company_name'],
                        'url' => $seller['url'],
                    ];
                }

                $response = [
                    'sellersCount' => $sellersCount,
                    'topSellers' => $topSellers,
                ];
            }
        }

        return $response;
    }

    public function getFixedOffersPriceRange()
    {
        $mysql_helper = new MysqlHelper($this->entity_manager->getConnection());
        $exchangeRates = new CurrencyExchange($mysql_helper->getConnection());
        $priceFrom = 10;
        $priceTo = 10000000000;
        $query = clone $this->query;
        $field = $this->sitecodeKey.self::SEARCH_FIELDS_PRICE_WL;
        $query->setRows(0)->disableFacet()->clearStatsFields()->clearFacetFields();
        $query->addQuery(self::REQUEST_FIELD_PRICE_TYPE, 'fixed');
//        $this->setCurrencyPrice($priceFrom, $priceTo);
        $query->addRangeQuery(
            $this->sitecodeKey.self::SEARCH_FIELDS_PRICE_WL,
            $priceFrom,
            $priceTo
        );
        $query->addStatsField($field);
        $searchResult = $this->client->execute($query);
        $priceStats = $searchResult->getStatsField($field);
        if ($searchResult->getNumberFound() < 3 || $priceStats['max'] == 0 || $priceStats['min'] >= $priceStats['max']) {
            return [];
        }
        $offerCurrency = SitecodeService::getSitecode($this->sitecodeId)['currency'];
        $priceStats['min'] = floor($priceStats['min'] / 10) * 10;
        $priceStats['max'] = ceil($priceStats['max'] / 100) * 100;

        return [
            'min' => $priceStats['min'],
            'max' => $priceStats['max'],
            'min_data_price' => $exchangeRates->getExchangeRates($priceStats['min'], $offerCurrency, false),
            'max_data_price' => $exchangeRates->getExchangeRates($priceStats['max'], $offerCurrency, false),
        ];
    }

    /**
     * Will find facet values for current search without query on given field.
     *
     * @param        $facetValues
     * @param string $field
     *
     * @return mixed
     */
    public function mergeFacetValuesForNotSelectedValues($facetValues, string $field)
    {
        $query = clone $this->query;
        $query->setRows(0)->disableStats()->clearStatsFields()->clearFacetFields();

        switch ($field) {
            case self::REQUEST_FIELD_MAKE:
            case self::REQUEST_FIELD_MODEL:
                $query->replaceRawQueryField(self::SEARCH_FIELDS_MODEL, '*');
            // no break
            case self::REQUEST_FIELD_VERSION:
                $query->replaceRawQueryField(self::SEARCH_FIELDS_VERSION, '*');
                break;
        }

        if (in_array($field, [self::REQUEST_FIELD_MODEL, self::REQUEST_FIELD_VERSION])) {
            $field .= '_facet_string';
        }

        $query->addFacetFields($field);
        $query->replaceRawQueryField($field, '*');

        $searchResult = $this->client->execute($query);

        $additionalFacetData = $searchResult->getFacetFields();

        if (in_array($field, [self::SEARCH_FIELDS_MAKE, self::SEARCH_FIELDS_MODEL, self::SEARCH_FIELDS_VERSION])) {
            asort($additionalFacetData);
        }
        if (isset($additionalFacetData[$field]) && count($additionalFacetData[$field])) {
            // Do a foreach because array_merge reorders the results to alphabetic
            foreach ($additionalFacetData[$field] as $key => $value) {
                if ((isset($facetValues[$key]) && $facetValues[$key] == 0) || ! isset($facetValues[$key])) {
                    $facetValues[$key] = $value;
                }
            }
        }
        $facetValues = array_filter($facetValues);

        return $facetValues;
    }

    /**
     * @return array
     */
    public function getFacetDataResults()
    {
        $result = [];
        $locale = $this->request->query->get('locale') ?: $this->defaultLocale;

        // Get range data
        $statsFields = $this->result->getStatsFields();

        if (! empty($statsFields) && is_array($statsFields)) {
            foreach ($statsFields as $fieldName => $fieldValues) {
                $fieldKey = $fieldName;
                if ($fieldKey == $this->sitecodeKey.self::SEARCH_FIELDS_PRICE_WL) {
                    $fieldKey = $fieldName = self::SEARCH_FIELDS_PRICE;
                }

                if (in_array($fieldKey, [self::SEARCH_FIELDS_WEIGHT, self::SEARCH_FIELDS_WEIGHT_NET,
                    self::SEARCH_FIELDS_MILEAGE, ])) {
                    $fieldKey = str_replace('_facet_double', '', $fieldKey);
                    $fromValue = (float) $fieldValues['min'];
                    $toValue = (float) $fieldValues['max'];
                } else {
                    $fromValue = (int) $fieldValues['min'];
                    $toValue = (int) $fieldValues['max'];
                }

                $result[$fieldKey] = $this->getRangeFacetData(
                    $fieldName,
                    $this->requestGet($fieldKey.'_from'),
                    $this->requestGet($fieldKey.'_to'),
                    (float) $fromValue,
                    (float) $toValue
                );
            }
        }

        $facetFields = $this->result->getFacetFields();
        /*
         * @todo For the moment we leave the facets as they are.
         * @todo Uncomment this in the future when we have no static filters
         */
        /*foreach ($this->searchFilters as $filter) {
            if (isset($facetFields[$filter['solrKey']])) {
                $result[$filter['solrKey']] = $filter;
                $result[$filter['solrKey']]['items'] = $facetFields[$filter['solrKey']];
            }
        }*/
        foreach ($facetFields as $facetName => $facetValues) {
            if ($facetName == self::SEARCH_FIELDS_MAKE) {
                $facetLookup = $this->getFacetMakesData($locale);
                $selected = $this->requestGet(self::REQUEST_FIELD_MAKE);
                // First get me the first 5 most popular makes
                // Stupid  code that adds complexity
                $popularFacetValues = $facetValues;
                if ($this->requestHas(self::REQUEST_FIELD_MAKE)) {
                    $popularFacetValues = $this->mergeFacetValuesForNotSelectedValues($facetValues, self::SEARCH_FIELDS_MAKE);
                }

                $result['popular_make'] = $this->transformFacetData(
                    'popular_make',
                    $selected,
                    $facetLookup,
                    $popularFacetValues,
                    [],
                    'popular_make',
                    self::FIELD_TYPE_MULTIPLE
                );

                // We also need to manually remove the 'other' make because...reasons
                // So we get the first 6 (usually 5 + the other) so we do not foreach through all of them
                $popularMakeSlugs = [];
                $result['popular_make']['items'] = array_slice($result['popular_make']['items'], 0, 6);
                foreach ($result['popular_make']['items'] as $key => $item) {
                    if ($item['value'] == 'other') {
                        unset($result['popular_make']['items'][$key]);
                    }

                    if ($item['value'] != 'other') {
                        $popularMakeSlugs[] = $item['label'];
                    }
                }

                // In case there is no other in the main makes (hopefully) we only want the first 5
                $result['popular_make']['items'] = array_slice($result['popular_make']['items'], 0, 5);
                // End of stupid code

                ksort($facetValues);
                // Get the additional values if the search was done without  make
                if ($this->requestHas(self::REQUEST_FIELD_MAKE)) {
                    $facetValues = $this->mergeFacetValuesForNotSelectedValues($facetValues, self::SEARCH_FIELDS_MAKE);
                }

                // we need to remove the popular makes from the all list so we add some more complexity here
                // Hope we will redo the filtering soon and get rid of this shit
                foreach ($popularMakeSlugs as $slug) {
                    unset($facetValues[$slug]);
                }

                // Add the 'other' value last one
                if (isset($facetValues['Other'])) {
                    $other = $facetValues['Other'];
                    unset($facetValues['Other']);
                    $facetValues['Other'] = $other;
                }
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

            // Copy paste the same magic for model
            if ($facetName == self::SEARCH_FIELDS_MODEL) {
                $facetLookup = $this->getFacetModelsData(
                    $this->requestGet(self::REQUEST_FIELD_MAKE),
                    $facetValues,
                    $locale
                );

                // Get the additional values if the search was done without  priceType
                if ($this->requestHas(self::REQUEST_FIELD_MODEL)) {
                    $facetValues = $this->mergeFacetValuesForNotSelectedValues(
                        $facetValues,
                        self::REQUEST_FIELD_MODEL
                    );
                }

                $selected = $this->requestGet(self::REQUEST_FIELD_MODEL);
                $facetKey = str_replace('_facet_string', '', $facetName);
                $result[$facetKey] = $this->transformFacetData(
                    $facetKey,
                    $selected,
                    $facetLookup,
                    $facetValues,
                    [],
                    $facetKey,
                    self::FIELD_TYPE_MULTIPLE
                );
            }
            // Copy paste the same magic for model

            // Copy paste the same magic for version
            if ($facetName == self::SEARCH_FIELDS_VERSION) {
                $facetLookup = $this->getFacetVersionsData(
                    $this->requestGet(self::REQUEST_FIELD_MODEL),
                    $locale
                );

                // Get the additional values if the search was done without  priceType
                if ($this->requestHas(self::REQUEST_FIELD_VERSION)) {
                    $facetValues = $this->mergeFacetValuesForNotSelectedValues(
                        $facetValues,
                        self::REQUEST_FIELD_VERSION
                    );
                }

                $selected = $this->requestGet(self::REQUEST_FIELD_VERSION);
                $facetKey = str_replace('_facet_string', '', $facetName);
                $result[$facetKey] = $this->transformFacetData(
                    $facetKey,
                    $selected,
                    $facetLookup,
                    $facetValues,
                    [],
                    $facetKey,
                    self::FIELD_TYPE_MULTIPLE
                );
            }
            // Copy paste the same magic for version

            if ($facetName == self::SEARCH_FIELDS_PRICE_TYPE) {
                $facetName = self::REQUEST_FIELD_PRICE_TYPE;
                $facetLookup = $this->getFacetPriceTypeData();

                // Get the additional values if the search was done without  priceType
                if ($this->requestHas(self::REQUEST_FIELD_PRICE_TYPE)) {
                    $facetValues = $this->mergeFacetValuesForNotSelectedValues(
                        $facetValues,
                        self::REQUEST_FIELD_PRICE_TYPE
                    );
                }

                $selected = $this->requestGet(self::REQUEST_FIELD_PRICE_TYPE);
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

            if ($facetName == self::SEARCH_FIELDS_TRANSMISSION) {
                $facetName = self::REQUEST_FIELD_TRANSMISSION;
                $facetLookup = $this->getFacetTransmissionData();

                // Get the additional values if the search was done without  priceType
                if ($this->requestHas(self::REQUEST_FIELD_TRANSMISSION)) {
                    $facetValues = $this->mergeFacetValuesForNotSelectedValues(
                        $facetValues,
                        self::SEARCH_FIELDS_TRANSMISSION
                    );
                }

                $selected = $this->requestGet(self::REQUEST_FIELD_TRANSMISSION);
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

            $displayFacetPAT = $this->allowFacetForPriceAnalysisType();

            // I have no idea what i'm doing
            if ($facetName == $this->sitecodeKey.self::SEARCH_FIELDS_PRICE_ANALYSIS_TYPE_WL && $displayFacetPAT) {
                $facetName = self::REQUEST_FIELD_PRICE_ANALYSIS_TYPE;
                $facetLookup = $this->getFacetPriceAnalysisTypeData();

                // Get the additional values if the search was done without priceType
                if ($this->requestHas(self::REQUEST_FIELD_PRICE_ANALYSIS_TYPE)) {
                    $facetValues = $this->mergeFacetValuesForNotSelectedValues(
                        $facetValues,
                        $this->sitecodeKey.self::SEARCH_FIELDS_PRICE_ANALYSIS_TYPE_WL
                    );
                }

                /**
                 * Because we get one key as 'false' we transform it to zero
                 * We also sort again by the predefined order
                 * Probably not the best way to do this but it's working.
                 */
                $withIntKeys = [];
                foreach ($facetValues as $key => $value) {
                    $withIntKeys[(int) $key] = $value;
                }
                $facetValues = array_replace(array_fill_keys(array_keys($facetLookup), null), $withIntKeys);
                /* THE END */

                $selected = $this->requestGet(self::REQUEST_FIELD_PRICE_ANALYSIS_TYPE);
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
                $facetLookup = $this->getFacetRegionsData($locale);
                // Get the additional values if the search was done without  country
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
            /* End of the shitty copy paste */

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

    public function allowFacetForPriceAnalysisType(): bool
    {
        $cat_1 = null;
        $cat_2 = null;
        $cat_3 = null;

        if ($this->requestHas(self::REQUEST_FIELD_CAT_L1)) {
            $cat_1 = $this->requestGet(self::REQUEST_FIELD_CAT_L1);
        }

        if ($this->requestHas(self::REQUEST_FIELD_CAT_L2)) {
            $cat_2 = $this->requestGet(self::REQUEST_FIELD_CAT_L2);
        }

        if ($this->requestHas(self::REQUEST_FIELD_CAT_L3)) {
            $cat_3 = $this->requestGet(self::REQUEST_FIELD_CAT_L3);
        }

        if (in_array($cat_1, $this->priceAnalysisCategories) && $cat_1) {
            return true;
        }

        if (in_array($cat_2, $this->priceAnalysisCategories) && $cat_2) {
            return true;
        }

        if (in_array($cat_3, $this->priceAnalysisCategories) && $cat_3) {
            return true;
        }

        return false;
    }

    public function getRangeFacetData($facetName, $minValue, $maxValue, $minLimit = 0, $maxLimit = 6250000, $step = 1)
    {
        $result['name'] = $facetName;
        $result['label'] = $facetName;

        $result['values']['min'] = $minValue;
        $result['values']['max'] = $maxValue;

        $result['values']['step'] = $step;
        $result['limits']['min'] = $minLimit;
        $result['limits']['max'] = $maxLimit;
        $result['type'] = self::FIELD_TYPE_RANGE;
        $result['search'] = $facetName;

        if ($facetName == self::SEARCH_FIELDS_PRICE) {
            $repo = $this->entity_manager->getRepository('TradusBundle:ExchangeRate');
            $result['rates'] = $repo->getExchangeRates();
            $result['pricelist'] = $this->getPriceRangeList($result);
        }
        if ($facetName == self::SEARCH_FIELDS_YEAR) {
            $maxYear = (new DateTime())->format('Y') + 1;
            if ($maxLimit > $maxYear) {
                $result['limits']['max'] = $maxYear;
            }
        }

        return $result;
    }

    private function getPriceRangeList(array $result)
    {
        $priceArr = (new SearchFilterService())->getPriceFilters();
        $currency = $this->requestFetch($this->sitecodeKey.self::SEARCH_FIELDS_CURRENCY_WL) ?? CurrencyExchange::DEFAULT_CURRENCY;

        if (array_key_exists($currency, $result['rates'])) {
            $max_exchange = ceil($result['limits']['max'] * $result['rates'][$currency]);
            while (max($priceArr) < $max_exchange) {
                $new_value = max($priceArr) * 2;
                array_push($priceArr, $new_value);
            }
        }

        return $priceArr;
    }

    private function buildFacet($id, string $value, array $facetLookup, $facetCount = 0, $checked = false): array
    {
        return [
            'label' => isset($facetLookup[$id]['name']) ? $facetLookup[$id]['name'] : '',
            'value' => $value,
            'id' => $id,
            'url' => isset($facetLookup[$id]['search_url']) ? $facetLookup[$id]['search_url'] : '',
            'resetLink' => @$facetLookup[$id]['reset_link'],
            'extra' => isset($facetLookup[$id]['extra']) ? $facetLookup[$id]['extra'] : '',
            'resultCount' => $facetCount,
            'checked' => $checked,
        ];
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
            } elseif (in_array($facetName, [self::REQUEST_FIELD_PRICE_TYPE, self::REQUEST_FIELD_PRICE_ANALYSIS_TYPE,
                    self::REQUEST_FIELD_TRANSMISSION, ]) && $facetCount > 0 && ! empty($facetLookup[$facetId])) {
                $facetLookup[$facetId]['search_url'] = '';
                $facetLookup[$facetId]['reset_link'] = '';
                $item = $this->buildFacet($facetId, $facetId, $facetLookup, $facetCount);

                $result['items'][] = $item;
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

        /* @todo Remove this shit once we have the new filters in the frontend */
        if ($source == 'price_analysis_type') {
            foreach ($result['items'] as $key => $item) {
                if ($item['label'] == '') {
                    unset($result['items'][$key]);
                }
            }
        }
        /* Remove this   */

        /* Other facet always at the bottom */
        if (! empty($otherFacetList)) {
            $result['items'] = array_merge($result['items'], $otherFacetList);
        }

        return $result;
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
            case $facetName == self::SEARCH_FIELDS_MAKE &&
                 in_array($item['value'], Make::OTHERS_VALUE_LIST):
                $otherFacetList[] = $item;

                break;
            case in_array($facetName, self::SEARCH_ALL_CATEGORIES) && $isOtherCategory:
                $otherFacetList[] = $item;

                break;
            default:
                $items[] = $item;
        }
    }

    /**
     * Add a request param.
     *
     * If you add a request param that already exists the param will be converted into a multivalue param,
     * unless you set the overwrite param to true.
     *
     * Empty params are not added to the request. If you want to empty a param disable it you should use
     * remove param instead.
     *
     * @param string       $key
     * @param string|array $value
     * @param bool         $overwrite
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
     * @param float $priceFrom
     * @param float $priceTo
     *
     * @return array
     * @throws CurrencyExchangeException
     */
    private function setCurrencyPrice(float &$priceFrom, float &$priceTo)
    {
        $currency = $this->requestFetch($this->sitecodeKey.self::SEARCH_FIELDS_CURRENCY_WL) ?? CurrencyExchange::DEFAULT_CURRENCY;

        if ($currency !== CurrencyExchange::DEFAULT_CURRENCY) {
            $mysqlHelper = new MysqlHelper($this->entity_manager->getConnection());
            $exchangeRates = new CurrencyExchange($mysqlHelper->getConnection());
            $priceFrom = $exchangeRates->getEuroValue($priceFrom, $currency);
            $priceTo = $exchangeRates->getEuroValue($priceTo, $currency);
        }

        return [$priceFrom, $priceTo];
    }

    /**
     * @return int
     */
    public function getFilterCount()
    {
        $sortParam = 0;
        $this->requestHas('sort') ? $sortParam++ : '';
        $this->requestHas('seller_id') ? $sortParam++ : '';
        $this->requestHas('offer_id') ? $sortParam++ : '';

        return count($this->getParams()) - 1 - $sortParam;
    }

    /**
     * @param string $paramName
     *
     * @return bool|mixed
     */
    public function getParam(String $paramName)
    {
        if (isset($this->params[$paramName])) {
            return $this->params[$paramName];
        }

        return false;
    }

    /**
     * @param string $paramName
     *
     * @return bool
     */
    public function requestHas(String $paramName)
    {
        return $this->request->query->has($paramName)
                && (! empty($this->request->query->get($paramName)) || $this->request->query->get($paramName) === 0);
    }

    /**
     * @param string $paramName
     *
     * @return mixed
     */
    public function requestFetch(String $paramName)
    {
        $paramValue = $this->request->query->get($paramName);
        if (is_string($paramValue)) {
            $paramValue = trim($paramValue);
        }

        return $paramValue;
    }

    /**
     * @param string $paramName
     *
     * @return mixed
     */
    public function requestGet(String $paramName)
    {
        $paramValue = $this->requestFetch($paramName);
        if (! empty($paramValue)) {
            $this->addParam($paramName, $paramValue);
        }

        return $paramValue;
    }

    /**
     * @param $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * @param $sellerClient
     */
    public function setSellerClient($client)
    {
        $this->sellerClient = $client;
    }

    /**
     * @param $sitecodeId
     */
    public function setSitecodeId($sitecodeId)
    {
        $this->sitecodeId = $sitecodeId;
    }

    /**
     * @return Query
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param string $relevancyBoostBuyerCountry
     */
    public function setRelevancyBoostBuyerCountry(?string $relevancyBoostBuyerCountry = null): void
    {
        $this->relevancyBoostBuyerCountry = empty($relevancyBoostBuyerCountry) ? null : $relevancyBoostBuyerCountry;
    }

    /**
     * @return bool
     */
    public static function isSearchingById($query = null): bool
    {
        if ($query && is_numeric($query) && strlen($query) >= 7) {
            return true;
        }

        return false;
    }

    /**
     * For the homepage search box we are getting the results from solr based on user interaction.
     */
    public function getHomepageResults($request)
    {
        $this->query = $this->client->getQuerySelect();
        $this->request = $request;
        $this->resetParams();

        // We add the make. We are sending the make name from the front so we can use the search directly
        if ($this->requestHas(self::REQUEST_FIELD_MAKE)) {
            $makesRequest = $this->requestGet(self::REQUEST_FIELD_MAKE);
            $searchMakes = $this->getMakeValuesAsIndexed([$makesRequest]);
            $this->query->addQuery(self::SEARCH_FIELDS_MAKE, $searchMakes);
        }

        // The model field is called 'model_face_string' in solr
        if ($this->requestHas(self::REQUEST_FIELD_MODEL)) {
            $this->query->addQuery(self::SEARCH_FIELDS_MODEL, $this->requestGet(self::REQUEST_FIELD_MODEL));
        }

        // The country field is called 'seller_country' in solr
        if ($this->requestHas(self::REQUEST_FIELD_COUNTRY)) {
            $this->query->addQuery(self::SEARCH_FIELDS_COUNTRY, $this->requestGet(self::REQUEST_FIELD_COUNTRY));
        }

        // The region field is called 'item_region_facet_string' in solr
        if ($this->requestHas(self::REQUEST_FIELD_REGION)) {
            $this->query->addQuery(self::SEARCH_FIELDS_REGION, $this->requestGet(self::REQUEST_FIELD_REGION));
        }

        // We always get the L1 category so if we receive the L2, also add that to the search
        $this->query->addQuery(self::SEARCH_FIELDS_CATEGORY, $this->requestGet(self::REQUEST_FIELD_CAT_L1));
        if ($this->requestHas(self::REQUEST_FIELD_CAT_L2)) {
            $this->query->addQuery(self::SEARCH_FIELDS_CATEGORY, $this->requestGet(self::REQUEST_FIELD_CAT_L2));
        }

        // Because we only have the year from filter we will add the current year as the year to
        if ($this->requestHas(self::REQUEST_DYNAMIC_YEAR_FROM)) {
            $this->query->addRangeQuery(
                self::SEARCH_FIELDS_YEAR,
                $this->requestGet(self::REQUEST_DYNAMIC_YEAR_FROM),
                $this->requestGet(self::REQUEST_FIELD_YEAR_TO)
            );
        }

        // Because we only have the mileage from filter we will add 0 as mileage from
        if ($this->requestHas(self::REQUEST_FIELD_MILEAGE_TO)) {
            $this->query->addRangeQuery(
                self::SEARCH_FIELDS_MILEAGE_STRING,
                0,
                $this->requestGet(self::REQUEST_FIELD_MILEAGE_TO)
            );
        }

        // Because we only have the weight to filter, we will add 0 as weight from
        if ($request->get(self::REQUEST_FIELD_WEIGHT_TO)) {
            $this->query->addRangeQuery(
                self::SEARCH_FIELDS_WEIGHT,
                0,
                $request->get(self::REQUEST_FIELD_WEIGHT_TO)
            );
        }

        // Because we only have the price to we set the price from to zero. We also take in consideration the currency
        if ($this->requestHas(self::REQUEST_FIELD_PRICE_TO)) {
            $priceFrom = 0;
            $priceTo = $this->requestGet(self::REQUEST_FIELD_PRICE_TO);
            $this->setCurrencyPrice($priceFrom, $priceTo);
            $this->query->addRangeQuery(
                $this->sitecodeKey.self::SEARCH_FIELDS_PRICE_WL,
                $priceFrom,
                $priceTo
            );
        }

        // default facets fields
        $facetFieldsArray = [
            self::SEARCH_FIELDS_MAKE,
            self::SEARCH_FIELDS_MODEL,
            self::SEARCH_FIELDS_REGION,
            self::SEARCH_FIELDS_CATEGORY,
            self::SEARCH_FIELDS_COUNTRY,
            $this->sitecodeKey.self::SEARCH_FIELDS_PRICE_WL,
        ];

        // default stats fields
        $statsFieldsArray = [
//            self::SEARCH_FIELDS_PRICE,
            $this->sitecodeKey.self::SEARCH_FIELDS_PRICE_WL,
            self::SEARCH_FIELDS_YEAR,
            self::SEARCH_FIELDS_MILEAGE,
            self::SEARCH_FIELDS_WEIGHT,
        ];

        // Add the dynamic facets
        foreach ($this->searchFilters as $filter) {
            $facetFieldsArray[] = $filter['solrKey'];
            $statsFieldsArray[] = $filter['solrKey'];
        }

        /* Enable facet data retrieval in search */
        $this->query->enableFacet()->setFacetFields($facetFieldsArray);

        /* Enable stats data retrieval in search */
        $this->query->enableStats()->setStatsFields($statsFieldsArray);

        $this->query->setRows(0);

        return $this->execute($this->query);
    }

    public function getTransmissionBySlug(array $trans): array
    {
        $response = [];
        if (! empty($trans)) {
            $transData = $this->getFacetTransmissionData();
            foreach ($transData as $key => $value) {
                if (in_array(strtolower($key), $trans)) {
                    $response[] = $key;
                }
            }
        }

        return $response;
    }

    /**
     * Get the search filters based on category
     * We are looking in Redis and if we don't find anything we build it from db and save in Redis.
     *
     * @throws NonUniqueResultException
     */
    public function getSearchFilters($search_page = null)
    {
        $results = [];
        $category = $this->getSelectedCategory(true);
        if (! $category) {
            return $results;
        }

        $redis = new RedisService(Category::REDIS_NAMESPACE_CATEGORIES_FILTERS);
        $results = $redis->getParameter($category);
        if (! $results) {
            $results = $this->buildFiltersForCategory($category, $search_page);
        }

        $this->setSearchFilters(json_decode($results, true));

        return $results;
    }

    /**
     * Building the filters based on category id and saving in Redis for later use.
     *
     * @param $category
     *
     * @return mixed
     * @throws NonUniqueResultException
     */
    public function buildFiltersForCategory($category, $search_page = null)
    {
        /** @var FilterConfigurationRepository $filterRepository */
        $filterRepository = $this->entity_manager->getRepository('TradusBundle:FilterConfiguration');
        $payload = $filterRepository->getCategoryFiltersPayload($category, $search_page);
        $newPayload[$category] = json_encode($payload);
        $filterRepository->setCategoryFiltersRedis($newPayload);

        return $newPayload[$category];
    }

    /**
     * We are putting the filters to the class object for easier access from different places.
     *
     * @param array $searchFilters
     */
    public function setSearchFilters(array $searchFilters): void
    {
        $this->searchFilters = $searchFilters;
    }

    /*
     * Get the Attribute Groups
     *
     * @return array
     */
    public function getAttributeGroups(): array
    {
        $groupList = [];
        $attributeGroupRepository = $this->entity_manager->getRepository('TradusBundle:AttributeGroup');
        $groupResult = $attributeGroupRepository->findAll();

        foreach ($groupResult as $group) {
            $id = $group->getId();
            $groupList[$id] = [
                'id' => $id,
                'groupName' => $this->translator->trans($group->getName()),
                'collapsable'=> $group->getCollapsableOption() ? true : false,
                'translationKey' => $group->getTranslationKey(),
                'items' => [],
            ];
        }

        return $groupList;
    }

    /**
     * Returns the list of filters with all necessary data for the front end filters.
     *
     * @param string locale string
     *
     * @return array
     */
    public function getFiltersData($locale = null)
    {
        $locale = $locale ?? $this->defaultLocale;
        $filters = [];
        $facetFields = $this->result->getFacetFields();

        foreach ($this->searchFilters as $filter) {
            if (isset($facetFields[$filter['solrKey']])) {
                $newArray = $filter;
                /*
                 * @todo To be modified with $filter['translationKey'] once we use key instead of text
                 */
                $newArray['name'] = $this->translator->trans($filter['translationText']);
                unset($newArray['filterOptions']);
                switch ($filter['filterType']) {
                    case FilterConfigurationInterface::FILTER_TYPE_RANGE:
                        $newArray['options'] = $this->mergeFilterOptionsText($filter);
                        break;

                    default:
                        $mergedFilters = $this->mergeFilterOptionsSelect(
                            $facetFields[$filter['solrKey']],
                            $filter,
                            $locale
                        );
                        $newArray['selectedOption'] = $mergedFilters['selectedOption'];
                        $newArray['options'] = $mergedFilters['options'];
                        break;
                }
                if (isset($newArray['measureUnit'])) {
                    $newArray['measureUnit'] = ! empty($newArray['measureUnit']) ? $newArray['measureUnit'] : null;
                }
                if (! empty($newArray['options'])) {
                    $filters[] = $newArray;
                }
            }
        }

        if (empty($filters)) {
            return [];
        }

        $attributeGroups = $this->getAttributeGroups();

        foreach ($filters as $filter => $filterValue) {
            $filterGroupId = $filterValue['filterGroup'];
            $filterValue['filterGroup'] = (string) $filterValue['filterGroup'];

            /*
             * Unset the unnecessary keys to reduce the payload
             */
            unset($filterValue['solrKey'], $filterValue['translationText']);
            $attributeGroups[$filterGroupId]['items'][] = $filterValue;
            $existingKeys[$filterGroupId] = $filterGroupId;
        }

        return array_values(array_intersect_key($attributeGroups, $existingKeys));
    }

    /**
     * We are only keeping the options that have results in the solr and count greater than 0.
     *
     * @param $solrKeys
     * @param $filter
     *
     * @return array
     */
    private function mergeFilterOptionsSelect($solrKeys, $filter, $locale)
    {
        /** We are matching the selected option for this one */
        $selected = [];
        $selectedOption = [];
        $options = [];
        if ($this->requestHas($filter['searchKey'])) {
            if (! is_array($this->requestGet($filter['searchKey']))) {
                $selected = explode(self::DELIMITER_MULTI_VALUE, $this->requestGet($filter['searchKey']));
            } else {
                $selected = $this->requestGet($filter['searchKey']);
            }
        }

        if (! empty($filter['filterOptions'])) {
            // This will add the values from the search without the current filter in order to have the counting right
            $solrKeys = $this->mergeFacetValuesForNotSelectedValues($solrKeys, $filter['solrKey']);

            foreach ($filter['filterOptions'] as $key => $option) {
                if (isset($solrKeys[$option['id']]) && $solrKeys[$option['id']] > 0) {
                    $newArray = $option;
                    $newArray['label'] = $this->translator->trans($option['label']);
                    $newArray['selected'] = in_array($option['slug'], $selected) ? true : false;
                    $newArray['count'] = $solrKeys[$option['id']];
                    $options[] = $newArray;
                    if ($newArray['selected']) {
                        if ($filter['filterType'] == FilterConfigurationInterface::FILTER_TYPE_RADIO) {
                            $newArray['resetLink'] = $this->getSearchUrl($locale).'?'.
                                $this->getSearchUrlParametersString([$filter['searchKey']]);
                            $selectedOption = $newArray;
                        } else {
                            $selectedOption[] = $newArray;
                        }
                    }
                }
            }

            // The first sort will sort the array by count DESC
            usort($options, function ($a, $b) {
                return $b['count'] > $a['count'];
            });

            // The second one will sort will based on selected true/false
            usort($options, function ($a, $b) {
                return $b['selected'] <=> $a['selected'];
            });
        }

        return ['options' => $options, 'selectedOption' => $selectedOption];
    }

    /**
     * We merge the default data with the selected options if they exist.
     *
     * @param $filter
     *
     * @return array
     */
    private function mergeFilterOptionsText($filter)
    {
        $options = [];
        foreach ($filter['filterOptions'] as $key => $option) {
            // Always sort the 'from' values ascending
            if ($key == 'from') {
                asort($option['values']);
            }

            // Always sort the 'to' values descending
            if ($key == 'to') {
                rsort($option['values']);
            }

            $newArray = $option;
            $newArray['selected'] = false;
            if ($this->requestHas($option['name'])) {
                $newArray['selected'] = $this->requestGet($option['name']);
            }
            $newArray['label'] = $this->translator->trans($option['label']);
            $options[] = $newArray;
        }

        return $options;
    }

    /**
     * @param Request $request
     */
    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }

    /**
     * Transforms the labels into ids so we can query solr.
     *
     * @param $values
     * @param $filter
     *
     * @return array
     */
    public function getValuesIds($values, $filter)
    {
        if (! $filter['filterOptions']) {
            return [];
        }
        $options = $filter['filterOptions'];
        $ids = [];
        foreach ($values as $value) {
            if (isset($options[$value])) {
                $ids[] = $options[$value]['id'];
            }
        }

        return $ids;
    }

    /**
     * Function getLecturaRssSolrByCategoryId.
     * @param int|null $categoryId
     * @param string|null $locale
     * @return array | null
     */
    public function getLecturaRssSolrByCategoryId(
        ?int $categoryId = null,
        ?string $locale = null
    ) {
        $query = $this->client->getQuerySelect();
        $query->setRows(CategoryLectura::LECTURA_RSS_MAX_RESULTS_PER_CATEGORY)
            ->addSort(CategoryLectura::SOLR_FIELDS_CREATE_DATE, 'DESC')
            ->addQuery(CategoryLectura::SOLR_FIELDS_LOCALE, $locale)
            ->addQuery(CategoryLectura::SOLR_FIELDS_CATEGORY, $categoryId);

        $this->result = $this->client->execute($query);

        return $this->result->getDocuments();
    }

    /**
     * Function getLecturaRssSolrByCategoryQuery.
     * @param int|null $categoryId
     * @param string $categoryName
     * @param string|null $locale
     * @return array | null
     */
    public function getLecturaRssSolrByCategoryQuery(
        ?int $categoryId = null,
        ?string $categoryName = null,
        ?string $locale = null
    ) {
        $query = $this->client->getQuerySelect();
        $query->setRows(CategoryLectura::LECTURA_RSS_MAX_RESULTS_PER_CATEGORY)
            ->addSort(CategoryLectura::SOLR_FIELDS_CREATE_DATE, 'DESC')
            ->addQuery(CategoryLectura::SOLR_FIELDS_LOCALE, $locale)
            ->addQuery(CategoryLectura::SOLR_FIELDS_QUERY, $categoryName)
            ->addQuery(CategoryLectura::SOLR_FIELDS_CATEGORY, $categoryId);
        $this->result = $this->client->execute($query);

        return $this->result->getDocuments();
    }

    /**
     * Function getMakesByCategoryQuery.
     * @param int|null $categoryId
     * @return array | null
     */
    public function getMakesByCategoryQuery(
        ?int $categoryId = null
    ) {
        if (! $categoryId) {
            return false;
        }
        $query = $this->client->getQuerySelect();
        $query->setRows(0)
            ->addQuery(self::SEARCH_FIELDS_CATEGORY, $categoryId)
            ->enableFacet()
            ->addFacetFields(self::SEARCH_FIELDS_MAKE_ID);

        $this->result = $this->client->execute($query);
        $facets = $this->result->getFacetFields();

        $result = $facets[self::SEARCH_FIELDS_MAKE_ID];

        $data = array_filter($result, function ($val) {
            if ($val > 0) {
                return true;
            }
        });

        return array_keys($data);
    }

    /**
     * Not proud of this.
     * I really hope we will rewrite the search one day.
     *
     * @param $facets
     * @param $newFacets
     * @return mixed
     */
    public function mergeFacetsWithoutOverwriting($facets, $newFacets)
    {
        foreach ($facets as $key => $items) {
            if ((isset($items['items']) && empty($items['items'])) ||
                in_array($key, ['make'])) {
                $facets[$key]['items'] = $newFacets[$key]['items'];
            }
        }

        return $facets;
    }

    /**
     * @return array
     */
    public function getTracking(): array
    {
        return $this->tracking;
    }

    /**
     * @return array
     */
    public function getSelectedFilters(): array
    {
        return array_values($this->selectedFilters);
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function setSelectedFilters(string $key, $value): void
    {
        $this->selectedFilters[$key] = [
            'filter' => $key,
            'resetUrl' => $value,
        ];
    }

    /**
     * @return mixed
     */
    public function getLocale()
    {
        if (! $this->locale) {
            return $this->defaultLocale;
        }

        return $this->locale;
    }

    /**
     * @param mixed $locale
     */
    public function setLocale($locale): void
    {
        $this->locale = $locale;
    }

    /**
     * Returns a reset link for a given filter / tag
     * There is a lot of code because we have lots of manual filters
     * Tried to consider all filters
     * Could not find an easier solution at this time.
     *
     * @todo We should refactor / delete this once we redo the searching in a better way
     *
     * @param $filter
     * @param bool $multiple
     * @param string $excluded
     * @throws NonUniqueResultException
     */
    public function createResetLink($filter, $multiple = false, $excluded = '')
    {
        $locale = $this->getLocale();
        $this->translator->setLocale($locale);
        $filters = [$filter];

        $tagName = '';
        $resetTag = [];
        $basePath = $this->getSearchBaseUrl($locale);
        $queryPath = $categoryPath = $makePath = $countryPath = $priceTypePath = $priceAnalysisTypePath = $transmissionPath = $regionPath = $dynamicPath = '';
        $category = false;
        $categoryFilters = [self::REQUEST_FIELD_CAT_L1, self::REQUEST_FIELD_CAT_L2, self::REQUEST_FIELD_CAT_L3];
        $manualFilters = [
            self::REQUEST_FIELD_CAT_L1,
            self::REQUEST_FIELD_CAT_L2,
            self::REQUEST_FIELD_CAT_L3,
            self::REQUEST_FIELD_MAKE,
            self::REQUEST_FIELD_COUNTRY,
            self::REQUEST_FIELD_PRICE_TYPE,
            self::REQUEST_FIELD_PRICE_ANALYSIS_TYPE,
            self::REQUEST_FIELD_TRANSMISSION,
            self::REQUEST_FIELD_REGION,
            self::REQUEST_FIELD_PRICE_FROM,
            self::REQUEST_FIELD_PRICE_TO,
        ];

        /* Query filter */
        if ($filter !== self::REQUEST_FIELD_QUERY) {
            $queryPath = $this->getQueryPath();
        }

        if ($filter == self::REQUEST_FIELD_QUERY) {
            $tagName = $this->requestGet(self::REQUEST_FIELD_QUERY);
        }
        /** Query filter */

        /** @var CategoryRepository $categoryRepo */
        $categoryRepo = $this->entity_manager->getRepository('TradusBundle:Category');
        /* Categories filters */
        if (! in_array($filter, $categoryFilters)) {
            $categoryPath = $this->getCategoryPath($locale);
        } elseif ($filter == self::REQUEST_FIELD_CAT_L3) {
            $category = $this->requestGet(self::REQUEST_FIELD_CAT_L2);
            $tagName = $categoryRepo->getCategoryName($this->requestGet(self::REQUEST_FIELD_CAT_L3), $locale);
        } elseif ($filter == self::REQUEST_FIELD_CAT_L2) {
            $category = $this->requestGet(self::REQUEST_FIELD_CAT_L1);
            $tagName = $categoryRepo->getCategoryName($this->requestGet(self::REQUEST_FIELD_CAT_L2), $locale);
        } elseif ($filter == self::REQUEST_FIELD_CAT_L1) {
            $tagName = $categoryRepo->getCategoryName($this->requestGet(self::REQUEST_FIELD_CAT_L1), $locale);
        }

        if ($category) {
            $categoryPath = $this->getCategoryPath($locale, $category);
        }
        /* Categories filters */

        /* Make Filter */
        if ($filter !== self::REQUEST_FIELD_MAKE) {
            $makePath = $this->getMakePath($locale);
        }

        if ($filter == self::REQUEST_FIELD_MAKE) {
            $filters = array_merge($filters, [self::REQUEST_FIELD_MODEL, self::REQUEST_FIELD_VERSION]);
            $makes = $this->getMakeValuesAsIndexed($this->requestGet(self::REQUEST_FIELD_MAKE));
            $make = reset($makes);
            $tagName = $this->translator->trans('Make').': '.$make;
        }
        /* Make Filter */

        /* Model Filter */
        if ($filter == self::REQUEST_FIELD_MODEL) {
            $filters = array_merge($filters, [self::REQUEST_FIELD_VERSION]);
            $models = $this->getFacetModelsData($this->requestGet(self::REQUEST_FIELD_MAKE));
            $model = $models[$this->requestGet(self::REQUEST_FIELD_MODEL)];
            $tagName = $this->translator->trans('Model').': '.$model['name'];
        }
        /* Model Filter */

        /* Version Filter */
        if ($filter == self::REQUEST_FIELD_VERSION) {
            $versions = $this->getFacetVersionsData($this->requestGet(self::REQUEST_FIELD_MODEL));
            $version = $versions[$this->requestGet(self::REQUEST_FIELD_VERSION)];
            $tagName = $this->translator->trans('Version').': '.$version['name'];
        }
        /* Version Filter */

        /* Price From Filter */
        if ($filter == self::REQUEST_FIELD_PRICE_FROM) {
            $tagName = $this->translator->trans('Price').' ('.
                $this->translator->trans('From').'): '.$this->requestGet(self::REQUEST_FIELD_PRICE_FROM);
        }
        /* Price From Filter */

        /* Price To Filter */
        if ($filter == self::REQUEST_FIELD_PRICE_TO) {
            $tagName = $this->translator->trans('Price').' ('.
                $this->translator->trans('To').'): '.$this->requestGet(self::REQUEST_FIELD_PRICE_TO);
        }
        /* Price To Filter */

        /* Country filter */
        if ($filter == self::REQUEST_FIELD_COUNTRY) {
            $country = Intl::getRegionBundle()->getCountryName($excluded, $locale);
            $tagName = $this->translator->trans('Country').': '.$country;
            if ($multiple === true) {
                $countryPath = $this->getCountryPath($locale, false, $excluded);
            }
        }

        if ($filter !== self::REQUEST_FIELD_COUNTRY) {
            $countryPath = $this->getCountryPath($locale, false, false);
        }
        /* Country filter */

        /* PriceType filter */
        if ($filter == self::REQUEST_FIELD_PRICE_TYPE) {
            $priceTypes = $this->getFacetPriceTypeData();
            $priceType = $priceTypes[$excluded];
            $tagName = $this->translator->trans('Price Type').': '.$this->translator->trans($priceType['name']);
            if ($multiple === true) {
                $priceTypePath = $this->getPriceTypePath($locale, $excluded);
            }
        }

        if ($filter !== self::REQUEST_FIELD_PRICE_TYPE) {
            $priceTypePath = $this->getPriceTypePath($locale);
        }
        /* PriceType filter */

        /* PriceAnalysisType filter */
        if ($filter == self::REQUEST_FIELD_PRICE_ANALYSIS_TYPE) {
            $priceAnalysisTypes = $this->getFacetPriceAnalysisTypeData();
            $priceAnalysisType = $priceAnalysisTypes[$excluded];
            $tagName = $this->translator->trans('Price rating').': '.$this->translator->trans($priceAnalysisType['name']);
            if ($multiple === true) {
                $priceAnalysisTypePath = $this->getPriceAnalysisTypePath($locale, $excluded);
            }
        }

        if ($filter !== self::REQUEST_FIELD_PRICE_ANALYSIS_TYPE) {
            $priceAnalysisTypePath = $this->getPriceAnalysisTypePath($locale);
        }
        /* PriceAnalysisType filter */

        /* Transmission filter */
        if ($filter == self::REQUEST_FIELD_TRANSMISSION) {
            $transmissions = $this->getFacetTransmissionData();
            $transmission = $transmissions[ucfirst($excluded)];
            $tagName = $this->translator->trans('Transmission').': '.$this->translator->trans($transmission['name']);
            if ($multiple === true) {
                $transmissionPath = $this->getTransmissionPath($locale, $excluded);
            }
        }

        if ($filter !== self::REQUEST_FIELD_TRANSMISSION) {
            $transmissionPath = $this->getTransmissionPath($locale);
        }
        /* Transmission filter */

        /* Region filter */
        if ($filter == self::REQUEST_FIELD_REGION) {
            $regions = $this->getFacetRegionsData($locale);
            $region = $regions[$excluded];
            $tagName = $this->translator->trans('Region').': '.$region['name'];
            if ($multiple === true) {
                $regionPath = $this->getRegionPath($locale, $excluded);
            }
        }

        if ($filter !== self::REQUEST_FIELD_REGION) {
            $regionPath = $this->getRegionPath($locale);
        }
        /* Region filter */

        /* Dynamic filters */
        if (! in_array($filter, $manualFilters)) {
            if ($excluded) {
                $label = $excluded;
            } else {
                $label = $filter;
            }

            if (isset($this->dynamicFilters[$label])) {
                if (strpos($filter, '_from') || strpos($filter, '_to')) {
                    $tagName = $this->dynamicFilters[$label].$this->requestGet($filter);
                } else {
                    $tagName = $this->dynamicFilters[$label];
                }
            } elseif (strpos($filter, '_from')) {
                $tagName = '> '.$this->requestGet($filter);
            } elseif (strpos($filter, '_to')) {
                $tagName = '< '.$this->requestGet($filter);
            }

            if ($multiple) {
                $filters['excluded'] = $excluded;
            }
        }
        if (empty($tagName)) {
            return false;
        }
        $dynamicPath = $this->getSearchUrlParametersString($filters);
        /** Dynamic filters */
        $resetPath = "{$basePath}{$queryPath}{$categoryPath}{$makePath}{$countryPath}{$regionPath}{$priceTypePath}{$priceAnalysisTypePath}{$transmissionPath}?{$dynamicPath}";
        $resetTag[$tagName] = $resetPath;
        $this->setSelectedFilters($tagName, $resetPath);
    }

    /**
     * Creates a list with all dynamic filters and their translation names
     * Based on the search criteria.
     *
     * @throws NonUniqueResultException
     */
    public function createDynamicFiltersList()
    {
        $searchFilters = $this->getSearchFilters();
        $categoryFilters = is_array($searchFilters) ? $searchFilters : json_decode($searchFilters);
        $this->translator->setLocale($this->getLocale());

        if (! empty($categoryFilters) && is_array($categoryFilters)) {
            foreach ($categoryFilters as $categoryFilter) {
                switch ($categoryFilter->filterType) {
                    case 'range':
                        foreach ($categoryFilter->filterOptions as $option) {
                            $this->dynamicFilters[$option->name] = $this->translator->trans($categoryFilter->translationText).' ('.$this->translator->trans($option->label).'): ';
                        }
                        break;
                    case 'checkbox':
                        foreach ($categoryFilter->filterOptions as $option) {
                            if ($option->translationKey) {
                                $this->dynamicFilters[$option->slug] = $this->translator->trans($categoryFilter->translationText).': '.$this->translator->trans($option->label);
                            }
                        }
                        break;
                    case 'select':
                    default:
                        break;
                }
            }
        }
    }

    /**
     * @param int|null $offerId
     * @param int|null $siteId
     * @return array|bool
     */
    public function getOfferById(
        ?int $offerId,
        ?int $siteId = null
    ) {
        if (! $offerId) {
            return false;
        }

        if (! $siteId) {
            $siteId = $this->sitecodeId;
        }

        $query = $this->client->getQuerySelect();
        $query->setRows(1)
            ->addQuery(self::SEARCH_FIELDS_OFFER_ID, $offerId)
            ->addQuery('site_facet_m_int', $siteId);

        $this->result = $this->client->execute($query);
        $result = $this->result->getDocuments();

        if (count($result) == 0) {
            return [];
        }

        return $result[0];
    }

    /**
     * @param int|null $sellerId
     * @return array|bool
     */
    public function getSearchSitecodesFromOffersBySeller(
        ?int $sellerId
    ) {
        if (! $sellerId) {
            return false;
        }

        $query = $this->client->getQuerySelect();
        $query->setRows(0)
            ->addQuery(self::SEARCH_SELLER_ID, $sellerId);

        $query->enableFacet()
            ->addFacetFields('site_facet_m_int');

        $this->result = $this->client->execute($query);
        $result = $this->result->getFacetField('site_facet_m_int');

        $sites = [];
        if (count($result) !== 0) {
            foreach ($result as $site => $count) {
                if ($count > 0) {
                    $sites[] = $site;
                }
            }
        }

        return $sites;
    }

    /**
     * @param int $sellerId
     * @param int $siteId
     * @return int|bool
     */
    public function getSearchTotalOffersBySellerSitecode(
        int $sellerId,
        int $siteId
    ) {
        $query = $this->client->getQuerySelect();
        $query->setRows(0)
            ->addQuery(self::SEARCH_SELLER_ID, $sellerId)
            ->addQuery('site_facet_m_int', $siteId);

        $query->enableFacet()
            ->addFacetFields('site_facet_m_int');

        $this->result = $this->client->execute($query);
        $result = $this->result->getNumberFound();

        $total = 0;
        if ($result) {
            $total = (int) $result;
        }

        return $total;
    }
}

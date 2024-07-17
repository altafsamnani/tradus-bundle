<?php

namespace TradusBundle\Service\Alerts\Rules;

use Locale;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Intl\Intl;
use TradusBundle\Entity\Category;
use TradusBundle\Entity\CategoryInterface;
use TradusBundle\Entity\Make;
use TradusBundle\Entity\Offer;
use TradusBundle\Entity\SimilarOfferAlert;
use TradusBundle\Repository\MakeRepository;
use TradusBundle\Service\Alerts\Notifications\PushNotification;
use TradusBundle\Service\Search\SearchService;
use TradusBundle\Service\Sitecode\SitecodeService;

/**
 * Class AlertRuleMatchingOffer.
 */
class AlertRuleMatchingOffer extends BaseAlertRule implements AlertRuleInterface
{
    /** @var AlertRuleResponse */
    protected $response;

    /** @var ConfigRuleMatchingOffer */
    protected $config;

    /*
     * @var SimilarOfferAlert
     */
    protected $similarOfferAlert;

    /** @var string */
    protected $filterMakeName = '';

    /** @var string */
    protected $filterMakeSlug = '';

    /** @var bool */
    protected $matchingTitle = false;

    /** @var object */
    protected $offerResult;

    /** @var SearchService */
    protected $search;

    /** @var int */
    protected $filterLimit;

    /** @var string */
    protected $filterSort;

    /** @var string */
    protected $filterFreeSellers;

    /** @var int */
    protected $filterCategoryId;

    /** @var int */
    protected $typeId;

    /** @var int */
    protected $subtypeId;

    /** @var array */
    protected $country;

    /** @var array */
    protected $priceRange;

    /** @var array */
    protected $yearRange;

    /** @var int */
    protected $sitecodeId;

    /** @var string */
    protected $locale;

    protected $type = self::RULE_TYPE_MATCHING_OFFER;
    protected $emailSubject = 'New offers have been selected for you!';

    /**
     * When you change this it will not change existing in the db.
     * @var array
     */
    protected $options = [
        'make' => null,
        'category' => null,
        'type' => null,
        'subtype' => null,
        'country' => null,
        'price' => ['min' => '', 'max' => ''],
        'year' => ['min' => '', 'max' => ''],
    ];

    public function getDataForUpdate()
    {
        $this->response = new AlertRuleResponse($this->entityManager);
        $this->config = new ConfigRuleMatchingOffer();
        $this->search = $this->container->get('tradus.search');
        $this->sitecodeId ? $this->search->setSitecodeId($this->sitecodeId) : '';
        $this->filterLimit = $this->config->getFilterLimit();
        $this->filterSort = $this->config->getFilterSort();
        $this->filterFreeSellers = $this->config->getFilterFreeSellers();

        /* get user information */
        $this->setUserData();
        // Matching offers
        $matchingTitle = false;

        $offer = null;
        $this->similarOfferAlert = $this->entityManager->getRepository('TradusBundle:SimilarOfferAlert')
            ->findOneBy(['alert' => $this->entity, 'status' => SimilarOfferAlert::STATUS_SUBSCRIBED]);
        if ($this->similarOfferAlert && $this->similarOfferAlert->getOffer()) {
            $matchingTitle = $this->similarOfferAlert->getOffer()->getTitleByLocale($this->locale);
            $offer = $this->similarOfferAlert->getOffer();
        }
        $matchingTitle = false;

        $makeId = $this->getOption('make');
        $this->filterCategoryId = $this->getOption('category');
        $this->typeId = $this->getOption('type');
        $this->subtypeId = $this->getOption('subtype');
        $this->country = $this->getOption('country');
        $this->priceRange = $this->getOption('price');
        $this->yearRange = $this->getOption('year');

        /* set make name */
        $this->setMakeName($makeId, $offer);

        /* get similar offer results */
        $this->getSimilarOffers();
        /* set display title name */
        $valueArray = [
            'make' => $this->filterMakeName,
            'category' => $this->filterCategoryId,
            'type' => $this->typeId,
            'subtype' => $this->subtypeId,
            'location' => $this->country,
            'price' => $this->priceRange,
            'year' => $this->yearRange,
        ];
        $this->response->setCategoryTitle($this->getFilterNamesForEmail($valueArray, $this->locale));

        /* get related offers */
        $this->getRelOffrRes();

        /* set the campaign and original id */
        $this->setOfferData($offer);

        return $this->response;
    }

    /**
     * @Set user related information
     * @return void
     */
    private function setUserData()
    {
        if ($this->getUser()) {
            $sitecodeService = new SitecodeService();
            $this->locale = $this->getUser()->getPreferredLocale() ?? $sitecodeService->getDefaultLocale();
            $this->response->setLocale($this->locale);
            $this->translator->setLocale($this->locale);
            $this->response->setData(AlertRuleResponse::DATA_ALERT_ID, $this->entity->getId());
            $this->response->setData(AlertRuleResponse::DATA_EMAIL_TO, $this->getUser()->getEmail());
            $this->response->setData(AlertRuleResponse::DATA_USER_FULL_NAME, $this->getUser()->getFullName());
            $this->response->setData(AlertRuleResponse::DATA_USER_ID, $this->getUser()->getId());
            $this->response->setData(
                AlertRuleResponse::DATA_EMAIL_FROM,
                $this->container->getParameter('sitecode')['emails']['alerts_email']
            );
            $url = '/account/alerts/unsubscribe?id='.$this->getEntity()->getId().'&uid='.$this->getUser()->getId();
            $this->response->setData(AlertRuleResponse::DATA_ALERT_UNSUBSCRIBE, $url);
        }
    }

    /**
     * @Set the make name and make slug name
     * @param $makeId
     * @param $offer
     * @return void
     */
    private function setMakeName($makeId, $offer)
    {
        /** @var MakeRepository $makesRepo */
        $makesRepo = $this->entityManager->getRepository('TradusBundle:Make');

        if (! empty($makeId) && $this->similarOfferAlert) {
            if (! empty($offer) && ! is_array($makeId)) {
                $make = $makesRepo->getMakeById($makeId);
                $this->filterMakeName = $make->getName();
                $this->filterMakeSlug = $make->getSlug();
            } else {
                if (is_array($makeId)) {
                    $makeNames = [];
                    $makeSlugs = [];
                    $makeIds = $makesRepo->getMakesByIds($makeId);
                    foreach ($makeIds as $make) {
                        $makeNames[] = $make['name'];
                        $makeSlugs[] = $make['slug'];
                    }
                    $this->filterMakeName = (count($makeNames) > 1) ? implode(' + ', $makeNames) : $makeNames[0];
                    $this->filterMakeSlug = $makeSlugs;
                }
            }
        }

        /* setting subject of email */
        $this->response->setData(
            AlertRuleResponse::DATA_EMAIL_SUBJECT,
            $this->translator->trans($this->emailSubject)
        );
        $this->response->setAlertString($this->filterMakeName);
    }

    /**
     * @Set result for similar offers
     * @return void
     */
    private function getSimilarOffers()
    {
        $filterFromDate = $this->getLastUpdateDate();

        /** @var Request $request */
        $request = new Request();
        $requestParamsArr = [
            SearchService::REQUEST_FIELD_PAGE => 1,
            SearchService::REQUEST_FIELD_LIMIT => 25,
            SearchService::REQUEST_FIELD_SORT => $this->filterSort,
            SearchService::REQUEST_FIELD_HAS_IMAGE_COUNT => 1,
         //   SearchService::REQUEST_FIELD_FROM_CREATE_DATE => $filterFromDate->format('Y-m-d\TH\:i\:s\Z'),
        ];
        $requestParamsArr = array_merge(
            $requestParamsArr,
            $this->setSearchParams(
                $this->filterMakeSlug,
                $this->filterCategoryId,
                $this->typeId,
                $this->subtypeId,
                $this->country,
                $this->priceRange,
                $this->yearRange
            )
        );
        $request->query = new ParameterBag($requestParamsArr);
        $query = $this->search->getQuerySelect();
        $query = $this->search->createQueryFromRequest($query, $request);
        $this->offerResult = $this->search->execute($query);

        if ($this->matchingTitle) {
            $this->offerResult->orderBySimilarity($this->matchingTitle, 'title_'.strtolower($this->locale))
                ->boostSellerTypesDocuments($this->filterFreeSellers, 1, 1)
                ->limitDocuments($this->filterLimit);
        } else {
            $this->offerResult->shuffleDocuments()
                ->boostSellerTypesDocuments($this->filterFreeSellers)
                ->limitDocuments($this->filterLimit);
        }

        $this->response->setSearchResult($this->offerResult);
        /* set redirection path for heading **/
        $this->setCategoryPath($this->response->getOffers());

        $this->response->setSearchUrl($this->search->getSearchUrlFull($this->locale));
        if ($this->similarOfferAlert && $this->similarOfferAlert->getSearchUrl()) {
            $this->response->setSearchUrl($this->similarOfferAlert->getSearchUrl());
        }
    }

    /**
     * @Set result for related offers
     * @return void
     */
    private function getRelOffrRes()
    {
        // Related offers
        if (count($this->offerResult->getDocuments())) {
            $request = new Request();
            $relatedParamsArr = [
                SearchService::REQUEST_FIELD_PAGE => 1,
                SearchService::REQUEST_FIELD_LIMIT => 50,
                SearchService::REQUEST_FIELD_HAS_IMAGE_COUNT => 1,
                SearchService::REQUEST_FIELD_SORT => SearchService::REQUEST_VALUE_SORT_RELEVANCY,
                SearchService::REQUEST_FIELD_PRICE_TYPE => 'fixed',
            ];
            $relatedParamsArr = array_merge(
                $relatedParamsArr,
                $this->setSearchParams(
                    null,
                    $this->filterCategoryId,
                    $this->typeId,
                    $this->subtypeId,
                    $this->country,
                    $this->priceRange,
                    $this->yearRange
                )
            );

            $request->query = new ParameterBag($relatedParamsArr);
            $query = $this->search->getQuerySelect();
            $query = $this->search->createQueryFromRequest($query, $request);
            $relatedResult = $this->search->execute($query);

            // Filter offers that are also shown in the matching result set
            foreach ($this->offerResult->getDocuments() as $document) {
                $relatedResult->filterDocuments(
                    SearchService::SEARCH_FIELDS_OFFER_ID,
                    $document[SearchService::SEARCH_FIELDS_OFFER_ID]
                );
            }

            if ($this->matchingTitle) {
                $relatedResult->orderBySimilarity($this->matchingTitle, 'title_'.strtolower($this->locale))
                    ->boostSellerTypesDocuments($this->filterFreeSellers, 1, 1)
                    ->limitDocuments($this->filterLimit);
            } else {
                $relatedResult->shuffleDocuments()
                    ->boostSellerTypesDocuments($this->filterFreeSellers)
                    ->limitDocuments($this->filterLimit);
            }

            $this->response->setRelatedSearchResult($relatedResult);
            $this->response->setRelatedSearchUrl($this->search->getSearchUrlFull($this->locale));

            /* get spare parts list */
            $this->getSpareParts();
        }
    }

    /**
     * @Set result for spare category
     * @return void
     */
    private function getSpareParts()
    {
        $spareCategoryId = CategoryInterface::SPARE_PARTS_ID;

        $request = new Request();
        $spareParamsArr = [
            SearchService::REQUEST_FIELD_PAGE => 1,
            SearchService::REQUEST_FIELD_LIMIT => 50,
            SearchService::REQUEST_FIELD_HAS_IMAGE_COUNT => 1,
            SearchService::REQUEST_FIELD_SORT => SearchService::REQUEST_VALUE_SORT_RELEVANCY,
        ];
        $spareParamsArr = array_merge(
            $spareParamsArr,
            $this->setSearchParams(
                $this->filterMakeSlug,
                $spareCategoryId,
                null,
                null,
                $this->country,
                $this->priceRange,
                $this->yearRange
            )
        );
        $request->query = new ParameterBag($spareParamsArr);
        $query = $this->search->getQuerySelect();
        $query = $this->search->createQueryFromRequest($query, $request);
        $spareResult = $this->search->execute($query);

        $this->response->setSparePartsCount(count($spareResult->getDocuments()));
        $this->response->setSparePartsUrl($this->search->getSearchUrlFull($this->locale));
    }

    /**
     * @To set search paramters for SearchService
     * @param string $make
     * @param string $catId
     * @param string $typeId
     * @param string $subtypeId
     * @param array $country
     * @param array $priceRange
     * @param array $yearRange
     * @return array
     */
    protected function setSearchParams($make, $catId, $typeId, $subtypeId, $country, $priceRange, $yearRange)
    {
        $request = [];
        $paramsArray = [
            SearchService::REQUEST_FIELD_MAKE => $make,
            SearchService::REQUEST_FIELD_CAT_L1 => $catId,
            SearchService::REQUEST_FIELD_CAT_L2 => $typeId,
            SearchService::REQUEST_FIELD_CAT_L3 => $subtypeId,
            SearchService::REQUEST_FIELD_COUNTRY => $country,
            SearchService::REQUEST_FIELD_PRICE_FROM => isset($priceRange['min']) ? $priceRange['min'] : '',
            SearchService::REQUEST_FIELD_PRICE_TO => isset($priceRange['max']) ? $priceRange['max'] : '',
            SearchService::REQUEST_FIELD_YEAR_FROM => isset($yearRange['min']) ? $yearRange['min'] : '',
            SearchService::REQUEST_FIELD_YEAR_TO => isset($yearRange['max']) ? $yearRange['max'] : '',
        ];

        foreach ($paramsArray as $key => $value) {
            if (! empty($value)) {
                $request[$key] = $value;
            }
        }

        return $request;
    }

    /**
     * @Function to get all filter names to show after similar offer alert email subject
     * @param array $aFilters
     * @param string $locale
     * @return string
     */
    public function getFilterNamesForEmail($aFilters, $locale)
    {
        $aFilterNames = [];

        if ($aFilters['make'] != '') {
            $aFilterNames[] = str_replace(' + ', ', ', $aFilters['make']);
        }

        $categoryId = $aFilters['subtype'] ?
            $aFilters['subtype'] : ($aFilters['type'] ?
                $aFilters['type'] : $aFilters['category']);
        if (! empty($categoryId)) {
            $oCategory = $this->entityManager->getRepository('TradusBundle:Category')->findOneBy(['id' => $categoryId]);
            $aFilterNames[] = $oCategory->getNameTranslation($locale);
        }

        if (! empty($aFilters['location'])) {
            Locale::setDefault($locale);
            $aCountryNames = [];
            foreach ($aFilters['location'] as $country_code) {
                $aCountryNames[] = Intl::getRegionBundle()->getCountryName($country_code);
            }
            $aFilterNames[] = implode(', ', $aCountryNames);
        }

        $aPriceFilters = (! empty($aFilters['price'])) ? array_filter($aFilters['price']) : null;
        if (! empty($aPriceFilters)) {
            $filterPriceRanges = '';
            $defaultCurrency = 'EUR';
            $currencySymbol = Intl::getCurrencyBundle()->getCurrencySymbol($defaultCurrency);
            foreach ($aPriceFilters as $priceRange) {
                $filterPriceRanges .= ($filterPriceRanges != '') ?
                    ' - '.$currencySymbol.$priceRange : $currencySymbol.$priceRange;
            }
            $aFilterNames[] = $filterPriceRanges;
        }

        if (is_array($aFilters['year'])) {
            $aYearFilters = (! empty($aFilters['year'])) ? array_filter($aFilters['year']) : null;
        }

        if (! empty($aYearFilters)) {
            $aFilterNames[] = implode(' - ', $aYearFilters);
        }

        return implode(' | ', $aFilterNames);
    }

    /**
     * @Function to set utm campaign and utm original offer id
     * @param $offer Offer
     * @return void
     */
    private function setOfferData($offer)
    {
        $filters = str_replace('/en/search/', '', preg_replace(
            '/\?sort=(price-asc|price-desc|score-desc|date-desc)/',
            '',
            $this->response->getSearchUrl()
        ));

        $offerId = 'alert-filters-'.rtrim($filters, '/');
        $campaignName = 'search-alerts';

        if (! empty($offer)) {
            $offerId = 'original-offer-id-'.$offer->getId();
            $campaignName = 'similar-offers-alert';
        }
        $this->response->setData(AlertRuleResponse::DATA_ORIGINAL_OFFER_ID, $offerId);
        $this->response->setData(AlertRuleResponse::DATA_CAMPAIGN_ID, $campaignName);
    }

    /**
     * Check make & category in rule is active.
     *
     * @return array|bool
     */
    public function validateRuleFilters()
    {
        $response = false;

        $makeId = $this->getOption('make');
        if (! empty($makeId)) {
            $makesRepo = $this->entityManager->getRepository('TradusBundle:Make');
            if ((is_array($makeId) && empty($makesRepo->getMakesByIds($makeId)))
                || (is_int($makeId) && empty($makesRepo->getMakeById($makeId)))
            ) {
                $response[] = 'Invalid Make - '.(is_array($makeId) ? implode(', ', $makeId) : $makeId);
            }
        }

        $categoryId = $this->getOption('category');
        $typeId = $this->getOption('type');
        $subtypeId = $this->getOption('subtype');

        $categoryId = $subtypeId ? $subtypeId : $typeId ? $typeId : $categoryId;
        if (! empty($categoryId)) {
            $categoryRepo = $this->entityManager->getRepository('TradusBundle:Category');
            if (empty($categoryRepo->getCategoryById((int) $categoryId))) {
                $response[] = 'Invalid Category - '.$categoryId;
            }
        }

        return $response;
    }

    /**
     * @param array $offers
     * @return void
     */
    public function setCategoryPath(array $offers)
    {
        if (count($offers)) {
            $catArr = $offers[0]['category_ids'];
            $catId = isset($catArr[2]) ? $catArr[2] : (isset($catArr[1]) ? $catArr[1] : $catArr[0]);

            $categoryRepo = $this->entityManager->getRepository('TradusBundle:Category')->find($catId);
            if ($categoryRepo) {
                $this->response->setData(
                    AlertRuleResponse::DATA_CATEGORY_PATH,
                    $this->locale.'/search/'.$categoryRepo->getSearchSlugUrl($this->locale)
                );
            }
        }
    }

    public function getEmail(): ?AlertRuleResponse
    {
        return $this->getDataForUpdate();
    }

    public function getPushNotification(): ?PushNotification
    {
        $data = $this->getDataForUpdate();
        if (! $data) {
            return null;
        }

        $amount = count($data->getOffers());
        if ($amount === 0) {
            return null;
        }

        $url = (new SitecodeService())->getSitecodeDomain().ltrim($data->getSearchUrl(), '/');
        $url .= (strpos($url, '?') > -1) ? '&' : '?';
        $url .= 'utm_source=flutter-app&utm_medium=push-notification&utm_campaign=new-offers-'.$this->locale;

        return new PushNotification(
            $this->getUser()->getId(),
            $this->translator->trans('@amount new offers based on your saved search', [
                '@amount' => $amount,
            ]),
            $this->translator->trans('Be first to check new offers on the market!'),
            $this->translator->trans('View new offers'),
            $url,
            ''
        );
    }
}

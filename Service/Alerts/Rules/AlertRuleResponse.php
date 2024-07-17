<?php

namespace TradusBundle\Service\Alerts\Rules;

use Doctrine\ORM\EntityManager;
use TradusBundle\Service\Search\Result;
use TradusBundle\Service\Sitecode\SitecodeService;
use TradusBundle\Transformer\OfferSearchTransformer;

class AlertRuleResponse
{
    public const DATA_EMAIL_TO = 'email_to';
    public const DATA_EMAIL_FROM = 'email_from';
    public const DATA_EMAIL_SUBJECT = 'email_subject';
    public const DATA_ALERT_UNSUBSCRIBE = 'alert_unsubscribe';
    public const DATA_USER_FIRST_NAME = 'user_first_name';
    public const DATA_USER_FULL_NAME = 'user_full_name';
    public const DATA_ALERT_ID = 'alert_id';
    public const DATA_USER_ID = 'user_id';
    public const DATA_ORIGINAL_OFFER_ID = 'offer_id';
    public const DATA_CAMPAIGN_ID = 'search-alerts';
    public const DATA_CATEGORY_PATH = 'category-url';

    protected $locale;

    /** @var EntityManager */
    protected $entityManager;

    /** @var array */
    protected $offers = [];

    /** @var bool */
    protected $parsed = false;

    /** @var Result */
    protected $searchResult;

    /** @var string */
    protected $searchUrl;

    /** @var string */
    protected $sparePartsUrl;

    /** @var string */
    protected $spareCount;

    /** @var string */
    protected $relatedSearchUrl;

    /** @var Result */
    protected $relatedSearchResult;

    /** @var array */
    protected $relatedOffers = [];

    /** @var int */
    protected $relatedNumberFound = 0;
    /** @var string */
    protected $alertString;

    /** @var int */
    protected $numberFound = 0;

    /** @var string */
    protected $categoryName;

    /** @var array */
    protected $data = [];

    /**
     * AlertRuleResponse constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        $sitecodeService = new SitecodeService();
        $this->locale = $sitecodeService->getDefaultLocale();
    }

    /**
     * @param Result $result
     */
    public function setSearchResult(Result $result)
    {
        $this->searchResult = $result;
    }

    /**
     * @param Result $result
     */
    public function setRelatedSearchResult(Result $result)
    {
        $this->relatedSearchResult = $result;
    }

    /**
     * @param string $url
     */
    public function setSearchUrl(string $url)
    {
        $this->searchUrl = $url;
    }

    /**
     * @return string
     */
    public function getSearchUrl()
    {
        return $this->searchUrl;
    }

    /**
     * @param string $url
     */
    public function setRelatedSearchUrl(string $url)
    {
        $this->relatedSearchUrl = $url;
    }

    /**
     * @return mixed
     */
    public function getRelatedSearchUrl()
    {
        return $this->relatedSearchUrl;
    }

    /**
     * @param string $url
     */
    public function setSparePartsUrl(string $url)
    {
        $this->sparePartsUrl = $url;
    }

    /**
     * @return mixed
     */
    public function getSparePartsUrl()
    {
        return $this->sparePartsUrl;
    }

    /**
     * @param string $text
     */
    public function setAlertString(string $text)
    {
        $this->alertString = $text;
    }

    /**
     * @return string
     */
    public function getCategoryTitle()
    {
        return $this->categoryName;
    }

    /**
     * @param string $text
     */
    public function setCategoryTitle(string $text)
    {
        $this->categoryName = $text;
    }

    /**
     * @return string
     */
    public function getAlertString()
    {
        return $this->alertString;
    }

    /**
     * @param string $locale
     */
    public function setLocale(string $locale)
    {
        $this->locale = $locale;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getData(string $name)
    {
        return $this->data[$name];
    }

    /**
     * @param string $name
     * @param $value
     */
    public function setData(string $name, $value)
    {
        $this->data[$name] = $value;
    }

    /**
     * @param int $value
     * @param $value
     */
    public function setSparePartsCount($value)
    {
        $this->spareCount = $value;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getSparePartsCount()
    {
        return $this->spareCount;
    }

    /**
     * @return array
     */
    public function getOffers()
    {
        $this->parse();

        return $this->offers;
    }

    /**
     * @return array
     */
    public function getRelatedOffers()
    {
        $this->parse();

        return $this->relatedOffers;
    }

    /**
     * This is the total number of results from the search, not the total returned in offers.
     * @return int
     */
    public function getNumberFound()
    {
        $this->parse();

        return $this->numberFound;
    }

    /**
     * This is the total number of results from the search, not the total returned in offers.
     * @return int
     */
    public function getRelatedNumberFound()
    {
        $this->parse();

        return $this->relatedNumberFound;
    }

    private function parse()
    {
        if ($this->parsed == false) {
            if ($this->searchResult) {
                $this->offers = (new OfferSearchTransformer(
                    $this->searchResult,
                    $this->locale,
                    1,
                    $this->entityManager
                ))->transform();
                $this->numberFound = $this->searchResult->getNumberFound();
            }
            if ($this->relatedSearchResult) {
                $this->relatedOffers = (new OfferSearchTransformer(
                    $this->relatedSearchResult,
                    $this->locale,
                    1,
                    $this->entityManager
                ))->transform();
                $this->relatedNumberFound = $this->relatedSearchResult->getNumberFound();
            }
            $this->parsed = true;
        }
    }
}

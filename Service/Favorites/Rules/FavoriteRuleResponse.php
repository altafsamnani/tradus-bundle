<?php

namespace TradusBundle\Service\Favorites\Rules;

use Doctrine\ORM\EntityManager;
use TradusBundle\Service\Search\Result;
use TradusBundle\Service\Sitecode\SitecodeService;
use TradusBundle\Transformer\OfferSearchTransformer;

class FavoriteRuleResponse
{
    public const DATA_EMAIL_TO = 'email_to';
    public const DATA_EMAIL_FROM = 'email_from';
    public const DATA_EMAIL_SUBJECT = 'email_subject';
    public const DATA_USER_FIRST_NAME = 'user_first_name';
    public const DATA_USER_ID = 'user_id';

    protected $locale;

    /** @var EntityManager */
    protected $entityManager;

    /** @var array */
    protected $offerData = [];

    /** @var bool */
    protected $parsed = false;

    /** @var string */
    protected $relatedSearchUrl;

    /** @var Result */
    protected $relatedSearchResult;

    /** @var array */
    protected $relatedOffers = [];

    /** @var int */
    protected $relatedNumberFound = 0;

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
     * @param array $data
     */
    public function setOfferData($data)
    {
        $this->offerData = $data;
    }

    /**
     * @return array
     */
    public function getOfferData()
    {
        return $this->offerData;
    }

    /**
     * @param string $locale
     */
    public function setLocale($locale)
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

    /**
     * @param string $name
     * @param mixed $value
     * @return array
     */
    public function setData(string $name, $value)
    {
        $this->data[$name] = $value;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getData(string $name)
    {
        return $this->data[$name];
    }

    private function parse()
    {
        if ($this->parsed == false) {
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

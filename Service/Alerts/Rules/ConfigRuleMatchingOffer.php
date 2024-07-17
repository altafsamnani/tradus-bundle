<?php

namespace TradusBundle\Service\Alerts\Rules;

use TradusBundle\Service\Config\ConfigService;
use TradusBundle\Service\Search\SearchService;

/**
 * Class ConfigRuleMatchingOffer.
 */
class ConfigRuleMatchingOffer implements ConfigRuleInterface
{
    /* @var int */
    protected $ruleType = AlertRuleMatchingOffer::RULE_TYPE_MATCHING_OFFER;

    /* @var string */
    protected $sendFirstUpdateAfter;

    /* @var string */
    protected $sendUpdateInterval;

    /* @var boolean */
    protected $filterFreeSellers;

    protected $filterIncludeCountries;

    /* @var string */
    protected $filterSort = SearchService::REQUEST_VALUE_SORT_DATE_DESC;

    /* @var int */
    protected $filterLimit;

    public function __construct()
    {
        $this->loadConfiguration();
    }

    public function loadConfiguration()
    {
        global $kernel;
        /* @var ConfigService $config */
        $config = $kernel->getContainer()->get('tradus.config');
        $this->sendFirstUpdateAfter = $config->getSettingValue('alert.rule.matchingOffer.sendFirstUpdateAfter');
        $this->sendUpdateInterval = $config->getSettingValue('alert.rule.matchingOffer.sendUpdateInterval');
        $this->filterLimit = $config->getSettingValue('alert.rule.matchingOffer.filterLimit');
        $this->filterFreeSellers = $config->getSettingValue('alert.rule.matchingOffer.filterFreeSellers');
        $this->filterIncludeCountries = $config->getSettingValue('alert.rule.matchingOffer.filterIncludeCountries');
    }

    public function getFilterIncludeCountries()
    {
        return $this->filterIncludeCountries;
    }

    /**
     * @return int
     */
    public function getRuleType()
    {
        return $this->ruleType;
    }

    /**
     * Get the date for sending the first update.
     * @return \DateTime
     */
    public function getFirstUpdateDate()
    {
        $createdAtDateTime = new \DateTime();
        $createdAtDateTime->modify('-'.$this->sendFirstUpdateAfter);

        return $createdAtDateTime;
    }

    /**
     * get the interval date for sending updates.
     * @return \DateTime
     */
    public function getIntervalDate()
    {
        $lastSendAtDateTime = new \DateTime();
        $lastSendAtDateTime->modify('-'.$this->sendUpdateInterval);

        return $lastSendAtDateTime;
    }

    /**
     * @return bool
     */
    public function getFilterFreeSellers()
    {
        return $this->filterFreeSellers;
    }

    /**
     * @return string
     */
    public function getFilterSort()
    {
        return $this->filterSort;
    }

    /**
     * @return int
     */
    public function getFilterLimit()
    {
        return $this->filterLimit;
    }
}

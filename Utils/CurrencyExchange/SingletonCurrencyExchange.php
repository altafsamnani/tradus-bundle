<?php

namespace TradusBundle\Utils\CurrencyExchange;

use TradusBundle\Factory\ProApiFactory;
use TradusBundle\Service\Redis\RedisService;

class SingletonCurrencyExchange
{
    // PLEASE DO NOT ADD LOGIC TO THIS FILE, THE EXCHANGES ARE HAPPENING ON TPRO SIDE
    /* @var ProApiFactory */
    private $apiPro;

    public function __construct()
    {
        global $kernel;

        $this->apiPro = $kernel->getContainer()->get('pro.api_factory');
    }

    // PLEASE DO NOT ADD LOGIC TO THIS FILE, THE EXCHANGES ARE HAPPENING ON TPRO SIDE
    public function getExchangeRates()
    {
        $redis = new RedisService('');
        $redis->setNamespace('');
        $rates = $redis->getParameter('exchange_rates:for_wl');

        if ($rates) {
            return json_decode($rates, true);
        }

        global $kernel;
        $container = $kernel->getContainer();
        /** @var ProApiFactory $apiPRO */
        $apiPRO = $container->get('pro.api_factory');
        $rates = $apiPRO->get('exchanges-wl');

        return $rates;
    }

    // PLEASE DO NOT ADD LOGIC TO THIS FILE, THE EXCHANGES ARE HAPPENING ON TPRO SIDE
}

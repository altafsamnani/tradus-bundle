<?php

namespace TradusBundle\Repository;

use Doctrine\ORM\EntityRepository;
use TradusBundle\Utils\CurrencyExchange\CurrencyExchange;
use TradusBundle\Utils\MysqlHelper\MysqlHelper;

class ExchangeRateRepository extends EntityRepository
{
    /**
     * @return array
     */
    public function getExchangeRates()
    {
        $mysqlHelper = new MysqlHelper($this->getEntityManager()->getConnection());
        $exchangeRates = new CurrencyExchange($mysqlHelper->getConnection());

        return $exchangeRates->getSingletonExchangeRate();
    }

    /**
     * @return array
     */
    public function getAllCurrencyExchangeRates()
    {
        $queryBuilder = $this->createQueryBuilder('exchange_rate');

        return $queryBuilder
            ->select('exchange_rate')
            ->where(
                $queryBuilder->expr()->in(
                    'exchange_rate.id',
                    $this->createQueryBuilder('max_rate')
                        ->select('MAX(max_rate.id)')
                        ->groupBy('max_rate.currency')
                        ->getDQL()
                )
            )
            ->getQuery()
            ->getArrayResult();
    }
}

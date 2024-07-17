<?php

namespace TradusBundle\Service\SearchAnalytics;

use Doctrine\ORM\EntityManagerInterface;
use TradusBundle\Entity\EntityValidationTrait;
use TradusBundle\Entity\SearchAnalytics;
use TradusBundle\Service\Sitecode\SitecodeService;

/**
 * Class SearchAnalyticsService.
 */
class SearchAnalyticsService
{
    use EntityValidationTrait;

    /* @var \Doctrine\ORM\EntityManagerInterface */
    protected $entityManager;

    /* @var \TradusBundle\Repository\SearchAnalyticsRepository */
    protected $repository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->repository = $this->entityManager->getRepository('TradusBundle:SearchAnalytics');
    }

    /**
     * Create Search Analytics method.
     *
     * @param array $param
     * @return bool
     */
    public function createSearchAnalytics(array $param)
    {
        $ssc = new SitecodeService();
        $sitecodeId = $ssc->getSitecodeId();

        $entityManager = $this->entityManager;

        $searchAnalytics = $this->repository->getSearchAnalytics($param);

        if (! $searchAnalytics) {
            $searchAnalytics = new SearchAnalytics();

            ! isset($param['keyword']) ?: $searchAnalytics->setKeyword($param['keyword']);
            ! isset($param['country']) ?: $searchAnalytics->setCountry($param['country']);
            ! isset($param['catL1']) ?: $searchAnalytics->setCategoryL1Id($param['catL1']);
            ! isset($param['catL2']) ?: $searchAnalytics->setCategoryL2Id($param['catL2']);
            ! isset($param['catL3']) ?: $searchAnalytics->setCategoryL3Id($param['catL3']);
            ! isset($param['queryString']) ?: $searchAnalytics->setQueryString($param['queryString']);
            ! isset($param['searchUrl']) ?: $searchAnalytics->setSearchUrl($param['searchUrl']);
            ! isset($param['resultCount']) ?: $searchAnalytics->setResultCount($param['resultCount']);

            $entityManager->persist($searchAnalytics);

            $searchAnalytics->setHitsForYesterday(0);
        }

        //Update the hits
        $totalHits = $searchAnalytics->getHits() + 1;
        $todayHits = $searchAnalytics->getHitsForToday() + 1;
        $last7DaysHits = $searchAnalytics->getHitsForWeek() + 1;
        $last30DaysHits = $searchAnalytics->getHitsForMonth() + 1;

        $searchAnalytics->setHits($totalHits);
        $searchAnalytics->setHitsForToday($todayHits);
        $searchAnalytics->setHitsForWeek($last7DaysHits);
        $searchAnalytics->setHitsForMonth($last30DaysHits);
        $searchAnalytics->setSitecodeId($sitecodeId);
        $entityManager->persist($searchAnalytics);
        $entityManager->flush();

        return true;
    }
}

<?php

namespace TradusBundle\Repository;

use Doctrine\ORM\EntityRepository;
use TradusBundle\Service\Sitecode\SitecodeService;

/**
 * Class ReportImageRepository.
 */
class ReportImageRepository extends EntityRepository
{
    public function getReportedImages($offerId = 0, $sessionToken = '', $userId = 0, $offerImageId = 0)
    {
        $ssc = new SitecodeService();
        $sitecodeId = $ssc->getSitecodeId();
        $reportedImages = [];

        if (empty($offerId) || empty($sessionToken)) {
            return $reportedImages;
        }

        $queryBuilder = $this->createQueryBuilder('report_image')
            ->select('report_image')
            ->where('report_image.offer = :offerId')
            ->andWhere('report_image.sitecodeId = :sitecodeId')
            ->setParameter('offerId', $offerId)
            ->setParameter('sitecodeId', $sitecodeId);

        if (! empty($userId)) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->eq('report_image.sessionToken', "'$sessionToken'"),
                    $queryBuilder->expr()->eq('report_image.userId', $userId)
                )
            );
        } else {
            $queryBuilder->andWhere("report_image.sessionToken = '$sessionToken'");
        }

        if (! empty($offerImageId)) {
            $queryBuilder->andWhere('report_image.offerImageId = :offerImageId')
                ->setParameter('offerImageId', $offerImageId);
        }

        return $queryBuilder->getQuery()->getResult();
    }
}

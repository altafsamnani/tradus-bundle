<?php

namespace TradusBundle\Repository;

use Doctrine\ORM\EntityRepository;
use TradusBundle\Entity\SearchAlert;
use TradusBundle\Entity\Sitecodes;

/**
 * SearchAlertRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class SearchAlertRepository extends EntityRepository
{
    /**
     * @param int $userId
     * @param int $sitecodeId
     * @return mixed
     */
    public function getSearchAlertsByUser(int $userId, int $sitecodeId = Sitecodes::SITECODE_TRADUS)
    {
        return $this->createQueryBuilder('search')
            ->where('search.user = :userId')
            ->andWhere('search.status = :status')
            ->andWhere('search.sitecodeId = :sitecodeId')
            ->addOrderBy('search.updated_at', 'desc')
            ->setParameter('status', SearchAlert::STATUS_SUBSCRIBED)
            ->setParameter('userId', $userId)
            ->setParameter('sitecodeId', $sitecodeId)
            ->getQuery()
            ->getResult();
    }
}

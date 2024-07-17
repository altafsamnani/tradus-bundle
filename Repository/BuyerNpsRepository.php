<?php

namespace TradusBundle\Repository;

use Doctrine\ORM\EntityRepository;

class BuyerNpsRepository extends EntityRepository
{
    public function getBuyerNpsInPeriod(int $userId, int $sitecodeId, $date)
    {
        $experience = $this->createQueryBuilder('nps')
            ->select('nps')
            ->andWhere('nps.userId = :userId')
            ->andWhere('nps.sitecodeId = :sitecodeId')
            ->andWhere('nps.createdAt > :date')
            ->setParameter('userId', $userId)
            ->setParameter('sitecodeId', $sitecodeId)
            ->setParameter('date', $date)
            ->getQuery();

        return $experience->getOneOrNullResult();
    }
}

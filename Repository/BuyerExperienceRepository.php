<?php

namespace TradusBundle\Repository;

use Doctrine\ORM\EntityRepository;

class BuyerExperienceRepository extends EntityRepository
{
    public function getBuyerExperience(int $userId, int $sitecodeId)
    {
        $experience = $this->createQueryBuilder('experience')
            ->select('experience')
            ->andWhere('experience.userId = :userId')
            ->andWhere('experience.sitecodeId = :sitecodeId')
            ->setParameter('userId', $userId)
            ->setParameter('sitecodeId', $sitecodeId)
           ->getQuery();

        return $experience->getOneOrNullResult();
    }
}

<?php

namespace TradusBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;

class AutologinRepository extends EntityRepository
{
    /**
     * This method is not being used but it's here for later tests.
     *
     * @param string $token
     * @return mixed
     * @throws NonUniqueResultException
     */
    public function findOneByAutologinToken(string $token)
    {
        $autologin = $this->createQueryBuilder('autologin')
            ->select('autologin')
            ->where('type = :type')
            ->andWhere('token = :token')
            ->andWhere('usedDate IS NULL')
            ->setParameter('type', 1)
            ->setParameter('token', $token)
            ->getQuery()
            ->getOneOrNullResult();

        return $autologin;
    }
}

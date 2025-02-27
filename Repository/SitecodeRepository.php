<?php

namespace TradusBundle\Repository;

use Doctrine\ORM\EntityRepository;
use TradusBundle\Entity\Sitecodes;

/**
 * SitecodeRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class SitecodeRepository extends EntityRepository
{
    /**
     * @param string $slug
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getSitecodeByName(String $name)
    {
        $query = $this->createQueryBuilder('sitecodes')
            ->select('sitecodes')
            ->andWhere('sitecodes.sitecode = :name')
            ->andWhere('sitecodes.status = :status')
            ->setMaxResults(1)
            ->setParameter('name', $name)
            ->setParameter('status', Sitecodes::STATUS_ONLINE);

        return $query->getQuery()->getOneOrNullResult();
    }
}

<?php

namespace TradusBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Class ConfigurationRepository.
 */
class ConfigurationRepository extends EntityRepository
{
    /**
     * @return mixed
     */
    public function getAllConfigurations()
    {
        return $this->createQueryBuilder('configuration')
            ->select('configuration')
            ->getQuery()
            ->getResult();
    }
}

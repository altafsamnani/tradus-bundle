<?php

namespace TradusBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use TradusBundle\Entity\Version;

/**
 * Class VersionRepository.
 */
class VersionRepository extends EntityRepository
{
    /**
     * Get a list of versions based on a one or more models.
     *
     * @param array $models
     * @return array
     */
    public function getVersionsByModels(array $models)
    {
        $versions = [];
        $query = $this->createQueryBuilder('v')
            ->select('v.versionSlug, v.versionName, m.modelSlug')
            ->leftJoin('TradusBundle:Model', 'm', Join::WITH, 'v.modelId = m.id')
            ->where('m.modelSlug IN (:models)')
            ->andWhere('v.status = :status')
            ->orderBy('v.versionSlug')
            ->setParameter('models', $models)
            ->setParameter('status', Version::STATUS_ACTIVE)
            ->getQuery();

        $results = $query->getResult();
        foreach ($results as $result) {
            $versions[$result['modelSlug']][$result['versionSlug']] = $result['versionName'];
        }

        return $versions;
    }
}

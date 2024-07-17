<?php

namespace TradusBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use TradusBundle\Entity\Model;

/**
 * Class ModelRepository.
 */
class ModelRepository extends EntityRepository
{
    /**
     * Get a list of models based on one or mode makes.
     *
     * @param array $makes
     * @return array
     */
    public function getModelsByMakes(array $makes)
    {
        $models = [];
        $query = $this->createQueryBuilder('mo')
            ->select('mo.modelSlug, mo.modelName, ma.slug')
            ->leftJoin('TradusBundle:Make', 'ma', Join::WITH, 'mo.makeId = ma.id')
            ->where('ma.slug IN (:makes)')
            ->andWhere('mo.status = :status')
            ->orderBy('mo.modelSlug')
            ->setParameter('makes', $makes)
            ->setParameter('status', Model::STATUS_ACTIVE)
            ->getQuery();

        $results = $query->getResult();
        foreach ($results as $result) {
            $models[$result['slug']][$result['modelSlug']] = $result['modelName'];
        }

        return $models;
    }
}

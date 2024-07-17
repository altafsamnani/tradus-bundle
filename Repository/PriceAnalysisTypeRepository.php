<?php

namespace TradusBundle\Repository;

use Doctrine\ORM\EntityRepository;

class PriceAnalysisTypeRepository extends EntityRepository
{
    /**
     * Returns the values of price analysis types based on their slugs.
     *
     * @param array $slugs
     * @return array
     */
    public function getValuesBySlug(array $slugs)
    {
        $slugs = $this->createQueryBuilder('price_analysis_type')
            ->select('price_analysis_type.value')
            ->where('price_analysis_type.slug IN (:slugs)')
            ->setParameter('slugs', $slugs)
            ->getQuery()
            ->getArrayResult();

        return array_map(function ($value) {
            if ($value['value'] == 0) {
                return 'false';
            }

            return $value['value'];
        }, $slugs);
    }
}

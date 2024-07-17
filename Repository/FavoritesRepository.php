<?php

namespace TradusBundle\Repository;

use Doctrine\ORM\EntityRepository;
use TradusBundle\Entity\Favorites;

class FavoritesRepository extends EntityRepository
{
    /**
     * Get favorites based on offer id
     * (and sitecode id).
     *
     * @param int $offerId
     * @param int $sitecodeId
     * @param null $groupBy
     * @return mixed
     */
    public function getFavoritesByOfferId(int $offerId, int $sitecodeId, $groupBy = null)
    {
        $query = $this->createQueryBuilder('favorites')
            ->where('favorites.offer = :offerId')
            ->andWhere('favorites.status = :status')
            ->andWhere('favorites.sitecodeId = :sitecodeId')
            ->setParameter('offerId', $offerId)
            ->setParameter('status', Favorites::STATUS_FAVORITE)
            ->setParameter('sitecodeId', $sitecodeId);

        if ($groupBy) {
            $query->groupBy('favorites.'.$groupBy);
        }

        return $query->getQuery()
            ->getResult();
    }
}

<?php

namespace TradusBundle\Repository;

use TradusBundle\Entity\Offer;
use TradusBundle\Repository\Contract\OfferAttributeRepositoryInterface;

class OfferAttributeRepository extends \Doctrine\ORM\EntityRepository implements OfferAttributeRepositoryInterface
{
    /**
     * @param Offer $offer
     * @throws \Doctrine\DBAL\DBALException
     */
    public function deleteAllByOffer(Offer $offer) : void
    {
        $sql = 'DELETE FROM offer_attributes where offer_id = :offerId';
        $statement = $this->getEntityManager()->getConnection()->prepare($sql);
        $statement->bindValue('offerId', $offer->getId());
        $statement->execute();
    }

    /**
     * @param Offer $offer
     * @throws \Doctrine\DBAL\DBALException
     */
    public function softDeleteAllByOffer(Offer $offer) : void
    {
        $sql = 'UPDATE offer_attributes set status = "'.self::STATUS_DELETED.'" where offer_id = :offerId';
        $statement = $this->getEntityManager()->getConnection()->prepare($sql);
        $statement->bindValue('offerId', $offer->getId());
        $statement->execute();
    }
}

<?php

namespace TradusBundle\Repository;

use TradusBundle\Entity\Offer;
use TradusBundle\Entity\OfferImageInterface;
use TradusBundle\Repository\Contract\OfferImageRepositoryInterface;

class OfferImageRepository extends \Doctrine\ORM\EntityRepository implements OfferImageRepositoryInterface
{
    /**
     * @param Offer $offer
     * @throws \Doctrine\DBAL\DBALException
     */
    public function deleteAllByOffer(Offer $offer) : void
    {
        $sql = 'DELETE FROM offer_images where offer_id = :offerId';
        $statement = $this->getEntityManager()->getConnection()->prepare($sql);
        $statement->bindValue('offerId', $offer->getId());
        $statement->execute();

        $offer->removeAllImage();
    }

    /**
     * @param Offer $offer
     * @throws \Doctrine\DBAL\DBALException
     */
    public function softDeleteAllByOffer(Offer $offer) : void
    {
        $sql = 'UPDATE offer_images set status = "'.self::STATUS_DELETED
                .'" where offer_id = :offerId';
        $statement = $this->getEntityManager()->getConnection()->prepare($sql);
        $statement->bindValue('offerId', $offer->getId());
        $statement->execute();
    }

    /**
     * @param array $params
     * @return array
     */
    public function calculateAspectRatio(array $params)
    {
        $response = [
            OfferImageInterface::PARAMETER_WIDTH => $params[OfferImageInterface::PARAMETER_WIDTH],
            OfferImageInterface::PARAMETER_HEIGHT => $params[OfferImageInterface::PARAMETER_HEIGHT],
        ];

        if ($params['imageWidth'] >= $params['imageHeight']) {
            if ($params['imageWidth'] <= $params['width'] && $params['imageHeight'] <= $params['height']) {
                return $response;
            }  // no resizing required
            $wRatio = $params['width'] / $params['imageWidth'];
            $hRatio = $params['height'] / $params['imageHeight'];
        } else {
            if ($params['imageHeight'] <= $params['width'] && $params['imageWidth'] <= $params['height']) {
                return $response;
            } // no resizing required
            $wRatio = $params['height'] / $params['imageWidth'];
            $hRatio = $params['width'] / $params['imageHeight'];
        }

        $resizeRatio = min($wRatio, $hRatio);

        $response = [
            OfferImageInterface::PARAMETER_WIDTH => floor($params['imageWidth'] * $resizeRatio),
            OfferImageInterface::PARAMETER_HEIGHT => floor($params['imageHeight'] * $resizeRatio),
        ];

        return $response;
    }
}

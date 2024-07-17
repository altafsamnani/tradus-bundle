<?php

namespace TradusBundle\Service\Seller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use TradusBundle\Entity\Seller;
use TradusBundle\Utils\Google\Map\MapApi;

class SellerService
{
    /** @var EntityManager */
    protected $entityManager;

    /**
     * SellerService constructor.
     *
     * @param EntityManager $entityManager entity manager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param Seller $seller
     * @return Seller
     * @throws ORMException
     */
    public function updateGeolocation(Seller $seller)
    {
        try {
            if (! $seller->getGeoLocation()) {
                $geoLocation = (new MapApi())->getGeoLocation($seller->getAddress());
                if (isset($geoLocation['status']) && $geoLocation['status'] == 'OK') {
                    $seller->setGeoLocation(json_encode($geoLocation));
                    $this->entityManager->persist($seller);
                    $this->entityManager->flush();
                }
            }
            $sellerGeoLocation = json_decode($seller->getGeoLocation(), true);
            $geoLocation = [];
            if ($sellerGeoLocation) {
                $geoLocation['lat'] = (float) $sellerGeoLocation['lat'];
                $geoLocation['lng'] = (float) $sellerGeoLocation['lng'];
            }
            $seller->setGeoLocationObject($geoLocation);
        } catch (InvalidParameterException $exception) {
            // do nothing for now
        }

        return $seller;
    }
}

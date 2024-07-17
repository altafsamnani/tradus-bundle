<?php

namespace TradusBundle\Repository\Contract;

use TradusBundle\Entity\Offer;

interface OfferAttributeRepositoryInterface
{
    // Status
    const STATUS_ONLINE = 100;
    const STATUS_DELETED = -200;

    public function deleteAllByOffer(Offer $offer): void;

    public function softDeleteAllByOffer(Offer $offer): void;
}

<?php

namespace TradusBundle\Service\Contract;

use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use TradusBundle\Entity\Attribute;
use TradusBundle\Entity\Offer;
use TradusBundle\Entity\OfferDescription;

/**
 * Interface OfferServiceInterface.
 */
interface OfferServiceInterface
{
    /**
     * @param string $slug
     * @param string $locale
     *
     * @return Offer
     * @throws NotFoundHttpException
     * @throws NonUniqueResultException
     */
    public function findOfferBySlug(string $slug, string $locale): Offer;

    /**
     * @param string $ad_id
     *
     * @return Offer
     * @throws NonUniqueResultException
     * @throws NotFoundHttpException
     */
    public function findOfferByAdId(string $ad_id): Offer;

    /**
     * @param int $offer_id
     *
     * @return Offer
     * @throws NonUniqueResultException
     * @throws NotFoundHttpException
     */
    public function findOfferById(int $offer_id);

    /**
     * @param array $params
     *
     * @return Offer
     * @throws UnprocessableEntityHttpException
     * @throws NotFoundHttpException
     */
    public function storeOffer(array $params): Offer;

    /**
     * @param array $params
     *
     * @return Offer
     * @throws NotFoundHttpException
     * @throws UnprocessableEntityHttpException
     */
    public function patchOffer(array $params): Offer;

    /**
     * @param array $params
     *
     * @return Offer
     * @throws NotFoundHttpException
     * @throws UnprocessableEntityHttpException
     */
    public function putOffer(array $params): Offer;

    /**
     * @param int $offer_id
     *
     * @return Offer
     * @throws NotFoundHttpException
     */
    public function deleteOffer(int $offer_id): Offer;

    /**
     * @param array $params
     * @param Offer|null $offer
     * @param bool $patch
     * @param bool $persist
     *
     * @return Offer
     * @throws UnprocessableEntityHttpException
     * @throws NotFoundHttpException
     *
     * TODO this method should not be so large it should be split into smaller methods.
     */
    public function populateOffer(
        array $params,
        ?Offer $offer = null,
        bool $patch = false,
        bool $persist = true
    ): Offer;

    /**
     * @param int $offer_id
     *
     * @return Offer
     * @throws NotFoundHttpException
     */
    public function restoreOffer(int $offer_id): Offer;

    /**
     * @param $descriptions
     * @param Offer $offer
     * @param $slug
     */
    public function saveOfferDescriptions($descriptions, Offer $offer, $slug);

    /**
     * @param array $params
     * @param Offer $offer
     * @param bool $patch
     *
     * @return Offer
     */
    public function setOfferAttributes(array $params, Offer $offer, bool $patch): Offer;

    /**
     * @param Offer $offer
     * @param Attribute $attribute
     * @param string $content
     * @param bool $patch
     */
    public function saveAttribute(Offer $offer, Attribute $attribute, string $content, bool $patch, $tamerValue = 0);

    /**
     * @param Offer $offer
     * @param OfferDescription $offerDescription
     * @param $title
     */
    public function saveTitle(Offer $offer, OfferDescription $offerDescription, $title);
}

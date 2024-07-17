<?php

namespace TradusBundle\Service\Contract;

use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use TradusBundle\Entity\Seller;

/**
 * Interface SellerServiceInterface.
 */
interface SellerServiceInterface
{
    public const DELETE_ACTION_ANONYMIZE = 'anonymize';

    /**
     * @param string $slug
     *
     * @return Seller
     * @throws NonUniqueResultException
     * @throws NotFoundHttpException
     */
    public function findSellerBySlug(string $slug): Seller;

    /**
     * @param string $email
     *
     * @return mixed
     */
    public function findSellerByEmail(string $email);

    /**
     * @param array $params
     *
     * @return Seller
     * @throws UnprocessableEntityHttpException
     */
    public function storeSeller(array $params): Seller;

    /**
     * @param array $params
     *
     * @return Seller
     * @throws NotFoundHttpException
     * @throws UnprocessableEntityHttpException
     */
    public function patchSeller(array $params): Seller;

    /**
     * @param array $params
     *
     * @return Seller
     * @throws NotFoundHttpException
     */
    public function putSeller(array $params): Seller;

    /**
     * @param int $seller_id
     * @param bool $hardDelete
     *
     * @return Seller
     * @throws NotFoundHttpException
     */
    public function deleteSeller(int $seller_id, bool $hardDelete = false): Seller;

    /**
     * @param array $params
     * @param Seller|null $seller
     *
     * @return Seller
     * @throws UnprocessableEntityHttpException
     */
    public function populateSeller(array $params, ?Seller $seller = null, bool $persist = true): Seller;

    /**
     * @param int $seller_id
     *
     * @return Seller
     * @throws NotFoundHttpException
     */
    public function restoreSeller(int $seller_id): Seller;

    /**
     * @return array
     */
    public function getSellerStatuses(): array;

    /**
     * @param Seller $seller
     *
     * @return mixed
     * @throws NonUniqueResultException
     */
    public function offlineSellerOffers(Seller $seller);
}

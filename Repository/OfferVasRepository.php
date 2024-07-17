<?php

namespace TradusBundle\Repository;

use DateTime;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use TradusBundle\Entity\OfferVas;
use TradusBundle\Entity\Vas;

class OfferVasRepository extends EntityRepository
{
    /**
     * Function getExpiredOffersVAS.
     * @param int $sitecodeId
     * @return mixed
     */
    public function getExpiredOffersVAS(int $sitecodeId = 1)
    {
        $query = $this->createQueryBuilder('ov')
            ->select('ov')
            ->where('ov.status= :status')
            ->andWhere('ov.endDate < :now')
            ->andWhere('ov.sitecodeId = :sitecode_id')
            ->setParameter('status', OfferVas::STATUS_ONLINE)
            ->setParameter('now', date('Y-m-d H:i:s'))
            ->setParameter('sitecode_id', $sitecodeId);

        return $query->getQuery()->getResult();
    }

    /**
     * Function getOffersToBumpVAS.
     * @param int $sitecodeId
     * @return mixed
     */
    public function getOffersToBumpVAS(int $sitecodeId = 1)
    {
        $query = $this->createQueryBuilder('ov')
            ->select('ov')
            ->where('ov.status= :status')
            ->andWhere('ov.startDate <= :now')
            ->andWhere('ov.endDate >= :now')
            ->andWhere('ov.sitecodeId = :sitecode_id')
            ->andWhere('ov.vasId = :vas_id')
            ->setParameter('status', OfferVas::STATUS_ONLINE)
            ->setParameter('now', date('Y-m-d H:i:s'))
            ->setParameter('sitecode_id', $sitecodeId)
            ->setParameter('vas_id', Vas::BUMP_UP);

        return $query->getQuery()->getResult();
    }

    /**
     * @param array $params
     * @throws OptimisticLockException
     * @throws Exception
     * @throws ORMException
     *
     * @return OfferVas
     */
    public function storeOfferVas(array $params): OfferVas
    {
        $offerVas = new OfferVas();
        $offerVas->setSitecodeId($params['sitecodeId']);
        $offerVas->setOfferId($params['offer']);
        $offerVas->setVasId($params['vasId']);
        if (is_string($params['startDate'])) {
            $params['startDate'] = new DateTime($params['startDate']);
        }
        if (is_string($params['endDate'])) {
            $params['endDate'] = new DateTime($params['endDate']);
        }
        $offerVas->setStartDate($params['startDate']);
        $offerVas->setEndDate($params['endDate']);
        $offerVas->setSitecodeId($params['sitecodeId']);

        $entityManager = $this->getEntityManager();

        $entityManager->persist($offerVas);
        $this->getEntityManager()->flush();

        return $offerVas;
    }
}

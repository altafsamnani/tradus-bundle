<?php

namespace TradusBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use TradusBundle\Entity\TradusUser;

/**
 * Class TradusUserRepository.
 */
class TradusUserRepository extends EntityRepository
{
    /**
     * @param string $email
     * @return mixed
     * @throws NonUniqueResultException
     */
    public function findOneByEmail(string $email)
    {
        $tradusUser = $this->createQueryBuilder('tradus_users')
            ->select('tradus_users')
            ->where('tradus_users.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();

        return $tradusUser;
    }

    /**
     * @param int $id
     * @return mixed
     * @throws NonUniqueResultException
     */
    public function findOneById(int $id)
    {
        $tradusUser = $this->createQueryBuilder('tradus_users')
            ->select('tradus_users')
            ->where('tradus_users.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();

        return $tradusUser;
    }

    /**
     * @param string $code
     * @return mixed
     * @throws NonUniqueResultException
     */
    public function findOneByConfirmationToken(string $code)
    {
        $tradusUser = $this->createQueryBuilder('tradus_users')
            ->select('tradus_users')
            ->where('tradus_users.confirmation_token = :code')
            ->andWhere('tradus_users.status = :status')
            ->setParameter('code', $code)
            ->setParameter('status', TradusUser::STATUS_PENDING)
            ->getQuery()
            ->getOneOrNullResult();

        return $tradusUser;
    }

    /**
     * @param string $appleId
     * @return mixed
     * @throws NonUniqueResultException
     */
    public function findOneByAppleId(string $appleId)
    {
        $tradusUser = $this->createQueryBuilder('tradus_users')
            ->select('tradus_users')
            ->where('tradus_users.appleID = :apple_id')
            ->setParameter('apple_id', $appleId)
            ->getQuery()
            ->getOneOrNullResult();

        return $tradusUser;
    }
}

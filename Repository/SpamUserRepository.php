<?php

namespace TradusBundle\Repository;

use Doctrine\ORM\EntityRepository;
use TradusBundle\Entity\SpamUser;

/**
 * Class SpamUserRepository.
 */
class SpamUserRepository extends EntityRepository
{
    /**
     * Finds one occurrence of a spammer based on email address.
     *
     * @param string $email spam user email
     *
     * @return mixed
     *
     * @throws NonUniqueResultException
     */
    public function findOneByEmail(string $email)
    {
        $spamUser = $this->createQueryBuilder('spam_users')
            ->select('spam_users')
            ->where('spam_users.email = :email')
            ->andWhere('spam_users.status = :status')
            ->setParameter('email', $email)
            ->setParameter('status', SpamUser::STATUS_ACTIVE)
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();

        return $spamUser;
    }
}

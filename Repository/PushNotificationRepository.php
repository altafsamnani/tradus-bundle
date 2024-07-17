<?php

namespace TradusBundle\Repository;

use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use TradusBundle\Entity\PushNotification;

class PushNotificationRepository extends EntityRepository
{
    /**
     * Store the push notification data and return the id.
     *
     * @param array $storeData
     * @param string $section
     * @param null $appDataId
     * @param null $userId
     * @return int
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function store($storeData = [], $section = 'data', $appDataId = null, $userId = null): int
    {
        $pushNotification = new PushNotification();
        $pushNotification->setAppDataId($appDataId);
        $pushNotification->setUserId($userId);
        $pushNotification->setPushtoken($storeData['to']);
        $pushNotification->setTitle($storeData['data']['title']);
        $pushNotification->setBody($storeData[$section]['body']);
        $pushNotification->setUrl($storeData['data']['url']);
        $pushNotification->setData(json_encode($storeData));
        $pushNotification->setCreatedAt(new DateTime());

        /** @var EntityManager $entityManager */
        $entityManager = $this->getEntityManager();
        $entityManager->persist($pushNotification);
        $this->getEntityManager()->flush();

        return $pushNotification->getId();
    }
}

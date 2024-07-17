<?php

namespace TradusBundle\Service\Helper;

use Aws\Emr\Exception\InternalServerErrorException;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use TradusBundle\Entity\Anonymize;
use TradusBundle\Service\Contract\AnonymizeServiceInterface;

/**
 * Class AnonymizeServiceHelper.
 */
class AnonymizeServiceHelper implements AnonymizeServiceInterface
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * AnonymizeServiceHelper constructor.
     *
     * @param EntityManagerInterface $entity_manager
     */
    public function __construct(
        EntityManagerInterface $entity_manager
    ) {
        $this->entityManager = $entity_manager;
    }

    /**
     * After anonymization save user id for cron run.
     *
     * @param int $userId id of the user
     * @param string $country
     *
     * @return Anonymize
     */
    public function anonymizeUser(int $userId, string $country): Anonymize
    {
        if (empty($country) || empty($userId)) {
            throw new InternalServerErrorException('Invalid argument supplied!');
        }

        $anonymized = new Anonymize();
        $anonymized->setUserId($userId);
        $anonymized->setCountry($country);
        $anonymized->setStatus(Anonymize::STATUS_DEACTIVATED);
        $anonymized->setCreatedAt(new DateTime());
        $this->entityManager->persist($anonymized);
        $this->entityManager->flush();

        return $anonymized;
    }
}

<?php

namespace TradusBundle\Service\TradusUser;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Translation\Translator;
use TradusBundle\Entity\BuyerNps;
use TradusBundle\Entity\EntityValidationTrait;
use TradusBundle\Entity\TradusUser;
use TradusBundle\Service\Switchboard\SwitchboardService;

/**
 * Class TradusUserService.
 */
class BuyerNpsService
{
    use EntityValidationTrait;

    /** @var EntityManagerInterface $entityManager */
    protected $entityManager;

    /** @var Translator $translator */
    protected $translator;

    /** @var SwitchboardService $switchboardService */
    protected $switchboardService;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        global $kernel;
        $this->translator = $kernel->getContainer()->get('translator');
        $this->entityManager = $entityManager;
        $this->switchboardService = $kernel->getContainer()->get('switchboard.service');
    }

    /**
     * Send the buyer nps survey using the switchboard.
     *
     * @param TradusUser $user
     * @param string $whitelabel
     */
    public function sendNpsSurvey(TradusUser $user, string $whitelabel)
    {
        try {
            $this->switchboardService->sendEmail($user, $whitelabel);
        } catch (Exception $e) {
            $logger = $this->container->get('logger');
            $logger->error($e->getMessage(), ['replyToConversation']);
        }
    }

    /**
     * After sending the buyer nps survey, save record to db.
     *
     * @param int $userId
     * @param int $sitecodeId
     * @param string $locale
     * @return BuyerNps
     */
    public function saveNpsSurvey(int $userId, int $sitecodeId, string $locale)
    {
        $buyerNps = new BuyerNps();
        $buyerNps->setUserId($userId);
        $buyerNps->setSitecodeId($sitecodeId);
        $buyerNps->setCreatedAt(new DateTime());
        $buyerNps->setLocale($locale);
        $this->entityManager->persist($buyerNps);
        $this->entityManager->flush();

        return $buyerNps;
    }
}

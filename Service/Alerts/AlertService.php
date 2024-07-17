<?php

namespace TradusBundle\Service\Alerts;

use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;
use TradusBundle\Entity\Alerts;
use TradusBundle\Entity\SimilarOfferAlert;
use TradusBundle\Entity\Sitecodes;
use TradusBundle\Entity\TradusUser;
use TradusBundle\Mailer\TradusMailer;
use TradusBundle\Repository\AlertsRepository;
use TradusBundle\Repository\SimilarOfferAlertRepository;
use TradusBundle\Service\Alerts\Notifications\PushNotification;
use TradusBundle\Service\Alerts\Rules\AlertRuleResponse;
use TradusBundle\Service\Config\ConfigService;
use TradusBundle\Service\Mail\MailService;
use TradusBundle\Service\TradusApp\TradusAppService;

/**
 * Class AlertService.
 */
class AlertService
{
    // @var EntityManager
    protected $entityManager;

    // @var TradusUser
    protected $user;

    // @var TradusMailer
    protected $mailer;

    /** @var ConfigService $config */
    protected $config;

    /** @var MailService */
    protected $mailService;

    /** @var string */
    protected $environment;

    /** @var int */
    protected $sitecodeId;
    /** @var ContainerInterface|null */
    private $container;

    public function __construct(EntityManager $entityManager, ?TradusMailer $mailer = null)
    {
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;

        global $kernel;

        $this->config = $kernel->getContainer()->get('tradus.config');

        $this->sitecodeId = $kernel
            ->getContainer()
            ->getParameter(Sitecodes::SITECODE_FIELD_CONFIG)[Sitecodes::SITECODE_FIELD_ID_CONFIG];

        $this->container = $kernel->getContainer();

        $this->mailService = $this->container->get('tradus.mail');
        $this->environment = $kernel->getEnvironment();
    }

    /**
     * @param TradusUser $user
     * @return $this
     */
    public function setUser(TradusUser $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return TradusUser $user
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param array $data
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     * @return Rules\AlertRuleMatchingOffer
     */
    public function createAlertMatchingOffer($data)
    {
        $offer = $searchUrl = null;
        if (! empty($data['offer'])) {
            $offer = $data['offer'];
        } else {
            $searchUrl = $data['searchUrl'];
        }

        $this->checkUserIsSet();
        $alert = new Rules\AlertRuleMatchingOffer($this->entityManager);
        $alert->setUser($this->getUser());
        if (! empty($offer)) {
            $alert->setOption('make', $offer->getMake()->getId());
            $alert->setOption('category', $offer->getCategory()->getId());
        } else {
            foreach (array_keys($alert->getOptions()) as $optName) {
                if (! empty($data[$optName])) {
                    $alert->setOption($optName, $data[$optName]);
                }
            }
        }
        $alertEntity = $alert->save($this->sitecodeId);

        if (! empty($offer)) {
            $criteriaArr = ['offer' => $offer, 'user' => $this->getUser()];
        } else {
            $criteriaArr = ['searchUrl' => $searchUrl, 'user' => $this->getUser()];
        }
        $criteriaArr['sitecodeId'] = $data['sitecodeId'];
        $similarOfferAlert = $this->entityManager->getRepository('TradusBundle:SimilarOfferAlert')
            ->findOneBy($criteriaArr);

        if (! $similarOfferAlert) {
            $similarOfferAlert = new SimilarOfferAlert();
            $similarOfferAlert->setUser($this->getUser());
            if (! empty($offer)) {
                $similarOfferAlert->setOffer($offer);
            } else {
                $similarOfferAlert->setSearchUrl($searchUrl);
            }
            $similarOfferAlert->setStatus(SimilarOfferAlert::STATUS_SUBSCRIBED);
            $similarOfferAlert->setAlert($alertEntity);
            $similarOfferAlert->setSitecodeId($data['sitecodeId']);
        } else {
            $similarOfferAlert->setStatus(SimilarOfferAlert::STATUS_SUBSCRIBED);
            $similarOfferAlert->setAlert($alertEntity);
        }
        $this->entityManager->persist($similarOfferAlert);
        $this->entityManager->flush();

        return $alert;
    }

    /**
     * @param OutputInterface $output
     * @return int
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    public function findAlertMatchingOffer(OutputInterface $output)
    {
        $configRule = new Rules\ConfigRuleMatchingOffer();
        $count = $this->findAlertsForSendingUpdate($configRule, $output);
        if (empty($count)) {
            $output->writeln('Nothing to send.');

            return 0;
        }

        return 1;
    }

    /**
     * @param  AlertRuleResponse  $response
     * @param  OutputInterface|null  $output
     * @return void
     */
    public function sendEmail(AlertRuleResponse $response, ?OutputInterface $output = null)
    {
        if (! $this->mailer) {
            throw new Exception('Mailer is not set', 500);
        }

        $alertId = $response->getData(AlertRuleResponse::DATA_ALERT_ID);
        $emailTo = $response->getData(AlertRuleResponse::DATA_EMAIL_TO);

        if (! count($response->getOffers())) {
            $output->writeln('['.$alertId.'] Skip update [no offers to show]: '.$emailTo);

            return;
        }

        $output->writeln('['.$alertId.'] Sending update to: '.$emailTo);
        $this->mailer->sendSimilarOffersAlertEmail($response);
    }

    public function sendPushNotification(PushNotification $notification, ?OutputInterface $output = null)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->container->get('doctrine.orm.tradus_entity_manager');
        $tradusAppService = new TradusAppService($entityManager);

        $tradusAppService->sendPushNotification($notification->toArray());
        $output->writeln('Push message sent successfully.');
    }

    /**
     * @param bool $onlyOfferAlerts
     * @return array
     * @throws Exception
     */
    public function getActiveSimilarOfferAlerts(bool $onlyOfferAlerts = false)
    {
        $this->checkUserIsSet();

        $similarOfferAlerts = $this->entityManager->getRepository('TradusBundle:SimilarOfferAlert')
            ->createQueryBuilder('similar_offer_alerts')
            ->select('similar_offer_alerts')
            ->where('similar_offer_alerts.user = :userId')
            ->andWhere('similar_offer_alerts.status = :status')
            ->andWhere('similar_offer_alerts.sitecodeId = :sitecodeId')
            ->setParameter('userId', $this->getUser()->getId())
            ->setParameter('sitecodeId', $this->sitecodeId)
            ->setParameter('status', SimilarOfferAlert::STATUS_SUBSCRIBED);

        if ($onlyOfferAlerts) {
            $similarOfferAlerts->andWhere('similar_offer_alerts.offer IS NOT NULL');
        }

        return $similarOfferAlerts->getQuery()->getResult();
    }

    /**
     * @param array $data
     * @param int $sitecodeId
     * @return null|object
     * @throws Exception
     */
    public function findSimilarOfferAlertByOffer($data, int $sitecodeId = Sitecodes::SITECODE_TRADUS)
    {
        $criteriaArr = [
            'sitecodeId' => $sitecodeId,
            'user' => $this->getUser(),
        ];
        if (! empty($data['offer'])) {
            $criteriaArr['offer'] = [$data['offer']];
        }
        if (! empty($data['searchUrl'])) {
            $criteriaArr['searchUrl'] = [$data['searchUrl']];
        }
        $this->checkUserIsSet();
        /** @var SimilarOfferAlertRepository $similarOfferRepo */
        $similarOfferRepo = $this->entityManager->getRepository('TradusBundle:SimilarOfferAlert');

        return $similarOfferRepo->findOneBy($criteriaArr);
    }

    /**
     * @param int $sId
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    public function unsubscribeOffer($sId)
    {
        $this->checkUserIsSet();
        $alert = false;
        /** @var SimilarOfferAlert $similarOfferAlert */
        $similarOfferAlert = $this->entityManager->getRepository('TradusBundle:SimilarOfferAlert')
            ->findOneBy(['user' => $this->getUser(), 'id' => $sId]);
        if ($similarOfferAlert && $similarOfferAlert->getStatus() == SimilarOfferAlert::STATUS_SUBSCRIBED) {
            $similarOfferAlert->setStatus(SimilarOfferAlert::STATUS_UNSUBSCRIBED);
            $this->entityManager->persist($similarOfferAlert);
            $this->entityManager->flush();
            $alert = $similarOfferAlert->getAlert();
        }
        if ($alert) {
            $criteria = [
                'alert' => $alert,
                'user' => $alert->getUser(),
                'status' => SimilarOfferAlert::STATUS_SUBSCRIBED,
            ];
            $similarOfferAlerts = $this->entityManager->getRepository('TradusBundle:SimilarOfferAlert')
                ->findby($criteria);
            if (! $similarOfferAlerts) {
                $alert->setStatus(Alerts::STATUS_DEACTIVATED);
                $this->entityManager->persist($alert);
                $this->entityManager->flush();
            }
        }
    }

    /**
     * @param int $alertId
     * @return bool
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function unsubscribe($alertId)
    {
        $alert = $this->getAlert($alertId);

        if (! $alert) {
            throw new NotFoundHttpException('Alert '.$alertId.' not found.');
        }

        $currentStatus = $alert->getStatus();
        if ($currentStatus != Alerts::STATUS_DEACTIVATED) {
            $alert->setStatus(Alerts::STATUS_DEACTIVATED);
            $this->entityManager->persist($alert);
            $this->entityManager->flush();
        }
        $condition = [
            'alert' => $alert,
            'user' => $alert->getUser(),
            'status' => SimilarOfferAlert::STATUS_SUBSCRIBED,
            'sitecodeId' => $this->sitecodeId,
        ];
        $similarOfferAlerts = $this->entityManager->getRepository('TradusBundle:SimilarOfferAlert')->findby($condition);
        if ($similarOfferAlerts && count($similarOfferAlerts)) {
            foreach ($similarOfferAlerts as $similarOfferAlert) {
                /* @var SimilarOfferAlert $similarOfferAlert */
                $similarOfferAlert->setStatus(SimilarOfferAlert::STATUS_UNSUBSCRIBED);
                $this->entityManager->persist($similarOfferAlert);
            }
            $this->entityManager->flush();
        }

        return true;
    }

    /**
     * @param $alertId
     * @return Alerts
     */
    public function getAlert($alertId)
    {
        /** @var AlertsRepository $alertsRepository */
        $alertsRepository = $this->entityManager->getRepository('TradusBundle:Alerts');
        /** @var Alerts $alert */
        $alert = $alertsRepository->findOneBy(['id' => $alertId]);

        return $alert;
    }

    /**
     * @param Rules\ConfigRuleInterface $configRule
     * @param OutputInterface $output
     * @return int
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    private function findAlertsForSendingUpdate(Rules\ConfigRuleInterface $configRule, OutputInterface $output)
    {
        /** @var AlertsRepository $alertsRepository */
        $alertsRepository = $this->entityManager->getRepository('TradusBundle:Alerts');

        $alertType = $configRule->getRuleType();
        $firstUpdateDate = $configRule->getFirstUpdateDate();
        $intervalDate = $configRule->getIntervalDate();

        // LIMIT AMOUNT OF UPDATES WE WANT TO SEND TO ONE USER
        $timeFrame = $this->config->getSettingValue('alert.limit.timeframe');
        $maxUpdates = $this->config->getSettingValue('alert.limit.updates');
        $userIds = $alertsRepository->maxAlertSentUsers($timeFrame, $maxUpdates, $this->sitecodeId);

        $total = 0;
        $lastId = 0;
        $alertError = [];
        while ($total < 250 && $alertEntity = $alertsRepository->findAllForSendingUpdate(
            $alertType,
            $firstUpdateDate,
            $intervalDate,
            $userIds,
            $this->sitecodeId,
            $lastId
        )) {
            if ($alertEntity) {
                $alertRule = null;
                $lastId = $alertEntity->getId();
                $methodsEntered = [];
                try {
                    $user = $alertEntity->getUser();

                    // DON"T SEND UPDATES TO DELETED USERS
                    if ($user->isDeleted()) {
                        $methodsEntered[] = 'User is Deleted';
                        // Remove alert because user is deactivated/deleted
                        $alertEntity->setStatus(Alerts::STATUS_DELETED);
                        $this->entityManager->persist($alertEntity);
                        $this->entityManager->flush();
                        continue;
                    }

                    // WHEN NEW RULE THEN ADD IT HERE!
                    if ($alertType == Rules\AlertRuleInterface::RULE_TYPE_MATCHING_OFFER) {
                        $methodsEntered[] = Rules\AlertRuleInterface::RULE_TYPE_MATCHING_OFFER;
                        $alertRule = new Rules\AlertRuleMatchingOffer($this->entityManager);
                        $alertRule->loadFromEntity($alertEntity);

                        $validateRule = $alertRule->validateRuleFilters();
                        if (! empty($validateRule)) {
                            throw new Exception(implode(' and ', $validateRule).' for alert id '.$lastId);
                        }
                    }

                    if (! $alertRule) {
                        continue;
                    }
                    // YEAH WE HAVE A VALID UPDATE
                    $total++;

                    if ($email = $alertRule->getEmail()) {
                        $methodsEntered[] = 'Found Email';
                        $this->sendEmail($email, $output);
                    }

                    if ($notification = $alertRule->getPushNotification()) {
                        $methodsEntered[] = 'Found Push Notification';
                        $this->sendPushNotification($notification, $output);
                    }

                    /** @var Alerts $alertEntity */
                    $alertEntity = $alertRule->getEntity();
                    $alertEntity->setLastSendAt(new DateTime());
                    $alertEntity->increaseUpdatesSend();

                    $this->entityManager->persist($alertEntity);
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                } catch (Throwable $t) {
                    $alertError[] = $t->getMessage().' lastID: '.$lastId.' Methods entered: '.implode(', ', $methodsEntered);
                    continue;
                }
            }
        }
        $output->writeln('Total found : ['.$total.']');

        if (! empty($alertError) && $this->environment != 'dev') {
            $this->mailService->sendAlertCronErrorEmail($alertError);
        }

        return $total;
    }

    private function checkUserIsSet()
    {
        if (! $this->user) {
            throw new Exception('User must be set', 500);
        }
    }

    /**
     * @param SimilarOfferAlert $offerAlert
     * @return array
     * @throws Exception
     */
    public function getAlertMatchingOffer(SimilarOfferAlert $offerAlert)
    {
        $this->checkUserIsSet();
        $data = [];
        $offerId = $offerAlert->getOffer() ? $offerAlert->getOffer()->getId() : null;
        $alertEntity = $offerAlert->getAlert();

        if (! empty($alertEntity)) {
            $alertRule = new Rules\AlertRuleMatchingOffer($this->entityManager);
            $alertRule->loadFromEntity($alertEntity);
            $ruleResponse = $alertRule->getDataForUpdate();

            if ($ruleResponse && $alertEntity) {
                $data['alertId'] = $alertEntity->getId();
                $data['searchUrl'] = $ruleResponse->getSearchUrl();
                $data['title'] = $ruleResponse->getCategoryTitle();
                $data['makeName'] = $ruleResponse->getAlertString();
                ! empty($offerId) ? $data['offerId'] = $offerId : null;
            }
        }

        return $data;
    }

    /**
     * @find similar alerts for the rule
     *
     * @param TradusUser $user
     * @param int $ruleType
     * @param array $data
     * @return mixed
     * @throws Exception
     */
    public function findSimilarAlerts(TradusUser $user, int $ruleType, array $data)
    {
        $this->checkUserIsSet();
        $alertRepository = $this->entityManager->getRepository('TradusBundle:Alerts');
        $ruleString = $alertRepository->buildAlertRule(
            ! empty($data['make']) ? $data['make'] : '',
            ! empty($data['category']) ? $data['category'] : '',
            ! empty($data['type']) ? $data['type'] : '',
            ! empty($data['subtype']) ? $data['subtype'] : '',
            ! empty($data['country']) ? $data['country'] : '',
            ! empty($data['priceFrom']) ? $data['priceFrom'] : '',
            ! empty($data['priceTo']) ? $data['priceTo'] : '',
            ! empty($data['yearFrom']) ? $data['yearFrom'] : '',
            ! empty($data['yearTo']) ? $data['yearTo'] : ''
        );

        return $alertRepository->checkUserAlertExist($user, $ruleType, json_encode($ruleString));
    }

    /**
     * @find similar alerts for the rule by identifier
     *
     * @param TradusUser $user
     * @param int $ruleType
     * @param array $data
     * @return mixed
     * @throws Exception
     */
    public function findSimilarAlertsIdentifier(TradusUser $user, int $ruleType, array $data, $offerId = null)
    {
        $this->checkUserIsSet();
        /** @var AlertsRepository $alertRepository */
        $alertRepository = $this->entityManager->getRepository('TradusBundle:Alerts');

        $data['make'] = ! empty($data['make']) ? $this->getMakeIds($data['make']) : '';

        $ruleString = $alertRepository->buildAlertRule(
            ! empty($data['make']) ? $data['make'] : '',
            ! empty($data['category']) ? $data['category'] : '',
            ! empty($data['type']) ? $data['type'] : '',
            ! empty($data['subtype']) ? $data['subtype'] : '',
            ! empty($data['country']) ? $data['country'] : '',
            ! empty($data['priceFrom']) ? $data['priceFrom'] : '',
            ! empty($data['priceTo']) ? $data['priceTo'] : '',
            ! empty($data['yearFrom']) ? $data['yearFrom'] : '',
            ! empty($data['yearTo']) ? $data['yearTo'] : ''
        );

        return $alertRepository->checkUserAlertIdentifierExist(
            $user,
            $ruleType,
            json_encode($ruleString),
            $offerId,
            $this->sitecodeId
        );
    }

    public function getMakeIds($makes)
    {
        $makesRepo = $this->entityManager->getRepository('TradusBundle:Make');
        if (is_array($makes) && ! empty($makes)) {
            $makeIds = [];
            $makeArr = $makesRepo->getMakesBySlug($makes);
            foreach ($makeArr as $make) {
                $makeIds[] = $make->getid();
            }

            return $makeIds;
        } elseif (is_string($makes) && ! empty($makes)) {
            $make = $makesRepo->getMakeBySlug($makes);

            return $make->getid();
        } else {
            return $makes;
        }
    }

    /**
     * @Unsubscribe the alert
     *
     * @param Alerts $alerts
     * @throws Exception
     */
    public function unsubscribeAlert(Alerts $alerts, $offerId = null)
    {
        $this->checkUserIsSet();

        $criteria = [
            'user' => $this->getUser(),
            'alert' => $alerts,
            'status' => SimilarOfferAlert::STATUS_SUBSCRIBED,
            'sitecodeId' => $this->sitecodeId,
        ];
        if ($offerId) {
            $criteria['offer'] = $offerId;
        }
        $similarOfferAlert = $this->entityManager->getRepository('TradusBundle:SimilarOfferAlert')
            ->findOneBy($criteria);
        if (! empty($similarOfferAlert)) {
            $similarOfferAlert->setStatus(SimilarOfferAlert::STATUS_UNSUBSCRIBED);
            $this->entityManager->persist($similarOfferAlert);
            $this->entityManager->flush();
        }

        $alerts->setStatus(Alerts::STATUS_DEACTIVATED);
        $this->entityManager->persist($alerts);
        $this->entityManager->flush();
    }

    /**
     * Function activateSimilarOfferAlert.
     * @param array $criteria
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function activateSimilarOfferAlert(array $criteria)
    {
        $similarOfferAlert = $this->entityManager->getRepository('TradusBundle:SimilarOfferAlert')
            ->findOneBy($criteria);
        if ($similarOfferAlert) {
            $similarOfferAlert->setStatus(SimilarOfferAlert::STATUS_SUBSCRIBED);
            $this->entityManager->persist($similarOfferAlert);
            $this->entityManager->flush();
        }
    }
}

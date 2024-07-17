<?php

namespace TradusBundle\Service\TradusApp;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Component\HttpFoundation\Response;
use TradusBundle\Entity\AppData;
use TradusBundle\Entity\Sitecodes;
use TradusBundle\Repository\PushNotificationRepository;
use TradusBundle\Repository\TradusAppRepository;
use TradusBundle\Service\Sitecode\SitecodeService;

/**
 * Class TradusAppService.
 */
class TradusAppService
{
    public const FIREBASE_URL = 'https://fcm.googleapis.com/fcm/send';
    public const FIREBASE_AUTH = 'key=AAAAqAo-RuQ:APA91bGb6BAle5IJvNyVsFKemcyO3rWxxftVaLThgQbweZ91v7yPl12mXgwkQk2LhhzZ-izW-dp08lfz8bU6r73tmMHX4tW_qKVqc3ikZWzagnzbDIuusY-ex6dQ3ov2FCYD8rDjeRD9';
    public const FIREBASE_TRIES = 3;

    /** @var EntityManager */
    protected $entityManager;

    /** @var TradusAppRepository */
    protected $repository;

    protected $defaultLocale;

    /** @var SitecodeService $sitecodeService */
    protected $sitecodeService;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->repository = $this->entityManager->getRepository('TradusBundle:AppData');
        $sitecodeService = new SitecodeService();
        $this->sitecodeService = $sitecodeService;
        $this->defaultLocale = $sitecodeService->getDefaultLocale();
    }

    /**
     * Create App Data.
     *
     * @param array $params
     * @param int $sitecodeId
     * @return AppData|bool
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function createAppData(array $params, int $sitecodeId = Sitecodes::SITECODE_TRADUS)
    {
        if (isset($params['pushNotificationId'])) {
            $appData = $this->repository->findOneBy(
                [
                    'pushtoken' => $params['pushNotificationId'],
                    'sitecodeId' => $sitecodeId,
                ]
            );

            if (! $appData) {
                $appData = new AppData();

                if (isset($params['pushNotificationId'])) {
                    $appData->setPushToken($params['pushNotificationId']);
                }

                if (isset($params['platform'])) {
                    $appData->setPlatform($params['platform']);
                }

                if (isset($params['deviceOs'])) {
                    $appData->setDeviceOS($params['deviceOs']);
                }

                if (isset($params['userAgent'])) {
                    $appData->setUserAgent($params['userAgent']);
                }

                if (empty($params['lang'])) {
                    $params['lang'] = $this->defaultLocale;
                }
                $appData->setLang($params['lang']);
                $appData->setSitecodeId($sitecodeId);

                $appData->setStatus(AppData::STATUS_ACTIVE);

                if (! empty($params['userId'])) {
                    $appData->setUserId(
                        $this->entityManager->getRepository('TradusBundle:TradusUser')->find($params['userId'])
                    );
                }
            }
            if (! empty($params['userId'])) {
                $appData->setUserId(
                    $this->entityManager->getRepository('TradusBundle:TradusUser')->find($params['userId'])
                );
            }

            $this->entityManager->persist($appData);
            $this->entityManager->flush();

            return $appData;
        }

        return false;
    }

    /**
     * Toggles the status of the AppData based on a pushToken.
     *
     * @param string $pushToken
     * @param int $sitecodeId
     * @return AppData | null
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function toggleAppData(string $pushToken, int $sitecodeId = Sitecodes::SITECODE_TRADUS)
    {
        $appData = $this->repository->findOneBy(
            [
                'pushtoken' => $pushToken,
                'sitecodeId' => $sitecodeId,
            ]
        );
        if (! $appData) {
            return;
        }
        $appData->setStatus($this->toggleStatus($appData->getStatus()));

        $this->entityManager->persist($appData);
        $this->entityManager->flush();

        return $appData;
    }

    /**
     * Toggles the appData status.
     *
     * @param string $status
     * @return string
     */
    private function toggleStatus(string $status): string
    {
        $toggle = [
            AppData::STATUS_ACTIVE   => AppData::STATUS_INACTIVE,
            AppData::STATUS_INACTIVE => AppData::STATUS_ACTIVE,
        ];

        return $toggle[$status];
    }

    /**
     * Based on a token returns the status of the app data.
     *
     * @param string $pushToken
     * @param int $sitecodeId
     * @return int | bool
     */
    public function checkStatusAppData(string $pushToken, int $sitecodeId = Sitecodes::SITECODE_TRADUS)
    {
        $appData = $this->repository->findOneBy(
            [
                'pushtoken' => $pushToken,
                'sitecodeId' => $sitecodeId,
            ]
        );

        if ($appData) {
            return $appData->getStatus();
        }

        return false;
    }

    /**
     * Based on user data, send a push notification message to mobile device.
     *
     * @param array $data
     *
     * @param array $appData
     * @return array
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function sendPushNotification(array $data, $appData = []): array
    {
        if (empty($appData)) {
            $appData = $this->repository->findBy(
                [
                    'userId' => $data['userId'],
                    'status' => AppData::STATUS_ACTIVE,
                ]
            );
        }

        if (! $appData) {
            return [
                'status' => Response::HTTP_NOT_FOUND,
                'message' => 'No active push found for this user',
            ];
        }
        $sitecodeId = isset($data['sitecodeId']) ? $data['sitecodeId'] : $this->sitecodeService->getSitecodeId();

        $trySendingResults = [];
        /** @var AppData $device */
        foreach ($appData as $device) {
            $curlData = [
                'priority' => 'high',
                'data' => [
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ],
                'to' => $device->getPushtoken(),
                'sitecodeId' => $sitecodeId,
            ];

            /**
             * For Android devices all info must be inside 'data' attribute.
             * For ios some should be inside 'notification'.
             */
            $section = 'data';
            if (in_array(strtolower($device->getPlatform()), ['ios', 'ipad', 'iphone'])) {
                $section = 'notification';
            }

            $curlData[$section]['title'] = $data['title'];

            if (isset($data['body'])) {
                $curlData[$section]['body'] = $data['body'];
            }

            if (isset($data['sound'])) {
                $curlData[$section]['sound'] = $data['sound'];
            }

            if (isset($data['url'])) {
                $curlData['data']['url'] = $data['url'];
            }

            if (isset($data['image'])) {
                $curlData['data']['image'] = $data['image'];
            }

            if (isset($data['button'])) {
                $curlData['data']['button'] = $data['button'];
            }

            $userId = null;
            if ($user = $device->getUserId()) {
                $userId = $user->getId();
            }

            // Here we store the push notification and send the id to be used by the app tracking
            /** @var PushNotificationRepository $pushNotificationRepository */
            $pushNotificationRepository = $this->entityManager->getRepository('TradusBundle:PushNotification');
            $pushNotificationId = $pushNotificationRepository->store($curlData, $section, $device->getId(), $userId);
            $curlData['data']['push_notification_id'] = $pushNotificationId;

            $trySendingResults[] = $this->trySending($curlData);
        }

        return [
            'status' => Response::HTTP_OK,
            'message' => json_encode($trySendingResults),
        ];
    }

    /**
     * Try the sending of push notification to device
     * After X tries the token is deactivated.
     *
     * @param array $curlData
     * @param int $try
     *
     * @return array
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function trySending(array $curlData, int $try = 1): array
    {
        $curl = curl_init(self::FIREBASE_URL);
        $payload = json_encode($curlData);

        curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
        curl_setopt(
            $curl,
            CURLOPT_HTTPHEADER,
            [
                'Content-Type:application/json',
                'Authorization:'.$this->sitecodeService->getSitecodeParameter('apps.firebase_auth'),
            ]
        );
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($curl);
        if ($errno = curl_errno($curl)) {
            $errorMessage = curl_strerror($errno);

            return [
                'status' => Response::HTTP_SERVICE_UNAVAILABLE,
                'message' => $errorMessage,
            ];
        }
        curl_close($curl);
        $response = json_decode($result);

        if ($response->failure == 1) {
            if ($try >= self::FIREBASE_TRIES) {
                $this->toggleAppData($curlData['to'], $curlData['sitecodeId']);

                return [
                    'status' => Response::HTTP_SERVICE_UNAVAILABLE,
                    'message' => 'Device not receiving push notifications. PushToken: '.$curlData['to'],
                ];
            }
            $try++;
            $this->trySending($curlData, $try);
        }

        return [
            'status' => Response::HTTP_OK,
            'message' => 'Push notification sent to pushToken: '.$curlData['to'],
        ];
    }
}

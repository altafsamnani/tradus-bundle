<?php

namespace TradusBundle\Service\Switchboard;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use TradusBundle\Entity\EntityValidationTrait;
use TradusBundle\Entity\Offer;
use TradusBundle\Entity\OfferImageInterface;
use TradusBundle\Entity\Seller;
use TradusBundle\Entity\TradusUser;
use TradusBundle\Repository\OfferRepository;
use TradusBundle\Service\Sitecode\SitecodeService;
use TradusBundle\Service\TradusUser\TradusUserService;

/**
 * Class SwitchboardService.
 */
class SwitchboardService
{
    use EntityValidationTrait;

    /** @var EntityManagerInterface */
    protected $entityManager;

    protected $accessToken;

    protected $switchboardConfig;

    /** @var ContainerInterface $container */
    protected $container;

    public function __construct(
        EntityManagerInterface $entityManager,
        ContainerInterface $container
    ) {
        $this->entityManager = $entityManager;
        $this->container = $container;
        $this->switchboardConfig = $container->getParameter('switchboard');
        $this->accessToken = $this->getAccessToken();
    }

    /**
     * Get Switchboard Access Token to be used in next calls.
     */
    public function getAccessToken()
    {
        $cache = $this->container->get('cache.app');
        $cacheItem = $cache->getItem(sprintf('switchboardAccessToken'));
        if (! $cacheItem->isHit()) {
            $client = new Client();
            $ret = $client->request(
                'POST',
                $this->switchboardConfig['oauth_endpoint'].'/oauth/token',
                [
                    'form_params' => [
                            'grant_type' => 'client_credentials',
                            'client_id' => $this->switchboardConfig['id'],
                            'client_secret' => $this->switchboardConfig['secret'],
                        ],
                ]
            );
            if ($ret->getStatusCode() == 200) {
                $body = json_decode((string) $ret->getBody());
                $cacheItem->set($body->access_token);
                $cache->save($cacheItem);
            }
        }

        return $cacheItem->get();
    }

    /**
     * Get Switchboard user id based on email.
     *
     * @param TradusUser $user
     * @param string $whitelabel
     * @return mixed
     */
    public function getSwitchboardUserId(TradusUser $user, string $whitelabel)
    {
        $client = new Client();
        $headers = [
            'Authorization' => 'Bearer '.$this->accessToken,
            'Accept'        => 'application/json',
        ];
        $ret = $client->patch($this->switchboardConfig['endpoint'].'/users/connect', [
            'form_params' => [
                'email' => $user->getEmail(),
                'white_label' => $whitelabel,
                'external_user_id' => $user->getId(),
            ],
            'headers' => $headers,
        ]);

        $response = json_decode($ret->getBody(), false);

        return $response->data->id;
    }

    /**
     * Save Switchboard user id in the database and return the updated entity to be used in further code.
     *
     * @param TradusUser $user
     * @return TradusUser
     */
    public function updateTradusSwitchboardId(TradusUser $user)
    {
        $sitecodeService = new SitecodeService();
        $whitelabel = $sitecodeService->getSitecodeKey();
        $switchboardUserId = $this->getSwitchboardUserId($user, $whitelabel);
        $user->setSwitchboardApiId($switchboardUserId);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    /**
     * Return notifications for user.
     *
     * @param int $userId
     * @param int|null $page
     * @return mixed
     */
    public function getUserSwitchboardNotifications(int $userId, ?int $page)
    {
        $site = $this->container->getParameter('sitecode')['site_key'];
        $endpoint = $this->switchboardConfig['endpoint'].'/users/'.$userId.'/white-labels/'.$site.'/notifications';
        if ($page) {
            $endpoint .= '?page='.$page;
        }

        $client = new Client();
        $headers = [
            'Authorization' => 'Bearer '.$this->accessToken,
            'Accept'        => 'application/json',
        ];
        $ret = $client->get($endpoint, [
            'headers' => $headers,
        ]);

        return json_decode($ret->getBody(), true);
    }

    /**
     * Mark given notifications as read.
     *
     * @param int $userId
     * @param array $notifications
     */
    public function updateUserNotifications(int $userId, array $notifications)
    {
        $site = $this->container->getParameter('sitecode')['site_key'];
        $endpoint = $this->switchboardConfig['endpoint'].'/users/'.$userId.'/white-labels/'.$site.'/notifications';
        $client = new Client();
        $headers = [
            'Authorization' => 'Bearer '.$this->accessToken,
            'Accept'        => 'application/json',
        ];
        $client->put($endpoint, [
            'headers' => $headers,
            'form_params' => ['read' => $notifications],
        ]);
    }

    /**
     * Return preferences for user.
     *
     * @param int $userId
     * @return mixed
     */
    public function getUserPreferences(int $userId)
    {
        $site = $this->container->getParameter('sitecode')['site_key'];
        $endpoint = $this->switchboardConfig['endpoint'].'/users/'.$userId.'/white-labels/'.$site.'/preferences';

        $client = new Client();
        $headers = [
            'Authorization' => 'Bearer '.$this->accessToken,
            'Accept'        => 'application/json',
        ];
        $ret = $client->get($endpoint, [
            'headers' => $headers,
        ]);

        return json_decode($ret->getBody(), true);
    }

    /**
     * Update user preferences.
     *
     * @param int $userId
     * @param array $preferences
     */
    public function updateUserPreferences(int $userId, array $preferences)
    {
        $site = $this->container->getParameter('sitecode')['site_key'];
        $endpoint = $this->switchboardConfig['endpoint'].'/users/'.$userId.'/white-labels/'.$site.'/preferences';
        $client = new Client();
        $headers = [
            'Authorization' => 'Bearer '.$this->accessToken,
            'Accept'        => 'application/json',
        ];
        $client->put($endpoint, [
            'headers' => $headers,
            'form_params' => $preferences,
        ]);
    }

    /**
     * Update all user preferences.
     *
     * @param int $userId
     * @param array $preferences
     */
    public function updateAllUserPreferences(int $userId, array $preferences)
    {
        $site = $this->container->getParameter('sitecode')['site_key'];
        $endpoint = $this->switchboardConfig['endpoint'].'/users/'.$userId.'/white-labels/'.$site.'/preferences/all';
        $client = new Client();
        $headers = [
            'Authorization' => 'Bearer '.$this->accessToken,
            'Accept'        => 'application/json',
        ];
        $client->put($endpoint, [
            'headers' => $headers,
            'form_params' => $preferences,
        ]);
    }

    public function createConversation(int $userId, Seller $seller, Offer $offer, string $message, string $html, int $emailId)
    {
        /** @var TradusUserService $tradusUserService */
        $tradusUserService = $this->container->get('tradus_user.service');
        $user = $tradusUserService->findOneById($userId);

        $site = $this->container->getParameter('sitecode')['site_key'];
        $endpoint = $this->switchboardConfig['endpoint'].'/users/'.$user->getSwitchboardApiId().'/white-labels/'.$site.'/conversations';
        $client = new Client();
        $headers = [
            'Authorization' => 'Bearer '.$this->accessToken,
            'Accept'        => 'application/json',
        ];

        // get the seller switchboard id
        $switchboardTproOwner = $this->container->getParameter('switchboard')['tpro_owner'];
        $ret = $client->get($this->switchboardConfig['endpoint'].'/users/'.$seller->getSellerContactEmail().'/owner/'.$switchboardTproOwner, [
            'headers' => $headers,
        ]);
        $receiverId = json_decode($ret->getBody())->data->id;
        $client->post($endpoint, [
            'headers' => $headers,
            'form_params' => [
                'receiver_id' => $receiverId,
                'message' => $message,
                'external_email_id' => $emailId,
                'html' => $html,
                'ad' => [
                    'id' => $offer->getId(),
                    'title' => $offer->getTitleByLocale(),
                ],
            ],
        ]);
    }

    public function replyToConversation($user, int $emailId, string $message, string $body, bool $fromInbox = false)
    {
        $switchboardId = $this->getSwitchboardId($user);
        $site = $this->container->getParameter('sitecode')['site_key'];
        $client = new Client();
        $headers = [
            'Authorization' => 'Bearer '.$this->accessToken,
            'Accept'        => 'application/json',
        ];

        $ret = $client->get($this->switchboardConfig['endpoint'].'/users/'.$switchboardId.'/white-labels/'.$site.'/external/'.$emailId.'/email', [
            'headers' => $headers,
        ]);

        $conversationId = json_decode($ret->getBody(), true);
        $client->post($this->switchboardConfig['endpoint'].'/users/'.$switchboardId.'/white-labels/'.$site.'/conversations/'.$conversationId, [
            'headers' => $headers,
            'form_params' => [
                'message' => $message,
                'html' => $body,
                'from_inbox' => $fromInbox,
            ],
        ]);
    }

    /**
     * Returns the switchboard id of a seller or buyer
     * If user it buyer we return the switchboard user id stored in the db
     * If the user is seller we make a call to switchboard system and return the id.
     *
     * @param $user
     * @return mixed
     */
    private function getSwitchboardId($user)
    {
        if ($user instanceof Seller) {
            $client = new Client();
            $headers = [
                'Authorization' => 'Bearer '.$this->accessToken,
                'Accept'        => 'application/json',
            ];
            $ret = $client->get($this->switchboardConfig['endpoint'].'/users/'.$user->getEmail().'/owner/'.$this->container->getParameter('switchboard')['tpro_owner'], [
                'headers' => $headers,
            ]);

            $response = json_decode($ret->getBody(), false);

            return $response->data->id;
        }

        if ($user instanceof TradusUser) {
            return $user->getSwitchboardApiId();
        }
    }

    public function sendEmail(TradusUser $user, string $whitelabel)
    {
        $client = new Client();
        $headers = [
            'Authorization' => 'Bearer '.$this->accessToken,
            'Accept'        => 'application/json',
        ];

        $switchboardUserId = $this->getSwitchboardId($user, $whitelabel);

        $client->post($this->switchboardConfig['endpoint'].'/payload', [
            'headers' => $headers,
            'form_params' => [
                'type' => 'buyerNps',
                'version' => 1,
                'user_id' => 43, //$switchboardUserId,
                'white_label' => $whitelabel,
                'locale' => 'en', //$user->getPreferredLocale(), -> we don't have all languages for the moment
                'payload' => [
                    'title' => 'Test buyer Nps email',
                    'body' => 'Test buyer Nps email',
                ],
            ],
        ]);
    }

    /**
     * Update switchboard user with some missing info
     * For the moment the only thing we need is the name.
     *
     * @param TradusUser $user
     * @param string $name
     */
    public function updateUserName(TradusUser $user, string $name)
    {
        $client = new Client();
        $headers = [
            'Authorization' => 'Bearer '.$this->accessToken,
            'Accept'        => 'application/json',
        ];
        $ret = $client->patch($this->switchboardConfig['endpoint'].'/users/'.$user->getSwitchboardApiId().'/reconnect', [
            'form_params' => [
                'email' => $user->getEmail(),
                'name' => $name,
            ],
            'headers' => $headers,
        ]);
    }

    /**
     * Get a list of conversations for user.
     *
     * @param TradusUser $user
     * @param string $locale
     * @return mixed
     * @throws NonUniqueResultException
     * @throws DBALException
     */
    public function getConversationsForUser(TradusUser $user, string $locale): array
    {
        $conversations = [];
        $site = $this->container->getParameter('sitecode')['site_key'];

        $client = new Client();
        $headers = [
            'Authorization' => 'Bearer '.$this->accessToken,
            'Accept'        => 'application/json',
        ];
        $ret = $client->get($this->switchboardConfig['endpoint'].'/users/'.$user->getSwitchboardApiId().'/white-labels/'.$site.'/conversations', [
            'headers' => $headers,
        ]);

        $emails = json_decode($ret->getBody(), true);
        /** @var OfferRepository $offerRepository */
        $offerRepository = $this->entityManager->getRepository(Offer::class);

        foreach ($emails['data'] as $email) {
            $conversation = $email;
            $offer = $offerRepository->getOfferById($email['offer_id']);
            if (! $offer) {
                continue;
            }
            $conversation['offer'] = [
                'sellerName' => $offer->getSeller()->getCompanyName(),
                'offerName' => $offer->getTitleByLocale($locale),

                'offerUrl' => $offer->getUrlByLocale($locale),
                'offerFallbackImage' => $offerRepository->getOfferFallbackImage($offer),
            ];

            $image = $offer->getImages()->first()->getUrl();
            $conversation['offer']['offerImage'] = [
                'small'  => [
                    'url' => $image.OfferImageInterface::IMAGE_SIZE_PRESETS[OfferImageInterface::IMAGE_SIZE_SMALL],
                ],
                'medium' => [
                    'url' => $image.OfferImageInterface::IMAGE_SIZE_PRESETS[OfferImageInterface::IMAGE_SIZE_MEDIUM],
                ],
                'large'  => [
                    'url' => $image.OfferImageInterface::IMAGE_SIZE_PRESETS[OfferImageInterface::IMAGE_SIZE_LARGE],
                ],
            ];
            $conversations[] = $conversation;
        }

        return $conversations;
    }

    /**
     * Get the number of unread conversations per user.
     *
     * @param TradusUser $user
     * @return mixed
     */
    public function getUnreadConversationsCountForUser(TradusUser $user)
    {
        $site = $this->container->getParameter('sitecode')['site_key'];

        $client = new Client();
        $headers = [
            'Authorization' => 'Bearer '.$this->accessToken,
            'Accept'        => 'application/json',
        ];
        $ret = $client->get($this->switchboardConfig['endpoint'].'/users/'.$user->getSwitchboardApiId().'/white-labels/'.$site.'/conversations/unread-count', [
            'headers' => $headers,
        ]);

        return json_decode($ret->getBody(), true);
    }

    /**
     * Get a list of conversations for user.
     *
     * @param TradusUser $user
     * @param string $locale
     * @return mixed
     * @throws NonUniqueResultException
     * @throws DBALException
     */
    public function getConversationById(TradusUser $user, string $locale, int $conversationId): array
    {
        $conversation = [];
        $site = $this->container->getParameter('sitecode')['site_key'];

        $client = new Client();
        $headers = [
            'Authorization' => 'Bearer '.$this->accessToken,
            'Accept'        => 'application/json',
        ];
        $ret = $client->get($this->switchboardConfig['endpoint'].'/users/'.$user->getSwitchboardApiId().'/white-labels/'.$site.'/conversations/'.$conversationId, [
            'headers' => $headers,
        ]);

        $data = json_decode($ret->getBody(), true);

        /** @var OfferRepository $offerRepository */
        $offerRepository = $this->entityManager->getRepository(Offer::class);
        if (isset($data['data'][0]['offer_id'])) {
            $offer = $offerRepository->getOfferById($data['data'][0]['offer_id']);
            $image = $offer->getImages()->first()->getUrl();
            $conversation['offer'] = [
                'sellerName' => $offer->getSeller()->getCompanyName(),
                'offerName' => $offer->getTitleByLocale($locale),
                'offerImage' => [
                    'small'  => [
                        'url' => $image.OfferImageInterface::IMAGE_SIZE_PRESETS[OfferImageInterface::IMAGE_SIZE_SMALL],
                    ],
                    'medium' => [
                        'url' => $image.OfferImageInterface::IMAGE_SIZE_PRESETS[OfferImageInterface::IMAGE_SIZE_MEDIUM],
                    ],
                    'large'  => [
                        'url' => $image.OfferImageInterface::IMAGE_SIZE_PRESETS[OfferImageInterface::IMAGE_SIZE_LARGE],
                    ],
                ],
                'offerPrice' => $offer->getPrice(),
                'offerCurrency' => $offer->getCurrency(),
                'offerCity' => $offer->getSeller()->getCity(),
                'offerUrl' => $offer->getUrlByLocale($locale),
                'offerFallbackImage' => $offerRepository->getOfferFallbackImage($offer),
            ];
            $conversation['messages'] = array_reverse($data['data']);
            $conversation['user'] = $data['user'];
        }

        return $conversation;
    }

    /**
     * Reply to a conversation.
     *
     * @param TradusUser $user
     * @param string $message
     * @param string $conversationId
     * @param bool $fromInbox
     */
    public function replyToConversationId(TradusUser $user, string $message, string $conversationId, bool $fromInbox = false)
    {
        $switchboardId = $this->getSwitchboardId($user);
        $site = $this->container->getParameter('sitecode')['site_key'];
        $client = new Client();
        $headers = [
            'Authorization' => 'Bearer '.$this->accessToken,
            'Accept'        => 'application/json',
        ];

        $client->post($this->switchboardConfig['endpoint'].'/users/'.$switchboardId.'/white-labels/'.$site.'/conversations/'.$conversationId, [
            'headers' => $headers,
            'form_params' => [
                'message' => $message,
                'html' => $message,
                'from_inbox' => $fromInbox,
            ],
        ]);
    }

    /**
     * Mark a conversation as read.
     *
     * @param TradusUser $user
     * @param int $conversationId
     */
    public function markConversationAsRead(TradusUser $user, int $conversationId)
    {
        $site = $this->container->getParameter('sitecode')['site_key'];

        $client = new Client();
        $headers = [
            'Authorization' => 'Bearer '.$this->accessToken,
            'Accept'        => 'application/json',
        ];
        $ret = $client->put($this->switchboardConfig['endpoint'].'/users/'.$user->getSwitchboardApiId().'/white-labels/'.$site.'/conversations/'.$conversationId, [
            'headers' => $headers,
        ]);
    }
}

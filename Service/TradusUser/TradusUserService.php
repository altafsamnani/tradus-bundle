<?php

namespace TradusBundle\Service\TradusUser;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use TradusBundle\Entity\Anonymize;
use TradusBundle\Entity\Autologin;
use TradusBundle\Entity\EntityValidationTrait;
use TradusBundle\Entity\Sitecodes;
use TradusBundle\Entity\TradusUser;
use TradusBundle\Repository\AutologinRepository;
use TradusBundle\Repository\TradusUserRepository;
use TradusBundle\Service\Helper\AnonymizeServiceHelper;
use TradusBundle\Service\Journal\JournalService;
use TradusBundle\Service\Switchboard\SwitchboardService;

/**
 * Class TradusUserService.
 */
class TradusUserService
{
    use EntityValidationTrait;

    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var TradusUserRepository */
    protected $repository;

    protected $journalService;

    public function __construct(
        EntityManagerInterface $entityManager,
        JournalService $journalService
    ) {
        $this->entityManager = $entityManager;
        $this->journalService = $journalService;
        $this->repository = $this->entityManager->getRepository('TradusBundle:TradusUser');
    }

    /**
     * @param string $email
     * @return mixed | TradusUser
     * @throws NonUniqueResultException
     */
    public function findOneByEmail(string $email)
    {
        return $this->repository->findOneByEmail($email);
    }

    /**
     * @param int $userId
     * @return mixed | TradusUser
     * @throws NonUniqueResultException
     */
    public function findOneById(int $userId)
    {
        return $this->repository->findOneById($userId);
    }

    /**
     * @param string $code
     * @return mixed
     * @throws NonUniqueResultException
     */
    public function findOneByConfirmationToken(string $code)
    {
        return $this->repository->findOneByConfirmationToken($code);
    }

    /**
     * @param string $code
     * @return mixed
     * @throws NonUniqueResultException
     */
    public function findOneByAppleId(string $code)
    {
        return $this->repository->findOneByAppleId($code);
    }

    /**
     * @param string $token
     *
     * $return void
     * @throws Exception
     */
    public function useAutologinToken(string $token)
    {
        /** @var AutologinRepository $repository */
        $repository = $this->entityManager->getRepository('TradusBundle:Autologin');
        $autologin = $repository->findOneBy([
            'token' => $token,
            'type' => Autologin::OFFER,
        ]);

        if ($autologin) {
            $autologin->setUsedDate(new DateTime());
            $this->entityManager->persist($autologin);
            $this->entityManager->flush();
        }
    }

    /**
     * @param string $token
     * @return mixed
     */
    public function findOneByAutologinToken(string $token)
    {
        /** @var AutologinRepository $repository */
        $repository = $this->entityManager->getRepository('TradusBundle:Autologin');

        return $repository->findOneBy([
            'token' => $token,
            'type' => Autologin::OFFER,
            'usedDate' => null,
        ]);
    }

    /**
     * Create user method.
     *
     * @param array $parameters Parameters for creating user
     * @param bool $update Create or update user
     *
     * @return TradusUser
     *
     * @throws NonUniqueResultException|UnprocessableEntityHttpException
     * @throws Exception
     */
    public function createUser(array $parameters, $update = false)
    {
        global $kernel;
        /** @var SwitchboardService $switchboardService */
        $switchboardService = $kernel->getContainer()->get('switchboard.service');
        $params = $this->cleanParams($parameters);
        if (isset($params['email'])) {
            /** @var TradusUser $tradusUser */
            $tradusUser = $this->findOneByEmail($params['email']);
            if ($tradusUser
                && $tradusUser->getStatus() == TradusUser::STATUS_DELETED
            ) {
                throw new UnprocessableEntityHttpException('user_inactive_exist');
            }

            if ($tradusUser
                && $tradusUser->getStatus() == TradusUser::STATUS_ACTIVE
                && $update === false
            ) {
                throw new UnprocessableEntityHttpException('user_already_exist');
            }

            if (! $tradusUser) {
                $tradusUser = new TradusUser();
            }
            $tradusUser->setValues($params);
            // If there is an update action we are sending the name to the switchboard for updating
            if ($update && isset($parameters['full_name'])) {
                $switchboardService->updateUserName($tradusUser, $parameters['full_name']);
            }

            if (isset($params['sitecodeId'])) {
                $tradusUser->setSitecodeId($params['sitecodeId']);
            }

            /** When creating a new account from the sending lead flow we do not save agreement date */
            $agreementDate = new DateTime();
            if (isset($parameters['agreementDate']) && $parameters['agreementDate'] == false) {
                $agreementDate = null;
            }
            /* When creating a new account from the sending lead flow we do not save agreement date */

            if (! $update) {
                $tradusUser->setAgreementDate($agreementDate);
                $tradusUser->setCitySelectedByUser(0);
            } else {
                $tradusUser->setCitySelectedByUser(1);
            }

            if (isset($parameters['social'])) {
                $tradusUser->setStatus(TradusUser::STATUS_ACTIVE);
            } else {
                if (! $update) {
                    $tradusUser->setStatus(TradusUser::STATUS_PENDING);
                    $tradusUser->setConfirmationToken(
                        sha1($tradusUser->generateToken())
                    );
                }
            }

            if (isset($parameters['status'])) {
                $tradusUser->setStatus($parameters['status']);
            }

            /*
             * Set defaults when not yet set.
             */
            if (empty($tradusUser->getPassword())) {
                $tradusUser->setPassword($tradusUser->generatePassword());
            } else {
                if ($update === false) {
                    $tradusUser->setPassword($tradusUser->getPassword());
                }
            }

            $tradusUser = $this->persist($tradusUser);
            $tradusUser = $switchboardService->updateTradusSwitchboardId($tradusUser);

            return $tradusUser;
        }
    }

    /**
     * Authenticate Social User.
     *
     * @param mixed $data Google UserInfoPlus data
     * @param Request $request request data
     *
     * @param int $sitecodeId
     * @return TradusUser|bool
     * @throws NonUniqueResultException
     */
    public function authenticateSocialUser($data, Request $request, int $sitecodeId = Sitecodes::SITECODE_TRADUS)
    {
        $user = null;
        if (isset($data['email'])) {
            $user = $this->findOneByEmail($data['email']);
        }

        if (! $user && isset($data['user_identifier'])) {
            $user = $this->findOneByAppleId($data['user_identifier']);
        }

        if (! $user) {
            $user = $this->createSocialUser($data, $request, $sitecodeId);
        }

        if ($user) {
            $user->setLastLogin(new DateTime());
            isset($data['sub']) && ! $user->getGoogleId() ? $user->setGoogleId($data['sub']) : '';
            isset($data['id']) && ! $user->getFaceBookId() ? $user->setFacebookId($data['id']) : '';
            isset($data['user_identifier']) && ! $user->getAppleId() ? $user->setAppleId($data['user_identifier']) : '';
            isset($data['locale']) && ! $user->getPreferredLocale() ? $user->setPreferredLocale($data['locale']) : '';
            $this->persist($user);

            $this->setUserJournal($user->getId(), 'user login', $user->getIp(), $request->headers->get('User-Agent'));
        }

        return $user;
    }

    /**
     * Create Social User.
     *
     * @param mixed $data Google UserInfoPlus data
     * @param Request $request request data
     *
     * @param int $sitecodeId
     * @return TradusUser|bool
     */
    public function createSocialUser($data, Request $request, int $sitecodeId = Sitecodes::SITECODE_TRADUS)
    {
        try {
            $userParams = TradusUser::transformSocialPayload($data, $request);
            $userParams['sitecodeId'] = $sitecodeId;
            $user = $this->createUser($userParams);
            $this->setUserJournal(
                $user->getId(),
                'user registration',
                $userParams['ip'],
                $request->headers->get('User-Agent')
            );

            return $user;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Create log for the user.
     *
     * @param int $userId Id of the user
     * @param $type
     * @param string $userIp User ip address
     * @param string $userAgent User agent
     *
     * @return string
     */
    public function setUserJournal($userId, $type, $userIp = null, $userAgent = null)
    {
        $this->journalService->setJournal(
            $type,
            $userAgent,
            null,
            null,
            $userId,
            null,
            null,
            null,
            $userIp
        );
    }

    /**
     * Generate Token.
     *
     * @param TradusUser $user User Object
     *
     * @return string
     */
    public function generateToken(TradusUser $user)
    {
        if (! $user || ($user
                && $user->getStatus() == TradusUser::STATUS_DELETED)
        ) {
            throw new UnprocessableEntityHttpException('user_inactive_exist');
        }
        $token = $user->generateToken();

        return TradusUser::generateTimeBasedCode(['token' => $token, 'email' => $user->getEmail()]);
    }

    /**
     * @param TradusUser $tradusUser
     * @return TradusUser
     *
     * @throws UnprocessableEntityHttpException|NonUniqueResultException
     */
    public function persist(TradusUser $tradusUser)
    {
        self::validateEntity($tradusUser);
        $this->entityManager->persist($tradusUser);
        $this->entityManager->flush();

        return $tradusUser;
    }

    /**
     * Clean unnecessary parameters.
     *
     * @param array $params the params to be saved
     *
     * @return array
     */
    private function cleanParams(array $params)
    {
        $newParams = [];
        if (count($params)) {
            foreach ($params as $paramName => $value) {
                /* Some support for param names that should be different */
                if ($paramName == 'from_email') {
                    $paramName = 'email';
                }

                if (in_array($paramName, TradusUser::AVAILABLE_FIELDS)) {
                    $newParams[$paramName] = $value;
                }
            }
        }

        return $newParams;
    }

    /**
     * Anonymize buyer data method.
     *
     * @param string $email buyer's email
     *
     * @return int
     * @throws NonUniqueResultException
     */
    public function anonymizeBuyer(string $email)
    {
        $anonymizeService = new AnonymizeServiceHelper($this->entityManager);
        $user = $this->findOneByEmail($email);

        if (! $user) {
            return 0;
        }

        $user->setCompany('');
        $user->setEmail('anonymized'.$user->getId().'@olx.com');
        $user->setPassword('');
        $user->setConfirmationToken('');
        $user->setFacebookId('');
        $user->setFirstName('anonymized');
        $user->setGoogleId('');
        $user->setInValidEmail(0);
        $user->setIp('');
        $user->setLastName('');
        $user->setPhone('');
        $user->setSubscribeEmailsOffersServices(0);
        $user->setStatus(TradusUser::STATUS_DELETED);
        $user->setAnonymizedAt(new DateTime());
        $user->setOldEmail(null);
        $user = $this->persist($user);

        $anonymizeService->anonymizeUser($user->getId(), $user->getCountry());

        return $user->getId();
    }

    /**
     * Get User Progress.
     *
     * @param TradusUser $user
     *
     * @return int
     */
    public function getUserProgress(TradusUser $user)
    {
        $percentage = 15;
        $emailVerifiedPercentage = 25;
        $progress = 0;
        $personalMaxFields = 5;
        $companyMaxFields = 9;
        $company = false;
        $progressUserFields = [
            'email' => $user->getEmail(),
            'full_name' => $user->getFullName(),
            'phone' => strlen($user->getPhone()) >= 5 ? $user->getPhone() : null,
            'postcode' => $user->getPostcode(),
            'preferred_locale' => $user->getPreferredLocale(),
            'city' => $user->getCity(),
        ];
        $progressCompanyFields = [
            'vat_number' => $user->getVatNumber(),
            'company_phone' => strlen($user->getCompanyPhone()) >= 5 ? $user->getCompanyPhone() : null,
            'company_website' => $user->getCompanyWebsite(),
            'company' => $user->getCompany(),
        ];
        $progressFields = $progressUserFields;
        if ($user->getUserType() == 2) {
            $company = true;
            $percentage = $percentage - 6;
            $emailVerifiedPercentage = $emailVerifiedPercentage - 6;
            $progressFields = array_merge($progressUserFields, $progressCompanyFields);
        }
        foreach ($progressFields as $userValue) {
            if (! empty($userValue)) {
                $progress++;
                if (($company && $progress >= $companyMaxFields) || (! $company && $progress >= $personalMaxFields)) {
                    break;
                }
            }
        }

        $progressPercentage = $progress * $percentage;
        if ($user->getStatus() == 100) {
            $progressPercentage = $progressPercentage + $emailVerifiedPercentage;
        }

        return $progressPercentage;
    }

    public function sendGrid($content, $method)
    {
        global $kernel;
        $sendGridData['company'] = ! empty($content['company']) ? $content['company'] : '';
        $sendGridData['email'] = $content['email'];
        $sendGridData['name'] = $content['full_name'] ?? preg_replace('/@.*/', '', $content['email']);
        $sendGridData['countryCode'] = $content['country'];
        $sendGridData['locale'] = $content['preferred_locale'];

        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            $kernel->getContainer()->getParameter('sendgrid_tpro_auth'),
        ];

        $handler = curl_init();
        curl_setopt($handler, CURLOPT_URL, $kernel->getContainer()->getParameter('sendgrid_tpro_url'));
        curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handler, CURLOPT_ENCODING, '');
        curl_setopt($handler, CURLOPT_MAXREDIRS, 10);
        curl_setopt($handler, CURLOPT_TIMEOUT, 0);
        curl_setopt($handler, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($handler, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($handler, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($handler, CURLOPT_POSTFIELDS, json_encode($sendGridData));
        curl_setopt($handler, CURLOPT_HTTPHEADER, $headers);
        $server_output = curl_exec($handler);
        curl_close($handler);
        if (isset($server_output)) {
            return true;
        }

        return false;
    }
}

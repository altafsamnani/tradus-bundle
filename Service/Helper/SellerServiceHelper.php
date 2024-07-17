<?php

namespace TradusBundle\Service\Helper;

use App\AdditionalServices;
use Brick\PhoneNumber;
use Brick\PhoneNumber\PhoneNumberParseException;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Intl\Intl;
use TradusBundle\Entity\Anonymize;
use TradusBundle\Entity\EntityValidationTrait;
use TradusBundle\Entity\Offer;
use TradusBundle\Entity\Seller;
use TradusBundle\Entity\SellerAdditionalServices;
use TradusBundle\Entity\SellerInterface;
use TradusBundle\Entity\SellerOption;
use TradusBundle\Entity\SellerOptionInterface;
use TradusBundle\Entity\SellerPreference;
use TradusBundle\Entity\SellerPreferenceInterface;
use TradusBundle\Entity\SellerSitecode;
use TradusBundle\Entity\Sitecodes;
use TradusBundle\Repository\OfferRepository;
use TradusBundle\Repository\SellerRepository;
use TradusBundle\Repository\SitecodeRepository;
use TradusBundle\Service\Contract\SellerServiceInterface;
use TradusBundle\Service\Journal\JournalService;
use TradusBundle\Service\Wrapper\SolrWrapper;
use TradusBundle\Service\Wrapper\V1APIWrapper;

/**
 * Class SellerServiceHelper.
 */
class SellerServiceHelper implements SellerServiceInterface
{
    use EntityValidationTrait;

    /** @var EntityManager $entityManager */
    public $entityManager;

    /** @var SellerRepository */
    public $repository;

    /** @var SitecodeRepository $sitecodes */
    public $sitecodes;

    /** @var AdditionalServicesRepository $additionalServices */
    public $additionalServicesRepository;

    /** @var V1APIWrapper */
    protected $api;

    /** @var LoggerInterface */
    protected $logger;

    /** @var SolrWrapper */
    private $solr_wrapper;

    /** @var JournalService */
    private $journalService;

    /** @var array */
    protected $sellerOptionsType = ['phone', 'lead_phone', 'lead_email'];

    /**
     * SellerServiceHelper constructor.
     *
     * @param EntityManagerInterface $entity_manager
     * @param V1APIWrapper           $api
     * @param SolrWrapper            $solr_wrapper
     * @param LoggerInterface        $logger
     * @param JournalService         $journalService
     */
    public function __construct(
        EntityManagerInterface $entity_manager,
        V1APIWrapper $api,
        SolrWrapper $solr_wrapper,
        LoggerInterface $logger,
        JournalService $journalService
    ) {
        $this->api = $api;
        $this->logger = $logger;
        $this->entityManager = $entity_manager;
        $this->solr_wrapper = $solr_wrapper;
        $this->repository = $this->entityManager->getRepository('TradusBundle:Seller');
        $this->sitecodes = $this->entityManager->getRepository('TradusBundle:Sitecodes');
        $this->additionalServicesRepository = $this->entityManager
            ->getRepository('TradusBundle:AdditionalServices');
        $this->journalService = $journalService;
    }

    /**
     * {@inheritdoc}
     */
    public function restoreSeller(int $seller_id): Seller
    {
        $seller = $this->findSellerById($seller_id);

        if (! $seller) {
            throw new NotFoundHttpException('Seller not found');
        }

        if ($seller->getStatus() === Seller::STATUS_ONLINE) {
            return $seller;
        }

        $seller->setStatus(Seller::STATUS_ONLINE);
        $seller->setSolrStatus(Seller::SOLR_STATUS_TO_UPDATE);
        $seller = $this->persistSeller($seller);

        return $seller;
    }

    /**
     * @param int         $seller_id
     * @param bool        $hardDelete
     * @param string|null $deleteAction
     *
     * @return Seller
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function deleteSeller(int $seller_id, bool $hardDelete = false, ?string $deleteAction = null): Seller
    {
        $seller = $this->findSellerById($seller_id);

        if (! $seller) {
            throw new NotFoundHttpException('Seller not found, on method: deleteSeller, with id: '.$seller_id);
        }

        if ($hardDelete == true) {
            try {
                $this->entityManager->remove($seller);
                $this->entityManager->flush();
            } catch (Exception $e) {
                $message = 'Something went wrong with persisting to the database.'.' ['.md5($e->getMessage()).']';
                $this->logger->error($e->getMessage(), ['deleteSeller']);

                throw new UnprocessableEntityHttpException($message);
            }
        } else {
            if ($deleteAction !== self::DELETE_ACTION_ANONYMIZE) {
                $newEmail = time().'_'.$seller->getEmail();
                $seller->setEmail($newEmail);
            }
            $seller->setStatus(SellerInterface::STATUS_DELETED);
            $seller->setSolrStatus(Seller::SOLR_STATUS_TO_UPDATE);
            $seller = $this->offlineSellerOffers($seller);
            $seller = $this->persistSeller($seller);
        }

        return $seller;
    }

    /**
     * @param array  $preferences
     * @param Seller $seller
     *
     * @return Seller
     */
    public function saveSellerPreference($preferences, Seller $seller)
    {
        if (isset($preferences[SellerPreferenceInterface::FIELD_PREFERENCES][SellerPreferenceInterface::FIELD_LANGUAGE_OPTIONS])) {
            $languageOptions = $preferences[SellerPreferenceInterface::FIELD_PREFERENCES][SellerPreferenceInterface::FIELD_LANGUAGE_OPTIONS];
            $sellerPreference = $seller->getId() ? $seller->getPreference() : null;
            $sellerPreference = (! $sellerPreference) ? new SellerPreference() : $seller->getPreference();
            $sellerPreference->setLanguageOptions($languageOptions);
            $seller->setPreference($sellerPreference);
        }

        return $seller;
    }

    /**
     * @param array $phoneParams
     * @param bool  $error
     *
     * @return array
     * @throws PhoneNumberParseException
     */
    private function validatePhone(array $phoneParams, $error = false): array
    {
        $phoneList = [];
        foreach ($phoneParams as $sellerPhoneNo) {
            $sellerPhoneNo = trim($sellerPhoneNo);
            try {
                $isPhoneNoValid = PhoneNumber\PhoneNumber::parse($sellerPhoneNo)->isValidNumber();
            } catch (PhoneNumberParseException  $e) {
                $isPhoneNoValid = false;
                if ($error) {
                    throw new UnprocessableEntityHttpException('Phone number invalid.');
                }
            }
            if ($isPhoneNoValid) {
                $sellerNumber = PhoneNumber\PhoneNumber::parse($sellerPhoneNo);
                $sellerCountryCode = $sellerNumber->getCountryCode(); // 44
                $sellerNationalNumber = $sellerNumber->getNationalNumber(); // 7123456789
                $phoneNo = '+'.$sellerCountryCode.$sellerNationalNumber;
                $phoneList[] = $phoneNo;
            } else {
                if ($error) {
                    throw new UnprocessableEntityHttpException('Phone number invalid.');
                }
            }
        }

        return $phoneList;
    }

    /**
     * @param array  $options
     * @param Seller $seller
     *
     * @return Seller
     * @throws PhoneNumberParseException
     */
    public function saveSellerOption($options, Seller $seller)
    {
        if (! empty($options[SellerOptionInterface::FIELD_OPTIONS])) {
            $getOptions = $seller->getOption();
            $keepExistingOptions = [];
            if ($getOptions) {
                foreach ($getOptions as $getOption) {
                    if (in_array($getOption->getValue(), $options[SellerOptionInterface::FIELD_OPTIONS][$getOption->getValueType()])) {
                        $keepExistingOptions[$getOption->getValueType()][] = $getOption->getValue();
                    } else {
                        $this->entityManager->remove($getOption);
                        $this->entityManager->flush();
                    }
                }
            }

            if ($options[SellerOptionInterface::FIELD_OPTIONS]) {
                foreach ($options[SellerOptionInterface::FIELD_OPTIONS] as $valueType => $valueOption) {
                    if (in_array($valueType, $this->sellerOptionsType)) {
                        $addOptions = isset($keepExistingOptions[$valueType]) ? array_diff($valueOption, $keepExistingOptions[$valueType]) : $valueOption;

                        if (in_array($valueType, ['phone', 'lead_phone'])) {
                            $addOptions = $this->validatePhone($addOptions);
                            $addOptions = isset($keepExistingOptions[$valueType]) ? array_diff($addOptions, $keepExistingOptions[$valueType]) : $addOptions;
                        }
                        if (! empty($addOptions)) {
                            foreach ($addOptions as $value) {
                                $sellerOption = null;
                                $sellerOption = new SellerOption();
                                $sellerOption->setValueType($valueType);
                                $sellerOption->setValue(trim($value));
                                $seller->addOption($sellerOption);
                            }
                        }
                    }
                }
            }
        }

        return $seller;
    }

    /**
     * @param Seller $seller
     *
     * @return Seller
     * @throws UnprocessableEntityHttpException
     */
    private function persistSeller(Seller $seller)
    {
        self::validateEntity($seller);

        try {
            $this->entityManager->persist($seller);
            $this->entityManager->flush();

            return $seller;
        } catch (UnprocessableEntityHttpException $e) {
            throw new UnprocessableEntityHttpException($e->getMessage());
            //throw new UnprocessableEntityHttpException("This seller already exists.");
        } catch (Exception $e) {
            $message = 'Something went wrong with persisting to the database.'.' ['.md5($e->getMessage()).']';
            $this->logger->error($e->getMessage(), ['persistSeller']);

            throw new UnprocessableEntityHttpException($message);
        }
    }

    /**
     * @param array $params
     *
     * @return Seller
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws PhoneNumberParseException
     */
    public function putSeller(array $params): Seller
    {
        $seller = $this->findSellerById((int) $params['seller_id']);

        if (! $seller) {
            throw new NotFoundHttpException('Seller not found');
        }

        $seller = $this->populateSeller($params, $seller);

        return $seller;
    }

    /**
     * @param array $params
     *
     * @return Seller
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws PhoneNumberParseException
     */
    public function storeSeller(array $params): Seller
    {
        return $this->populateSeller($params);
    }

    /**
     * @param array       $params
     * @param Seller|null $seller
     * @param bool        $persist
     *
     * @return Seller
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws PhoneNumberParseException
     */
    public function populateSeller(array $params, ?Seller $seller = null, bool $persist = true): Seller
    {
        $updateCase = 1;
        $appFlow = [];

        if (! $seller) {
            $seller = new Seller();
            $updateCase = 0;
        }
        if (! empty($params[SellerInterface::FIELD_USER_ID])) {
            $seller->setUserId($params[SellerInterface::FIELD_USER_ID]);
        }

        if (! isset($params['patch'])) {
            $params['patch'] = false;
        }

        if (! empty($params[SellerInterface::FIELD_V1_SELLER_ID])) {
            $seller->setV1Id($params[SellerInterface::FIELD_V1_SELLER_ID]);
        }

        $seller->setStatus(Seller::STATUS_ONLINE);
        $seller->setSolrStatus(Seller::SOLR_STATUS_TO_UPDATE);

        if (! empty($params[SellerInterface::FIELD_SLUG])) {
            $seller->setSlug($params[SellerInterface::FIELD_SLUG]);
        }

        if (! empty($params[SellerInterface::FIELD_GEO_LOCATION])) {
            $seller->setGeoLocation($params[SellerInterface::FIELD_GEO_LOCATION]);
        }

        if (! empty($params[SellerInterface::FIELD_EMAIL])) {
            $seller->setEmail($params[SellerInterface::FIELD_EMAIL]);
        }

        if (! $params['patch']) {
            $sellerId = false;
            if (! isset($params[SellerInterface::FIELD_SITECODES])) {
                global $kernel;
                $siteKey = $kernel->getContainer()->getParameter('sitecode')['site_key'];
                $siteKey ? $params[SellerInterface::FIELD_SITECODES] = [$siteKey] : '';
            }
        } else {
            $sellerId = $seller->getId();
        }
        if (isset($params[SellerInterface::FIELD_SITECODES])) {
            $queryBuilder = $this->entityManager
                ->getRepository('TradusBundle:Seller')->createQueryBuilder('seller')
                ->select('seller.email')
                ->join('seller.sitecodes', 'ss')
                ->join('ss.name', 'sitecodeTb')
                ->andWhere('sitecodeTb.sitecode IN (:sitecode)')
                ->andWhere('seller.email = :email')
                ->setParameter('email', $seller->getEmail())
                ->setParameter('sitecode', $params[SellerInterface::FIELD_SITECODES]);

            if ($sellerId) {
                $queryBuilder->andWhere('seller.id != :sellerId')
                    ->setParameter('sellerId', $sellerId);
            }

            $email = $queryBuilder->getQuery()->getArrayResult();

            if (! empty($email)) {
                throw new UnprocessableEntityHttpException('This seller already exists.');
            }
        }

        if (! empty($params[SellerInterface::FIELD_CITY])) {
            $seller->setCity($params[SellerInterface::FIELD_CITY]);
        }

        if (! empty($params[SellerInterface::FIELD_COUNTRY])) {
            // Check if it's a valid country
            $countries = Intl::getRegionBundle()->getCountryNames();
            if (isset($countries[strtoupper($params[SellerInterface::FIELD_COUNTRY])])) {
                $seller->setCountry(strtoupper($params[SellerInterface::FIELD_COUNTRY]));
            } else {
                throw new UnprocessableEntityHttpException(
                    'Sellers Country '.$params[SellerInterface::FIELD_COUNTRY].' is not supported.'
                );
            }
        }

        if (! empty($params[SellerInterface::FIELD_ADDRESS])) {
            $seller->setAddress($params[SellerInterface::FIELD_ADDRESS]);
        }

        if (isset($params[SellerInterface::FIELD_STATUS])
            && $seller->getStatus() !== $params[SellerInterface::FIELD_STATUS]) {
            $seller->setStatus((int) $params[SellerInterface::FIELD_STATUS]);
        }

        if (! empty($params[SellerInterface::FIELD_COMPANY_NAME])) {
            if ((! $seller->getSlug() ||
                $params[SellerInterface::FIELD_COMPANY_NAME] != $seller->getCompanyName())
                && empty($params[SellerInterface::FIELD_SLUG])
            ) {
                $seller->setSlug($this->repository->generateSlug($params[SellerInterface::FIELD_COMPANY_NAME]));
            }
            $seller->setCompanyName($params[SellerInterface::FIELD_COMPANY_NAME]);
        }

        if (! empty($params[SellerInterface::FIELD_WEBSITE])) {
            $seller->setWebsite($params[SellerInterface::FIELD_WEBSITE]);
        }

        if (isset($params[SellerInterface::FIELD_TYPE])
            && $params[SellerInterface::FIELD_TYPE] !== $seller->getSellerType()) {
            $seller->setSellerType($params[SellerInterface::FIELD_TYPE]);
        }

        if (array_key_exists(SellerInterface::FIELD_LOGO, $params)) {
            $seller->setLogo($params[SellerInterface::FIELD_LOGO]);
        }

        if (isset($params[SellerInterface::FIELD_LOCALE]) && ! empty($params[SellerInterface::FIELD_LOCALE])) {
            $seller->setLocale($params[SellerInterface::FIELD_LOCALE]);
        }

        if (isset($params[SellerInterface::FIELD_V1_SELLER_ID])) {
            $seller->setV1Id($params[SellerInterface::FIELD_V1_SELLER_ID]);
        }

        if (isset($params[SellerInterface::FIELD_SOURCE])) {
            $seller->setSource($params[SellerInterface::FIELD_SOURCE]);
        }

        if (isset($params[SellerInterface::FIELD_PARENT_SELLER_ID])) {
            /** @var Seller $parentSeller */
            $parentSeller = $this->entityManager
                ->getRepository('TradusBundle:Seller')
                ->find((int) $params[SellerInterface::FIELD_PARENT_SELLER_ID]);

            $appFlow[] = 'Parameter parent seller id found';

            if (! empty($parentSeller)) {
                $appFlow[] = 'Setting parent seller id to seller';
                $seller->setParentSellerId($parentSeller);
            }
        }

        if (isset($params[SellerInterface::FIELD_ANALYTICS_API_TOKEN])) {
            $seller->setAnalyticsApiToken($params[SellerInterface::FIELD_ANALYTICS_API_TOKEN]);
        }

        if (isset($params[SellerInterface::FIELD_ROLES])) {
            $seller->setRoles($params[SellerInterface::FIELD_ROLES]);
        }

        if (isset($params[SellerInterface::FIELD_PASSWORD])) {
            $seller->setPassword($params[SellerInterface::FIELD_PASSWORD]);
        }

        $appFlow[] = 'Setting point of contact field to seller';
        $seller->setPointOfContact($params[SellerInterface::FIELD_POINT_OF_CONTACT] ?? 0);

        $seller->setWhatsappEnabled((int) $params[SellerInterface::FIELD_WHATSAPP_ENABLED]);

        if ($seller && $seller->getWhatsappEnabled() != $params[SellerInterface::FIELD_WHATSAPP_ENABLED]) {
            $this->updateOfferSolrStatus($seller);
        }

        if (! empty($params[SellerInterface::FIELD_PHONE])) {
            $phone = $params[SellerInterface::FIELD_PHONE];
            $phoneList = $this->validatePhone([$phone], true);
            $seller->setPhone($phoneList[0]);
        }

        if (! empty($params[SellerInterface::FIELD_MOBILE_PHONE])) {
            $phone = $params[SellerInterface::FIELD_MOBILE_PHONE];
            $phoneList = $this->validatePhone([$phone], true);
            $seller->setMobilePhone($phoneList[0]);
        }

        $seller->setTestuser((int) ! empty($params[SellerInterface::FIELD_TESTUSER_FLAG]));

        if ($persist) {
            $seller = $this->persistSeller($seller);
            $seller = $this->saveSellerPreference($params, $seller);
            $seller = $this->saveSellerOption($params, $seller);
            if (isset($params[SellerInterface::FIELD_ADDITIONAL_SERVICES])) {
                $queryString =
                    'DELETE TradusBundle:SellerAdditionalServices ss WHERE ss.sellerId = '.$seller->getId();
                $query = $this->entityManager->createQuery($queryString);
                $query->execute();
                foreach ($params[SellerInterface::FIELD_ADDITIONAL_SERVICES] as $serviceId) {
                    $service = $this->additionalServicesRepository->find($serviceId);

                    if ($service) {
                        $sellerService = new SellerAdditionalServices();
                        $sellerService->setSeller($seller);
                        $sellerService->setService($service);
                        $this->entityManager->persist($sellerService);
                    }
                }
                $this->entityManager->flush();
            }

            if (isset($params[SellerInterface::FIELD_SITECODES])) {
                $queryString = 'DELETE TradusBundle:SellerSitecode ss WHERE ss.sellerId = '.$seller->getId();
                $query = $this->entityManager->createQuery($queryString);
                $query->execute();

                $siteCodesNames = $params[SellerInterface::FIELD_SITECODES];
                foreach ($siteCodesNames as $name) {
                    $siteCode = $this->sitecodes->getSitecodeByName($name);
                    if (! $siteCode) {
                        continue;
                    }
                    $siteCodeId = $siteCode->getId();

                    $sellerSiteCode = new SellerSitecode();
                    $sellerSiteCode->setStatus(Sitecodes::STATUS_ONLINE);
                    $sellerSiteCode->setSellerId($seller->getId());
                    $sellerSiteCode->setSitecode($siteCodeId);
                    $sellerSiteCode->setSeller($seller);
                    $sellerSiteCode->setName($siteCode);
                    $sellerSiteCode->setCreatedAt(new DateTime());
                    $sellerSiteCode->setUpdatedAt(new DateTime());

                    $this->entityManager->persist($sellerSiteCode);
                }
                $this->entityManager->flush();
            }

            $this->journalService->setJournal(
                ! empty($updateCase) ? 'export_seller_update' : 'export_seller_create',
                'seller',
                'seller exported from TPRO',
                serialize(['parameters_received' => $params, 'app_flow' => $appFlow]),
                $seller->getId(),
                $seller->getId(),
                0,
                null,
                $seller->getId()
            );
        }

        return $seller;
    }

    /**
     * {@inheritdoc}
     */
    public function getSellerStatuses(): array
    {
        return Seller::getValidStatusList();
    }

    /**
     * @param string $slug
     *
     * @return Seller
     * @throws NonUniqueResultException
     * @throws Exception
     */
    public function findSellerBySlug(string $slug): Seller
    {
        $seller = $this->repository->getSellerBySlug($slug);

        if (! $seller) {
            throw new NotFoundHttpException('Seller is not found, method: findSellerBySlug with slug: '.$slug);
        }

        global $kernel;
        $translator = $kernel->getContainer()->get('translator');
        $seller->setSince($translator);

        return $seller;
    }

    /**
     * @param int $id
     *
     * @return Seller|null
     * @throws NotFoundHttpException
     */
    public function findSellerById(int $id): Seller
    {
        /** @var Seller $seller */
        $seller = $this->repository->find($id);
        if (! $seller) {
            throw new NotFoundHttpException('Seller not found, method: findSellerById with id: '.$id);
        }

        return $seller;
    }

    /**
     * {@inheritdoc}
     */
    public function findSellerByEmail(string $email)
    {
        $seller = $this->repository->findOneBy([SellerInterface::FIELD_EMAIL => $email], ['status' => 'DESC']);

        if (! $seller) {
            throw new NotFoundHttpException('Seller not found, method: findSellerByEmail with email: '.$email);
        }

        return $seller;
    }

    /**
     * @param string $email
     * @param int    $userId
     *
     * @return object|null
     */
    public function findSellerByRequest(string $email, int $userId)
    {
        $seller = $this->repository->findOneBy(
            [SellerInterface::FIELD_EMAIL => $email, SellerInterface::FIELD_ID => $userId],
            ['status' => 'DESC']
        );

        if (! $seller) {
            throw new NotFoundHttpException('Seller not found, method: findSellerByRequest with email: '.$email.' & userId: '.$userId);
        }

        return $seller;
    }

    /**
     * @param array $params
     *
     * @return Seller
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws PhoneNumberParseException
     */
    public function patchSeller(array $params): Seller
    {
        $seller = $this->findSellerById($params[SellerInterface::FIELD_SELLER_ID]);
        $seller = $this->populateSeller($params, $seller);

        return $seller;
    }

    /**
     * @param Seller $seller
     *
     * @return mixed|Seller
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function offlineSellerOffers(Seller $seller)
    {
        /** @var OfferRepository $offerRepository */
        $offerRepository = $this->entityManager->getRepository('TradusBundle:Offer');
        $offers = $offerRepository->getAllActiveOffersBySeller($seller);

        if (count($offers) > 0) {
            /** @var Offer $offer */
            foreach ($offers as $offer) {
                $offer->setStatus(Offer::STATUS_OFFLINE);
                $this->entityManager->persist($offer);
                $this->entityManager->flush();
                $this->solr_wrapper->delete('update', $offer->getId());
            }
        }

        return $seller;
    }

    /**
     * Anonymize seller data method.
     *
     * @param string $email seller's email
     *
     * @return int
     * @throws Exception
     */
    public function anonymizeSeller(string $email, int $userId)
    {
        try {
            if (empty($userId)) {
                return 0;
            }
            /** @var Seller $seller */
            $seller = $this->findSellerByRequest($email, $userId);
        } catch (NotFoundHttpException $e) {
            return 0;
        }

        $anonymizeService = new AnonymizeServiceHelper($this->entityManager);
        $seller->setEmail('anonymized'.$seller->getId().'@olx.com');
        $seller->setSlug('anonymized-'.$seller->getId());
        $seller->setAddress('');
        $seller->setCity('');
        $seller->setCompanyName('');
        $seller->setLogo('');
        $seller->setPhone('');
        $seller->setSellerType(0);
        $seller->setSource('');
        $seller->setName('');
        $seller->setLocale('');
        $seller->setGeoLocation('');
        $seller->setStatus(Seller::STATUS_DELETED);
        $seller->setAnonymizedAt(new DateTime());
        $this->entityManager->persist($seller);
        $this->entityManager->flush();

        $anonymizeService->anonymizeUser($seller->getId(), $seller->getCountry());

        return $seller->getId();
    }

    public function updateOfferSolrStatus($seller)
    {
        $offerRepository = $this->entityManager->getRepository('TradusBundle:Offer');
        $offers = $offerRepository->getAllActiveOffersBySeller($seller);
        if (count($offers) > 0) {
            /** @var Offer $offer */
            foreach ($offers as $offer) {
                $offer->setSolrStatus(Offer::SOLR_STATUS_TO_UPDATE);
                $this->entityManager->persist($offer);
                $this->entityManager->flush();
            }
        }
    }
}

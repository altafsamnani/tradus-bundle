<?php

namespace TradusBundle\Service\Helper;

use Cocur\Slugify\Slugify;
use DateTime;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use TradusBundle\Entity\Attribute;
use TradusBundle\Entity\Category;
use TradusBundle\Entity\EntityValidationTrait;
use TradusBundle\Entity\Make;
use TradusBundle\Entity\Model;
use TradusBundle\Entity\Offer;
use TradusBundle\Entity\OfferAttribute;
use TradusBundle\Entity\OfferDepreciations;
use TradusBundle\Entity\OfferDescription;
use TradusBundle\Entity\OfferDescriptionInterface;
use TradusBundle\Entity\OfferImage;
use TradusBundle\Entity\OfferImageInterface;
use TradusBundle\Entity\OfferInterface;
use TradusBundle\Entity\Seller;
use TradusBundle\Entity\SellerInterface;
use TradusBundle\Entity\Version;
use TradusBundle\Factory\MessageQueueFactory\GearmanManager;
use TradusBundle\Factory\MessageQueueFactory\MessageQueueFactory;
use TradusBundle\Repository\AttributeRepository;
use TradusBundle\Repository\CategoryRepository;
use TradusBundle\Repository\Contract\OfferAttributeRepositoryInterface;
use TradusBundle\Repository\ExchangeRateRepository;
use TradusBundle\Repository\ModelRepository;
use TradusBundle\Repository\OfferAttributeRepository;
use TradusBundle\Repository\OfferImageRepository;
use TradusBundle\Repository\OfferRepository;
use TradusBundle\Repository\VersionRepository;
use TradusBundle\Service\Contract\OfferServiceInterface;
use TradusBundle\Service\Journal\JournalService;
use TradusBundle\Service\Sitecode\SitecodeService;
use TradusBundle\Service\Wrapper\SolrWrapper;

/**
 * Class OfferServiceHelper.
 */
class OfferServiceHelper implements OfferServiceInterface
{
    use EntityValidationTrait;

    /** @var EntityManagerInterface */
    public $entityManager;

    /** @var Slugify */
    protected $slugify;

    /** @var OfferRepository */
    public $repository;

    /** @var LoggerInterface */
    protected $logger;

    /** @var SolrWrapper */
    private $solr_wrapper;

    /** @var JournalService */
    private $journalService;

    /** @var OfferImageRepository */
    private $offerImageRepository;

    protected $defaultLocale;

    /**
     * OfferServiceHelper constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param Slugify                $slugify
     * @param SolrWrapper            $solr_wrapper
     * @param LoggerInterface        $logger
     * @param JournalService         $journalService
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        Slugify $slugify,
        SolrWrapper $solr_wrapper,
        LoggerInterface $logger,
        ?JournalService $journalService = null
    ) {
        $this->entityManager = $entityManager;
        $this->slugify = $slugify;
        $this->logger = $logger;
        $this->repository = $this->entityManager
            ->getRepository('TradusBundle:Offer');
        $this->solr_wrapper = $solr_wrapper;
        $this->journalService = $journalService;
        $this->offerImageRepository = $this->entityManager->getRepository('TradusBundle:OfferImage');
        $sitecodeService = new SitecodeService();
        $this->defaultLocale = $sitecodeService->getDefaultLocale();
    }

    /**
     * @param string $slug
     * @param string $locale
     * @return Offer
     * @throws NonUniqueResultException
     */
    public function findOfferBySlug(string $slug, ?string $locale = null): Offer
    {
        $offer = $this->repository->getOfferBySlug($slug, $locale);

        if (! $offer) {
            throw new NotFoundHttpException(
                'Offer not found, method: findOfferBySlug, with slug: '.$slug.' and locale: '.$locale
            );
        }

        return $offer;
    }

    /**
     * @param string $ad_id
     * @return Offer
     * @throws NonUniqueResultException
     */
    public function findOfferByAdId(string $ad_id): Offer
    {
        $offer = $this->repository->getOfferByAdId($ad_id);
        if (! $offer) {
            throw new NotFoundHttpException('Offer not found, method: findOfferByAdId, with id: '.$ad_id);
        }

        return $offer;
    }

    /**
     * @param int $offer_id
     * @return Offer
     * @throws NonUniqueResultException
     */
    public function findOfferById(int $offer_id): Offer
    {
        $offer = $this->repository->getOfferById($offer_id);
        if (! $offer) {
            throw new NotFoundHttpException('Offer not found, method: findOfferById, with id: '.$offer_id);
        }

        return $offer;
    }

    /**
     * @param array $params
     * @return Offer
     * @throws Exception
     */
    public function storeOffer(array $params): Offer
    {
        try {
            return $this->populateOffer($params);
        } catch (DBALException $e) {
            throw new DBALException($e->getMessage());
        } catch (NonUniqueResultException $e) {
            throw new NonUniqueResultException($e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     * @throws NonUniqueResultException
     * @throws DBALException
     */
    public function patchOffer(array $params): Offer
    {
        if (! empty($params[OfferInterface::FIELD_OFFER_ID])) {
            $offer = $this->findOfferById((int) $params[OfferInterface::FIELD_OFFER_ID]);
        }

        if (! isset($offer) && ! empty($params[OfferInterface::FIELD_AD_ID])) {
            $offer = $this->findOfferByAdId($params[OfferInterface::FIELD_AD_ID]);
        }

        return $this->populateOffer($params, $offer, true);
    }

    /**
     * {@inheritdoc}
     * @throws NonUniqueResultException
     * @throws DBALException
     */
    public function putOffer(array $params): Offer
    {
        $offer = $this->findOfferById((int) $params[OfferInterface::FIELD_OFFER_ID]);

        return $this->populateOffer($params, $offer);
    }

    /**
     * {@inheritdoc}
     * @throws NonUniqueResultException
     * @throws DBALException
     */
    public function deleteOffer(int $offer_id): Offer
    {
        $offer = $this->findOfferById($offer_id);
        $seller = $offer->getSeller();
        $offer->setStatus(Offer::STATUS_DELETED);
        $offer->setSolrStatus(Offer::SOLR_STATUS_TO_UPDATE);
        $this->entityManager->persist($offer);
        $this->entityManager->flush();

        if (in_array($seller->getSolrStatus(), [Seller::SOLR_STATUS_IN_INDEX, Seller::SOLR_STATUS_NOT_IN_INDEX])) {
            $seller->setSolrStatus(Seller::SOLR_STATUS_TO_UPDATE);
            $this->entityManager->persist($seller);
            $this->entityManager->flush();
        }

        /** @var OfferAttributeRepository $offerAttributeRepo */
        $offerAttributeRepo = $this->entityManager->getRepository('TradusBundle:OfferAttribute');
        $offerAttributeRepo->softDeleteAllByOffer($offer);
        /** @var OfferImageRepository $offerImageRepo */
        $offerImageRepo = $this->entityManager->getRepository('TradusBundle:OfferImage');
        $offerImageRepo->softDeleteAllByOffer($offer);

        /**
         * Publish a message to queue in order to notify users that have this offer in favourites that it was removed
         * We should move the queue name into a common place to see all of them.
         */
        $messageFactory = new MessageQueueFactory(GearmanManager::NAME);
        $messageFactory->publish('offer_favourite_removed', ['offer_id' => $offer_id]);

        return $offer;
    }

    /**
     * {@inheritdoc}
     * @param array $params
     * @param Offer|null $offer
     * @param bool $patch
     * @param bool $persist
     * @return Offer
     * @throws DBALException
     * @throws NonUniqueResultException
     * @throws Exception
     */
    public function populateOffer(array $params, ?Offer $offer = null, bool $patch = false, bool $persist = true): Offer
    {
        if (! $offer) {
            $offer = new Offer();
        }
        $appFlow = [];

        if (empty($params[OfferInterface::FIELD_OFFER_ID]) || ! isset($params[OfferInterface::FIELD_OFFER_ID])) {
            throw new NotFoundHttpException('offer_id not sent');
        }

        $offerInDB = $this->repository->find($params[OfferInterface::FIELD_OFFER_ID]);

        if ($offerInDB != null && ! $patch) {
            throw new UnprocessableEntityHttpException('offer_id already exists');
        } elseif (! $patch) {
            $offer->setId($params[OfferInterface::FIELD_OFFER_ID]);
        }

        if (! empty($params[OfferInterface::FIELD_AD_ID]) && ! $patch) {
            $adIdOffer = $this->entityManager->getRepository(
                'TradusBundle:Offer'
            )->findOneBy([
                OfferInterface::FIELD_AD_ID => $params[OfferInterface::FIELD_AD_ID],
                ]);

            $offer->setAdId($params[OfferInterface::FIELD_AD_ID]);

            if ($adIdOffer) {
                $offer = $adIdOffer;
                $params[OfferInterface::FIELD_OFFER_ID] = $offer->getId();
            }
        }

        if (! empty($params[OfferInterface::FIELD_V1_OFFER_ID])) {
            $offer->setV1Id($params[OfferInterface::FIELD_V1_OFFER_ID]);
        }
        if (! empty($params[OfferInterface::FIELD_TPRO_ID])) {
            $offer->setTproId($params[OfferInterface::FIELD_TPRO_ID]);
        }

        $offer->setStatus(OfferInterface::STATUS_ONLINE);

        if (! empty($params[OfferInterface::FIELD_MODEL])) {
            $offer->setModel($params[OfferInterface::FIELD_MODEL]);
        }

        if (! empty($params[OfferInterface::FIELD_MODEL_NAME])) {
            $model = $this->getModel(
                $params[OfferInterface::FIELD_MODEL_NAME],
                $params[OfferInterface::FIELD_MAKE]
            );
            $offer->setModelId($model);

            if (! empty($params[OfferInterface::FIELD_VERSION_NAME])) {
                $offer->setVersionId(
                    $this->getVersion(
                        $params[OfferInterface::FIELD_VERSION_NAME],
                        $model
                    )
                );
            }
        }

        if (! empty($params[OfferInterface::FIELD_LECTURA_ID])) {
            $appFlow[] = 'Setting lectura id: '.abs($params[OfferInterface::FIELD_LECTURA_ID]);
            $offer->setLecturaId(abs($params[OfferInterface::FIELD_LECTURA_ID]));
        }

        // $offer->setUrl('url code');//todo generate URL

        if (! empty($params[OfferInterface::FIELD_CATEGORY])) {
            /** @var Category $category */
            $category = $this->entityManager
                ->getRepository('TradusBundle:Category')
                ->find((int) $params[OfferInterface::FIELD_CATEGORY]);

            if (! $category) {
                throw new NotFoundHttpException('Category not found');
            }
            $offer->setCategory($category);
        }

        if (! empty($params[OfferInterface::FIELD_MAKE])) {
            /** @var Make $make */
            $make = $this->entityManager
                ->getRepository('TradusBundle:Make')
                ->find($params[OfferInterface::FIELD_MAKE]);

            if (! $make) {
                throw new NotFoundHttpException('Make not found');
            }
            $offer->setMake($make);
        }

        if (! empty($params[OfferInterface::FIELD_SELLER])) {
            /** @var Seller $seller */
            $seller = $this->entityManager
                ->getRepository('TradusBundle:Seller')
                ->find((int) $params[OfferInterface::FIELD_SELLER]);

            if (! $seller) {
                throw new NotFoundHttpException('SELLER is not found');
            }

            if ($seller->getStatus() != SellerInterface::STATUS_ONLINE) {
                throw new UnprocessableEntityHttpException('SELLER is not online');
            }

            $offer->setSeller($seller);
        } else {
            // We require a seller
            throw new NotFoundHttpException('SELLER field is not set from API');
        }

        if (array_key_exists(OfferInterface::FIELD_PRICE, $params)) {
            $offer->setPrice((float) $params[OfferInterface::FIELD_PRICE]);
        }

        if (! empty($params[OfferInterface::FIELD_CURRENCY])) {
            $offer->setCurrency($params[OfferInterface::FIELD_CURRENCY]);
        }

        if (! empty($params[OfferInterface::FIELD_SITECODE])) {
            $siteCode = $this->entityManager
                ->getRepository('TradusBundle:Sitecodes')
                ->findOneBy([OfferInterface::FIELD_SITECODE => $params[OfferInterface::FIELD_SITECODE]]);

            if (! $siteCode) {
                throw new NotFoundHttpException('Sitecode not found');
            }

            $offer->setSitecode($siteCode->getId());
        }

        if (! empty($params[OfferInterface::FIELD_VIDEO_URL])) {
            $offer->setVideoUrl($params[OfferInterface::FIELD_VIDEO_URL]);
        }

        $imageDuplicated = (isset($params[OfferInterface::FIELD_IMAGE_DUPLICATED])
            && $params[OfferInterface::FIELD_IMAGE_DUPLICATED]) ? 1 : 0;
        $offer->setImageDuplicated($imageDuplicated);

        //Price Analysis
        if (array_key_exists(OfferInterface::FIELD_PRICE_ANALYSIS_TYPE, $params)) {
            $offer->setPriceAnalysisType($params[OfferInterface::FIELD_PRICE_ANALYSIS_TYPE]);
        }

        if (array_key_exists(OfferInterface::FIELD_PRICE_ANALYSIS_VALUE, $params)) {
            $offer->setPriceAnalysisValue($params[OfferInterface::FIELD_PRICE_ANALYSIS_VALUE]);
        }

        if (array_key_exists(OfferInterface::FIELD_PRICE_ANALYSIS_DATA, $params)) {
            $offer->setPriceAnalysisData($params[OfferInterface::FIELD_PRICE_ANALYSIS_DATA]);
        }

        // Set the sortindex for tracking order in search and we know last bumpUp
        if (! $offer->getBumpedAt()) {
            $offer->setSortIndex();
        }

        $priceType = isset($params['extra'][OfferInterface::FIELD_PRICE_TYPE]['value']) ? $params['extra'][OfferInterface::FIELD_PRICE_TYPE]['value'] : $offer::DEFAULT_PRICE_TYPE;
        $offer->setPriceType($priceType);

        if ($priceType == 'upon-request' && (float) $params[OfferInterface::FIELD_PRICE] > 0) {
            $evaluationPriceBasedOnType = $this->getPriceForUponRequest($offer);
            $offer->setPrice($evaluationPriceBasedOnType);
        }

        if (! empty($params[OfferInterface::FIELD_CREATED_AT])) {
            $created_at = new DateTime();
            $created_at->setTimestamp($params[OfferInterface::FIELD_CREATED_AT]);
            $offer->setCreatedAt($created_at);
            $offer->setUpdatedAt(new DateTime());
        }

        if ($persist) {
            self::validateEntity($offer);
            $solrStatus = $offer->getStatus() != OfferInterface::STATUS_SEMI_ACTIVE ?
                Offer::SOLR_STATUS_TO_UPDATE : Offer::SOLR_STATUS_NOT_IN_INDEX;
            $offer->setSolrStatus($solrStatus);
            $metadata = $this->entityManager->getClassMetaData(get_class($offer));
            $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);
            $this->entityManager->persist($offer);
            $this->entityManager->flush();
            $offer = $this->repository->find($params[OfferInterface::FIELD_OFFER_ID]);
        }

        if (! empty($params[OfferInterface::FIELD_EXTRA])) {
            $offer = $this->setOfferAttributes($params[OfferInterface::FIELD_EXTRA], $offer, $patch);
        }

        if (! empty($params[OfferInterface::FIELD_SLUG])) {
            $slug = $params[OfferInterface::FIELD_SLUG];
        } else {
            $slug = null;
        }

        $descriptions_received = array_key_exists(OfferInterface::FIELD_DESCRIPTIONS, $params);
        if ($descriptions_received) {
            $this->saveOfferDescriptions($params[OfferInterface::FIELD_DESCRIPTIONS], $offer, $slug);
        } elseif (! $descriptions_received && empty($offer->getId())) {
            // For new offers without a description we add an empty description so the
            // OfferDescription
            $this->saveOfferDescriptions([
                $this->defaultLocale => '',
            ], $offer, $slug);
        }

        if (isset($params[OfferInterface::FIELD_IMAGES])) {
            if ($patch) {
                if ($offer->getImages()) {
                    $this->offerImageRepository->deleteAllByOffer($offer);
                }
            }

            if (! empty($params[OfferInterface::FIELD_IMAGES])) {
                foreach ($params[OfferInterface::FIELD_IMAGES] as $index => $image) {
                    $offerImage = new OfferImage();
                    $offerImage->setUrl($image[OfferImageInterface::PARAMETER_URL]);
                    $offerImage->setImageTextFound($image[OfferImageInterface::PARAMETER_IMAGE_TEXT]);
                    $offerImage->setOffer($offer);
                    $offerImage->setStatus(OfferImage::STATUS_ONLINE);
                    $imageSizes = ! empty($image['sizes']) ?
                        $image['sizes'] :
                        '{"small":{"width":94,"height":71},"medium":{"width":261,"height":196},"large":{"width":933,"height":700}}';
                    $offerImage->setSizes($imageSizes);
                    $offerImage->setSizeStatus(! empty($image['sizes']) ?
                        OfferImageInterface::SIZE_STATUS_FOUND : OfferImageInterface::SIZE_STATUS_NOT_FOUND);

                    if (! empty($image[OfferImageInterface::PARAMETER_SORT_ORDER]) ||
                        (isset($image[OfferImageInterface::PARAMETER_SORT_ORDER]) &&
                            $image[OfferImageInterface::PARAMETER_SORT_ORDER] == 0)) {
                        $sort_order = $image[OfferImageInterface::PARAMETER_SORT_ORDER];
                    } else {
                        $sort_order = $index;
                    }
                    $offerImage->setSortOrder($sort_order);

                    if (! empty($image[OfferImageInterface::PARAMETER_SORT_ORDER_POSE]) ||
                        (isset($image[OfferImageInterface::PARAMETER_SORT_ORDER_POSE]) &&
                         $image[OfferImageInterface::PARAMETER_SORT_ORDER_POSE] == 0)) {
                        $sort_order_pose = $image[OfferImageInterface::PARAMETER_SORT_ORDER_POSE];
                    } else {
                        $sort_order_pose = $index;
                    }
                    $offerImage->setSortOrderPose($sort_order_pose);

                    $offer->addImage($offerImage);
                    $this->entityManager->persist($offerImage);
                }
            }
        }

        if (! empty($params[OfferInterface::FIELD_STATUS])) {
            $mappingStatus = OfferInterface::STATUS_MAPPING;
            $status = $mappingStatus[0];
            if (array_key_exists($params[OfferInterface::FIELD_STATUS], $mappingStatus)) {
                $status = $mappingStatus[$params[OfferInterface::FIELD_STATUS]];
            }
            $offer->setStatus($status);
        }

        if (array_key_exists(OfferInterface::FIELD_POSE_STATUS, $params)) {
            $offer->setPoseStatus($params[OfferInterface::FIELD_POSE_STATUS]);
        }

        // Disabling depreciation
        /*if (!empty($params[OfferInterface::FIELD_DEPRECIATION])) {
            $this->setDepreciation($offer, $params[OfferInterface::FIELD_DEPRECIATION]);
        }*/

        if ($persist) {
            self::validateEntity($offer);
            $solrStatus = $offer->getStatus() != OfferInterface::STATUS_SEMI_ACTIVE ?
                Offer::SOLR_STATUS_TO_UPDATE : Offer::SOLR_STATUS_NOT_IN_INDEX;
            $offer->setSolrStatus($solrStatus);

            $this->entityManager->persist($offer);
            $this->entityManager->flush();

            $seller = $offer->getSeller();
            if ($offer->getStatus() != OfferInterface::STATUS_SEMI_ACTIVE && $seller &&
                in_array($seller->getSolrStatus(), [Seller::SOLR_STATUS_IN_INDEX, Seller::SOLR_STATUS_NOT_IN_INDEX])
            ) {
                $seller->setSolrStatus(Seller::SOLR_STATUS_TO_UPDATE);
                $this->entityManager->persist($seller);
                $this->entityManager->flush();
            }
        }

        if ($this->journalService) {
            $this->journalService->setJournal(
                ! empty($patch) ? 'export_offer_update' : 'export_offer_create',
                'offer',
                'advert exported from TPRO',
                serialize(['parameters_receeived' => $params, 'app_flow' => $appFlow]),
                ! empty($seller->getId()) ? $seller->getId() : 0,
                ! empty($offer->getId()) ? $offer->getId() : 0,
                0,
                null,
                ! empty($seller->getId()) ? $seller->getId() : 0
            );
        }

        return $offer;
    }

    /**
     * TO index an offer into the search engine.
     * @param Offer $offer
     */
    public function indexOffer(Offer $offer)
    {
        $locales = OfferInterface::SUPPORTED_LOCALES;

        if ($offer->getCurrency() != 'EUR') {
            /** @var ExchangeRateRepository $rate */
            $rate = $this->entityManager
                ->getRepository('TradusBundle:ExchangeRate')
                ->findOneBy(['currency' => $offer->getCurrency()]);
        } else {
            $rate = null;
        }

        $payload = $offer->generateSolrPayload($locales, $rate);

        $this->solr_wrapper->post('update', [$payload]);
    }

    /**
     * @param string $file_path
     *
     * @return array
     * @throws UnprocessableEntityHttpException
     */
    public function processImageForApollo($file_path)
    {
        $base_name = basename($file_path);
        $file_name_parts = explode('.', $base_name);
        $extension = $file_name_parts[count($file_name_parts) - 1];
        $content = @file_get_contents($file_path);

        // Create a new file name for this image.
        $new_file_name = sha1(uniqid()).$extension;
        $media_path = '/tmp/'.$new_file_name;

        switch ($extension) {
            case 'gif':
                $image = imagecreatefromgif($file_path);
                imagepng($image, $media_path);
                break;

            // These formats are supported by Apollo.
            case 'png':
            case 'jpg':
            case 'jpeg':
                file_put_contents($media_path, $content);
                break;
            default:
                throw new UnprocessableEntityHttpException("This extension: $extension is not supported.");
        }

        return [$media_path, $new_file_name];
    }

    /**
     * {@inheritdoc}
     * @throws NonUniqueResultException
     */
    public function restoreOffer(int $offer_id): Offer
    {
        $offer = $this->findOfferById($offer_id);

        if ($offer->getStatus() === OfferInterface::STATUS_ONLINE) {
            return $offer;
        }

        $offer->setStatus(OfferInterface::STATUS_ONLINE);
        $offer->setSolrStatus(Offer::SOLR_STATUS_TO_UPDATE);
        $this->entityManager->persist($offer);
        $this->entityManager->flush();

        return $offer;
    }

    /**
     * {@inheritdoc}
     */
    public function saveOfferDescriptions($descriptions, Offer $offer, $slug)
    {
        $existingLocaleList = [];
        $newLocaleList = [];
        $isPatch = $offer->getId() ? true : false;
        foreach ($descriptions as $locale => $description) {
            $title = null;
            if (is_array($description) && ! empty($description[OfferDescriptionInterface::FIELD_TITLE])) {
                $title = $description[OfferDescriptionInterface::FIELD_TITLE];
            }

            // POST
            if (! $isPatch) {
                $offerDescription = new OfferDescription();
                $offerDescription->setLocale($locale);
                $offerDescription->setDescription($description);
                $offerDescription->setOffer($offer);
                $offer->addDescription($offerDescription);
                $this->saveTitle($offer, $offerDescription, $title);
            } else {
                // PATCH
                $new_desc = true;
                foreach ($offer->getDescriptions() as $offerDescription) {
                    $existingLocale = $offerDescription->getLocale();
                    $existingLocaleList[$existingLocale] = $existingLocale;
                    $existingLocaleDescriptions[$existingLocale] = $offerDescription;
                    if ($existingLocale == $locale) {
                        $offerDescription->setDescription($description);
                        $new_desc = false;
                    }
                }
                if ($new_desc) {
                    $offerDescription = new OfferDescription();
                    $offerDescription->setLocale($locale);
                    $offerDescription->setDescription($description);
                    $offerDescription->setOffer($offer);
                    $offer->addDescription($offerDescription);
                }

                $this->saveTitle($offer, $offerDescription, $title);
            }
            $newLocaleList[$locale] = $locale;
        }

        if ($isPatch) {
            $deleteLocales = array_diff($existingLocaleList, $newLocaleList);
            foreach ($deleteLocales as $deleteLocale) {
                $deleteDescription = $existingLocaleDescriptions[$deleteLocale];
                $this->entityManager->remove($deleteDescription);
                $this->entityManager->flush();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setOfferAttributes(array $params, Offer $offer, bool $patch): Offer
    {
        /** @var AttributeRepository $attributeRepository */
        $attributeRepository = $this->entityManager->getRepository('TradusBundle:Attribute');

        if ($offer->getAttributes()) {
            /** @var OfferAttributeRepositoryInterface $offerAttributeRepository */
            $offerAttributeRepository = $this->entityManager->getRepository('TradusBundle:OfferAttribute');
            $offerAttributeRepository->deleteAllByOffer($offer);
        }

        foreach ($params as $attributeName => $attributeValue) {
            $content = isset($attributeValue['value']) ? $attributeValue['value'] : null;
            $tamerValue = isset($attributeValue['tamer']) && $attributeValue['tamer'] ? $attributeValue['tamer'] : 0;
            if ($attributeName != OfferInterface::FIELD_PRICE_TYPE && $content) {
                /** @var Attribute $attribute */
                $attribute = $attributeRepository->findOneBy([
                    'name' => $attributeName,
                    'parentId' => null,
                ]);

                if (! $attribute) {
                    throw new NotFoundHttpException(
                        sprintf('attribute not found: [%s: %s]', $attributeName, $content)
                    );
                }

                if (is_array($content)) {
                    foreach ($content as $c) {
                        $this->saveAttribute($offer, $attribute, $c, $patch, $tamerValue);
                    }
                } else {
                    $this->saveAttribute($offer, $attribute, $content, $patch, $tamerValue);
                }
            }
        }

        return $offer;
    }

    public function setDepreciation(Offer $offer, array $params)
    {
        $offerDepreciation = new OfferDepreciations();
        $offerDepreciation->setOffer($offer);
        $offerDepreciation->setExtraAgeMonths($params['extra_age_months']);
        $offerDepreciation->setListingPredictedPrice(floatval($params['listing_predicted_price']));
        $offerDepreciation->setListingIntervalFactor($params['listing_interval_factor']);
        $offerDepreciation->setListingAnnualFactor($params['listing_annual_factor']);
        $offerDepreciation->setVersionAnnualFactor($params['version_annual_factor']);
        $offerDepreciation->setCategoryAnnualFactor($params['category_annual_factor']);
        $this->entityManager->persist($offerDepreciation);
        $this->entityManager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function saveAttribute(Offer $offer, Attribute $att, string $content, bool $patch, $tamerValue = 0)
    {
        $offerAttribute = new OfferAttribute();

        if ($att->getAttributeType() == Attribute::ATTRIBUTE_TYPE_LIST) {
            $optionRepository = $this->entityManager->getRepository('TradusBundle:AttributeOption');
            $option = $optionRepository->find($content);
            if ($option) {
                $offerAttribute->setOptionId($option->getId());
                $content = $option->getContent();
            } else {
                return false;
            }
        }

        $offerAttribute->setAttribute($att);
        $offerAttribute->setOffer($offer);
        $offerAttribute->setContent($content);
        $offerAttribute->setStatus(OfferAttributeRepository::STATUS_ONLINE);
        $offerAttribute->setTamerStatus($tamerValue);
        $offer->addAttribute($offerAttribute);
        $this->entityManager->persist($offerAttribute);
    }

    /**
     * @param Offer $offer
     * @param OfferDescription $offerDescription
     * @param $title
     */
    public function saveTitle(Offer $offer, OfferDescription $offerDescription, $title = null)
    {
        if (! $title) {
            $titleParts = [];
            $modelText = $offer->getModel();

            if ($offer->getMake()) {
                $makeName = $offer->getMake()->getName();

                // Only add makeName into the title when not yet in model(and should not be Other
                $makeNameInModelText = strpos(strtolower($modelText), strtolower($makeName));
                if ($makeNameInModelText !== 0 && $makeName != 'Other') {
                    $titleParts[] = $makeName;
                }
            }

            if ($modelText) {
                $titleParts[] = $modelText;
            }

            // @TODO can we not filter a single attribute load all makes no sense.
            // We had $offer->getExtra()->getConstructionYear() before which made more sense.
            if ($offer->getAttributes()) {
                foreach ($offer->getAttributes() as $offer_att) {
                    $att_name = $offer_att->getAttribute()->getName();

                    if ($att_name == OfferInterface::FIELD_CONSTRUCTION_YEAR) {
                        $construction_year = $offer_att->getContent();
                        if (! empty($construction_year)) {
                            $titleParts[] = '- '.$construction_year;
                        }
                        break;
                    }
                }
            }
            $title = ! empty($titleParts) ? implode(' ', $titleParts) : '';
        }

        $offerDescription->setTitle($title);

        // Set temp slug, will be overwritten when we flush the data.
        $offerDescription->setTitleSlug(uniqid());
        $this->entityManager->persist($offerDescription);
    }

    /**
     * @param $locale
     * @return string
     */
    public static function localizedMake($locale)
    {
        $locale = self::getShortLocale($locale);

        $localizedMakes = ['en' => 'make-',
            'nl' => 'merk-',
            'pl' => 'marka-',
            'pt-pt' => 'marca-',
            'ro' => 'marca-',
            'ru' => 'marka-',
            'es' => 'marca-',
            'it' => 'produttore-',
            'fr' => 'fabricant-',
            'de' => 'marke-',
            'sk' => 'značka-',
            'fl' => 'merk-', // Flemish,
            'nl-be' => 'merk-', // Flemish,
            'lt' => 'markė-', //Lithuanian,
            'uk' => 'головешка-', // Ukrainian (not RU),
            'tr' => 'marka-', // Turkish,
            'el' => 'μάρκα-', // Greek,
            'hu' => 'márka-', // Hungarian,
            'sr' => 'Марка-', // Serbian,
            'da' => 'mærke-', //Danish,
            'hr' => 'marka-', // Croatian,
            'bg' => 'марка-', // Bulgarian,
        ];

        if (isset($localizedMakes[$locale])) {
            return transliterator_transliterate('Russian-Latin/BGN;Any-Latin;Latin-ASCII;', $localizedMakes[$locale]);
        }

        return transliterator_transliterate('Russian-Latin/BGN;Any-Latin;Latin-ASCII;', $localizedMakes['en']);
    }

    /**
     * @param $locale
     * @return string
     */
    public static function localizedLocation($locale)
    {
        $locale = self::getShortLocale($locale);
        $localizedLocations = [
            'en' => 'location-',
            'nl' => 'locatie-',
            'pl' => 'lokalizacja-',
            'pt-pt' => 'localizacao-',
            'ro' => 'locatie-',
            'ru' => 'mesto-',
            'es' => 'ubicacion-',
            'it' => 'posizione-',
            'fr' => 'emplacement-',
            'de' => 'standort-',
            'sk' => 'umiestnenia-', //Slovakia
            'fl' => 'lokaasje-', // Flemish, leave for backwards compatibility replaced by nl-be
            'nl-be' => 'lokaasje-', // Flemish,
            'lt' => 'lokacija-', //Lithuanian,
            'uk' => 'Місцезнаходження-', // Ukrainian (not RU),
            'tr' => 'konum-', // Turkish,
            'el' => 'τοποθεσία-', // Greek,
            'hu' => 'elhelyezkedés-', // Hungarian,
            'sr' => 'локација-', // Serbian,
            'da' => 'Beliggenhed-', //Danish,
            'hr' => 'mjesto-', // Croatian,
            'bg' => 'местоположение-', // Bulgarian,
        ];

        if (isset($localizedLocations[$locale])) {
            return transliterator_transliterate('Russian-Latin/BGN;Any-Latin;Latin-ASCII;', $localizedLocations[$locale]);
        }

        return transliterator_transliterate('Russian-Latin/BGN;Any-Latin;Latin-ASCII;', $localizedLocations['en']);
    }

    /**
     * @param $locale
     * @return string
     */
    public static function localizedRegion($locale)
    {
        $locale = self::getShortLocale($locale);
        $localizedRegion = [
            'en' => 'region-',
            'nl' => 'regio-',
            'pl' => 'region-',
            'pt-pt' => 'região-',
            'ro' => 'regiune-',
            'ru' => 'регион-',
            'es' => 'región-',
            'it' => 'regione-',
            'fr' => 'région-',
            'de' => 'region-',
            'sk' => 'región-',
            'fl' => 'region-',
            'nl-be' => 'region-',
            'lt' => 'region-',
            'uk' => 'регіон-',
            'tr' => 'bölge-',
            'el' => 'Περιοχή-',
            'hu' => 'terület-',
            'sr' => 'region-',
            'da' => 'område-',
            'hr' => 'region-',
            'bg' => 'pегион-',
        ];

        if (isset($localizedRegion[$locale])) {
            return transliterator_transliterate('Russian-Latin/BGN;Any-Latin;Latin-ASCII;', $localizedRegion[$locale]);
        }

        return transliterator_transliterate('Russian-Latin/BGN;Any-Latin;Latin-ASCII;', $localizedRegion['en']);
    }

    /**
     * @param $locale
     * @return string
     */
    public static function localizedPriceType($locale)
    {
        /* @TODO Fix the selected value from phraseApp */
        $locale = self::getShortLocale($locale);
        $localizedPriceType = [
            'en' => 'pricetype-',
            'nl' => 'prijsbespreekkostenaantal-',
            'pl' => 'pricetype-',
            'pt-pt' => 'pricetype-',
            'ro' => 'pricetype-',
            'ru' => 'pricetype-',
            'es' => 'pricetype-',
            'it' => 'pricetype-',
            'fr' => 'pricetype-',
            'de' => 'preistyp-',
            'sk' => 'typceny-',
            'fl' => 'pricetype-',
            'nl-be' => 'pricetype-',
            'lt' => 'kainųtipas-',
            'uk' => 'pricetype-',
            'tr' => 'pricetype-',
            'el' => 'pricetype-',
            'hu' => 'pricetype-',
            'sr' => 'прицеtипе-',
            'da' => 'pristype-',
            'hr' => 'pricetype-',
            'bg' => 'pricetype-',
        ];

        if (isset($localizedPriceType[$locale])) {
            return transliterator_transliterate('Russian-Latin/BGN;Any-Latin;Latin-ASCII;', $localizedPriceType[$locale]);
        }

        return transliterator_transliterate('Russian-Latin/BGN;Any-Latin;Latin-ASCII;', $localizedPriceType['en']);
    }

    /**
     * @param $locale
     * @return string
     */
    public static function localizedTransmission($locale)
    {
        /* @TODO Fix the selected value from phraseApp */
        $locale = self::getShortLocale($locale);

        $localizedTrans = [
            'en' => 'transmission-',
            'nl' => 'transmissie-',
            'pl' => 'transmission-',
            'pt-pt' => 'transmissão-',
            'ro' => 'transmisie-',
            'ru' => 'tрансмиссия-',
            'es' => 'transmisión-',
            'it' => 'trasmissione-',
            'fl' => 'transmission-',
            'fr' => 'transmission-',
            'de' => 'getriebe-',
            'sk' => 'prevodovka-',
            'nl-be' => 'aandrijving-',
            'lt' => 'transmisija-',
            'uk' => 'tрансмісія-',
            'tr' => 'Şanzıman-',
            'el' => 'transmission-',
            'hu' => 'erőátvitel-',
            'sr' => 'prenos-',
            'da' => 'gearkasser-',
            'hr' => 'prijenos-',
            'bg' => 'tрансмисия-',
        ];

        if (isset($localizedTrans[$locale])) {
            return transliterator_transliterate('Russian-Latin/BGN;Any-Latin;Latin-ASCII;', $localizedTrans[$locale]);
        }

        return transliterator_transliterate('Russian-Latin/BGN;Any-Latin;Latin-ASCII;', $localizedTrans['en']);
    }

    /**
     * For cases were the locale comes in a extended format.
     * @param string $locale
     * @return string
     */
    public static function getShortLocale(?string $locale = null)
    {
        if (strpos($locale, '_') !== false) {
            $arr = explode('_', $locale);

            return strtolower($arr[0]);
        }

        return $locale;
    }

    /**
     * Function getPriceForUponRequest.
     * @param Offer $offer
     * @return float
     * @throws DBALException
     */
    public function getPriceForUponRequest(Offer $offer): float
    {
        $category = $offer->getCategory();
        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = $this->entityManager->getRepository('TradusBundle:Category');
        $categoryL1 = $categoryRepository->getTopLevelCategoryId($category);

        $price = $offer->getPrice() ?? 0;

        switch ($categoryL1) {
            case 1:
            case 50:
            case 83:
                if ($price < 1000) {
                    return 0;
                }
                break;
            case 118:
                if ($price < 200) {
                    return 0;
                }
                break;
            case 4014:
                if ($price < 300) {
                    return 0;
                }
                break;
        }

        return $price;
    }

    /**
     * Returns a model object based on the name and make id
     * If the model does not exist it creates one first.
     *
     * @param string $modelName
     * @param int $makeId
     * @return Model
     * @throws Exception
     */
    private function getModel(string $modelName, int $makeId): Model
    {
        /** @var ModelRepository $modelRepository */
        $modelRepository = $this->entityManager->getRepository('TradusBundle:Model');
        /** @var Model $model */
        $model = $modelRepository->findOneBy([
            'modelName' => $modelName,
            'status' => Model::STATUS_ACTIVE,
        ]);
        if ($model) {
            return $model;
        }
        /** @var Slugify $slugify */
        $slugify = new Slugify();

        $model = new Model();
        $model->setModelName($modelName);
        $model->setMakeId($makeId);
        $model->setModelSlug($slugify->slugify($modelName));
        $model->setStatus(Model::STATUS_ACTIVE);
        $model->setCreatedAt(new DateTime());
        $this->entityManager->persist($model);
        $this->entityManager->flush();

        return $model;
    }

    /**
     * Returns a version object based on the name and model id
     * If the version does not exist it creates one first.
     *
     * @param string $versionName
     * @param Model $model
     * @return Version
     * @throws Exception
     */
    private function getVersion(string $versionName, Model $model): Version
    {
        $modelId = $model->getId();
        /** @var VersionRepository $versionRepository */
        $versionRepository = $this->entityManager->getRepository('TradusBundle:Version');
        /** @var Version $version */
        $version = $versionRepository->findOneBy([
            'versionName' => $versionName,
            'modelId' => $modelId,
            'status' => Version::STATUS_ACTIVE,
        ]);

        if ($version) {
            return $version;
        }

        /** @var Slugify $slugify */
        $slugify = new Slugify();

        $version = new Version();
        $version->setVersionName($versionName);
        $version->setVersionSlug($slugify->slugify($versionName));
        $version->setModelId($modelId);
        $version->setStatus(Version::STATUS_ACTIVE);
        $version->setCreatedAt(new DateTime());

        $this->entityManager->persist($version);
        $this->entityManager->flush();

        return $version;
    }
}

<?php

namespace TradusBundle\Transformer;

use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Exception;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Constraints\Date;
use TradusBundle\Entity\Attribute;
use TradusBundle\Entity\Offer;
use TradusBundle\Entity\OfferDescriptionInterface;
use TradusBundle\Entity\OfferImageInterface;
use TradusBundle\Entity\OfferInterface;
use TradusBundle\Entity\PriceType;
use TradusBundle\Entity\Seller;
use TradusBundle\Entity\Sitecodes;
use TradusBundle\Repository\ReportImageRepository;
use TradusBundle\Service\Config\ConfigService;
use TradusBundle\Service\Config\ConfigServiceInterface;
use TradusBundle\Service\Helper\OfferServiceHelper;
use TradusBundle\Service\Offer\OfferService;
use TradusBundle\Service\Search\SearchService;
use TradusBundle\Service\Seller\SellerService;
use TradusBundle\Service\Sitecode\SitecodeService;
use TradusBundle\Utils\CurrencyExchange\CurrencyExchange;
use TradusBundle\Utils\CurrencyExchange\CurrencyExchangeException;
use TradusBundle\Utils\MysqlHelper\MysqlHelper;

/**
 * Class OfferTransformer.
 */
class OfferTransformer extends AbstractTransformer
{
    public const TMV_DEFAULT_CURRENCY = 'EUR';

    /** @var Offer */
    private $offer;

    /** @var string */
    private $locale;

    /** @var array */
    private $attributeIds = [];

    /** @var EntityManager */
    private $entityManager;

    /** @var Translator */
    private $translator;

    /** @var ConfigService */
    private $configuration;

    /** @var string */
    protected $defaultLocale;

    /** @var int */
    private $userId;

    /** @var mixed */
    private $imageVersion;

    /** @var string */
    protected $sessionToken;

    /** @var int */
    protected $sitecodeId;

    /** @var string */
    protected $sitecodeTitle;

    /** @var string */
    private $sitecodeKey;

    /**
     * OfferTransformer constructor.
     *
     * @param Offer $offer
     * @param string $locale
     * @param EntityManager $entityManager
     * @param int $userId
     */
    public function __construct(Offer $offer, ?string $locale = null, ?EntityManager $entityManager = null, $userId = 0, $sessionToken = null, $imageVersion = null)
    {
        $this->offer = $offer;
        $this->userId = $userId;
        $this->sessionToken = $sessionToken;
        $this->imageVersion = $imageVersion;
        $this->attributeIds = [
            OfferInterface::FIELD_INTERIOR,
            OfferInterface::FIELD_EXTERIOR,
            OfferInterface::FIELD_CHASSIS_OPTIONS,
            OfferInterface::FIELD_OPTIONS_ATTACHMENTS,
        ];
        $this->entityManager = $entityManager;
        $ssc = new SitecodeService();
        $this->sitecodeTitle = $ssc->getSitecodeTitle();
        $this->sitecodeId = $ssc->getSitecodeId();
        $this->sitecodeKey = $ssc->getSitecodeKey();

        global $kernel;
        $this->translator = $kernel->getContainer()->get('translator');
        $this->configuration = new ConfigService($entityManager);
        $this->defaultLocale = $kernel->getContainer()->getParameter(ConfigServiceInterface::DEFAULT_LOCALE_CONFIG);
        $this->locale = $locale ?? $this->defaultLocale;
    }

    /**
     * @param bool $isApp
     * @param string $sitecode
     *
     * @return array
     * @throws CurrencyExchangeException
     * @throws ORMException
     * @throws Exception
     */
    public function transform($isApp = false, $sitecode = ''): array
    {
        $offer = $this->offer;
        $locale = $this->locale;
        $this->translator->setLocale($locale);

        // Categories.
        $category = $offer->getCategory();
        $categoryId = $category->getId();
        $categories = $category ? $category->getCatsArray($this->locale) : [];
        $categoryName = array_column($categories, 'label');

        // Descriptions.
        $descriptions = [];
        $allTitleSlugs = [];
        $englishTitleSlug = null;
        foreach ($offer->getDescriptions() as $description) {
            $descriptionText = $description->getDescription();
            $descriptions[$description->getLocale()][OfferDescriptionInterface::FIELD_DESCRIPTION]
                = $isApp && $descriptionText ? $this->cleanText($descriptionText) : $descriptionText;
            $descriptions[$description->getLocale()][OfferDescriptionInterface::FIELD_TITLE]
                = $description->getTitle();
            $descriptions[$description->getLocale()][OfferDescriptionInterface::FIELD_SLUG]
                = $description->getTitleSlug();
            $allTitleSlugs[] = $description->getTitleSlug();
            if ($description->getLocale() == $this->defaultLocale) {
                $englishTitleSlug = $description->getTitleSlug();
            }
        }

        // Fetch title.
        $result['label'] = $offer->getOfferTitle($offer);

        // Fetch gallery.
        $images = $this->fetchGallery();
        if (isset($images[0])) {
            $result['image'] = [
                'small'  => [
                    'url' => $images[0][OfferImageInterface::IMAGE_SIZE_SMALL][OfferImageInterface::PARAMETER_URL],
                ],
                'medium' => [
                    'url' => $images[0][OfferImageInterface::IMAGE_SIZE_MEDIUM][OfferImageInterface::PARAMETER_URL],
                ],
                'large'  => [
                    'url' => $images[0][OfferImageInterface::IMAGE_SIZE_LARGE][OfferImageInterface::PARAMETER_URL],
                ],
            ];
        }

        // Slug Category.
        $slugCategory = '';
        if ($category) {
            $slugCategory = $category->getSlugUrl($this->locale);
        }

        if ($offer->getMake()) {
            $slugCategory .= '/'.$offer->getMake()->getSlug();
        }

        // Alternates.
        $alternates = null;

        foreach (OfferInterface::SUPPORTED_LOCALES as $supportedLocale) {
            $alternates[$supportedLocale] = $offer->getUrlByLocale(
                $supportedLocale,
                $englishTitleSlug ?? $allTitleSlugs[0]
            );
        }

        // Search Slug Category.
        $searchSlugCategory = '';
        if ($category) {
            $searchSlugCategory = $category->getSearchSlugUrl($this->locale);
        }

        if ($offer->getMake()) {
            $searchSlugCategory .=
                OfferServiceHelper::localizedMake($this->locale).$offer->getMake()->getSlug().'/';
        }

        //TODO to be refactored once the mobile version handles the dynamic attributes
        //   # Extra.
        $extra = [];
        $extra['showSteeringWheel'] = false;
        $offerGrossPrice = false;
        $rightHandCountries = $this->configuration->getSettingValue('steeringwheel.rightHand');
        if ($offer->getAttributes()) {
            foreach ($offer->getAttributes() as $offer_attribute) {
                if (! $offer_attribute->getAttribute() || $offer_attribute->getStatus() !== Attribute::STATUS_ONLINE) {
                    continue;
                }
                $attributeName = $offer_attribute->getAttribute()->getName();

                if ($attributeName == OfferInterface::FIELD_CONDITION
                    && $offer_attribute->getContent() == OfferInterface::CONDITION_USED) {
                    continue;
                }
                if ($attributeName == OfferInterface::FIELD_GROSS_PRICE) {
                    $offerGrossPrice = (int) $offer_attribute->getContent();
                    continue;
                }
                if ($attributeName == OfferInterface::FIELD_COUNTRY) {
                    $attributeName = OfferInterface::FIELD_MANUFACTURING_COUNTRY;
                }
                if ($attributeName == OfferInterface::FIELD_STEERING_WHEEL_SIDE) {
                    $extra[OfferInterface::FIELD_STEERING_WHEEL_SIDE_ORIGINAL]
                        = $offer_attribute->getContent();
                }
                $translatedAttributeContent = $this->translator->trans($offer_attribute->getContent());
                if (in_array($attributeName, $this->attributeIds)) {
                    $extra[$attributeName][] = $translatedAttributeContent;
                } else {
                    $extra[$attributeName] = $translatedAttributeContent;
                }
            }
            $extra['rightHandCountries'] = $rightHandCountries;
        }

        $itemLocation = json_decode($extra['item_location'] ?? '{}', true);
        $extra['item_lat'] = ! empty($itemLocation['lat']) ? $itemLocation['lat'] : '';
        $extra['item_lng'] = ! empty($itemLocation['lng']) ? $itemLocation['lng'] : '';
        $extra['item_city'] = ! empty($itemLocation['city']) ? $itemLocation['city'] : '';
        $extra['item_country'] = ! empty($itemLocation['country']) ? $itemLocation['country'] : '';

        //********************************END to be refactored

        $extraAttributes = [];
        $doNotTranslateTypes = [
            Attribute::ATTRIBUTE_TYPE_BOOLEAN,
            Attribute::ATTRIBUTE_TYPE_DECIMAL,
            Attribute::ATTRIBUTE_TYPE_NUMERIC,
            Attribute::ATTRIBUTE_TYPE_TEXT,
        ];
        /**
         * I have a deja-vu
         * line 179: `if ($offer->getAttributes()) {`.
         */
        $attributes = $offer->getAttributes();
        if ($attributes) {
            //Build attributes from the offer_attributes table
            $optionRepo = $this->entityManager->getRepository('TradusBundle:AttributeOption');
            foreach ($attributes as $attribute) {
                if (! $attribute->getAttribute() || $attribute->getStatus() !== Attribute::STATUS_ONLINE) {
                    continue;
                }
                if ($attribute->getAttribute()->getStatus() !== Attribute::STATUS_ONLINE) {
                    continue;
                }
                $key = $attribute->getAttribute()->getGroup();

                if ($key) {
                    $attributeType = $attribute->getAttribute()->getAttributeType();

                    /* $translationKey will be used for next step on PhraseApp */
                    if ($attributeType == Attribute::ATTRIBUTE_TYPE_LIST) {
                        $option = $optionRepo->find($attribute->getOptionId());

                        if (! $option) {
                            continue;
                        }

                        $translationKey = $option->getTranslationKey();
                        $translationContent = $option->getContent();
                    } else {
                        $translationKey = $attribute->getAttribute()->getTranslationKey();
                        $translationContent = $attribute->getContent();
                    }

                    $label = $this->translator->trans($attribute->getAttribute()->getTranslationText());
                    $tamer = $attribute->getTamerStatus();

                    if ($attribute->getAttribute()->getSelectMultiple() !== 1) {
                        $content = in_array($attributeType, $doNotTranslateTypes) ? $translationContent :
                            $this->translator->trans($translationContent);

                        $extraAttributes[$key->getId()]['items'][] = [
                            'id' => $attribute->getAttribute()->getId(),
                            'label'=> $label,
                            'translation_content'   => $content,
                            'measure_unit'          => $attribute->getAttribute()->getMeasureUnit(),
                            'sort_order'            => $attribute->getAttribute()->getSortOrder(),
                            'type'                  => $attribute->getAttribute()->getAttributeType(),
                            'tamer'                 => $tamer,
                        ];
                    } else {
                        $extraAttributes[$key->getId()]['multi_items'][$attribute->getAttribute()->getId()]['items'][]
                            = [
                            'translation_content' => $this->translator->trans($translationContent),
                            'sort_order' => $option->getSortOrder(),
                            ];

                        $extraAttributes[$key->getId()]['multi_items'][$attribute->getAttribute()->getId()]['label']
                            = $label;

                        $tamerStatus = $extraAttributes[$key->getId()]['multi_items'][$attribute->getAttribute()->getId()]['tamer'] ?? 0;

                        if (! $tamerStatus) {
                            $extraAttributes[$key->getId()]['multi_items'][$attribute->getAttribute()->getId()]['tamer']
                                = $tamer;
                        }

                        $extraAttributes[$key->getId()]['multi_items'][$attribute->getAttribute()->getId()]['sort_order'] = $attribute->getAttribute()->getSortOrder();
                    }
                }
            }

            // ADDING OVERVIEW SECTION BY DEFAULT
            $overviewAttributesArray = empty($extraAttributes[1]['items']) ? [] : $extraAttributes[1]['items'];

            if (! empty($offer->getMake()->getName())) {
                $makeName = $offer->getMake()->getName();
                $makeContent = strtolower($makeName) == 'other' ? $this->translator->trans($makeName) : $makeName;
                $overviewAdditionalAttributes[] =
                    $this->getStaticAttributes($this->translator->trans('Make'), $makeContent, -2);
            }

            if (! empty($offer->getModel())) {
                $overviewAdditionalAttributes[] =
                    $this->getStaticAttributes($this->translator->trans('Model'), $offer->getModel(), -1);
            }

            if (! empty($offer->getAttribute('condition'))) {
                $overviewAdditionalAttributes[] =
                    $this->getStaticAttributes($this->translator->trans('Condition'), $this->translator->trans($offer->getAttribute('condition')));
            }

            if (! empty($offer->getAttribute('construction_year'))) {
                $year = $offer->getAttribute('construction_year');
                if (! empty($offer->getAttribute('construction_month'))) {
                    $year = $offer->getAttribute('construction_month').'/'.$year;
                }

                foreach ($overviewAttributesArray as $index => $overviewAttribute) {
                    if ($overviewAttribute['id'] === 100342) {
                        $overviewAttributesArray[$index]['translation_content'] = $year;
                    }
                }
            }

            $OverviewAttributesArray = array_merge($overviewAdditionalAttributes, $overviewAttributesArray);
            $siteName = $this->sitecodeTitle;
            $OverviewAttributesArray[] = $this->getStaticAttributes(ucfirst($siteName).' ID', $offer->getId(), 100);

            $extraAttributes[1]['items'] = $OverviewAttributesArray;
            //OVERVIEW ENDs

            //Build Group Info
            $keysFound = array_keys($extraAttributes);
            $groupRepo = $this->entityManager->getRepository('TradusBundle:AttributeGroup');
            foreach ($keysFound as $key) {
                $group = $groupRepo->find($key);

                if ($group) {
                    //Will be used in next step for PhraseApp
                    //$extraAttributes[$key]['translation_key'] = $group->getTranslationKey();
                    $extraAttributes[$key]['translation_text'] = $this->translator->trans($group->getName());
                    $extraAttributes[$key]['sort_order'] = $group->getSortOrder();
                }
            }

            //Sort Groups
            array_multisort(array_column($extraAttributes, 'sort_order'), SORT_ASC, $extraAttributes);

            //Sort items
            foreach ($extraAttributes as $k => $group) {
                if (isset($group['items'])) {
                    $temp = $group['items'];
                    array_multisort(array_column($temp, 'sort_order'), SORT_ASC, $temp);
                    $extraAttributes[$k]['items'] = $temp;
                }
                if (isset($group['multi_items'])) {
                    //Sort childs
                    foreach ($group['multi_items'] as $kChild => $multi_group) {
                        $temp = $multi_group['items'];
                        array_multisort(array_column($temp, 'sort_order'), SORT_ASC, $temp);
                        $extraAttributes[$k]['multi_items'][$kChild]['items'] =
                            implode(
                                ', ',
                                array_column($temp, 'translation_content')
                            );
                    }

                    //Sort heads
                    $temp = $extraAttributes[$k]['multi_items'];
                    array_multisort(array_column($temp, 'sort_order'), SORT_ASC, $temp);
                    $extraAttributes[$k]['multi_items'] = $temp;
                }
            }
        }

        $offerMake = $offer->getMake();
        $sellerService = new SellerService($this->entityManager);
        $seller = $sellerService->updateGeolocation($offer->getSeller());
        $result['id'] = $offer->getId();
        $result['url'] = $alternates[$this->locale];

        global $kernel;
        $container = $kernel->getContainer();
        $locales = $container->getParameter('app.locales');
        if ($locales) {
            $localesAvailable = explode('|', $locales);
        } else {
            $localesAvailable = [$container->getParameter(Sitecodes::LOCALE_CONFIG)];
        }

        foreach ($localesAvailable as $loc) {
            if (isset($alternates[$loc])) {
                $result['offer_url_'.$loc] = $alternates[$loc];
            }
        }

        $whereSitecode = $sitecode ? ['sitecode' => $sitecode] : ['id' => $offer->getSitecode()];
        $sitecodes = $this->entityManager->getRepository('TradusBundle:Sitecodes')
            ->findOneBy($whereSitecode);
        $result['full_url'] = 'https://www.'.$sitecodes->getDomain().$alternates[$this->locale];
        $result['model'] = $offer->getModel();
        $result['version'] = $offer->getVersionId();
        $result['price_type'] = $offer->getPriceType();
        $result['make'] = $offerMake->getName();
        $result['make_id'] = $offerMake->getId();
        $result['make_slug'] = $offerMake->getSlug();
        $seller->setSince($this->translator);

        $sellerTransformer = new SellerTransformer($seller);
        $result['seller'] = $sellerTransformer->transform();

        $result['seller_additional_services'] = json_decode($seller->getAdditionalServicesPayload(), true);
        if (isset($result['seller_additional_services'])) {
            $sellerAdditionalServices = [];
            foreach ($result['seller_additional_services'] as $service) {
                $service['title'] = $this->translator->trans($service['title']);
                $service['description'] = $this->translator->trans($service['description']);
                $sellerAdditionalServices[] = $service;
            }
            $result['seller_additional_services'] = $sellerAdditionalServices;
        }

        $result['sellerOption'] = $seller->getOptionValues();

        // TO REMOVE THIS KEY ONCE APP IS NOT USING IT
        $result['sellerSince'] = $this->getSellerSince($seller);
        // The madness ends here

        $result['created_at'] = $offer->getCreatedAt();
        $result['dateAddedText'] = OfferService::timeElapsedString($result['created_at']
            ->format('Y-m-d H:i:s'), $this->translator);
        $result['category'] = $categoryName;
        $result['category_id'] = $categoryId;
        $result['category_ids'][] = $categories[0]['id'];
        $result['categories'] = $categories;
        $result['first_lead'] = $offer->getAnalytics() ? $offer->getAnalytics()->getEmails() ? false : true : true;
        $result['lectura_id'] = $offer->getLecturaId();
        $result['visited'] = $offer->hasUserVisitedOffer($this->userId);
        $result['fallbackImage'] = $this->entityManager->getRepository('TradusBundle:Offer')
            ->getOfferFallbackImage($offer);
        $result['video_url'] = $offer->getVideoUrl();
        if ($offer->getDepreciation()) {
            $result['depreciation'] = $offer->getDepreciationPayload();
        }

        $mysqlHelper = new MysqlHelper($this->entityManager->getConnection());
        $exchangeRates = new CurrencyExchange($mysqlHelper->getConnection());

        $result = array_merge($result, $this->getWlPrice($offer, $exchangeRates));

        $result['attributes'] = $extraAttributes;
        $result['extra'] = $extra;
        $result['descriptions'] = $descriptions;
        $result['images'] = $images;
        $result['slug_category'] = $slugCategory;
        $result['alternates'] = $alternates;
        $result['categoryLink'] = '/'.$this->locale.'/search/'.$searchSlugCategory;

        switch ($offer->getStatus()) {
            case Offer::STATUS_ONLINE:
                $status = 'live';
                break;

            case Offer::STATUS_OFFLINE:
                $status = 'offline';
                break;

            case Offer::STATUS_DELETED:
                $status = 'deleted';
                break;

            case Offer::STATUS_SEMI_ACTIVE:
                $status = 'live';
                break;

            default:
                $status = 'offline';
                break;
        }

        $result['status'] = $status;

        $sellerCountry = $offer->getSeller()->getCountry();
        $offerCountry = $extra['item_country'] !== '' ? $extra['item_country']
            : Intl::getRegionBundle()->getCountryName($sellerCountry);
        $result['country'] = $offerCountry;

        // Add price analysis data
        // We only send this if the offers is in the selected categories.
        // If there is no available regression data but in selected categories we send 'false'
        // in order to show the message 'No price rating'
        $result = $this->getRegressionData($result, $offer, $exchangeRates);

        return $result;
    }

    /**
     * getStaticAttributes.
     */
    protected function getStaticAttributes($label, $content, $sort = 0)
    {
        return [
            'id' => 0,
            'label' => $label,
            'translation_content' => $content,
            'measure_unit' => '',
            'sort_order' => $sort,
            'type' => 'string',
        ];
    }

    /**
     * @param array|Offer $offer
     *
     * TODO replace this by something uniform which can be used anywhere.
     *
     * @return array
     */
    public function getExchangeRates($offer)
    {
        if (is_array($offer)) {
            $offerCurrency = $offer['currency'];
            $offerPrice = $offer['price'];
        } else {
            $offerCurrency = $offer->getCurrency();
            $offerPrice = $offer->getPrice();
        }
        $usedExchangeRates = ['DKK', 'RON', 'HUF', 'PLN', 'GBP', 'RUB', 'CHF', 'SEK', 'TRY', 'UAH', 'USD', 'EUR'];

        $exchangeRates = [];

        foreach ($usedExchangeRates as $currency) {
            if ($currency == 'EUR' && $offerCurrency != 'EUR') {
                $rate = $this->entityManager
                    ->getRepository('TradusBundle:ExchangeRate')->findOneBy(
                        ['currency' => $offerCurrency],
                        ['updated_at' => 'desc']
                    );
                $exchangeRates[$currency] = ceil($offerPrice / $rate->getRate());
            } else {
                $rate = $this->entityManager
                    ->getRepository('TradusBundle:ExchangeRate')
                    ->findOneBy(['currency' => $currency], ['updated_at' => 'desc']);

                if ($offerCurrency == $currency) {
                    $exchangeRates[$currency] = ceil($offerPrice);
                } else {
                    $exchangeRates[$currency] = ceil($offerPrice * $rate->getRate());
                }
            }
        }

        return $exchangeRates;
    }

    /**
     * Function for fetching the gallery for an offer.
     *
     * @return array
     */
    private function fetchGallery()
    {
        $gallery = [];
        $reportedImages = $this->getReportedImages();

        $images = $this->imageVersion == 'pose_v1' ? $this->offer->getPoseImages() : $this->offer->getImages();
        foreach ($images as $image) {
            $imageResult = [
                OfferImageInterface::IMAGE_SIZE_SMALL => [],
                OfferImageInterface::IMAGE_SIZE_MEDIUM => [],
                OfferImageInterface::IMAGE_SIZE_LARGE => [],
            ];

            // Check if we have the sizes for this image.
            if ($image->getSizes()) {
                $sizes = json_decode($image->getSizes(), true);
                $imageUrl = $image->getUrl();
                foreach (OfferImageInterface::IMAGE_SIZES as $imageSize) {
                    $url = $imageUrl.OfferImageInterface::IMAGE_SIZE_PRESETS[$imageSize];
                    $imageResult[$imageSize] = [
                        OfferImageInterface::PARAMETER_URL => $url,
                        OfferImageInterface::PARAMETER_WIDTH => $sizes[$imageSize][OfferImageInterface::PARAMETER_WIDTH],
                        OfferImageInterface::PARAMETER_HEIGHT => $sizes[$imageSize][OfferImageInterface::PARAMETER_HEIGHT],
                        OfferImageInterface::PARAMETER_ID => $image->getId(),
                        OfferImageInterface::PARAMETER_IMAGE_REPORTED => in_array($image->getId(), $reportedImages),
                    ];
                }
            }

            $gallery[] = $imageResult;
        }

        return $gallery;
    }

    private function getReportedImages()
    {
        $offerId = $this->offer->getId();
        $userId = $this->userId;
        $sessionToken = $this->sessionToken;
        $reportedImages = [];

        if (! empty($offerId) && ! empty($sessionToken)) {
            /** @var ReportImageRepository $reportImageRepo */
            $reportImageRepo = $this->entityManager->getRepository('TradusBundle:ReportImage');
            $reportedImages = array_map(function ($reportImage) {
                return $reportImage->getOfferImageId();
            }, $reportImageRepo->getReportedImages($offerId, $sessionToken, $userId));
        }

        return $reportedImages;
    }

    /**
     * @param $result
     * @param Offer $offer
     * @param CurrencyExchange $exchangeRates
     * @return mixed
     * @throws CurrencyExchangeException
     */
    public function getRegressionData($result, Offer $offer, CurrencyExchange $exchangeRates)
    {
        $priceAnalysisCategories = $this->configuration->getSettingValue('priceAnalysis.categories');

        if (! in_array($offer->getCategory()->getId(), $priceAnalysisCategories)
            || ! in_array($offer->getPriceType(), [Offer::PRICE_TYPE_FIXED, PriceType::UPON_REQUEST])
        ) {
            return $result;
        }

        $result['priceAnalysisType'] = ! empty($offer->getPriceAnalysisType()) ?
            $offer->getPriceAnalysisType() : false;
        $result['priceAnalysisValue'] = ! is_null($offer->getPriceAnalysisValue()) ?
            $offer->getPriceAnalysisValue() : false;
        $result['priceAnalysisData'] = ! is_null($offer->getPriceAnalysisData()) ?
            $offer->getPriceAnalysisData() : false;
        $result['regressionData'] = false;

        $regressionData = json_decode($offer->getRegressionData());
        $priceAnalysisData = json_decode($offer->getPriceAnalysisData(), true);

        if (! $priceAnalysisData) {
            return $result;
        }

        $priceAnalysisValue = str_replace('-', '', $offer->getPriceAnalysisValue());

        $result['regressionData'] = [
            'analysis_val' => $priceAnalysisValue,
        ];

        // Added this back to fix the missing tmv value.
        // We have TMV data calculated in EUR only
        $regressionLine = isset($priceAnalysisData['price']) ?
            $priceAnalysisData['price'] : (($regressionData) ? $regressionData->regression_line : false);

        if ($regressionLine) {
            $result['regressionData']['regression_line'] = (float) $regressionLine;
            $result['regressionData']['regression_line_data_price']
                = $exchangeRates->getExchangeRates($regressionLine, self::TMV_DEFAULT_CURRENCY);

            if (isset($result['data_price'], $result['regressionData']['regression_line_data_price'])) {
                $result['regressionData']['analysis_val_data_price'] = $exchangeRates->getEstimatedPrice(
                    $result['data_price'],
                    $result['regressionData']['regression_line_data_price']
                );
            }
        }

        $includedFactors = isset($priceAnalysisData['model_included_factors']) ?
            $priceAnalysisData['model_included_factors'] : false;
        $result['regressionData']['included_factors'] = ! empty($includedFactors) ?
            $this->getRatingFactorsText($includedFactors, true) : $includedFactors;

        $excludedFactors = isset($priceAnalysisData['model_excluded_factors']) ?
            $priceAnalysisData['model_excluded_factors'] : false;
        $result['regressionData']['excluded_factors'] = ! empty($excludedFactors) ?
            $this->getRatingFactorsText($excludedFactors) : $excludedFactors;

        $ratingBounds = isset($priceAnalysisData['rating_bounds']) ? $priceAnalysisData['rating_bounds'] : [];
        $result['regressionData']['rating_bounds'] = $ratingBounds;

        $result['regressionData']['invalid_factors'] = (is_null($offer->getPriceAnalysisType())
            || $offer->getPriceAnalysisType() == 0) ? [
            'title' => $this->translator
                ->trans('Not enough information available from the offer to show the price valuation.'),
            ] : false;

        $result['regressionData']['price_ranges'] = [];

        foreach ($ratingBounds as $key => $priceRange) {
            $min = isset($priceRange['min']) ? $priceRange['min'] : false;
            if ($min) {
                $result['regressionData']['price_ranges'][$key]['min']
                        = $exchangeRates->getExchangeRates($min, self::TMV_DEFAULT_CURRENCY);
            }

            $max = isset($priceRange['max']) ? $priceRange['max'] : false;
            if ($min) {
                $result['regressionData']['price_ranges'][$key]['max']
                        = $exchangeRates->getExchangeRates($max, self::TMV_DEFAULT_CURRENCY);
            }
        }

        $lowerBoundsMax = isset($priceAnalysisData['rating_bounds'][1]['max']) ?
            $priceAnalysisData['rating_bounds'][1]['max'] : false;
        $result['regressionData']['lower_bounds_max'] = $lowerBoundsMax;
        if ($lowerBoundsMax) {
            $result['regressionData']['lower_bounds_max_data_price']
                = $exchangeRates->getExchangeRates($lowerBoundsMax, self::TMV_DEFAULT_CURRENCY);
        }

        $upperBoundsMin = isset($priceAnalysisData['rating_bounds'][5]['min']) ?
            $priceAnalysisData['rating_bounds'][5]['min'] : false;
        $result['regressionData']['upper_bounds_min'] = $upperBoundsMin;
        if ($upperBoundsMin) {
            $result['regressionData']['upper_bounds_min_data_price']
                = $exchangeRates->getExchangeRates($upperBoundsMin, self::TMV_DEFAULT_CURRENCY);
        }

        return $result;
    }

    /**
     * @param Seller $seller
     *
     * @return int
     *
     * @throws Exception
     */
    private function getSellerSince(Seller $seller)
    {
        $createdDate = $seller->getCreatedAt();
        $today = date('Y-m-d H:i:s');
        $dateDiff = $createdDate->diff(new DateTime($today));

        switch ($dateDiff->y) {
            case 0:
                return $this->translator->trans('Registered on TRADUS less than a year');
                break;
            case 1:
                return $this->translator->trans('Registered on TRADUS for a year');
                break;
            default:
                return $this->translator->trans(
                    'Registered on TRADUS for @duration years',
                    ['@duration' => $dateDiff->y]
                );
                break;
        }
    }

    /**
     * To get the text to be displayed for the factors included or excluded while calculating price analysis data.
     *
     * @param array $aFactors
     * @param bool $updateKey
     * @return array
     */
    private function getRatingFactorsText(array $aFactors, $updateKey = false)
    {
        $aFactorText = [
            'country' => 'Country',
            'make' => 'Make',
            'model' => 'Model',
            'version' => 'Version',
            'construction_year' => 'Year',
            'constructionMonth' => 'Month',
            'mileage' => 'Mileage',
            'axleConfiguration' => 'Axle configuration',
            'maxPower' => 'Engine power',
            'weight' => 'Gross weight',
            'transmission' => 'Transmission',
            'cabin' => 'Cabin type',
            'emissionLevel' => 'Emission category',
            'drive' => 'Drive',
            'height' => 'Height',
            'length' => 'Length',
            'width' => 'Width',
            'maxPayload' => 'Max. payload',
            'engineCapacityDisplacement' => 'Engine capacity or displacement',
            'noSeats' => 'No. of seats',
            'cargoSpaceHeight' => 'Cargo space height',
            'cargoSpaceLength' => 'Cargo space length',
            'cargoSpaceWidth' => 'Cargo space width',
            'noDoors' => 'No. of doors',
        ];

        $aResult = [];
        if ($updateKey) {
            $aFactors = array_map('ucfirst', $this->flattenArray($aFactors));

            if (isset($aFactors['country'])) {
                $countries = Intl::getRegionBundle()->getCountryNames();
                $aFactors['country'] = isset($countries[$aFactors['country']]) ? $countries[$aFactors['country']] : $aFactors['country'];
            }
            unset($aFactors['category_l3_name']);
        }

        $aFactorKeys = $updateKey ? array_keys($aFactors) : $aFactors;
        foreach ($aFactorKeys as $factorKey => $factorValue) {
            if (array_key_exists($factorValue, $aFactorText)) {
                $aResult[$factorKey] = $this->translator->trans($aFactorText[$factorValue]);
            } else {
                $factorText = ucwords(str_replace('_', ' ', $factorValue));
                $aResult[$factorKey] = $this->translator->trans($factorText);
            }
        }

        return $updateKey ? array_combine($aResult, $aFactors) : $aResult;
    }

    /**
     * To convert multidimensional array to single.
     *
     * @param array $aFactors
     * @return array
     */
    private function flattenArray(array $aFactors)
    {
        $aResult = [];
        foreach ($aFactors as $factorData) {
            $aResult = array_merge($aResult, $factorData);
        }

        return $aResult;
    }

    /**
     * @param Offer $offer
     * @return array
     * @throws CurrencyExchangeException
     */
    private function getWlPrice(Offer $offer, CurrencyExchange $exchangeRates): array
    {
        $result = [
            'currency' => 'EUR',
            'data_price' => [],
            'gross_price' => 0,
            'price' => 0,
        ];

        //Prevent crawlers
        if ($offer->getStatus() !== OfferInterface::STATUS_ONLINE) {
            return $result;
        }

        global $kernel;
        $container = $kernel->getContainer();
        /** @var SearchService $search */
        $search = $container->get('tradus.search');
        $searchResults = $search->getOfferById($offer->getId());

        if (empty($offer->getSeller()->getTestuser())) {
            if (count($searchResults) == 0) {
                return $result;
            }

            /*$currency = isset($searchResults[$this->sitecodeKey.'_currency_facet_string']) ?
                $searchResults[$this->sitecodeKey.'_currency_facet_string'] : 'EUR';
            $price = isset($searchResults[$this->sitecodeKey.'_price_facet_double']) ?
                $searchResults[$this->sitecodeKey.'_price_facet_double'] : 0;
            $grossPrice = isset($searchResults[$this->sitecodeKey.'_gross_price_facet_double']) ?
                $searchResults[$this->sitecodeKey.'_gross_price_facet_double'] : 0;*/

            $currency = $searchResults['currency_facet_string'] ?? 'EUR';
            $price = $searchResults['price'] ?? 0;
            $grossPrice = $searchResults['gross_price_facet_double'] ?? 0;
        } else {
            $currency = 'EUR';
            $price = $grossPrice = 0;
            if (! empty($offer->getCurrency())) {
                $currency = $offer->getCurrency();
                $price = $offer->getPrice();
                $grossPrice = 0;
            }
        }

        $currencyWl = isset($searchResults[$this->sitecodeKey.'_currency_facet_string']) ?
            $searchResults[$this->sitecodeKey.'_currency_facet_string'] : 'EUR';
        $result['currency'] = $currencyWl;
        $result['data_price'] = $exchangeRates->getExchangeRates($price, $currency, false);
        $result['price'] = $result['data_price'][$currencyWl];

        if ($grossPrice > 0) {
            $result['gross_price_data_price'] = $exchangeRates->getExchangeRates($grossPrice, $currency, false);
            $result['gross_price'] = $result['gross_price_data_price'][$currencyWl];
        }

        return $result;
    }
}

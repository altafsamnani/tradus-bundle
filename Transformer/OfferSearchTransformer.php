<?php

namespace TradusBundle\Transformer;

use Doctrine\ORM\EntityManager;
use Locale;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Translation\Translator;
use TradusBundle\Entity\AttributeOption;
use TradusBundle\Entity\Offer;
use TradusBundle\Entity\OfferImageInterface;
use TradusBundle\Entity\Sitecodes;
use TradusBundle\Service\Config\ConfigService;
use TradusBundle\Service\Config\ConfigServiceInterface;
use TradusBundle\Service\Offer\OfferService;
use TradusBundle\Service\Sitecode\SitecodeService;
use TradusBundle\Utils\CurrencyExchange\CurrencyExchange;
use TradusBundle\Utils\CurrencyExchange\CurrencyExchangeException;
use TradusBundle\Utils\MysqlHelper\MysqlHelper;

/**
 * Class OfferSearchTransformer.
 */
class OfferSearchTransformer
{
    /** @var array */
    private $solr;

    /** @var string */
    private $locale;

    /** @var string */
    private $currency;

    /** @var int */
    private $categoryId;

    /** @var int */
    private $userId;

    /** @var EntityManager */
    private $entityManager;

    /** @var Translator */
    private $translator;

    /** @var ConfigService */
    private $configuration;

    /**
     * OfferSearchTransformer constructor.
     * @param mixed $solr
     * @param string $locale
     * @param int $categoryId
     * @param EntityManager $entityManager
     * @param int $userId
     * @param string $currency
     */
    public function __construct(
        $solr,
        ?string $locale = null,
        int $categoryId = 0,
        ?EntityManager $entityManager = null,
        int $userId = 0,
        string $currency = 'EUR'
    ) {
        $this->categoryId = $categoryId;
        $this->userId = $userId;
        $this->solr = $solr;
        $this->currency = $currency;
        $this->entityManager = $entityManager;
        global $kernel;
        $this->translator = $kernel->getContainer()->get('translator');
        $this->configuration = $kernel->getContainer()->get('tradus.config');
        $this->locale = $locale ?? $kernel->getContainer()->getParameter(ConfigServiceInterface::DEFAULT_LOCALE_CONFIG);
    }

    /**
     * Tranforms the Search Offer Response.
     *
     * @return array
     * @throws CurrencyExchangeException
     */
    public function transform()
    {
        $locale = $this->locale;
        $this->translator->setLocale($locale);
        Locale::setDefault($locale);
        $countries = Intl::getRegionBundle()->getCountryNames();
        $mysql_helper = new MysqlHelper($this->entityManager->getConnection());
        $exchangeRates = new CurrencyExchange($mysql_helper->getConnection());
        $attributeOptionRepository = $this->entityManager->getRepository('TradusBundle:AttributeOption');
        $priceOptionsNames = [];
        foreach (AttributeOption::PRICE_OPTIONS_DISPLAY_SEARCH as $option) {
            $priceOption = $attributeOptionRepository->find($option);
            $priceOptionsNames[$option] = $priceOption->getContent();
        }

        $sitecodeService = new SitecodeService();
        $siteKey = $sitecodeService->getSitecodeKey();
        $priceRatingAllowed = false;

        if (is_array($this->solr)) {
            $documents = $this->solr['response']['docs'];
            $priceRatingAllowed = ! empty($this->solr['extra_filters_allowed']['priceRating']);
        } else {
            $documents = $this->solr->getDocuments();
        }

        global $kernel;
        $container = $kernel->getContainer();
        $locales = $container->getParameter('app.locales');
        if ($locales) {
            $localesAvailable = explode('|', $locales);
        } else {
            $localesAvailable = [$container->getParameter(Sitecodes::LOCALE_CONFIG)];
        }

        $offers = [];
        $offersList = [];
        $offerIds = array_column($documents, 'offer_id');
        $visitedOffer = $this->getOffersAnalyticsData($offerIds);
        $offerService = new OfferService();
        foreach ($documents as $doc) {
            if (in_array($doc['offer_id'], $offersList)) {
                continue;
            }
            $offersList[] = $doc['offer_id'];
            $offer = [];
            $offer['id'] = intval($doc['offer_id']);
            $offer['category_id'] = reset($doc['category']);
            $offer['dateAdded'] = $doc['create_date'];
            $offer['sortIndex'] = $doc['sort_index'];
            $offer['sellerType'] = @$doc['seller_type'];
            $offer['imagesCount'] = isset($doc['images_count_facet_int']) ? $doc['images_count_facet_int'] : '';
            $offer['score'] = isset($doc['score']) ? $doc['score'] : '';

            $offer['ad_id'] = isset($doc['ad_id_facet_string']) ? $doc['ad_id_facet_string'] : '';

            if (empty($doc['offer_url_'.$locale])) {
                $url = $doc['offer_url_en'];
            } else {
                $url = $doc['offer_url_'.$locale];
            }

            foreach ($localesAvailable as $loc) {
                if (isset($doc['offer_url_'.$loc])) {
                    $offer['offer_url_'.$loc] = $doc['offer_url_'.$loc];
                }
            }

            $model = '';
            if (isset($doc['model_str'])) {
                if (is_array($doc['model_str'])) {
                    $model = $doc['model_str'][0];
                } else {
                    $model = $doc['model_str'];
                }
            }

            $offer['url'] = $url;
            $offer['path'] = '/'.$locale.'/offer/'.$doc['offer_id'];
            $offer['make'] = $doc['make_str'];
            $offer['model'] = $model;
            $offer['version'] = isset($doc['version_str']) ? $doc['version_str'] : '';
            $offer['video_url'] = isset($doc['video_url_facet_string']) ? $doc['video_url_facet_string'] : '';
            $offer['highlight'] = isset($doc[$siteKey.'_offer_highlight_facet_int']) ? $doc[$siteKey.'_offer_top_facet_int'] : 0;
            $offer['top'] = isset($doc[$siteKey.'_offer_top_facet_int']) ? $doc[$siteKey.'_offer_top_facet_int'] : 0;
            $offer['locale'] = $locale;
            $offer['constructionYear'] = isset($doc['year']) ? $doc['year'] : 0;
            $offer['city'] = isset($doc['seller_city']) ? $doc['seller_city'] : '';
            $offer['category'] = isset($doc['category_name_'.$locale]) ? $doc['category_name_'.$locale] : '';
            $offer['category_ids'] = isset($doc['category']) ? $doc['category'] : '';
            $offer['price_type'] = isset($doc['price_type']) ? $doc['price_type'] : 'fixed';

            $priceWL = isset($doc[$siteKey.'_price_facet_double']) ?
                $doc[$siteKey.'_price_facet_double'] : 0;

            $currencyOffer = isset($doc[$siteKey.'_currency_facet_string']) ?
                $doc[$siteKey.'_currency_facet_string'] : 'EUR';

            $offer['data_price'] = $exchangeRates->getExchangeRates($priceWL, $currencyOffer, false);
            $offer['price'] = $offer['data_price'][$this->currency];
            $offer['currency'] = $this->currency;
            $offer['displayGross'] = isset($doc['gross_net_facet_int']) ? $doc['gross_net_facet_int'] : 0;

            if ($offer['price_type'] == 'fixed') {
                $grossPriceWL = isset($doc[$siteKey.'_gross_price_facet_double']) ?
                    $doc[$siteKey.'_gross_price_facet_double'] : 0;
                $offer['gross_price_data_price'] =
                    $exchangeRates->getExchangeRates($grossPriceWL, $currencyOffer, false);
                $offer['gross_price'] = $offer['gross_price_data_price'][$this->currency];
            }

            $offer['country'] = isset($doc['seller_country']) ? $countries[$doc['seller_country']] : '';
            $itemLocation = json_decode($doc['item_location_facet_string'] ?? '{}', true);
            $offer['item_lat'] = ! empty($itemLocation['lat']) ? $itemLocation['lat'] : '';
            $offer['item_lng'] = ! empty($itemLocation['lng']) ? $itemLocation['lng'] : '';
            $offer['item_city'] = ! empty($itemLocation['city']) ? $itemLocation['city'] : '';
            $offer['item_country'] = ! empty($itemLocation['country']) ? $itemLocation['country'] : '';

            if (isset($doc['depreciation_facet_string'])) {
                $offer['depreciation'] = json_decode($doc['depreciation_facet_string'], true);
            }

            if (! empty($doc['thumbnail'])) {
                $thumbnail = $doc['thumbnail'];
            } else {
                $thumbnail = null;
            }

            $offer['image'] = [
                'small' => [
                    'url' => $thumbnail ? $thumbnail.OfferImageInterface::IMAGE_SIZE_PRESETS[OfferImageInterface::IMAGE_SIZE_SMALL] : $thumbnail,
                ],
                'medium' => [
                    'url' => $thumbnail ? $thumbnail.OfferImageInterface::IMAGE_SIZE_PRESETS[OfferImageInterface::IMAGE_SIZE_MEDIUM] : $thumbnail,
                ],
                'large' => [
                    'url' => $thumbnail,
                ],
            ];

            if (isset($doc['pose_status_facet_int'])) {
                $offer['status_pose'] = $doc['pose_status_facet_int'];
                if ($doc['pose_status_facet_int'] == 1 && ! empty($doc['thumbnail_pose_facet_string'])) {
                    $poseThumbnail = $doc['thumbnail_pose_facet_string'];
                    $offer['image_pose'] = [
                        'small' => [
                            'url' => $poseThumbnail ? $poseThumbnail.OfferImageInterface::IMAGE_SIZE_PRESETS[OfferImageInterface::IMAGE_SIZE_SMALL] : $poseThumbnail,
                        ],
                        'medium' => [
                            'url' => $poseThumbnail ? $poseThumbnail.OfferImageInterface::IMAGE_SIZE_PRESETS[OfferImageInterface::IMAGE_SIZE_MEDIUM] : $poseThumbnail,
                        ],
                        'large' => [
                            'url' => $poseThumbnail,
                        ],
                    ];
                }
            }
            $offer['seller_id'] = $doc['seller_id'];
            $offer['label'] = isset($doc['title_'.$locale]) ? $doc['title_'.$locale] : '';
            if (empty($doc['title_'.$locale])) {
                $offer['label'] = isset($doc['title_en']) ? $doc['title_en'] : '';
            } else {
                $offer['label'] = $doc['title_'.$locale];
            }

            $offer['weight'] = isset($doc['weight_facet_string']) ? floatval($doc['weight_facet_string']) : 0;
            $offer['weightNet'] = isset($doc['weight_net_facet_double'])
                ? floatval($doc['weight_net_facet_double']) : 0;

            // @todo: a quick fix for mobile search api, needs to be revered to floatval once fixed in app
            $offer['mileage'] = isset($doc['mileage_facet_string']) ? intval($doc['mileage_facet_string']) : 0;
            $offer['mileageUnit'] =
                ! empty($doc['mileage_unit_facet_string']) ? $doc['mileage_unit_facet_string'] : null;
            $offer['hoursRun'] = isset($doc['hours_run_facet_string']) ? floatval($doc['hours_run_facet_string']) : 0;

            $offer['dateAddedText'] = OfferService::timeElapsedString($doc['create_date'], $this->translator);
            $fallbackImage = '';

            switch ($offer['category_id']) {
                case 50:
                    $fallbackImage = 'assets/'.$siteKey.'/offer-result/farm';
                    break;
                case 83:
                    $fallbackImage = 'assets/'.$siteKey.'/offer-result/construction';
                    break;
                case 118:
                    $fallbackImage = 'assets/'.$siteKey.'/offer-result/spare-parts';
                    break;
                case 4014:
                    $fallbackImage = 'assets/'.$siteKey.'/offer-result/material-handling';
                    break;
                case 1:
                default:
                    $fallbackImage = 'assets/'.$siteKey.'/offer-result/transport';
                    break;
            }

            if ($offer['video_url']) {
                $video_id = OfferService::getVideoId($offer['video_url']);
                $offer['fallbackImage'] = '/img.youtube.com/vi/'.$video_id.'/0.jpg';
            } else {
                $offer['fallbackImage'] = "$fallbackImage.svg";
            }

            $offer['emailFallbackImage'] = "$fallbackImage.png";
            $offer['seller']['id'] = $doc['seller_id'];
            $offer['seller']['profileUrl'] = '/'.$locale.'/s/'.$doc['seller_url'].'/';
            $offer['seller']['isPremium'] = $doc['seller']['sellerType'] ?? 0;
            $offer['seller']['companyName'] = $doc['seller']['company_name'] ?? '';

            /* @todo :  remove $doc[seller_whatsapp_facet_int] once all sellers got re-indexed to solr with their whatsapp_enbaled_facet_int */
            $offer['seller']['whatsapp_enabled'] = intval($doc['seller']['whatsapp_enabled_facet_int'] ?? $doc['seller_whatsapp_facet_int'] ?? 0);

            $offer['seller']['phone'] = ! empty($doc['seller_phone_facet_string']) ?
                $doc['seller_phone_facet_string'] : null;
            $offer['seller']['mobile_phone'] = ! empty($doc['seller_mobile_phone_facet_string']) ?
                $doc['seller_mobile_phone_facet_string'] : null;
            $offer['seller']['options'] = ! empty($doc['seller_options_facet_string']) ?
                (array) json_decode($doc['seller_options_facet_string']) : [];
            $offer['steeringWheelSide']
                = ! empty($doc['steering_wheel_side_facet_string']) ? $doc['steering_wheel_side_facet_string'] : null;

            $offer['showSteeringWheel'] = false;
            $rightHandCountries = $this->configuration->getSettingValue('steeringwheel.rightHand');
            $offer['rightHandCountries'] = $rightHandCountries;

            $offer['visited'] = in_array($offer['id'], $visitedOffer) ? true : false;

            $priceAnalysisCategories = $this->configuration->getSettingValue('priceAnalysis.categories');

            $children = end($doc['category']);

            if ($priceRatingAllowed
                && in_array($children, $priceAnalysisCategories)
                && ($offer['price_type'] === Offer::PRICE_TYPE_FIXED)
                && isset($doc[$siteKey.'_price_analysis_type_facet_string'])
            ) {
                $offer['priceAnalysisType'] = $offerService->getPriceAnalysisDetails((int) $doc[$siteKey.'_price_analysis_type_facet_string']);
            }

            $offer['priceOptions'] = $this->getPriceOptionsForSearch(
                $priceOptionsNames,
                isset($doc['price_options_facet_m_int']) ? $doc['price_options_facet_m_int'] : []
            );

            $offers[] = $offer;
        }

        return $offers;
    }

    /**
     * Get analytics data for offers.
     * @param array $offerIds
     *
     * @return array
     */
    private function getOffersAnalyticsData($offerIds)
    {
        $visitedOffer = [];
        if ($this->userId) {
            $analyticsData = $this->entityManager->getRepository('TradusBundle:OfferAnalyticsData')->findBy([
                'type' => 'visit',
                'user_id' => $this->userId,
                'offer_id' => $offerIds,
            ]);

            foreach ($analyticsData as $offerItem) {
                $visitedOffer[] = intval($offerItem->getOfferId());
            }
        }

        return $visitedOffer;
    }

    /*
     * get PriceOptionsForSearch for the Labels
     * @param array $priceOptionsNames
     * @param array $options
     *
     * @return array
     */
    private function getPriceOptionsForSearch(array $priceOptionsNames, ?array $options = null): array
    {
        if ($options == null) {
            return [];
        }

        $result = [];
        foreach ($options as $option) {
            if (in_array($option, AttributeOption::PRICE_OPTIONS_DISPLAY_SEARCH)) {
                $result[] = $priceOptionsNames[$option];
            }
        }

        return $result;
    }
}

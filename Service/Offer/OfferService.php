<?php

namespace TradusBundle\Service\Offer;

use DateTime;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use TradusBundle\Entity\Offer;
use TradusBundle\Factory\ProApiFactory;
use TradusBundle\Service\Config\ConfigServiceInterface;
use TradusBundle\Service\Redis\RedisService;

class OfferService
{
    public const GREAT_PRICE = 'great-price';
    public const GOOD_PRICE = 'good-price';
    public const FAIR_PRICE = 'fair-price';
    public const HIGH_PRICE = 'high-price';
    public const OVERPRICE = 'overprice';
    public const NO_PRICE = 'no-price';

    public const NO_PRICE_LABEL = 'No price rating';

    public const GREAT_PRICE_VALUE = 1;
    public const GOOD_PRICE_VALUE = 2;
    public const FAIR_PRICE_VALUE = 3;
    public const HIGH_PRICE_VALUE = 4;
    public const OVERPRICE_VALUE = 5;
    public const NO_PRICE_VALUE = false;

    public const GREAT_PRICE_COLOR = '#008148';
    public const GOOD_PRICE_COLOR = '#97cc04';
    public const FAIR_PRICE_COLOR = '#0eb1d2';
    public const HIGH_PRICE_COLOR = '#efca08';
    public const OVERPRICE_COLOR = '#fb3640';
    public const NO_PRICE_COLOR = '#c7c7c7';

    /**
     * @return array
     */
    public static function getPriceTypeConsts()
    {
        return [
            self::GREAT_PRICE_VALUE,
            self::GOOD_PRICE_VALUE,
            self::FAIR_PRICE_VALUE,
            self::HIGH_PRICE_VALUE,
            self::OVERPRICE_VALUE,
        ];
    }

    public function getPriceAnalysisDetails($priceAnalysisType)
    {
        global $kernel;
        $priceAnalysisTypeLabels = $kernel->getContainer()->getParameter('sitecode')['pt_labels'];

        switch ($priceAnalysisType) {
            case self::GREAT_PRICE_VALUE:
                $class = self::GREAT_PRICE;
                $color = self::GREAT_PRICE_COLOR;
                break;
            case self::GOOD_PRICE_VALUE:
                $class = self::GOOD_PRICE;
                $color = self::GOOD_PRICE_COLOR;
                break;
            case self::FAIR_PRICE_VALUE:
                $class = self::FAIR_PRICE;
                $color = self::FAIR_PRICE_COLOR;
                break;
            case self::HIGH_PRICE_VALUE:
                $class = self::HIGH_PRICE;
                $color = self::HIGH_PRICE_COLOR;
                break;
            case self::OVERPRICE_VALUE:
                $class = self::OVERPRICE;
                $color = self::OVERPRICE_COLOR;
                break;
            case self::NO_PRICE_VALUE:
            default:
                $priceAnalysisType = false;
                $class = self::NO_PRICE;
                $color = self::NO_PRICE_COLOR;
                break;
        }

        $label = isset($priceAnalysisTypeLabels[$priceAnalysisType]) ? $priceAnalysisTypeLabels[$priceAnalysisType] : self::NO_PRICE_LABEL;

        return [
            'value' => $priceAnalysisType,
            'class' => $class,
            'label' => $label,
            'color' => $color,
        ];
    }

    /**
     * @param int $id
     * @param string $locale
     * @param int|null $userId
     * @param null $fromApi
     * @return mixed
     */
    public function getOfferById(
        $id,
        $locale = null,
        ?int $userId = null,
        $imageVersion = 'original'
    ) {
        global $kernel;
        $container = $kernel->getContainer();
        $locale = $locale ?? $kernel->getContainer()->getParameter(ConfigServiceInterface::DEFAULT_LOCALE_CONFIG);
        $cache = $container->get('cache.app');
        if (is_array($id)) {
            $id = $id['id'];
        }

        $cachedOfferKey = 'pose_v1' ? 'offer_%s_%s_%s' : 'offer_%s_%s';
        $cacheItem = $cache->getItem(sprintf($cachedOfferKey, $locale, $id, $imageVersion));

        if (true || ! Offer::CACHE_OFFER_ITEM || ! $cacheItem->isHit()) {
            try {
                $requestParams = [
                    'locale'  =>  $locale,
                    'image_version' => $imageVersion,
                ];

                if ($userId) {
                    $requestParams['user_id'] = $userId;
                }
                $api = $container->get('tradus.api_factory');
                $offer = $api->get('offer/'.$id.'/id', $requestParams);
            } catch (NotFoundHttpException $exception) {
                // TODO: Work in progress
                throw new NotFoundHttpException('Offer not found: '.$id);
            }
            $cacheItem->set($offer);
            $cacheItem->expiresAfter(Offer::CACHE_OFFER_ITEM);
            $cache->save($cacheItem);
        } else {
            $offer = $cacheItem->get();
        }

        return $offer;
    }

    /**
     * @param int $id
     * @param string $locale
     * @param int|null $userId
     * @param null $fromApi
     * @return mixed
     */
    public function getProOfferById(
        $id,
        $locale = null,
        ?int $userId = null,
        $imageVersion = 'original'
    ) {
        global $kernel;
        $container = $kernel->getContainer();
        $locale = $locale ?? $kernel->getContainer()->getParameter(ConfigServiceInterface::DEFAULT_LOCALE_CONFIG);
        $cache = $container->get('cache.app');
        if (is_array($id)) {
            $id = $id['id'];
        }

        $cachedOfferKey = 'pose_v1' ? 'offerV2_%s_%s_%s' : 'offerV2_%s_%s';
        $cacheItem = $cache->getItem(sprintf($cachedOfferKey, $locale, $id, $imageVersion));

        if (true || ! Offer::CACHE_OFFER_ITEM || ! $cacheItem->isHit()) {
            try {
                $requestParams = [
                    'locale'  =>  $locale,
                    'image_version' => $imageVersion,
                ];
                if ($userId) {
                    $requestParams['user_id'] = $userId;
                }
                /** @var ProApiFactory $api */
                $api = $container->get('pro.api_factory');
                $offer = $api->get("items/{$id}/full", $requestParams);
            } catch (NotFoundHttpException $exception) {
                // TODO: Work in progress
                throw new NotFoundHttpException('Offer not found: '.$id);
            }
            $cacheItem->set($offer);
            $cacheItem->expiresAfter(Offer::CACHE_OFFER_ITEM);
            $cache->save($cacheItem);
        } else {
            $offer = $cacheItem->get();
        }

        return $offer;
    }

    /**
     * Function setLastViewedOffer.
     * @param Request $request
     * @param int $offerId
     * @param int|null $userId
     * @return array|string
     */
    public function setLastViewedOffer(Request $request, int $offerId, ?int $userId = null)
    {
        //Adding this in case we need to remove expired ones when doing the retrieval...
        $limit = Offer::COOKIE_OFFERS_LAST_VIEWED_BY_USER_MAX_STORE + 10;

        $lastViewedArray = [];

        if (! $userId) {
            $cookies = $request->cookies;
            $lastViewed = $cookies->get(Offer::COOKIE_OFFERS_LAST_VIEWED_BY_USER);
        } else {
            $redis = new RedisService(Offer::REDIS_NAMESPACE_LAST_VIEWED, RedisService::EXPIRE_LENGTH * 30);
            $lastViewed = $redis->getParameter($userId);
        }

        if ($lastViewed) {
            $lastViewedArray = explode(',', $lastViewed);
        }

        if (in_array($offerId, $lastViewedArray)) {
            return $lastViewed;
        }

        array_unshift($lastViewedArray, $offerId);

        if (count($lastViewedArray) > $limit) {
            $lastViewedArray = array_slice($lastViewedArray, 0, $limit);
        }
        $lastViewedArray = implode(',', $lastViewedArray);

        if ($userId) {
            //Logged In
            $redis->setParameter($userId, $lastViewedArray);
        }

        return $lastViewedArray;
    }

    public static function getVideoId($videoUrl)
    {
        $videoId = '';
        if ($videoUrl) {
            parse_str(parse_url($videoUrl, PHP_URL_QUERY), $video_query);
            $videoId = isset($video_query['v']) ? $video_query['v'] : '';
            if (empty($videoId)) {
                if (strpos($videoUrl, 'youtu.be') !== false) {
                    preg_match("/(youtu.be\/)\K\S+/", $videoUrl, $video);
                    $videoId = ! empty($video[0]) ? $video[0] : '';
                }
            }
        }

        return $videoId;
    }

    public static function timeElapsedString($datetime, $translator)
    {
        $now = new DateTime();
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);

        $diff->w = floor($diff->d / 7);
        $diff->months = floor($diff->days / 30);
        $diff->d -= $diff->w * 7;

        if ($diff->months > 3) {
            return $translator->trans('more than 3 months ago');
        }

        $string = [
            'y' => 'year',
            'm' => 'month',
            'w' => 'week',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second',
        ];
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k.' '.$v.($diff->$k > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }

        if (empty($string)) {
            return $translator->trans('just now');
        }

        $string = array_slice($string, 0, 1);
        $string = explode(' ', reset($string));
        $duration = current($string);
        $period = $translator->trans(end($string));

        return $translator->trans('@duration @period ago', ['@duration' => $duration, '@period' => $period]);
    }
}

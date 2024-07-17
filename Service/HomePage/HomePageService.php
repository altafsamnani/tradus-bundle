<?php

namespace TradusBundle\Service\HomePage;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use TradusBundle\Entity\Category;
use TradusBundle\Entity\CategoryInterface;
use TradusBundle\Entity\Offer;
use TradusBundle\Entity\OfferVas;
use TradusBundle\Entity\Vas;
use TradusBundle\Service\OfferLatest\OfferLatestService;
use TradusBundle\Service\OfferRelated\OfferRelatedService;
use TradusBundle\Service\Redis\RedisService;
use TradusBundle\Service\Sitecode\SitecodeService;
use TradusBundle\Transformer\OfferTransformer;
use TradusBundle\Utils\CurrencyExchange\CurrencyExchangeException;

class HomePageService
{
    /** @var object ConfigService */
    public $config;

    /** @var EntityManager $entityManager */
    public $entityManager;

    /** @var SearchService $search */
    public $search;

    /** @var SitecodeService $sitecodeService */
    public $sitecodeService;

    public $locale;

    public function __construct(?EntityManager $entityManager = null, $locale = null)
    {
        global $kernel;
        if (! $entityManager) {
            $this->entityManager = $kernel->getContainer()->get('doctrine.orm.tradus_entity_manager');
        } else {
            $this->entityManager = $entityManager;
        }

        $this->search = $kernel->getContainer()->get('tradus.search');
        $this->config = $kernel->getContainer()->get('tradus.config');
        $ssc = new SitecodeService();
        $this->sitecodeService = $ssc;

        $sitecodeId = $ssc->getSitecodeId();

        $sitecodeEntity = $this->entityManager->getRepository('TradusBundle:Sitecodes')->find($sitecodeId);
        if (! $locale) {
            $locale = $sitecodeEntity->getDefaultLocale();
        }
        $this->locale = $locale;
    }

    public function getHomePageAds()
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->entityManager;
        $skipCategories = [CategoryInterface::SPARE_PARTS_ID, CategoryInterface::PROCESSING_EQUIPMENT_ID];

        static $redis = false;

        if (! $redis) {
            $redis = new RedisService(OfferVas::REDIS_NAMESPACE_VAS_HOME);
        }

        $categories = $entityManager->getRepository('TradusBundle:Category')->findBy([
            'parent' => null,
            'status' => Category::STATUS_ONLINE,
        ]);

        $data = [];
        foreach ($categories as $category) {
            if (in_array($category->getId(), $skipCategories)) {
                continue;
            }

            $temp = $redis->getParameter($category->getId());
            if (! $temp) {
                //If not found rebuild it
                $this->buildHomePageVAS();
                $temp = $redis->getParameter($category->getId());
            }

            $temp = json_decode($temp, true);
            $homepageObj = $temp['homepage'];
            $dayObj = $temp['day'];
            $suggestions = $temp['suggestions'];

            if (count($homepageObj) >= Offer::HOMEPAGE_NUMBER_SLOTS) {
                $homepageObj = $this->shuffleDocs($homepageObj, Offer::HOMEPAGE_NUMBER_SLOTS);
            } else {
                $homepageObj = array_merge(
                    $homepageObj,
                    $this->shuffleDocs($suggestions, Offer::HOMEPAGE_NUMBER_SLOTS - count($homepageObj))
                );
            }

            if (count($dayObj) > Offer::DAY_OFFER_NUMBER_SLOTS) {
                $random = mt_rand(0, count($dayObj) - 1);
                $dayObj = $dayObj[$random];
            } else {
                if (count($suggestions) > 0) {
                    $random = mt_rand(0, count($suggestions) - 1);
                    $dayObj = $suggestions[$random];
                    $dayObj['seller']['url'] = $dayObj['seller']['profileUrl'];
                    $dayObj['seller']['company_name'] = $dayObj['seller']['companyName'];
                }
            }

            $data[$category->getId()] = [
                'homepage' => $this->setUrlForLocale($homepageObj, 'homepage'),
                'day'      => $this->setUrlForLocale($dayObj, 'day'),
            ];
        }

        return $data;
    }

    /**
     * @param $offers
     * @return mixed
     */
    public function setUrlForLocale($offers, $type)
    {
        if (count($offers) == 0) {
            return [];
        }
        if ($type == 'day') {
            $offers = [$offers];
        }
        $switch = 'offer_url_'.$this->locale;
        foreach ($offers as $k => $offer) {
            $offers[$k]['url'] = $offer[$switch];
            $offers[$k]['path'] = $offer[$switch];
            if (isset($offers[$k]['full_url'])) {
                $offers[$k]['full_url'] = $offer[$switch];
            }
        }
        if ($type == 'day') {
            return $offers[0];
        }

        return $offers;
    }

    /**
     * Setter for buildHomePageVAS.
     */
    public function buildHomePageVAS()
    {
        $this->sitecodeId = $this->sitecodeService->getSitecodeId();

        //This is only to store in redis the Premium Offers (Suggestions)
        $offerLatestService = new OfferLatestService($this->entityManager);
        $suggestions = $offerLatestService->findLatests($this->locale, true);

        if (count($suggestions) == 0) {
            return false;
        }
        //Find the offers for the home page
        $homepageAds = $this->buildHomePageAds(Vas::HOMEPAGE);

        //Find the offers of the day
        $dayAds = $this->buildHomePageAds(Vas::DAY_OFFER);

        //Store in redis
        static $redis = false;

        if (! $redis) {
            $redis = new RedisService(OfferVas::REDIS_NAMESPACE_VAS_HOME, 90 * 60);
        }

        foreach ($suggestions as $key => $value) {
            $toRedis = [
                'homepage' => isset($homepageAds[$key]) ? $homepageAds[$key] : [],
                'day' => isset($dayAds[$key]) ? $dayAds[$key] : [],
                'suggestions' => $suggestions[$key],
            ];

            $redis->setParameter($key, json_encode($toRedis));
        }

        return true;
    }

    /**
     * @param $type
     * @return array
     * @throws ORMException
     * @throws CurrencyExchangeException
     */
    public function buildHomePageAds($type)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->entityManager;

        $categories = $entityManager->getRepository('TradusBundle:Category')->findBy([
            'parent' => null,
            'status' => Category::STATUS_ONLINE,
        ]);
        $homepageAdsArray = [];

        foreach ($categories as $category) {
            $offerRepo = $entityManager->getRepository('TradusBundle:Offer');
            $homepageAds = $offerRepo->getHomeOffers($category, $type);

            foreach ($homepageAds as $offer) {
                $offerTransformed = (new OfferTransformer($offer, $this->locale, $entityManager, 0))
                    ->transform(false, $this->sitecodeService->getSitecodeKey());

                //Fixing the format from dateTime
                $offerTransformed['created_at'] = $offerTransformed['created_at']->format('Y-m-d H:i:s');
                $homepageAdsArray[$category->getId()][]
                    = $offerTransformed;
            }
        }

        return $homepageAdsArray;
    }

    /**
     * @param $docs
     */
    public function shuffleDocs($docs, int $slots)
    {
        $entityManager = $this->entityManager;
        $randomClass = new OfferRelatedService($entityManager);
        $totalDocs = count($docs);
        $numbers = $randomClass->generateRandomArray($slots, $totalDocs);

        if ($slots > $totalDocs) {
            return [];
        }

        $shuffled = [];
        for ($i = 0; $i < count($numbers); $i++) {
            $shuffled[] = $docs[$numbers[$i]];
        }

        return $shuffled;
    }
}

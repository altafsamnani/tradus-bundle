<?php

namespace TradusBundle\Service\OfferRelated;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use TradusBundle\Entity\Offer;
use TradusBundle\Service\Helper\OfferServiceHelper;
use TradusBundle\Service\Redis\RedisService;
use TradusBundle\Service\Search\SearchService;
use TradusBundle\Service\Wrapper\CURLWrapper;
use TradusBundle\Transformer\OfferSearchTransformer;
use TradusBundle\Utils\CurrencyExchange\CurrencyExchangeException;

class OfferRelatedService
{
    /** @var object ConfigService */
    public $config;

    /** @var EntityManager $entityManager */
    public $entityManager;

    /** @var SearchService $search */
    public $search;

    /** @var int $offerNumber */
    public $offerNumber;

    /** @var string $locale */
    public $locale;

    /** @var int $offerId */
    public $offerId;

    /** @var object $offerViewedByOthers */
    public $offerViewedByOthers;

    /** @var $redis RedisService */
    public $redis;

    /** @var string */
    private $mlURL;

    /** @var string */
    private $mlToken;

    /** @var int */
    private $siteID;

    /**
     * OfferRelatedService constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        global $kernel;
        $this->search = $kernel->getContainer()->get('tradus.search');
        $this->config = $kernel->getContainer()->get('tradus.config');
        $this->mlURL = $kernel->getContainer()->getParameter('olx.ml')['url'];
        $this->mlToken = $kernel->getContainer()->getParameter('olx.ml')['token'];
        $this->siteID = $kernel->getContainer()->getParameter('sitecode')['site_id'];
    }

    /**
     * @param string $variant
     * @param int $userId
     * @param array $offersId
     * @param int $offersRequired
     * @param string $locale
     * @return array
     * @throws CurrencyExchangeException
     */
    public function getMLSuggestions(string $variant, OfferServiceHelper $helper, int $userId, array $offersId, int $offersRequired, string $locale): array
    {
        $url = $this->mlURL.'v1/recsys';

        $offersPayload = [];
        foreach ($offersId as $offerId) {
            //Find
            $offer = $helper->findOfferById($offerId);

            if (! $offer) {
                continue;
            }

            $payloadConstructionYear = null;
            $payloadMileage = null;
            $payloadHoursRun = null;

            $attr = $offer->getAttributes();
            foreach ($attr as $a) {
                if ($a->getAttribute()->getName() == 'construction_year') {
                    $payloadConstructionYear = $a->getContent();
                } elseif ($a->getAttribute()->getName() == 'mileage') {
                    $payloadMileage = $a->getContent();
                } elseif ($a->getAttribute()->getName() == 'hours_run') {
                    $payloadHoursRun = $a->getContent();
                }
            }

            $offersPayload[] = $this->buildOfferPayloadML($offer, $locale, $payloadConstructionYear, $payloadMileage, $payloadHoursRun);
        }

        $body = [
            'sitecode_id' => $this->siteID,
            'user_id' => $userId,
            'requested_recs' => $offersRequired,
            'offers' => $offersPayload,
        ];

        if ($variant !== '') {
            $body['ab_variant_id'] = $variant;
        }

        $headers = [
            'Content-Type' => 'application/json',
            'x-api-key' => $this->mlToken,
        ];

        $curl = new CURLWrapper(
            $url,
            'POST',
            'application/json',
            json_encode($body),
            $headers
        );

        $curl->enableRedirectFollow();
        $response = $curl->exec();

        if (isset($response['status']) && $response['status'] != 200 && $response['status'] != 204) {
            return [
                'error' => 'status',
                'extra' => isset($response['status']) ? $response['status'] : 'no-status',
                'request' => $body,
            ];
        }

        if (! $response['recommended_offers']) {
            return [
                'error' => 'no-offers',
                'extra' => '',
                'request' => $body,
            ];
        }

        return $this->buildMLSuggestions(
            $response['recommended_offers'],
            $body,
            $response['recommender_name'],
            $response['recommendation_id']
        );
    }

    /**
     * @param array $recommendedOffers
     * @param array $body
     * @param string $recommenderName
     * @param $recommendationId
     * @return array
     * @throws CurrencyExchangeException
     */
    public function buildMLSuggestions(array $recommendedOffers, array $body, string $recommenderName, $recommendationId)
    {
        if (count($recommendedOffers) == 0) {
            return [
                'error' => 'no-offers',
                'extra' => $recommendedOffers,
                'request' => $body,
            ];
        }

        $typeAlgorithm = ['also_viewed_algorithm' => 'ml_algorithm'];
        if ($recommenderName != '') {
            $typeAlgorithm = ['also_viewed_algorithm' => $recommenderName];
        }

        $offers = $this->offerIdsToString($recommendedOffers);
        $searchResult = $this->search->findOffersByIds($offers);

        $searchResult['offers'] = (
        new OfferSearchTransformer(
            $searchResult,
            $this->locale,
            -1,
            $this->entityManager
        ))->transform(); //Used -1 for category no need for it

        if (empty($searchResult['offer_ids'])) {
            return [
                'error' => 'no-offers-solr',
                'extra' => $recommendedOffers,
                'request' => $body,
            ];
        }

        $searchResult = array_merge($searchResult, ['recommendation_id' => $recommendationId, 'recommender_name' => $recommenderName]);

        //Reorder the recomendations like we received them, since solr is doing reorder
        $offerIds = [];
        $offerDocs = [];
        $i = 0;
        foreach ($recommendedOffers as $offer) {
            if (in_array($offer, $searchResult['offer_ids'])) {
                $offerIds[$i] = $offer;
                $indexInResponse = array_search($offer, array_column($searchResult['response']['docs'], 'offer_id'));
                $offerDocs[$i++] = $searchResult['response']['docs'][$indexInResponse];
            }
        }

        $searchResult['offer_ids'] = $offerIds;
        $searchResult['response']['docs'] = $offerDocs;

        return array_merge(
            $typeAlgorithm,
            $searchResult
        );
    }

    /**
     * @param $related
     * @return array
     */
    public function totalOfferRecords(array $related)
    {
        $this->redis = $this->redis ?? new RedisService('SimilarOffers:', 3600);
        $typeAlgorithm = ['also_viewed_algorithm' => 'default'];
        if (count($related['offers']) > $this->offerNumber) {
            $related['result_count'] = $this->offerNumber;
            if (! empty($related['offer_ids'])) {
                $related['offer_ids'] = array_slice($related['offer_ids'], 0, $this->offerNumber);
            }
            $related['offers'] = array_slice($related['offers'], 0, $this->offerNumber);
            $related['response']['docs'] = array_slice(
                $related['response']['docs'],
                0,
                $this->offerNumber
            );
        }
        $this->redis->setParameter(
            $this->offerId.':'.$this->locale.':'.$this->offerNumber,
            gzcompress(serialize($related))
        );
        $related = array_merge($typeAlgorithm, $related);

        return $related;
    }

    /**
     * @param array $offersResult
     * @return array
     */
    private function offerIdsToString(array $offersResult)
    {
        $offers = json_encode($offersResult);
        $offers = preg_replace('/[^0-9,]/', '', $offers); //No need to sanitize here

        return explode(',', $offers);
    }

    /**
     * @param int $count
     * @param int $max
     * @return array
     */
    public function generateRandomArray(int $count, int $max): array
    {
        if ($count <= 0 || $max <= 0) {
            return [];
        }

        $numbers = [];
        if (($count / $max) >= 0.8) {
            for ($i = 0; $i < $count; $i++) {
                $numbers[] = $i;
            }

            return $numbers;
        }
        $done = false;
        while (! $done) {
            $number = mt_rand(0, $max - 1);
            if (! in_array($number, $numbers)) {
                array_push($numbers, $number);
            }
            if (count($numbers) === $count) {
                $done = true;
            }
        }

        return $numbers;
    }

    /**
     * @param Offer $offer
     * @param string $locale
     * @param $year
     * @param $mileage
     * @param $hoursRun
     * @return array
     */
    private function buildOfferPayloadML(Offer $offer, string $locale, $year = null, $mileage = null, $hoursRun = null):array
    {
        $payload = [
            'offer_id' => $offer->getId(),
            'attributes' => [
                'title' => $offer->getTitleByLocale($locale),
                'category_id' => $offer->getCategory()->getId(),
                'country_id' => $offer->getSeller()->getCountry(),
                'make_id' => $offer->getMake()->getId(),
                'price' => $offer->getPrice(),
        ], ];

        if ($year) {
            $payload['attributes']['construction_year'] = (int) $year;
        }

        if ($mileage) {
            $payload['attributes']['mileage'] = (int) $mileage;
        }

        if ($hoursRun) {
            $payload['attributes']['hours_run'] = (int) $hoursRun;
        }

        return $payload;
    }
}

<?php

namespace TradusBundle\Service\Utils;

use TradusBundle\Entity\Offer;
use TradusBundle\Service\Countries\CountriesService;
use TradusBundle\Service\Offer\OfferService;
use TradusBundle\Service\Redis\RedisService;

class LastViewedService
{
    /**
     * @param int $userId
     * @param string $locale
     * @param bool $fromApi
     * @return array
     */
    public function getLastViewed(int $userId, string $locale, bool $fromApi = false)
    {
        $offerService = new OfferService();

        $results = [];
        $redis = new RedisService(Offer::REDIS_NAMESPACE_LAST_VIEWED);
        $lastViewedRedis = $redis->getParameter($userId);
        $redis = null;
        if ($lastViewedRedis) {
            $lastViewedRedisArray = explode(',', $lastViewedRedis);

            if (! $fromApi) {
                foreach ($lastViewedRedisArray as $offerId) {
                    $offer = $offerService->getOfferById($offerId, $locale, $userId);
                    $default = $offer['seller']['city'].', '.$offer['seller']['country'];
                    /**
                     * The Intl bundle returns the country name without the need for a new service
                     * Intl::getRegionBundle()->getCountryName($offer['seller']['country']);.
                     */
                    $countries = new CountriesService();
                    $country = $countries->getCountry($offer['seller']['country']);

                    if (! $country) {
                        $country = $default;
                    }

                    $offer['country'] = $country;
                    if ($offer) {
                        array_push($results, $offer);
                    }
                }
            } else {
                $results = $lastViewedRedisArray;
            }

            /*
            $requiredOffers = Offer::OTHER_OFFERS_LAST_VIEWED_NUMBER_SLOTS - count($results);

            if ($requiredOffers > 0) {
                $lastSuggestionsViewed = $this->getTopViewed();
                if ($lastSuggestionsViewed) {
                    $lastSuggestionsViewedArray = explode(',', $lastSuggestionsViewed);
                    $randArray = $this->generateRandomArray($requiredOffers, count($lastSuggestionsViewedArray));

                    foreach ($randArray as $index) {
                        $offerId = $lastSuggestionsViewedArray[$index];
                        $offer = $this->getOfferById($offerId, $locale);
                        $default = $offer['seller']['city'].', '.$offer['seller']['country'];
                        $countries = new CountriesService();
                        $country = $countries->getCountry($offer['seller']['country']);

                        if (!$country) {
                            $country = $default;
                        }

                        $offer['country'] = $country;
                        if ($offer && $requiredOffers > 0) {
                            $requiredOffers--;
                            array_push($results, $offer);
                        }
                    }
                }
            }*/
        }

        return $results;
    }
}

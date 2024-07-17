<?php

namespace TradusBundle\Utils\Google\Map;

use TradusBundle\Utils\Google\AbstractApi;

/**
 * Class MapApi.
 */
class MapApi extends AbstractApi implements MapApiInterface
{
    /**
     * GoogleTranslationApiClient constructor.
     */
    public function __construct()
    {
        global $kernel;

        $this->apiKey = $kernel->getContainer()->getParameter(static::API_KEY_OFFSET);
    }

    /**
     * Function for get Longitude and Latitude from Address.
     *
     * @param string $address
     *
     * @return array
     */
    public function getGeoLocation(string $address)
    {
        $parsedRequest = $this->executeRequest([
            self::API_ADDRESS_PARAMETER => $address,
        ]);

        return array_merge(
            ['status' => $parsedRequest['status'] ?? ''],
            $parsedRequest['results'][0]['geometry']['location'] ?? []
        );
    }
}

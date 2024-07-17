<?php

namespace TradusBundle\Utils\Google\Map;

/**
 * Interface MapApiInterface.
 */
interface MapApiInterface
{
    const API_BASE_PATH = 'https://maps.googleapis.com/maps/api/geocode/json';
    const API_KEY_OFFSET = 'google.maps.apiKey';

    // The api parameters.
    const API_KEY_PARAMETER = 'key';
    const API_ADDRESS_PARAMETER = 'address';
    const API_PARAMETERS = [
        self::API_KEY_PARAMETER,
        self::API_ADDRESS_PARAMETER,
    ];

    const STATUS_OK = 'OK';
}

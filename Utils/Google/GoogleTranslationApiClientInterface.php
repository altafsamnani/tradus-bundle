<?php

namespace TradusBundle\Utils\Google;

/**
 * Interface GoogleTranslationApiClientInterface.
 */
interface GoogleTranslationApiClientInterface
{
    const API_BASE_PATH = 'https://translation.googleapis.com/language/translate/v2';
    const API_DETECTION_PATH = '/detect';
    const API_KEY_OFFSET = 'google.translate.apiKey';
    const GOOGLE_TRANSLATE_MAX_CHARS = 5000;

    // Detection example: ?key=somekey&q=es%20gibt%20kein%20bier%20auf%20hawaii.
    // Translation example: ?key=somekey&source=bg&target=ro&q=dragon

    // The api parameters.
    const API_KEY_PARAMETER = 'key';
    const API_QUERY_PARAMETER = 'q';
    const API_SOURCE_PARAMETER = 'source';
    const API_TARGET_PARAMETER = 'target';
    const API_FORMAT_PARAMETER = 'format';
    const API_FORMAT_TEXT = 'text';
    const API_FORMAT_HTML = 'html';
    const API_PARAMETERS = [
        self::API_KEY_PARAMETER,
        self::API_QUERY_PARAMETER,
        self::API_SOURCE_PARAMETER,
        self::API_TARGET_PARAMETER,
        self::API_FORMAT_PARAMETER,
    ];
}

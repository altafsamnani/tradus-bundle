<?php

namespace TradusBundle\Utils\Aws;

/**
 * Interface AwsTranslationApiClientInterface.
 */
interface AwsTranslationApiClientInterface
{
    const SUPPORTED_LANGUAGES = [
        'ar',
        'zh',
        'zh-TW',
        'cs',
        'da',
        'nl',
        'en',
        'fi',
        'fr',
        'de',
        'he',
        'id',
        'it',
        'ja',
        'ko',
        'pl',
        'pt',
        'ru',
        'es',
        'sv',
        'tr',
    ];
    const API_KEY_REGION = 'aws.region';
    const API_KEY_VERSION = 'aws.version';
}

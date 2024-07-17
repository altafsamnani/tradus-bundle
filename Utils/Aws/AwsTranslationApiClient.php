<?php

namespace TradusBundle\Utils\Aws;

use Aws\Comprehend\ComprehendClient;
use Aws\Translate\TranslateClient;

/**
 * Class AwsTranslationApiClient.
 */
class AwsTranslationApiClient implements AwsTranslationApiClientInterface
{
    /**
     * @var bool
     */
    private $isApp;

    /**
     * Comprehend client.
     *
     * @var ComprehendClient
     */
    protected $client;

    /*
     * Aws Credential Options
     *
     * @var Array
     */
    private $options = [];

    /**
     * GoogleTranslationApiClient constructor.
     *
     * @param bool $isApp
     */
    public function __construct($isApp = false)
    {
        //$this->init();
        $this->isApp = $isApp;
        $this->init();
    }

    private function init()
    {
        global $kernel;
        $region = $kernel->getContainer()->getParameter(static::API_KEY_REGION);
        $version = $kernel->getContainer()->getParameter(static::API_KEY_VERSION);

        $region ? $this->options['region'] = $region : '';
        $version ? $this->options['version'] = $version : '';
    }

    /**
     * Function to detect the language.
     *
     * @param string $text
     *
     * @return string|false
     */
    public function detectLanguage(string $text)
    {
        $client = new ComprehendClient($this->options);
        $result = $client->detectDominantLanguage(['Text' => $text]);
        if ($result->hasKey('Languages')) {
            foreach ($result->get('Languages') as $languages) {
                $scoreLanguages[$languages['LanguageCode']] = $languages['Score'];
            }
            arsort($scoreLanguages);

            return $detectedLanguage = key($scoreLanguages);
        }

        return false;
    }

    /**
     * Function to get the translation.
     *
     * @param string $text
     * @param string $fromLanguage
     * @param string $toLanguage
     *
     * @return string|false
     */
    public function getTranslation(string $text, string $fromLanguage, string $toLanguage)
    {
        $client = new TranslateClient($this->options);
        $result = $client->translateText([
            'SourceLanguageCode' => $fromLanguage,
            'TargetLanguageCode' => $toLanguage,
            'Text' => $text,
        ]);

        return $result->hasKey('TranslatedText') ? $result->get('TranslatedText') : false;
    }
}

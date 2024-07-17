<?php

namespace TradusBundle\Utils\Google;

/**
 * Class GoogleTranslationApiClient.
 */
class GoogleTranslationApiClient extends AbstractApi implements GoogleTranslationApiClientInterface
{
    /**
     * @var bool
     */
    private $isApp;

    /**
     * GoogleTranslationApiClient constructor.
     *
     * @param bool $isApp
     */
    public function __construct($isApp = false)
    {
        $this->init();
        $this->isApp = $isApp;
    }

    /**
     * Initialization function to set credentials.
     *
     * @return void
     */
    private function init()
    {
        if (empty(getenv('GOOGLE_TRANSLATION_API_KEY'))) {
            global $kernel;
            $this->apiKey = $kernel->getContainer()->getParameter(static::API_KEY_OFFSET);
        } else {
            $this->apiKey = getenv('GOOGLE_TRANSLATION_API_KEY');
        }
    }

    /**
     * Function to detect the language of a given text.
     *
     * @param string $text
     *
     * @return bool|string
     * @throws InvalidParameterException
     */
    public function detectLanguage(string $text)
    {
        $url = self::API_BASE_PATH.self::API_DETECTION_PATH;
        $parameters = [self::API_QUERY_PARAMETER => $text];
        $result = $this->executeRequest($parameters, $url);

        // If we have an api response the first item will be the correct language.
        if (isset($result['data']['detections'][0][0]['language'])) {
            $langcode = $result['data']['detections'][0][0]['language'];

            return $langcode;
        }

        return false;
    }

    /**
     * Function to get a translation from a given text into another language.
     *
     * @param string $text
     * @param string $fromLangauge
     * @param string $toLangauge
     *
     * @return bool|string
     * @throws InvalidParameterException
     */
    public function getTranslation(string $text, string $fromLangauge, string $toLangauge)
    {
        $parameters = [
            self::API_SOURCE_PARAMETER => $fromLangauge,
            self::API_TARGET_PARAMETER => $toLangauge,
            self::API_QUERY_PARAMETER => $text,
            self::API_FORMAT_PARAMETER => $this->isApp ? self::API_FORMAT_TEXT : self::API_FORMAT_HTML,
        ];
        $result = $this->executeRequest($parameters);
        if (isset($result['data']['translations'][0]['translatedText'])) {
            $translation = $result['data']['translations'][0]['translatedText'];

            return $translation;
        }

        return false;
    }
}

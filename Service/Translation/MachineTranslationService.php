<?php

namespace TradusBundle\Service\Translation;

use Aws\Exception\AwsException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use TradusBundle\Utils\Aws\AwsTranslationApiClient;
use TradusBundle\Utils\Google\GoogleTranslationApiClient;
use TradusBundle\Utils\Google\InvalidParameterException;
use TradusBundle\Utils\MysqlHelper\MysqlHelper;

/**
 * Class MachineTranslationService.
 */
class MachineTranslationService
{
    const TRANSLATE_MAX_CHARS = 5000;

    /**
     * @var \PDO
     */
    private $connection;

    /**
     * @var bool
     */
    private $isApp;

    /*
     * @var AwsTranslationApiClient
     */
    private $awsClient;

    /*
     * @var GoogleTranslationApiClient
     */
    private $googleClient;

    /*
     * @var LoggerInterface
     */
    private $logger;

    /**
     * MachineTranslationService constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface $logger
     */
    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        // Get a PDO connection for mysql queries.
        $mysql_helper = new MysqlHelper($entityManager->getConnection());
        $this->connection = $mysql_helper->getConnection();
        $this->logger = $logger;
    }

    /**
     * Get the translation from the string.
     *
     * @param string $text
     * @param string $toLanguage
     * @param bool $skipSameLanguage
     * @param bool $isApp
     *
     * @return string|false
     * @throws InvalidParameterException|AwsException
     */
    public function getTranslation(
        string $text,
        string $toLanguage,
        $skipSameLanguage = true,
        $isApp = false
    ) {
        //1. Check if translation exists.
        $translation = $this->lookupTranslation($text, $toLanguage);
        if ($translation) {
            return $translation;
        }
        $this->isApp = $isApp;

        $origText = $text;
        if (strlen($text) > self::TRANSLATE_MAX_CHARS) {
            $text = substr($text, 0, self::TRANSLATE_MAX_CHARS).'....';
        }

        //2. Detect the langauge from AWS
        $fromLangauge = $this->detectLanguage($text);

        // Skip translations of the same language.
        if ($fromLangauge == $toLanguage) {
            return $skipSameLanguage ? $origText : false;
        }

        //3. If AWS supported langauge then translate
        if (in_array($fromLangauge, AwsTranslationApiClient::SUPPORTED_LANGUAGES) &&
            in_array($toLanguage, AwsTranslationApiClient::SUPPORTED_LANGUAGES)
        ) {
            try {
                return $this->getAwsTranslation($text, $fromLangauge, $toLanguage);
            } catch (AwsException $awsException) {
                $this->logger->error($awsException->getMessage());
            }
        }

        //4. Google translate If AWS not supported language or it fails
        try {
            return $this->getGoogleTranslation($text, $fromLangauge, $toLanguage);
        } catch (InvalidParameterException $invalidParameterException) {
            $this->logger->error($invalidParameterException->getMessage());
        }

        return false;
    }

    /**
     * Function for obtaining the language of a given text.
     *
     * @param string $text
     *
     * @return string|false
     * @throws InvalidParameterException|AwsException
     */
    private function detectLanguage(string $text)
    {
        // Check if we can find the entry in our lookup table.
        if ($langCode = $this->lookupEntry($text)) {
            return $langCode;
        }

        try {
            if ($langCode = $this->detectLanguageFromAws($text, $this->isApp)) {
                $this->createLookupEntry($text, $langCode);

                return $langCode;
            }
        } catch (AwsException $exception) {
            $this->logger->error($exception->getMessage());
        }

        if ($langCode = $this->detectLanguageFromGoogle($text, $this->isApp)) {
            $this->createLookupEntry($text, $langCode);

            return $langCode;
        }

        return false;
    }

    /**
     * Function to detect the language from AWS.
     *
     * @param string $text
     *
     * @return string|false
     * @throws InvalidParameterException
     */
    public function detectLanguageFromGoogle(string $text)
    {
        $this->googleClient = new GoogleTranslationApiClient($this->isApp);

        return $this->googleClient->detectLanguage($text);
    }

    /**
     * Function to detect the language from AWS.
     *
     * @param string $text
     *
     * @return string|false
     * @throws InvalidParameterException
     */
    public function detectLanguageFromAws(string $text)
    {
        $this->awsClient = new AwsTranslationApiClient($this->isApp);

        return $this->awsClient->detectLanguage($text);
    }

    /**
     * @param string $string
     * @param string $fromLanguage
     * @param string $toLangauge
     *
     * @return string
     * @throws AwsException
     */
    public function getAwsTranslation(
        string $text,
        string $fromLanguage,
        string $toLangauge
    ) {
        if ($this->awsClient === null) {
            $this->awsClient = new AwsTranslationApiClient($this->isApp);
        }

        $translation = $this->awsClient->getTranslation($text, $fromLanguage, $toLangauge);
        $this->createTranslation($text, $translation, $toLangauge, 2);

        $this->logger->info('original: '.$text, ['aws', 'original', $fromLanguage]);
        $this->logger->info('translation: '.$translation, ['aws', 'translation', $toLangauge]);

        return $translation;
    }

    /**
     * @param string $string
     * @param string $fromLanguage
     * @param string $toLangauge
     *
     * @return string
     * @throws InvalidParameterException
     */
    public function getGoogleTranslation(
        string $text,
        string $fromLanguage,
        string $toLangauge
    ) {
        if ($this->googleClient === null) {
            $this->googleClient = new GoogleTranslationApiClient($this->isApp);
        }

        $translation = $this->googleClient->getTranslation($text, $fromLanguage, $toLangauge);
        $this->createTranslation($text, $translation, $toLangauge);
        $this->logger->info('original: '.$text, ['google', 'original', $fromLanguage]);
        $this->logger->info('translation: '.$translation, ['google', 'translation', $toLangauge]);

        return $translation;
    }

    /**
     * Function for finding a translation in the database.
     *
     * @param string $text
     * @param string $translation_locale
     * @return bool|string
     */
    private function lookupTranslation(string $text, string $translation_locale)
    {
        $hash = $this->getHash($text);
        $query = '
            SELECT t.translation
            FROM translation_source l
            LEFT JOIN translations t ON l.id = t.translation_source_id
            WHERE l.hash = :hash AND t.langcode = :langcode
            LIMIT 0, 1
        ';
        $statement = $this->connection->prepare($query);
        $statement->execute([
            ':hash' => $hash,
            ':langcode' => $translation_locale,
        ]);
        $result = $statement->fetch();

        return ! empty($result[0]) ? $result[0] : false;
    }

    /**
     * Function for inserting a translation for a given text.
     *
     * @param string $original_text
     * @param string $translation_text
     * @param string $translation_locale
     * @param int $translator
     */
    private function createTranslation(
        string $original_text,
        string $translation_text,
        string $translation_locale,
        int $translator = 1
    ) {
        [, $lookup_id] = $this->lookupEntry($original_text, true);
        if ($lookup_id) {
            $query = '
          INSERT INTO translations (`translation_source_id`, `langcode`, `translation`, `translator`)
          VALUES (:translation_source_id, :langcode, :translation, :translator)
        ';
            $statement = $this->connection->prepare($query);
            $statement->execute([
                ':translation_source_id' => $lookup_id,
                ':langcode' => $translation_locale,
                ':translation' => $translation_text,
                ':translator' => $translator,
            ]);
        }
    }

    /**
     * Function for creating a lookup entry.
     *
     * @param string $text
     * @param string $locale
     */
    private function createLookupEntry(string $text, string $locale)
    {
        if ($text) {
            $hash = $this->getHash($text);
            $query = 'INSERT INTO translation_source (`langcode`, `hash`) VALUES (:locale, :hash)';
            $statement = $this->connection->prepare($query);
            $statement->execute([':locale' => $locale, ':hash' => $hash]);
        }
    }

    /**
     * Function for getting the locale by string.
     *
     * @param string $text
     * @param bool $return_id
     *   Whether both the id and the langcode are required.
     * @return mixed
     */
    private function lookupEntry(string $text, bool $return_id = false)
    {
        $hash = $this->getHash($text);
        $query = 'SELECT `langcode`, `id` FROM translation_source WHERE `hash` = :hash';
        $statement = $this->connection->prepare($query);
        $statement->execute([':hash' => $hash]);
        $result = $statement->fetch();
        if (! empty($result[0])) {
            return $return_id ? [$result[0], $result[1]] : $result[0];
        }

        return false;
    }

    /**
     * Helper function for getting the hash.
     *
     * @param string $text
     * @return string
     */
    private function getHash(string $text)
    {
        return hash('sha256', $text);
    }
}

<?php

namespace TradusBundle\Service\Translation;

use Symfony\Component\Translation\TranslatorInterface;
use TradusBundle\Entity\Translation;

/**
 * Class TranslationService.
 */
class TranslationService
{
    public const CONFIG_NAME = 'translation';
    public const CONFIG_PARAMS = 'parameters';
    public const LOCALES = 'locales';
    public const LOCALE = 'locale';
    public const TOP_LOCALES = 'top_locales';
    public const APP_LOCALES = 'app.locales';
    public const ALTERNATE_LOCALES = 'alternate_locales';
    public const DEFAULT_LOCALE = 'default_locale';

    /** @var array */
    protected $translationConfig;

    /** @var array */
    protected $paramsConfig;

    /** @var TranslatorInterface */
    protected $translator;

    protected $container;

    /**
     * TranslationService constructor.
     * @param TranslatorInterface $translator
     */
    public function __construct(?TranslatorInterface $translator = null)
    {
        global $kernel;

        if ($translator) {
            $this->translator = $translator;
        } else {
            //Find the translator interface
            $this->translator = new TranslatorInterface();
        }

        $this->container = $kernel->getContainer();
    }

    /**
     * @return array
     */
    public function getAllowedLanguages()
    {
        return explode('|', $this->container->getParameter(self::APP_LOCALES));
    }

    /*
     * @return array
     */
    public function getAlternateLocales()
    {
        return $this->container->getParameter(self::ALTERNATE_LOCALES);
    }

    /**
     * @return array
     */
    public function getTopLanguages()
    {
        return $this->container->getParameter(self::TOP_LOCALES);
    }

    /**
     * @return string
     */
    public function getDefaultLanguage()
    {
        return $this->container->getParameter(self::LOCALE);
    }

    /**
     * @param $locale
     * @return bool
     */
    public function isLocaleSupported(string $locale)
    {
        return in_array($locale, $this->getAllowedLanguages());
    }

    /**
     * Function to replicate the Drupal trans behaviour.
     *
     * @param string $string
     *   The string that needs to be translated.
     * @param array $parameters
     *   The parameters that need to be passed on to the translator.
     *
     * @return string
     *   Returns the translated string.
     */
    public function trans(string $string, array $parameters = [], $fallback = true)
    {
        // Xliff supports no html in a translation but we have it in our
        // translations.
        // TODO @link http://docs.oasis-open.org/xliff/xliff-core/v2.0/os/xliff-core-v2.0-os.html.
        if ($string !== strip_tags($string)) {
            $text = urldecode($this->translator->trans(urlencode($string), $parameters));
            // Fallback to default language when target in file is empty
            if (! $text && $fallback == true) {
                $text = urldecode($this->translator->trans(urlencode($string), $parameters, null, $this->getDefaultLanguage()));
            }
        } else {
            $string = preg_replace("/[\r\n]+/", ' ', $string);

            $text = $this->translator->trans($string, $parameters);
            // Fallback to default language when target in file is empty
            if (! $text && $fallback == true) {
                $text = $this->translator->trans($string, $parameters, null, $this->getDefaultLanguage());
            }
        }

        // Return empty for untranslated placeholder strings.
        if ($this->isUntranslatedSeoDescriptionPlaceholder($text)) {
            return '';
        }

        return $text;
    }

    /**
     * Get Phraseapp Keys by parsing XLIFF as we dont use KEYS.
     *
     * @param string $string
     *   The string that needs to be translated.
     * @param array $loadResource
     *   Loaded Xliff resource
     * @param string $moduleKey
     *
     * @return string
     *   Returns the translated string.
     */
    public function transPhraseApp(string $string, $loadResource, $moduleKey)
    {
        $phraseKey = $loadResource->getMetadata($string)['id'];
        $prefix = '{{__phrase_';
        $suffix = '__}}';
        if (! $phraseKey) {
            $pieces = explode(' ', $string);
            $phraseKey = $moduleKey.'.'.strtolower(implode('_', array_splice($pieces, 0, 4)));
        }

        // Return ID of translation key with pre- and suffix for PhraseApp
        return $prefix.$phraseKey.$suffix;
    }

    /**
     * Helper method to trace untranslated placeholders which we should not display.
     *
     * @param $string
     * @return bool
     */
    public function isUntranslatedSeoDescriptionPlaceholder(string $string)
    {
        /** Matches the placeholder <span>make-test</span> pr <span>category-1</span> items*/
        $regex = '/^<span>((make|category)\-.+)<\/span>$/';

        return preg_match($regex, $string) === 1;
    }

    /**
     * Replaces placeholders in text with your variables.
     * @param string $text
     * @param array $variables
     * @return mixed|string
     */
    public function replacePlaceHolders(String $text, array $variables)
    {
        foreach ($variables as $variableName => $variableValue) {
            $text = str_replace($variableName, $variableValue, $text);
        }

        return $text;
    }

    /**
     * Set locale for the translation service.
     */
    public function setLocale($locale)
    {
        $this->translator->setLocale($locale);
    }
}

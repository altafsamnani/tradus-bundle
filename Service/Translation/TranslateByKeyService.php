<?php

namespace TradusBundle\Service\Translation;

use Symfony\Component\HttpFoundation\Request;

class TranslateByKeyService
{
    /* @var String */
    private $defaultLocale;

    /* @var String */
    private $locale;

    /* @var array */
    private $localesAvailable;

    public function __construct(?String $locale = null)
    {
        global $kernel;

        $this->defaultLocale = $kernel
            ->getContainer()
            ->getParameter('locale');

        $this->localesAvailable = explode('|', $kernel
            ->getContainer()
            ->getParameter('app.locales'));

        if (! $locale) {
            $request = Request::createFromGlobals();
            $locale = ($request->get('locale')) ? $request->get('locale') :
                $locale = $this->defaultLocale;
        }

        if (! in_array($locale, $this->localesAvailable)) {
            $locale = $this->defaultLocale;
        }

        $this->locale = $locale;
    }

    /**
     * @param string $key
     * @return string
     */
    public function translateByKey(String $key): String
    {
        global $kernel;
        $dataPath = $kernel->getTranslationsDir().'/messages.'.$this->locale.'.xlf';

        if (! file_exists($dataPath)) {
            return $key;
        }

        $xmlData = simplexml_load_file($dataPath);
        $translationObj = $xmlData->xpath('//*[@id = "'.$key.'"]');

        if (count($translationObj) == 0) {
            if ($this->locale !== $this->defaultLocale) {
                $this->locale = $this->defaultLocale;

                return $this->translateByKey($key);
            }

            return $key;
        }

        return (string) $translationObj[0]->target;
    }

    /**
     * @return mixed
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param mixed $locale
     */
    public function setLocale($locale)
    {
        if (! in_array($locale, $this->localesAvailable)) {
            $locale = $this->defaultLocale;
        }

        $this->locale = $locale;
    }

    /**
     * @return array
     */
    public function getLocalesAvailable()
    {
        return $this->localesAvailable;
    }

    /**
     * @param array $localesAvailable
     */
    public function setLocalesAvailable(array $localesAvailable)
    {
        $this->localesAvailable = $localesAvailable;
    }

    /**
     * @return string
     */
    public function getDefaultLocale()
    {
        return $this->defaultLocale;
    }

    /**
     * @param string $defaultLocale
     */
    public function setDefaultLocale($defaultLocale)
    {
        $this->defaultLocale = $defaultLocale;
    }
}

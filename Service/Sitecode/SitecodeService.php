<?php

namespace TradusBundle\Service\Sitecode;

use Symfony\Component\Yaml\Yaml;
use TradusBundle\Entity\Sitecodes;

class SitecodeService
{
    private $sitecodeId;
    private $sitecodeKey;

    /** @var string $sitecodeTitle */
    private $sitecodeTitle;

    /** @var string $sitecodeDomain */
    private $sitecodeDomain;

    /** @var string $defaultLocale */
    private $defaultLocale;

    /** @var array $supportedLocales */
    private $supportedLocales;

    /** @var string $sitecodeDomainDev */
    private $sitecodeDomainDev;

    /** @var string $sitecodeMetaTags */
    private $sitecodeMetaTags = [];

    public function __construct($apiEnvironment = null)
    {
        $sitecodeConfig = [
            Sitecodes::SITECODE_FIELD_ID_CONFIG => Sitecodes::SITECODE_TRADUS,
            Sitecodes::SITECODE_FIELD_KEY_CONFIG => Sitecodes::SITECODE_KEY_TRADUS,
            Sitecodes::SITECODE_FIELD_TITLE_CONFIG => Sitecodes::SITECODE_TITLE_TRADUS,
            Sitecodes::SITECODE_FIELD_DOMAIN_CONFIG => Sitecodes::SITECODE_DOMAIN_TRADUS,
            Sitecodes::SITECODE_FIELD_DOMAIN_DEV_CONFIG => Sitecodes::SITECODE_DOMAIN_DEV_TRADUS,
            Sitecodes::LOCALE_CONFIG => Sitecodes::SITECODE_LOCALE_TRADUS,
        ];

        $this->setApiSitecodeParameters($sitecodeConfig, $apiEnvironment);
    }

    /**
     * @param array $sitecodeConfig siteconde config list
     * @param bool  $apiEnvironment if call is from api then set it
     *
     * @return void
     */
    private function setApiSitecodeParameters($sitecodeConfig, $apiEnvironment): void
    {
        global $kernel;
        $container = $kernel->getContainer();
        if ($apiEnvironment) {
            $config = Yaml::parse(
                file_get_contents($kernel->getProjectDir().'/src/TradusBundle/config/config_'.$apiEnvironment.'.yml')
            );
        } elseif ($container->getParameter(Sitecodes::SITECODE_FIELD_CONFIG)) {
            $config = $container;
        }

        if (isset($config)) {
            $sitecodeConfig = $this->getConfigParameter($config, Sitecodes::SITECODE_FIELD_CONFIG);
            $sitecodeConfig[Sitecodes::LOCALE_CONFIG] = $this->getConfigParameter($config, Sitecodes::LOCALE_CONFIG);
            $sitecodeConfig[Sitecodes::SUPPORTED_LOCALES] = $this->getConfigParameter($config, Sitecodes::SUPPORTED_LOCALES);
        }
        $this->setSitecodeValues($sitecodeConfig);
    }

    /**
     * @param array $sitecodeConfig siteconde config list
     *
     * @return void
     */
    private function setSitecodeValues($sitecodeConfig): void
    {
        $this->setSitecodeId($sitecodeConfig[Sitecodes::SITECODE_FIELD_ID_CONFIG]);
        $this->setSitecodeKey($sitecodeConfig[Sitecodes::SITECODE_FIELD_KEY_CONFIG]);
        $this->setSitecodeTitle($sitecodeConfig[Sitecodes::SITECODE_FIELD_TITLE_CONFIG]);
        $this->setSitecodeDomain($sitecodeConfig[Sitecodes::SITECODE_FIELD_DOMAIN_CONFIG]);
        $this->setSitecodeDomainDev($sitecodeConfig[Sitecodes::SITECODE_FIELD_DOMAIN_DEV_CONFIG]);
        $this->setDefaultLocale($sitecodeConfig[Sitecodes::LOCALE_CONFIG]);

        if (isset($sitecodeConfig[Sitecodes::SUPPORTED_LOCALES])) {
            $this->setSupportedLocales($sitecodeConfig[Sitecodes::SUPPORTED_LOCALES]);
        }

        if (isset($sitecodeConfig[Sitecodes::SITECODE_FIELD_HEAD_META])) {
            $this->setSitecodeMeta($sitecodeConfig[Sitecodes::SITECODE_FIELD_HEAD_META]);
        }
    }

    /**
     * @param        $config
     * @param string $parameter
     *
     * @return mixed
     */
    public function getConfigParameter($config, $parameter)
    {
        if (empty($config)) {
            global $kernel;
            $config = $kernel->getContainer();
        }

        if (is_array($config)) {
            return $config['parameters'][$parameter];
        } else {
            return $config->getParameter($parameter);
        }
    }

    /**
     * @return mixed
     */
    public function getSitecodeId()
    {
        return $this->sitecodeId;
    }

    /**
     * @param mixed $sitecodeId
     */
    public function setSitecodeId($sitecodeId): void
    {
        $this->sitecodeId = $sitecodeId;
    }

    /**
     * @return mixed
     */
    public function getSitecodeMeta()
    {
        return $this->sitecodeMetaTags;
    }

    /**
     * @param mixed $sitecodeMetaTags
     */
    public function setSitecodeMeta($sitecodeMetaTags): void
    {
        $this->sitecodeMetaTags = $sitecodeMetaTags;
    }

    /**
     * @return mixed
     */
    public function getSitecodeKey()
    {
        return $this->sitecodeKey;
    }

    /**
     * @param mixed $sitecodeKey
     */
    public function setSitecodeKey($sitecodeKey): void
    {
        $this->sitecodeKey = $sitecodeKey;
    }

    /**
     * @return string
     */
    public function getSitecodeTitle()
    {
        return $this->sitecodeTitle;
    }

    /**
     * @param string $sitecodeTitle
     */
    public function setSitecodeTitle($sitecodeTitle)
    {
        $this->sitecodeTitle = $sitecodeTitle;
    }

    /**
     * @return string
     */
    public function getSitecodeDomain()
    {
        return $this->sitecodeDomain;
    }

    /**
     * @param string $sitecodeDomain
     */
    public function setSitecodeDomain($sitecodeDomain)
    {
        $this->sitecodeDomain = $sitecodeDomain;
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
    public function setDefaultLocale($defaultLocale): void
    {
        $this->defaultLocale = $defaultLocale;
    }

    /**
     * @return array
     */
    public function getSupportedLocales()
    {
        return $this->supportedLocales;
    }

    /**
     * @param string $supportedLocales
     */
    public function setSupportedLocales($supportedLocales): void
    {
        if ($supportedLocales) {
            $localesAvailable = explode('|', $supportedLocales);
        } else {
            $localesAvailable = [$this->getDefaultLocale()];
        }
        $this->supportedLocales = $localesAvailable;
    }

    /**
     * @return string
     */
    public function getSitecodeDomainDev(): string
    {
        return $this->sitecodeDomainDev;
    }

    /**
     * @param string $sitecodeDomainDev
     */
    public function setSitecodeDomainDev(string $sitecodeDomainDev): void
    {
        $this->sitecodeDomainDev = $sitecodeDomainDev;
    }

    /**
     * Note: The domain is expected to be in https://www.tradus.com/  format.
     *
     * @param $iconUrl
     *
     * @return string
     */
    public function getAssetIcon($iconUrl): string
    {
        $domain = $this->getSitecodeDomain();
        $keyName = $this->getSitecodeKey();
        $icon = basename($iconUrl);

        return "{$domain}{$keyName}/category-assets/{$icon}";
    }

    /**
     * Get custom parameters based on a nested string
     * Ex: $parameter = 'apps.firebase_auth'.
     *
     * @param string $parameter
     *
     * @return mixed
     */
    public function getSitecodeParameter(string $parameter)
    {
        global $kernel;
        $container = $kernel->getContainer();
        $containerConfig = $container->getParameter('sitecode');
        $parameters = explode('.', $parameter);
        foreach ($parameters as $element) {
            $containerConfig = $containerConfig[$element];
        }

        return $containerConfig;
    }

    /* Get all sitecode domains
     *
     * @return array
     */
    public static function getSitecodeDomains()
    {
        global $kernel;
        $entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $sitecodeList = [];
        $sitecodes = $entityManager->getRepository('TradusBundle:Sitecodes')->findAll();
        foreach ($sitecodes as $sitecode) {
            $sitecodeList[$sitecode->getId()] = 'mail.'.$sitecode->getDomain();
        }

        return $sitecodeList;
    }

    /* Get sitecode domain(s)
     *
     * @param $id mixed sitecodeid
     *
     * @return mixed
     */
    public static function getSitecode($id = null)
    {
        global $kernel;
        $sitecodeList = [];
        $criteria = $id ? ['id' => $id] : [];
        $entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $sitecodes = $entityManager->getRepository('TradusBundle:Sitecodes')->findBy($criteria);
        foreach ($sitecodes as $sitecode) {
            $sitecodeList[$sitecode->getId()] = [
                'sitecode' => $sitecode->getSitecode(),
                'domain' => $sitecode->getDomain(),
                'locale' => $sitecode->getDefaultLocale(),
                'currency' => $sitecode->getDefaultCurrency(),
            ];
        }

        return $id ? $sitecodeList[$id] : $sitecodeList;
    }

    public function getStartSellingLink($locale)
    {
        $locale = $locale ?? $this->getDefaultLocale();

        if ($this->getSitecodeKey() == Sitecodes::SITECODE_KEY_OTOMOTOPROFI && $locale == $this->getDefaultLocale()) {
            $sellerUrl = $this->getSitecodeParameter('landing_page_pl');
        } else {
            $sellerUrl = $this->getSitecodeParameter('seller_link');
        }

        $sellerUrl = $sellerUrl.(strpos($sellerUrl, '?') ? '&' : '?').'locale='.$locale;

        return $sellerUrl;
    }
}

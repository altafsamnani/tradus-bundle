<?php

namespace TradusBundle\Service\Config;

use Doctrine\ORM\EntityManagerInterface;
use TradusBundle\Entity\Configuration;
use TradusBundle\Entity\Sitecodes;
use TradusBundle\Repository\ConfigurationRepository;
use TradusBundle\Service\Sitecode\SitecodeService;

/**
 * Class ConfigService.
 */
class ConfigService implements ConfigServiceInterface
{
    /**
     * All the settings.
     * @var array
     */
    protected $settings = [];

    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var SitecodeService */
    protected $sitecodeService;

    public function __construct(?EntityManagerInterface $entityManager = null)
    {
        $scs = new SitecodeService();

        $this->sitecodeService = $scs;
        $this->entityManager = $entityManager;
        $this->loadSettings();
    }

    /**
     * @param string $name
     * @return ConfigResult
     */
    public function getSetting(string $name)
    {
        $sitecodeId = $this->sitecodeService->getSitecodeId();

        return isset($this->settings[$sitecodeId][$name])
            ? $this->settings[$sitecodeId][$name] : $this->settings[Sitecodes::SITECODE_TRADUS][$name];
    }

    /**
     * @param string $name
     * @return bool|mixed|null
     */
    public function getSettingValue(string $name)
    {
        return $this->getSetting($name)->getValue();
    }

    /**
     * Loads all settings.
     */
    protected function loadSettings()
    {
        $this->loadFromDatabase();
    }

    /**
     * Loads all setting from the database.
     */
    protected function loadFromDatabase()
    {
        if ($this->entityManager) {
            /** @var ConfigurationRepository $configurationRepository */
            $configurationRepository = $this->entityManager->getRepository('TradusBundle:Configuration');
            $configurations = $configurationRepository->getAllConfigurations();

            /* @var Configuration $configuration */
            if ($configurations) {
                foreach ($configurations as $configuration) {
                    /* @var ConfigResult $configResult */
                    if (isset($this->settings[$configuration->getSitecodeId()][$configuration->getName()])) {
                        $configResult = $this->settings[$configuration->getSitecodeId()][$configuration->getName()];
                    } else {
                        $configResult = new ConfigResult();
                    }
                    $configResult->setConfigurationEntity($configuration);
                    $this->settings[$configuration->getSitecodeId()][$configuration->getName()] = $configResult;
                }
            }
        }
    }
}

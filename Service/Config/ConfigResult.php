<?php

namespace TradusBundle\Service\Config;

use TradusBundle\Entity\Configuration;
use TradusBundle\Entity\Sitecodes;

class ConfigResult
{
    public const DATA_NAME = 'name';
    public const DATA_VALUE = 'value';
    public const DATA_DISPLAY_NAME = 'display_name';
    public const DATA_DEFAULT_VALUE = 'default_value';
    public const DATA_GROUP = 'group';
    public const DATA_VALUE_TYPE = 'value_type';
    public const DATA_POSSIBLE_VALUES = 'possible_values';
    public const DATA_SITECODE = 'sitecode_id';

    /** @var array */
    protected $data = [];

    /** @var Configuration */
    protected $configurationEntity;

    public function __construct(array $options = [])
    {
        if (count($options)) {
            $this->parseOptions($options);
        }
    }

    /**
     * Set internal values.
     * @param array $options
     */
    public function parseOptions(array $options)
    {
        foreach ($options as $name => $value) {
            switch ($name) {
                case self::DATA_NAME:
                case self::DATA_DISPLAY_NAME:
                case self::DATA_VALUE:
                case self::DATA_VALUE_TYPE:
                case self::DATA_DEFAULT_VALUE:
                case self::DATA_GROUP:
                case self::DATA_POSSIBLE_VALUES:
                case self::DATA_SITECODE:
                    $this->setData($name, $value);
                    break;
            }
        }
    }

    /**
     * @param bool $fallbackToDefaultValue
     * @return bool|mixed|null
     */
    public function getValue($disableFallbackToDefault = false)
    {
        $result = $this->getRawValue();
        if ($result === null && $disableFallbackToDefault === false) {
            $result = $this->getDefaultValue();
        }

        return $result;
    }

    public function getRawValue()
    {
        return $this->getData(self::DATA_VALUE);
    }

    /**
     * @return bool|mixed
     */
    public function getName()
    {
        return $this->getData(self::DATA_NAME);
    }

    /**
     * @return bool|mixed
     */
    public function getDefaultValue()
    {
        return $this->getData(self::DATA_DEFAULT_VALUE);
    }

    public function getGroup()
    {
        return $this->getData(self::DATA_GROUP);
    }

    /**
     * @param string $name
     * @param int|null $sitecodeId
     * @return mixed|null
     */
    protected function getData(string $name, ?int $sitecodeId = null)
    {
        $sitecodeId = $sitecodeId ? $sitecodeId : Sitecodes::SITECODE_TRADUS;
        if (isset($this->data[$sitecodeId][$name])) {
            return $this->data[$sitecodeId][$name];
        } elseif (isset($this->data[Sitecodes::SITECODE_TRADUS][$name])) { //Fallback
            return $this->data[Sitecodes::SITECODE_TRADUS][$name];
        }

        return null;
    }

    /**
     * @param string $name
     * @param $value
     * @param int|null $sitecodeId
     */
    protected function setData(string $name, $value, ?int $sitecodeId = null)
    {
        $sitecodeId = $sitecodeId ? $sitecodeId : Sitecodes::SITECODE_TRADUS;
        $this->data[$sitecodeId][$name] = $value;
    }

    /**
     * @param Configuration $configuration
     */
    public function setConfigurationEntity(Configuration $configuration)
    {
        $this->configurationEntity = $configuration;
        $this->setData(self::DATA_NAME, $configuration->getName());
        $this->setData(self::DATA_VALUE, $configuration->getValue());
        $this->setData(self::DATA_GROUP, $configuration->getGroup());
        $this->setData(self::DATA_SITECODE, $configuration->getSitecodeId());
    }

    /**
     * @return Configuration
     */
    public function getConfigurationEntity()
    {
        return $this->configurationEntity;
    }
}

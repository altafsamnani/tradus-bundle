<?php

namespace TradusBundle\Service\Redis;

use Redis;
use TradusBundle\Entity\Sitecodes;

class RedisService
{
    public const EXPIRE_LENGTH = 86400; // 1 day

    /** @var Redis $client */
    private $client;

    /** @var string $namespace */
    private $namespace;

    /** @var int $expire */
    private $expire;

    /** @var string $sitecodeKey */
    private $sitecodeKey;

    /**
     * @param string $redisPrefix
     */
    public function __construct(string $redisPrefix, $expire = null)
    {
        $this->expire = ($expire ? $expire : self::EXPIRE_LENGTH);  //In seconds

        global $kernel;
        $container = $kernel->getContainer();
        $this->sitecodeKey = Sitecodes::SITECODE_KEY_TRADUS.'.';
        if ($container->getParameter(Sitecodes::SITECODE_FIELD_CONFIG)) {
            $this->sitecodeKey =
                $container->getParameter(Sitecodes::SITECODE_FIELD_CONFIG)[Sitecodes::SITECODE_FIELD_KEY_CONFIG].'.';
        }

        $this->namespace = $this->sitecodeKey.$redisPrefix;
        $this->changeNamespace($this->namespace);
    }

    /*
     * @param string $redisNamespace
     */
    public function changeNamespace(string $redisNamespace)
    {
        if (substr($redisNamespace, 0, strlen($this->sitecodeKey)) !== $this->sitecodeKey) {
            $redisNamespace = $this->sitecodeKey.$redisNamespace;
        }

        $this->namespace = $redisNamespace;
        $this->client = SingletonRedisClient::getClient();
    }

    /**
     * @param string $redisNamespace
     */
    public function setNamespace(string $redisNamespace)
    {
        $this->namespace = $redisNamespace;
    }

    /*
     * Function getAllKeysFromNamespace
     * Beware! do not use this method on big collections
     */
    public function getAllKeysFromNamespace()
    {
        if (trim($this->namespace) === '') {
            return false;
        }

        $key = $this->namespace.'*';
        if (! $this->client) {
            return false;
        }

        return $this->client->keys($key);
    }

    /*
     * Function getAllFromNamespace
     * Beware! do not use this method on big collections
     */
    public function getAllFromNamespace()
    {
        if (trim($this->namespace) === '') {
            return false;
        }

        $key = $this->namespace.'*';
        if (! $this->client) {
            return false;
        }

        $availableKeys = $this->getAllKeysFromNamespace($key);

        return array_combine($availableKeys, $this->client->mGet($availableKeys));
    }

    /**
     * Function getExpire.
     * @return int
     */
    public function getExpire(): int
    {
        if ($this->expire) {
            return $this->expire;
        }

        return self::EXPIRE_LENGTH;
    }

    /**
     * Function setExpire.
     * @param int $expire
     */
    public function setExpire(int $expire): void
    {
        $this->expire = $expire;
    }

    /**
     * Function setParameter.
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function setParameter(string $key, $value): bool
    {
        $key = $this->namespace.$key;

        if (! $this->client) {
            return false;
        }
        if (! $this->client->setEx($key, $this->expire, $value)) {
            return false;
        }

        return true;
    }

    /**
     * Function setByPayload.
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function setByPayload(array $array): bool
    {
        $success = true;
        foreach ($array as $key => $value) {
            if (! $this->setParameter($key, $value)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Function getParameter.
     * @param mixed $key
     * @return mixed $value
     */
    public function getParameter($key)
    {
        $key = $this->namespace.$key;
        if (! $this->client) {
            return false;
        }

        return $this->client->get($key);
    }

    /**
     * Function getParameters.
     * @param string $key
     * @return mixed $value
     */
    public function getParameters(array $keys)
    {
        if (! $this->client) {
            return false;
        }

        return array_combine($keys, $this->client->mGet($keys));
    }

    public function getLLEN(string $key)
    {
        $key = $this->namespace.$key;
        if (! $this->client) {
            return false;
        }

        return $this->client->LLEN($key);
    }

    public function LINDEX(string $key, int $index)
    {
        $key = $this->namespace.$key;

        if (! $this->client) {
            return false;
        }

        return $this->client->LINDEX($key, $index);
    }
}

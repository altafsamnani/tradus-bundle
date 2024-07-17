<?php

namespace TradusBundle\Service\Redis;

use Exception;
use Redis;

class SingletonRedisClient
{
    private static $client = false;

    public static function getClient()
    {
        if (! self::$client) {
            global $kernel;

            if (! $kernel->getContainer()) {
                return self::$client;
            }

            $url = $kernel->getContainer()->getParameter('redis')['url'];
            $port = $kernel->getContainer()->getParameter('redis')['port'];

            try {
                self::$client = new Redis();
                self::$client->connect($url, $port);
            } catch (Exception $exception) {
                self::$client = false;
            }
        }

        return self::$client;
    }
}

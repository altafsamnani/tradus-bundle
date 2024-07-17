<?php

namespace  TradusBundle\Factory;

use Exception;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use TradusBundle\Service\Sitecode\SitecodeService;
use TradusBundle\Service\Wrapper\ProApiWrapper;

class ProApiFactory
{
    private $api;

    private $cache;

    private $clientId;

    private $clientSecret;

    private $endpoint;

    public function __construct()
    {
        global $kernel;
        $this->cache = new FilesystemAdapter('', 0, $kernel->getCacheDir());
        $this->setClientCredentials($kernel);
        $this->createApiClient();
    }

    private function createApiClient()
    {
        $authToken = $this->generateBasicToken();

        $this->api = new ProApiWrapper(['endpoint' => $this->endpoint, 'auth_token' => $authToken]);
    }

    private function setClientCredentials($kernel)
    {
        $scs = new SitecodeService();
        $whiteLabel = $scs->getSitecodeKey();
        $parameters = $kernel->getContainer()->getParameter('pro');
        $this->endpoint = $parameters['endpoint'];
        $this->clientId = $parameters[$whiteLabel]['client_id'];
        $this->clientSecret = $parameters[$whiteLabel]['client_secret'];
    }

    private function generateBasicToken()
    {
        $cache = $this->cache;
        $authToken = $cache->getItem('pro.basic_token_'.$this->clientId);

        if (! $authToken->isHit()) {
            $basicToken = base64_encode($this->clientId.':'.$this->clientSecret);

            if (! empty($basicToken)) {
                $authToken->set('Basic '.$basicToken);
                $authToken->expiresAfter(3500);
                $cache->save($authToken);
            } else {
                throw new Exception('Error creating basic token for client: '.$this->clientId, 1);
            }
        }

        $authToken = $authToken->get();

        return $authToken;
    }

    public function get($endpoint, $data = [])
    {
        // we can cache `get` calls if we call $api->get('categories', ['cache' => true, 'cache_expire' => 1000]);
        if (false && ! empty($data['cache'])) {
            if (empty($data['cache_expire'])) {
                $cache_expire = 2000;
            } else {
                $cache_expire = $data['cache_expire'];
            }

            unset($data['cache']);
            unset($data['cache_expire']);

            $cache = $this->cache;
            $item = $cache->getItem(str_replace('/', '_', $endpoint).'_v2_'.json_encode($data));

            if (! $item->isHit()) {
                $ret = $this->api->get($endpoint, $data);
                $item->set($ret);
                $item->expiresAfter($cache_expire);
                $cache->save($item);
            }

            return $item->get();
        } else {
            return $this->api->get($endpoint, $data);
        }
    }
}

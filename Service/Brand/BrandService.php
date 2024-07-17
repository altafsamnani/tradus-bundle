<?php

namespace TradusBundle\Service\Brand;

use Doctrine\ORM\EntityManagerInterface;
use TradusBundle\Entity\Model;
use TradusBundle\Entity\Version;
use TradusBundle\Repository\ModelRepository;
use TradusBundle\Repository\VersionRepository;
use TradusBundle\Service\Redis\RedisService;

/**
 * Class BrandService.
 */
class BrandService
{
    /** @var EntityManagerInterface */
    protected $entityManager;

    /**
     * BrandService constructor.
     * @param EntityManagerInterface|null $entityManager
     */
    public function __construct(?EntityManagerInterface $entityManager = null)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param array $makes
     * @return array
     */
    public function getModelsByMakes(array $makes)
    {
        $models = [];
        $redis = new RedisService('Models:');

        foreach ($makes as $make) {
            if ($redis->getParameter($make)) {
                $models[$make] = unserialize($redis->getParameter($make));
            }
        }

        // Early return if we find the result in Redis
        if (! empty($models)) {
            return $models;
        }

        // If we do not find it in Redis we get it from Db and save it in Redis for later usage
        /** @var ModelRepository $modelRepo */
        $modelRepo = $this->entityManager->getRepository('TradusBundle:Model');
        $models = $modelRepo->getModelsByMakes($makes);

        foreach ($models as $make => $model) {
            $redis->setParameter($make, serialize($model));
        }

        return $models;
    }

    /**
     * @param array $models
     * @return array
     */
    public function getVersionsByModels(array $models)
    {
        $versions = [];
        $redis = new RedisService('Versions:');

        foreach ($models as $model) {
            if ($redis->getParameter($model)) {
                $versions[$model] = unserialize($redis->getParameter($model));
            }
        }

        // Early return if we find the result in Redis
        if (! empty($versions)) {
            return $versions;
        }

        // If we do not find it in Redis we get it from Db and save it in Redis for later usage
        /** @var VersionRepository $versionRepo */
        $versionRepo = $this->entityManager->getRepository('TradusBundle:Version');
        $versions = $versionRepo->getVersionsByModels($models);

        foreach ($versions as $model => $version) {
            $redis->setParameter($model, serialize($version));
        }

        return $versions;
    }

    /**
     * @param array $hashKey
     * @return array
     */
    public function getModelsByMakesInSearch(string $hashKey)
    {
        $redis = new RedisService(
            Model::REDIS_NAMESPACE_MODELS
        );

        $modelsRedis = $redis->getParameter($hashKey);

        if ($modelsRedis) {
            return ['models' => json_decode($modelsRedis, true), 'exists' => true];
        }

        return ['models' => [], 'exists' => false];
    }

    /**
     * @param string $hashKey
     * @param array $models
     * @return bool
     */
    public function setModelsByMakesInSearch(string $hashKey, array $models)
    {
        $redis = new RedisService(
            Model::REDIS_NAMESPACE_MODELS,
            Model::REDIS_EXPIRATION_MODELS
        );

        return $redis->setParameter($hashKey, json_encode($models));
    }

    /**
     * @param array $hashKey
     * @return array
     */
    public function getVersionsByModelsInSearch(string $hashKey)
    {
        $redis = new RedisService(
            Version::REDIS_NAMESPACE_VERSIONS
        );

        $versionRedis = $redis->getParameter($hashKey);

        if ($versionRedis) {
            return ['versions' => json_decode($versionRedis, true), 'exists' => true];
        }

        return ['versions' => [], 'exists' => false];
    }

    /**
     * @param string $hashKey
     * @param array $models
     * @return bool
     */
    public function setVersionsByModelsInSearch(string $hashKey, array $versions)
    {
        $redis = new RedisService(
            Version::REDIS_NAMESPACE_VERSIONS,
            Version::REDIS_EXPIRATION_VERSIONS
        );

        return $redis->setParameter($hashKey, json_encode($versions));
    }
}

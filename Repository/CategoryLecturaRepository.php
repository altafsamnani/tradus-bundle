<?php

namespace TradusBundle\Repository;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityRepository;
use TradusBundle\Entity\CategoryLectura;
use TradusBundle\Service\Redis\RedisService;
use TradusBundle\Service\Search\SearchService;

/**
 * Class CategoryLecturaRepository.
 */
class CategoryLecturaRepository extends EntityRepository
{
    const EMPTY_DEFAULT = 'empty';

    /**
     * Function getCategoryLectura.
     * @return mixed
     * @throws DBALException
     */
    public function getCategories()
    {
        $connection = $this->getEntityManager()->getConnection();
        $sql = 'SELECT distinct(category_id) as category FROM category_lectura';

        $stmt = $connection->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();

        return array_column($result, 'category');
    }

    /**
     * Function getCategoryLectura.
     * @return mixed
     * @throws DBALException
     */
    public function getCategoryLectura()
    {
        $connection = $this->getEntityManager()->getConnection();
        $sql = 'SELECT group_concat(category_id) as category, lectura_url, locale 
                FROM category_lectura GROUP BY lectura_url';

        $stmt = $connection->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * @param int|null $categoryId
     * @param string|null $locale
     * @return array
     */
    public function getLecturaRssFeed(int $categoryId = null, string $locale = null): array
    {
        if (! $categoryId || ! $locale) {
            return [];
        }

        $key = $locale.':'.$categoryId;
        $redis = new RedisService(CategoryLectura::REDIS_NAMESPACE_LECTURA_RSS);
        $redisValue = $redis->getParameter($key);

        if ($redisValue == self::EMPTY_DEFAULT) {
            return [];
        }

        $feed = json_decode($redisValue, true);

        if (! $feed) {
            $data = $this->buildLecturaRssFeed($categoryId, $locale);
            $feed = [];
            if ($this->setLecturaRssFeed($redis, $categoryId, $locale, $data)) {
                $feed = json_decode($redis->getParameter($key), true);
            }
        }

        return $feed;
    }

    /**
     * @param RedisService $redis
     * @param int|null $categoryId
     * @param string|null $locale
     * @param null $payload
     * @return bool
     */
    public function setLecturaRssFeed(
        RedisService $redis,
        int $categoryId = null,
        string $locale = null,
        array $payload = null
    ) {
        if (! $categoryId || ! $locale || ! $payload || count($payload) == 0) {
            $redis->setParameter($locale.':'.$categoryId, self::EMPTY_DEFAULT);

            return false;
        }
        $redis->setExpire(CategoryLectura::REDIS_LIFE_SPAN);

        return $redis->setParameter($locale.':'.$categoryId, json_encode($payload));
    }

    /**
     * @param int|null $categoryId
     * @param string|null $locale
     * @param int|null $categoryL1Id
     * @param array|null $parentPayload
     * @return array
     */
    public function buildLecturaRssFeed(
        int $categoryId = null,
        string $locale = null,
        int $categoryL1Id = null,
        array $parentPayload = null
    ) {
        global $kernel;
        $solr = $kernel->getContainer()->getParameter('solr');
        $search = new SearchService(
            ['endpoint' => $solr['lectura_rss_endpoint']],
            $this->getEntityManager()
        );

        //If there is a direct hit
        $documents = $search->getLecturaRssSolrByCategoryId($categoryId, $locale);

        if (count($documents) == 0) {
            //Search within the parent
            $entityManager = $this->getEntityManager();
            $categoryRepository = $entityManager->getRepository('TradusBundle:Category');
            $category = $categoryRepository->find($categoryId);

            if (! $category || ! $locale) {
                return [];
            }

            if (! $categoryL1Id) {
                $categoryL1Id = $category->getL1ParentId();

                if (! $categoryL1Id) {
                    return [];
                }
                $categoryL1Id = $categoryL1Id->getId();
            }

            $categoryName = $category->getAllCategoryNames([$locale]);

            $data = $search->getLecturaRssSolrByCategoryQuery(
                $categoryL1Id,
                $categoryName[$locale][0],
                $locale
            );

            $documents = array_merge($documents, $data);

            //if didn't found, go for the parent and replace them
            if (count($documents) == 0) {
                if ($parentPayload) {
                    $documents = $parentPayload;
                } else {
                    $documents = $search->getLecturaRssSolrByCategoryId($categoryL1Id, $locale);
                }
            }
        }

        return $documents;
    }
}

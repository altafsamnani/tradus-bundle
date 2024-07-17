<?php

namespace TradusBundle\Service\OfferLatest;

use Doctrine\ORM\EntityManager;
use TradusBundle\Entity\Category;
use TradusBundle\Service\Redis\RedisService;
use TradusBundle\Transformer\OfferSearchTransformer;

class OfferLatestService
{
    /** @var object ConfigService */
    public $config;

    /** @var EntityManager $entityManager */
    public $entityManager;

    /** @var SearchService $search */
    public $search;

    /** @var RedisService $redis */
    public $redis;

    public function __construct(EntityManager $entityManager = null)
    {
        global $kernel;
        if (! $entityManager) {
            $this->entityManager = $kernel->getContainer()->get('doctrine.orm.tradus_entity_manager');
        } else {
            $this->entityManager = $entityManager;
        }

        $this->search = $kernel->getContainer()->get('tradus.search');
        $this->config = $kernel->getContainer()->get('tradus.config');
    }

    public function findLatests(string $locale = 'en', $overrideHome = null)
    {
        $source = $overrideHome ? null : 'home';
        $entityManager = $this->entityManager;
        $categories = $entityManager->getRepository('TradusBundle:Category')->findBy([
            'parent' => null,
            'status' => Category::STATUS_ONLINE,
        ]);
        $search = $this->search;

        foreach ($categories as $category) {
            /** @var SearchService $search */
            $searchResult = $search->findLatestPremiumOffersBy($category->getId(), 1000, $source);

            $suggestions[$category->getId()]
                = (new OfferSearchTransformer($searchResult, $locale, $category->getId(), $entityManager))
                        ->transform();
        }

        return $suggestions;
    }
}

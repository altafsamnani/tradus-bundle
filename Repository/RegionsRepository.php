<?php

namespace TradusBundle\Repository;

use Cocur\Slugify\Slugify;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use TradusBundle\Entity\Regions;

class RegionsRepository extends EntityRepository
{
    public function addRegion(array $data): void
    {
        $slugify = new Slugify();
        foreach ($data as $region => $details) {
            $regions = $this->findOneBy([
                'name' => $region,
                'country' => $details['country'],
                'locale' => $details['locale'],
            ]);
            if (! $regions) {
                $regions = new Regions();
                $regions->setCountry($details['country']);
                $regions->setName($region);
                $regions->setCountryCode($details['country_code']);
                $regions->setLocale($details['locale']);
                $regions->setSlug($slugify->slugify($region));

                /** @var EntityManager $entityManager */
                $entityManager = $this->getEntityManager();
                $entityManager->clear();
                $entityManager->persist($regions);
                $entityManager->flush();
            }
        }
    }
}

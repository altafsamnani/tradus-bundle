<?php

namespace TradusBundle\Repository;

use Cocur\Slugify\Slugify;
use Doctrine\ORM\EntityRepository;
use TradusBundle\Entity\AttributeOption;

/**
 * Class AttributeOptionRepository.
 */
class AttributeOptionRepository extends EntityRepository
{
    public function getOptionsByIds(array $ids)
    {
        $results = [];
        $attributeOptions = $this->findBy(['id' => $ids]);
        foreach ($attributeOptions as $option) {
            /** @var AttributeOption $option */

            // We need to remove this once we have slugs for every attribute option
            if (! $option->getSlug()) {
                $option->setSlug($this->slugifyOption($option));
            }
            $results[$option->getSlug()] = $option->toArray();
        }

        return $results;
    }

    /**
     * We create a slug and set in into the db for further use
     * We should remove this if we have slugs for every attribute options
     * Or we can keep it as a backup.
     * @param AttributeOption $attributeOption
     * @return string
     */
    private function slugifyOption(AttributeOption $attributeOption)
    {
        /** @var Slugify $slugify */
        $slugify = new Slugify();
        $slug = $slugify->slugify($attributeOption->getContent());
        $this->createQueryBuilder('ao')
            ->update()
            ->set('ao.slug', ':slug')
            ->where('ao.id = :id')
            ->setParameter('slug', $slug)
            ->setParameter('id', $attributeOption->getId())
            ->getQuery()
            ->execute();

        return $slug;
    }
}

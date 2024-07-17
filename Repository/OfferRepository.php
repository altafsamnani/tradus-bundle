<?php

namespace TradusBundle\Repository;

use DateTime;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use PDO;
use TradusBundle\Entity\Category;
use TradusBundle\Entity\CategoryInterface;
use TradusBundle\Entity\Offer;
use TradusBundle\Entity\OfferImageInterface;
use TradusBundle\Entity\Seller;
use TradusBundle\Service\Redis\RedisService;
use TradusBundle\Service\Sitecode\SitecodeService;

/**
 * Class OfferRepository.
 */
class OfferRepository extends EntityRepository
{
    public const VIEWED_BY_OTHERS_LIMIT_OFFER_ANALYTICS = 15;
    public const VIEWED_BY_OTHERS_EXPIRATION = 1;

    /**
     * @param string $slug
     * @param string $locale |null
     *
     * @return Offer
     * @throws NonUniqueResultException
     */
    public function getOfferBySlug(string $slug, ?string $locale = null)
    {
        //TODO: remove all joins that are not needed, that data should be lazy loaded.
        $queryBuilder = $this->createQueryBuilder('offer')
            ->select('offer, i, d, c, ctype, csubtype, m, s')
            ->leftJoin('offer.images', 'i')
            ->leftJoin('offer.descriptions', 'd')
            ->leftJoin('offer.make', 'm')
            ->leftJoin('offer.seller', 's')
            ->leftJoin('offer.category', 'c')
            ->leftJoin('c.parent', 'ctype')
            ->leftJoin('ctype.parent', 'csubtype')
            ->andWhere('d.title_slug = :slug')
            ->setParameter('slug', $slug);

        if ($locale) {
            $queryBuilder->andWhere('d.locale = :locale')->setParameter('locale', $locale);
        }

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @param string $adId
     *
     * @return Offer|null
     * @throws NonUniqueResultException
     */
    public function getOfferByAdId(string $adId)
    {
        //TODO: remove all joins that are not needed, that data should be lazy loaded.
        return $this->createQueryBuilder('offer')
            ->select('offer, i, d, c, ctype, csubtype, m, s')
            ->leftJoin('offer.images', 'i')
            ->leftJoin('offer.descriptions', 'd')
            ->leftJoin('offer.make', 'm')
            ->leftJoin('offer.seller', 's')
            ->leftJoin('offer.category', 'c')
            ->leftJoin('c.parent', 'ctype')
            ->leftJoin('ctype.parent', 'csubtype')
            ->andWhere('offer.ad_id = :ad_id')
            ->setParameter('ad_id', $adId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param string $adId
     * @return mixed
     * @throws NonUniqueResultException
     */
    public function findOfferByAdId(string $adId)
    {
        return $this->createQueryBuilder('offer')
            ->select('offer')
            ->where('offer.ad_id = :ad_id')
            ->setParameter('ad_id', $adId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param int $offerId
     *
     * @return Offer|null
     * @throws NonUniqueResultException
     */
    public function getOfferById(int $offerId)
    {
        //TODO: remove all joins that are not needed, that data should be lazy loaded.
        $query = $this->createQueryBuilder('offer')
            ->select('offer, i, d, c, ctype, csubtype, m, s')
            ->leftJoin('offer.images', 'i')
            ->leftJoin('offer.descriptions', 'd')
            ->leftJoin('offer.make', 'm')
            ->leftJoin('offer.seller', 's')
            ->leftJoin('offer.category', 'c')
            ->leftJoin('c.parent', 'ctype')
            ->leftJoin('ctype.parent', 'csubtype')
            ->andWhere('offer.id = :id')
            ->setParameter('id', $offerId);

        return $query->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param Seller $seller
     * @return mixed
     * @throws NonUniqueResultException
     */
    public function getCountActiveOffersBySeller(Seller $seller)
    {
        $query = $this->createQueryBuilder('offer')
            ->select('COUNT(offer)')
            ->where('offer.seller= :seller_id')
            ->andWhere('offer.status= :status')
            ->setParameter('seller_id', $seller->getId())
            ->setParameter('status', Offer::STATUS_ONLINE);

        return $query->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Seller $seller
     * @return mixed
     */
    public function getAllActiveOffersBySeller(Seller $seller)
    {
        $query = $this->createQueryBuilder('offer')
            ->select('offer')
            ->where('offer.seller= :seller_id')
            ->andWhere('offer.status= :status')
            ->setParameter('seller_id', $seller->getId())
            ->setParameter('status', Offer::STATUS_ONLINE);

        return $query->getQuery()->getResult();
    }

    /**
     * @param DateTime $startDateTime
     * @param DateTime $endDateTime
     * @param int $sellerType
     * @param int $weekDay
     * @return array
     * @throws DBALException
     */
    public function getIdsOfActiveOffersByBumpBetween(
        DateTime $startDateTime,
        DateTime $endDateTime,
        int $sellerType,
        int $weekDay = 0
    ) {
        $connection = $this->getEntityManager()->getConnection();
        $sql = 'SELECT offers.id, offers.created_at
                FROM offers 
                LEFT JOIN sellers ON offers.seller_id = sellers.id 
                WHERE (
                      ((offers.created_at >= :start_date AND offers.created_at < :end_date)
                      )
                     )
                 AND seller_type = :seller_type
                 AND offers.status = :offer_status';

        if ($weekDay) {
            $sql .= ' AND DAYOFWEEK(offers.created_at) = '.$weekDay;
        }

        $stmt = $connection->prepare($sql);
        $stmt->execute([
            'start_date' => $startDateTime->format('Y-m-d H:i:s'),
            'end_date' => $endDateTime->format('Y-m-d H:i:s'),
            'seller_type' => $sellerType,
            'offer_status' => Offer::STATUS_ONLINE,
        ]);

        // returns an array of arrays (i.e. a raw data set)
        return $stmt->fetchAll();
    }

    /**
     * @param int $category depth =2
     * @return int
     */
    public function getTransportWheelsType($category)
    {
        $wtCat1 = [6];
        $wtCat2 = [2, 3, 4, 5, 7, 3696, 3721];
        $wtCat3 = [8, 84, 85, 86, 87, 88, 89, 90, 91, 92, 93, 94, 95, 96];
        $wtCat4 = [51, 52, 53, 54, 55, 56, 57, 58, 59];

        if (in_array($category, $wtCat1)) {
            return 1;
        } elseif (in_array($category, $wtCat2)) {
            return 2;
        } elseif (in_array($category, $wtCat3)) {
            return 3;
        } elseif (in_array($category, $wtCat4)) {
            return 4;
        } else {
            return -1;
        }
    }

    /**
     * @param Offer $offer
     * @return bool
     */
    public function isOfferAllowedShippingQuote(Offer $offer)
    {
        $wheelsTransportType = -1;
        if (isset($offer['categories'][1]['id'])) {
            $wheelsTransportType = $this->getTransportWheelsType($offer['categories'][1]['id']);
        }

        if (empty($offer['weight'])
            || empty($offer['height'])
            || empty($offer['width'])
            || empty($offer['length'])
            || $wheelsTransportType === -1
            || $offer['status'] !== Offer::OFFER_STATUS_LIVE) {
            return false;
        }

        return true;
    }

    /**
     * To get default image of an offer, if not available returning default blank image for category.
     * @param Offer $offer
     * @return string
     * @throws DBALException
     */
    public function getDefaultImage(Offer $offer)
    {
        $scs = new SitecodeService();

        $offerId = $offer->getId();
        $connection = $this->getEntityManager()->getConnection();

        $sql = "SELECT OI.url as imageUrl FROM offer_images AS OI 
                INNER JOIN (
                  SELECT I.id, MIN(I.sort_order) AS minSortOrder FROM offer_images AS I 
                  WHERE I.offer_id = $offerId AND I.status = ".OfferImageInterface::STATUS_ONLINE.'
                ) AS OI1 
                ON OI1.id = OI.id 
                WHERE OI1.minSortOrder = OI.sort_order LIMIT 1';
        $stmt = $connection->prepare($sql);
        $stmt->execute();
        $image = $stmt->fetch();

        if (empty($image['imageUrl'])) {
            global $kernel;
            $environment = $kernel->getEnvironment();
            $urlDomain = ($environment == 'dev') ? $scs->getSitecodeDomainDev() : $scs->getSitecodeDomain();
            $image = "$urlDomain".$this->getOfferFallbackImage($offer);
        } else {
            $image = $image['imageUrl'];
        }

        return $image;
    }

    /**
     * To get fallback image for an offer, if not available any category returning default transport image.
     * @param Offer $offer
     * @return string
     * @throws DBALException
     */
    public function getOfferFallbackImage(Offer $offer)
    {
        /** @var CategoryRepository $categoryRepo */
        $categoryRepo = $this->getEntityManager()->getRepository('TradusBundle:Category');
        $categoryId = $categoryRepo->getTopLevelCategoryId($offer->getCategory());
        switch ($categoryId) {
            case CategoryInterface::FARM_ID:
                $image = 'farm.png';
                break;
            case CategoryInterface::CONSTRUCTION_ID:
                $image = 'construction.png';
                break;
            case CategoryInterface::SPARE_PARTS_ID:
                $image = 'spare-parts.png';
                break;
            default: // also covers [case CategoryInterface::TRANSPORT_ID]
                $image = 'transport.png';
                break;
        }
        $ssc = new SitecodeService();

        return "assets/{$ssc->getSitecodeKey()}/offer-result/$image";
    }

    /**
     * Function for fetching the offers statement.
     *
     * @param PDO $connection
     * @param int $sitecodeId
     * @return array
     */
    public function getPDOAllActiveOffers(PDO $connection, int $sitecodeId): array
    {
        $query = 'SELECT o.id
            FROM offers o INNER JOIN seller_sitecode ssc ON (o.seller_id = ssc.seller_id)
            WHERE o.status = '.Offer::STATUS_ONLINE.'
            AND ssc.sitecode = '.$sitecodeId.'
            AND NOT o.make_id IS NULL
            ORDER BY o.id DESC';
        $offerQuery = $connection->prepare(trim($query));
        $offerQuery->execute();
        $offersArray = [];
        while ($offerEntry = $offerQuery->fetch()) {
            $offersArray[] = ['id' => $offerEntry['id']];
        }

        return $offersArray;
    }

    /**
     * Function for fetching the offers viewed / interacted statement by the last 100 users.
     *
     * @param PDO $connection
     * @param Offer $offer
     * @param bool $interacted
     * @return array
     */
    public function getOtherOffersViewedStatement(
        PDO $connection,
        Offer $offer,
        bool $interacted = false
    ): array {
        $offerId = $offer->getId();
        $categoryId = $offer->getCategory()->getId();
        $redis = new RedisService(Category::REDIS_NAMESPACE_CATEGORY_TREE);
        $tree = $redis->getParameter($categoryId);

        if (! $tree) {  //Fetch it from DB
            $category = $offer->getCategory();
            if ($categoryParent = $category->getParent()) {
                if ($categoryParentL2 = $category->getParent()) {
                    $categoryId = $categoryParentL2->getId();
                } else {
                    $categoryId = $categoryParent->getId();
                }
            }

            $query = "
            SELECT id FROM categories WHERE id = $categoryId
            UNION 
            SELECT id FROM categories WHERE parent_id = $categoryId
            UNION
            SELECT id FROM categories WHERE parent_id IN (SELECT id FROM categories WHERE parent_id = $categoryId)
            ";

            $categoriesQuery = $connection->prepare($query);
            $categoriesQuery->execute();
            $payload = [];
            while ($categories = $categoriesQuery->fetch()) {
                $payload[] = $categories['id'];
            }
            $treeIds = implode(',', $payload);
            $redis->setParameter($categoryId, json_encode(['tree' => $treeIds]));
        } else {
            $tree = json_decode($tree, true);

            if (isset($tree['tree'])) {
                $treeIds = implode(',', $tree['tree']);
            }
        }

        $dateLimit = date('Y-m-d', strtotime('-'.self::VIEWED_BY_OTHERS_LIMIT_OFFER_ANALYTICS.' days'));
        $extraSQL = $interacted ? "type <> 'visit'" : "type = 'visit'";

        $query = '
            SELECT offer_id, COUNT(*) as hits
            FROM (
              SELECT user_index FROM offer_analytics_data WHERE offer_id='.$offerId." and user_id >0 and created_at > '".$dateLimit."' GROUP BY user_index
               ORDER BY id DESC limit ".Offer::OTHER_OFFERS_VIEWED_LIMIT_USERS.'
              ) as oad_users
              LEFT JOIN offer_analytics_data as oad_offers USING (user_index)
              INNER JOIN offers AS o ON o.id = oad_offers.offer_id
              INNER JOIN seller_sitecode AS ssc ON o.seller_id = ssc.seller_id
              WHERE o.status = '.Offer::STATUS_ONLINE.' AND oad_offers.offer_id <> '.$offerId.' AND '.$extraSQL." 
              AND oad_offers.created_at > '".$dateLimit."' AND o.category_id IN (".$treeIds.')
              GROUP BY oad_offers.offer_id
              ORDER BY hits DESC limit '.Offer::OTHER_OFFERS_VIEWED_LIMIT_OFFERS.'
        ';
        $offersViewedQuery = $connection->prepare($query);
        $offersViewedQuery->execute();
        $payload = [];
        while ($offer = $offersViewedQuery->fetch()) {
            $payload[] = $offer['offer_id'];
        }

        return $payload;
    }

    /**
     * Function setting in redis the offers viewed / interacted by the last 100 users.
     *
     * @param PDO $connection
     * @param Offer $offer
     * @return bool
     */
    public function setOtherOffersViewedInteracted(
        PDO $connection,
        Offer $offer
    ): bool {
        $offerId = $offer->getId();
        static $redis = false;
        $offersViewed = self::getOtherOffersViewedStatement($connection, $offer, false);
        $offersInteracted = self::getOtherOffersViewedStatement($connection, $offer, true);

        $payload = [];
        $payload['viewed'] = $offersViewed;
        $payload['interacted'] = $offersInteracted;
        $payload['expiration'] = strtotime('+'.self::VIEWED_BY_OTHERS_EXPIRATION.' days');

        if (! $redis) {
            $redis = new RedisService(Offer::REDIS_NAMESPACE_OTHER_VIEWS);
        }

        if ($redis) {
            return $redis->setParameter($offerId, json_encode($payload));
        }

        return false;
    }

    public function getHomeOffers(Category $categoryL1, int $type)
    {
        $ssc = new SitecodeService();
        $sitecodeId = $ssc->getSitecodeId();

        $categoryRepo = $this->getEntityManager()->getRepository('TradusBundle:Category');
        $categories = $categoryRepo->getAllChildrenIds($categoryL1);
        $categories[] = $categoryL1->getId();

        $queryBuilder = $this->createQueryBuilder('offer')
            ->select('offer, ov')
            ->Join('offer.vas', 'ov')
            ->Join('offer.seller', 'os')
//            ->leftJoin('offer.images', 'oi')
            ->andWhere('offer.status = :status')
            ->andWhere('os.status = :sellerStatus')
            ->andWhere('os.testuser = :notTestFlag')
            ->andWhere('os.seller_type > :notPremium')
            ->andWhere('ov.sitecodeId = :sitecode_id')
            ->andWhere('ov.startDate <= :now')
            ->andWhere('ov.endDate >= :now')
            ->andWhere('ov.vasId = :vas_type')
            ->andWhere('offer.category IN (:categories)')
//            ->groupBy('offer.id')
//            ->having('count(oi.id) > 0')
            ->setParameter('status', Offer::STATUS_ONLINE)
            ->setParameter('sellerStatus', Seller::STATUS_ONLINE)
            ->setParameter('notTestFlag', Seller::TESTUSER_IS_NOT_FLAG)
            ->setParameter('notPremium', Seller::SELLER_TYPE_FREE)
            ->setParameter('sitecode_id', $sitecodeId)
            ->setParameter('now', date('Y-m-d H:i:s'))
            ->setParameter('vas_type', $type)
            ->setParameter('categories', $categories);

        $result = $queryBuilder->getQuery()->getResult();

        shuffle($result);

        return array_slice($result, 0, 30);
    }

    /**
     * Based on an offer id we set the solr status to 'to_update'.
     *
     * @param int $offerId
     * @return int
     */
    public function setOfferToReindex(int $offerId)
    {
        $this->createQueryBuilder('offer')
                ->update()
                ->set('offer.solr_status', ':status')
                ->where('offer.id = :offerId')
                ->setParameter('status', Offer::SOLR_STATUS_TO_UPDATE)
                ->setParameter('offerId', $offerId)
                ->getQuery()
                ->execute();

        return $offerId;
    }
}

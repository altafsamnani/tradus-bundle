<?php

namespace TradusBundle\Repository;

use DateTime;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Expr\Join;
use TradusBundle\Entity\Alerts;
use TradusBundle\Entity\SimilarOfferAlert;
use TradusBundle\Entity\Sitecodes;
use TradusBundle\Entity\TradusUser;

/**
 * Class AlertsRepository.
 */
class AlertsRepository extends EntityRepository
{
    public const ALERT_DEFAULT_MAKE = null;
    public const ALERT_DEFAULT_CATEGORY = null;
    public const ALERT_DEFAULT_TYPE = null;
    public const ALERT_DEFAULT_SUBTYPE = null;
    public const ALERT_DEFAULT_COUNTRY = null;
    public const ALERT_DEFAULT_PRICE_LO = '';
    public const ALERT_DEFAULT_PRICE_HI = '';
    public const ALERT_DEFAULT_YEAR_LO = '';
    public const ALERT_DEFAULT_YEAR_HI = '';

    /**
     * @param TradusUser $user
     * @param int $ruleType
     * @param string $ruleIdentifier
     * @return mixed
     */

    /**
     * @param TradusUser $user
     * @param int $ruleType
     * @param string $ruleIdentifier
     * @param int $sitecodeId
     * @return mixed
     * @throws NonUniqueResultException
     */
    public function findExistingRule(TradusUser $user, int $ruleType, string $ruleIdentifier, int $sitecodeId)
    {
        return $this->createQueryBuilder('alerts')
            ->select('alerts')
            ->where('alerts.rule_identifier = :rule_identifier')
            ->andWhere('alerts.user = :user_id')
            ->andWhere('alerts.rule_type = :rule_type')
            ->andWhere('alerts.sitecodeId = :sitecodeId')
            ->setParameter('user_id', $user->getId())
            ->setParameter('rule_type', $ruleType)
            ->setParameter('rule_identifier', $ruleIdentifier)
            ->setParameter('sitecodeId', $sitecodeId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param int $ruleType
     * @param DateTime $createdAt
     * @param DateTime $lastSendAt
     * @param string $userIds
     * @param int $last_id
     * @param int $sitecodeId
     * @return mixed
     */
    public function findAllForSendingUpdate(
        int $ruleType,
        DateTime $createdAt,
        DateTime $lastSendAt,
        string $userIds,
        int $sitecodeId,
        int $last_id = 0
    ) {
        $query = $this->createQueryBuilder('alerts')
            ->select('alerts')
            ->leftJoin('TradusBundle:SimilarOfferAlert', 's', Join::WITH, 's.alert = alerts.id')
            ->where('alerts.id > :last_id')
            ->andWhere('alerts.status = :status')
            ->andWhere('s.status = :s_status')
            ->andWhere('alerts.rule_type = :rule_type')
            ->andWhere('alerts.user NOT IN (:user_id)')
            ->andWhere('(
                (alerts.last_send_at IS NULL AND alerts.created_at <= :created_at)
                 OR
                (alerts.last_send_at IS NOT NULL AND alerts.last_send_at <= :last_send_at)
             )')
            ->andWhere('s.sitecodeId = :sitecodeId')
            ->orderBy('alerts.id', 'ASC')
            ->setMaxResults(1)
            ->setParameter('last_id', $last_id)
            ->setParameter('created_at', $createdAt)
            ->setParameter('last_send_at', $lastSendAt)
            ->setParameter('rule_type', $ruleType)
            ->setParameter('status', Alerts::STATUS_ACTIVE)
            ->setParameter('s_status', SimilarOfferAlert::STATUS_SUBSCRIBED)
            ->setParameter('user_id', (! empty($userIds) ? $userIds : ' '))
            ->setParameter('sitecodeId', $sitecodeId)
            ->getQuery();

        return $query->getOneOrNullResult();
    }

    /**
     * @param int $userId
     * @param DateTime $startCountDate
     * @return int
     * @throws DBALException
     */
    public function countAlertsSend(int $userId, DateTime $startCountDate)
    {
        $connection = $this->getEntityManager()->getConnection();
        $sql = 'SELECT count(*) as count FROM alerts 
                WHERE user_id = :user_id AND `status` = :status AND last_send_at >= :start_count_date;';

        $stmt = $connection->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'start_count_date' => $startCountDate->format('Y-m-d H:i:s'),
            'status' => Alerts::STATUS_ACTIVE,
        ]);

        // returns an array of arrays (i.e. a raw data set)
        $result = $stmt->fetchAll();

        return $result[0]['count'];
    }

    /**
     * @param string $timeFrame
     * @param int $maxUpdates
     * @return string
     */
    public function maxAlertSentUsers(string $timeFrame, int $maxUpdates, int $sitecodeId)
    {
        $startCountDate = new DateTime();
        $startCountDate->modify('-'.$timeFrame);

        $connection = $this->getEntityManager()->getConnection();
        $sql = 'SELECT count(id) as alertCount, user_id  FROM alerts
                  WHERE `status` = :status AND last_send_at >= :start_count_date AND sitecode_id = :sitecodeId
                  group by user_id
                  HAVING alertCount > :max_update';
        $stmt = $connection->prepare($sql);
        $stmt->execute([
            'start_count_date' => $startCountDate->format('Y-m-d H:i:s'),
            'status' => Alerts::STATUS_ACTIVE,
            'max_update' => $maxUpdates,
            'sitecodeId' => $sitecodeId,
        ]);

        return ! empty($stmt->fetchAll()) ? implode(',', array_column($stmt->fetchAll(), 'user_id')) : '';
    }

    /**
     * Get user alert by rule.
     *
     * @param TradusUser $user
     * @param int $ruleType
     * @param string $ruleString
     * @return mixed
     */
    public function checkUserAlertExist(TradusUser $user, int $ruleType, string $ruleString)
    {
        return $this->createQueryBuilder('alerts')
            ->select('alerts')
            ->where('alerts.rule = :rule')
            ->andWhere('alerts.user = :user_id')
            ->andWhere('alerts.status = :status')
            ->andWhere('alerts.rule_type = :rule_type')
            ->setParameter('rule', $ruleString)
            ->setParameter('user_id', $user->getId())
            ->setParameter('status', Alerts::STATUS_ACTIVE)
            ->setParameter('rule_type', $ruleType)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get user alert by rule identifier.
     *
     * @param TradusUser $user
     * @param int $ruleType
     * @param string $ruleString
     * @param null $offerId
     * @param int $sitecodeId
     * @return mixed
     * @throws NonUniqueResultException
     */
    public function checkUserAlertIdentifierExist(
        TradusUser $user,
        int $ruleType,
        string $ruleString,
        $offerId = null,
        $sitecodeId = Sitecodes::SITECODE_TRADUS
    ) {
        $ruleString = implode('', explode(chr(92), $ruleString));
        $ruleIdentifier = md5($ruleString);
        $qb = $this->createQueryBuilder('alerts')
            ->select('alerts')
            ->where('alerts.rule_identifier = :rule_identifier')
            ->andWhere('alerts.user = :user_id')
            ->andWhere('alerts.rule_type = :rule_type')
            ->andWhere('alerts.sitecodeId = :sitecodeId')
            ->setParameter('rule_identifier', $ruleIdentifier)
            ->setParameter('user_id', $user->getId())
            ->setParameter('rule_type', $ruleType)
            ->setParameter('sitecodeId', $sitecodeId);

        if ($offerId) {
            $qb->leftJoin('TradusBundle:SimilarOfferAlert', 's', Join::WITH, 's.alert = alerts.id')
                ->andWhere('s.offer = :s_offer')
                ->andWhere('s.sitecodeId = :sitecodeId')
                ->setParameter('s_offer', $offerId)
                ->setParameter('sitecodeId', $sitecodeId);
        }

        return $qb->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Function buildAlertRule.
     * @param string | null $make
     * @param int | null $category
     * @param int | null $type
     * @param int | null $subtype
     * @param array | null $country
     * @param float |null $priceFrom
     * @param float |null $priceTo
     * @param int |null $yearFrom
     * @param int |null $yearTo
     * @return array
     */
    public static function buildAlertRule(
        $make,
        $category,
        $type,
        $subtype,
        $country,
        $priceFrom,
        $priceTo,
        $yearFrom,
        $yearTo
    ): array {
        $make = ! empty($make) && is_array($make) && (int) $category == 1 ? $make[0] : $make;

        return [
            'make' => ! empty($make) ? $make : self::ALERT_DEFAULT_MAKE,
            'category' => ! empty($category) ? (int) $category : self::ALERT_DEFAULT_CATEGORY,
            'type' => ! empty($type) ? (int) $type : self::ALERT_DEFAULT_TYPE,
            'subtype' => ! empty($subtype) ? (int) $subtype : self::ALERT_DEFAULT_SUBTYPE,
            'country' => ! empty($country) ? $country : self::ALERT_DEFAULT_COUNTRY,
            'price' => [
                'min' => (! empty($priceFrom) && ! empty($priceTo))
                    ? (float) $priceFrom : self::ALERT_DEFAULT_PRICE_LO,
                'max' => (! empty($priceFrom) && ! empty($priceTo))
                    ? (float) $priceTo : self::ALERT_DEFAULT_PRICE_HI,
            ],
            'year' => [
                'min' => (! empty($yearFrom) && ! empty($yearTo))
                    ? (int) $yearFrom : self::ALERT_DEFAULT_YEAR_LO,
                'max' => (! empty($yearFrom) && ! empty($yearTo))
                    ? (int) $yearTo : self::ALERT_DEFAULT_YEAR_HI,
            ],
        ];
    }
}

<?php

namespace TradusBundle\Service\Alerts\Rules;

use DateTime;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Component\Translation\Translator;
use TradusBundle\Entity\Alerts;
use TradusBundle\Entity\Sitecodes;
use TradusBundle\Entity\TradusUser;
use TradusBundle\Repository\AlertsRepository;

/**
 * Class BaseAlertRule.
 */
class BaseAlertRule
{
    /** @var EntityManager */
    protected $entityManager;

    /** @var AlertsRepository */
    protected $alertsRepository;

    /** @var ObjectRepository|EntityRepository */
    protected $similarOfferAlertRepository;

    /** @var Alerts */
    protected $entity;

    /** @var int */
    protected $type;

    /** @var array */
    protected $options = [];

    /** @var TradusUser */
    protected $user;

    /** @var int */
    protected $sitecodeId;

    /** @var array */
    protected $rule = [];

    protected $container;
    /** @var Translator */
    protected $translator;

    /**
     * BaseAlertRule constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->alertsRepository = $this->entityManager->getRepository('TradusBundle:Alerts');
        $this->similarOfferAlertRepository = $this->entityManager->getRepository('TradusBundle:SimilarOfferAlert');

        global $kernel;
        $this->container = $kernel->getContainer();
        $this->translator = $this->container->get('translator');
    }

    /**
     * @return TradusUser
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param TradusUser $user
     * @return BaseAlertRule
     */
    public function setUser(TradusUser $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @param mixed $sitecodeId
     * @return BaseAlertRule
     */
    public function setSitecodeId($sitecodeId): self
    {
        $this->sitecodeId = $sitecodeId;

        return $this;
    }

    /**
     * Get options value.
     * @param $key
     * @return bool|mixed
     */
    public function getOption($key)
    {
        if (! array_key_exists($key, $this->options)) {
            return false;
        }

        return $this->options[$key];
    }

    /**
     * Set options value.
     *
     * @param $key
     * @param $value
     * @return bool
     */
    public function setOption($key, $value)
    {
        if (! array_key_exists($key, $this->options)) {
            return false;
        }

        $this->options[$key] = $value;

        return true;
    }

    /**
     * Get options value.
     *
     * @param $key
     * @param $value
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     * @return BaseAlertRule
     */
    public function setType(int $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return array
     */
    public function getRule(): array
    {
        return $this->rule;
    }

    /**
     * @param array $rule
     * @return BaseAlertRule
     */
    public function setRule(?array $rule = null): self
    {
        $this->rule = $rule;

        return $this;
    }

    /**
     * Creates the rule we need for the alert.
     */
    public function createRule()
    {
        foreach ($this->options as $optionName => $optionValue) {
            $this->rule[$optionName] = $optionValue;
        }
    }

    /**
     * Creates a new Alerts Entity.
     * @return Alerts
     */
    public function createNewEntity()
    {
        $alertsEntity = new Alerts();
        $alertsEntity->setUser($this->getUser());
        $alertsEntity->setRuleType($this->getType());
        $alertsEntity->setStatus(Alerts::STATUS_ACTIVE);
        $alertsEntity->setCreatedAt(new DateTime());
        $alertsEntity->setUpdatedAt(new DateTime());

        return $alertsEntity;
    }

    public function loadFromEntity(Alerts $alertEntity)
    {
        $this->entity = $alertEntity;
        $rules = json_decode($alertEntity->getRule(), true);
        if (is_array($rules)) {
            $this->setRule($rules);
            $this->setType($alertEntity->getRuleType());
            foreach ($rules as $ruleName => $ruleValue) {
                $this->setOption($ruleName, $ruleValue);
            }
            $this->setUser($alertEntity->getUser());
            if ($entitySitecodeId = $alertEntity->getSitecodeId()) {
                $this->setSitecodeId($entitySitecodeId);
            }
        }
    }

    /**
     * Saves the entity.
     *
     * @param int $sitecodeId
     * @return Alerts|null
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws NonUniqueResultException
     */
    public function save(int $sitecodeId = Sitecodes::SITECODE_TRADUS)
    {
        $this->createRule();

        $alertEntity = $this->findMatchingEntityRule($sitecodeId);
        if (! $alertEntity) {
            $alertEntity = $this->createNewEntity();
            $ruleJson = json_encode($this->parseData($this->getRule()));
            $ruleJson = implode('', explode(chr(92), $ruleJson));
            $alertEntity->setRule($ruleJson);
            $alertEntity->setRuleIdentifier(md5($ruleJson));
            $alertEntity->setSitecodeId($sitecodeId);
        } else {
            $alertEntity->setStatus(Alerts::STATUS_ACTIVE);
        }
        $this->entityManager->persist($alertEntity);
        $this->entityManager->flush();
        $this->entity = $alertEntity;

        return $this->entity;
    }

    /**
     * @param int $sitecodeId
     * @return Alerts|null
     * @throws NonUniqueResultException
     */
    public function findMatchingEntityRule(int $sitecodeId)
    {
        $ruleIdentifier = md5(json_encode($this->getRule()));

        return $this->alertsRepository->findExistingRule(
            $this->getUser(),
            $this->getType(),
            $ruleIdentifier,
            $sitecodeId
        );
    }

    /**
     * @return DateTime
     */
    public function getLastUpdateDate()
    {
        $lastSendAt = $this->entity->getLastSendAt();
        $createdAt = $this->entity->getCreatedAt();
        if (! $lastSendAt) {
            return $createdAt;
        }

        return $lastSendAt;
    }

    /**
     * @return Alerts
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Function parseData.
     * */
    public function parseData(array $data): array
    {
        return AlertsRepository::buildAlertRule(
            $data['make'],
            $data['category'],
            $data['type'],
            $data['subtype'],
            $data['country'],
            $data['price']['min'],
            $data['price']['max'],
            $data['year']['min'],
            $data['year']['max']
        );
    }
}

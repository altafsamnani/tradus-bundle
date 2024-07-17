<?php

namespace TradusBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Journal.
 * @ORM\Table(name="journal")
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\JournalRepository")
 */
class Journal
{
    public const ACTION_USER_LOGIN = 'user login';
    public const ACTION_USER_REGISTRATION = 'user registration';
    public const ACTION_USER_LOGOUT = 'user logout';
    public const ACTION_USER_CHANGED_PASS = 'user changed password';
    public const ACTION_USER_CHANGED_DATA = 'user changed data';
    public const ACTION_USER_SUGGESTIONS = 'suggestions';
    public const DEFAULT_ANONYMOUS_USER_ID = 1;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /** @ORM\Column(name="action", type="string", length=100, nullable=false) */
    private $action;

    /** @ORM\Column(name="title", type="string", length=255, nullable=false) */
    private $title;

    /** @ORM\Column(name="description", type="text", nullable=false) */
    private $description;

    /**
     * @ORM\ManyToOne(targetEntity="TradusBundle\Entity\TradusUser")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $userId;

    /**
     * @ORM\ManyToOne(targetEntity="TradusBundle\Entity\Seller")
     * @ORM\JoinColumn(name="seller_id", referencedColumnName="id")
     */
    private $sellerId;

    /** @ORM\Column(name="resource", type="integer", nullable=true) */
    private $resource;

    /** @ORM\Column(name="agent", type="text", nullable=false) */
    private $agent;

    /** @ORM\Column(name="client", type="integer", nullable=true) */
    private $client;

    /** @ORM\Column(name="execution_time", type="integer", nullable=true) */
    private $executionTime;

    /**
     * @var string
     *
     * @ORM\Column(name="ip", type="string", length=255, nullable=true)
     */
    protected $ip;

    /**
     * @var DateTime
     * @Assert\DateTime()
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var DateTime
     * @Assert\DateTime()
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private $updatedAt;

    /**
     * @var DateTime
     * @Assert\DateTime()
     *
     * @ORM\Column(name="deleted_at", type="datetime", nullable=true)
     */
    private $deletedAt;

    /**
     * @ORM\ManyToOne(targetEntity="TradusBundle\Entity\Sitecodes", inversedBy="journal")
     * @ORM\JoinColumn(name="sitecode_id", referencedColumnName="id")
     */
    private $sitecode;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set action.
     *
     * @param string $action
     *
     * @return Journal
     */
    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Get action.
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return Journal
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return Journal
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set userId.
     *
     * @param int|null $userId
     *
     * @return Journal
     */
    public function setUser($userId = null)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return int|null
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set sellerId.
     *
     * @param int|null $sellerId
     *
     * @return Journal
     */
    public function setSeller($sellerId = null)
    {
        if (! empty($sellerId)) {
            $this->sellerId = $sellerId;
        }

        return $this;
    }

    /**
     * Get sellerId.
     *
     * @return int|null
     */
    public function getSellerId()
    {
        return $this->sellerId;
    }

    /**
     * Set resource.
     *
     * @param int|null $resource
     *
     * @return Journal
     */
    public function setResource($resource = null)
    {
        $this->resource = $resource;

        return $this;
    }

    /**
     * Get resource.
     *
     * @return int|null
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Set agent.
     *
     * @param string $agent
     *
     * @return Journal
     */
    public function setAgent($agent)
    {
        $this->agent = $agent;

        return $this;
    }

    /**
     * Get agent.
     *
     * @return string
     */
    public function getAgent()
    {
        return $this->agent;
    }

    /**
     * Set client.
     *
     * @param int $client
     *
     * @return Journal
     */
    public function setClient($client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Get client.
     *
     * @return int
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Set executionTime.
     *
     * @param int|null $executionTime
     *
     * @return Journal
     */
    public function setExecutionTime($executionTime = null)
    {
        $this->executionTime = $executionTime;

        return $this;
    }

    /**
     * Get executionTime.
     *
     * @return int|null
     */
    public function getExecutionTime()
    {
        return $this->executionTime;
    }

    /**
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     */
    public function setIp(string $ip): void
    {
        $this->ip = trim($ip);
    }

    /**
     * Set deletedAt.
     *
     * @param DateTime|null $deletedAt
     *
     * @return Journal
     */
    public function setDeletedAt($deletedAt = null)
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * Get deletedAt.
     *
     * @return DateTime|null
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    /**
     * Set createdAt.
     *
     * @param DateTime $createdAt
     *
     * @return Journal
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt.
     *
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set updatedAt.
     *
     * @param DateTime|null $updatedAt
     *
     * @return Journal
     */
    public function setUpdatedAt($updatedAt = null)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt.
     *
     * @return DateTime|null
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @return Sitecodes
     */
    public function getSitecode()
    {
        return $this->sitecode ? $this->sitecode : Sitecodes::SITECODE_TRADUS;
    }

    /**
     * @param mixed $sitecode
     */
    public function setSitecode(Sitecodes $sitecode): void
    {
        $this->sitecode = $sitecode ? $sitecode : Sitecodes::SITECODE_TRADUS;
    }
}

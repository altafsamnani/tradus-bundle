<?php

namespace TradusBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation\Exclude;
use Symfony\Component\Validator\Constraints as Assert;
use TradusBundle\Service\Sitecode\SitecodeService;

/**
 * @ORM\Table(name="app_data")
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\AppDataRepository")
 */
class AppData
{
    public const STATUS_ACTIVE = 100;
    public const STATUS_INACTIVE = -100;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /** @ORM\Column(name="deviceid", type="string", length=50, nullable=true) */
    private $deviceid;

    /** @ORM\Column(name="platform", type="string", length=200, nullable=true) */
    private $platform;

    /** @ORM\Column(name="deviceos", type="string", length=200, nullable=true) */
    private $deviceOs;

    /** @ORM\Column(name="pushtoken", type="string", unique=true, length=250, nullable=false) */
    private $pushtoken;

    /** @ORM\Column(name="lang", type="string", nullable=false) */
    private $lang;

    /**
     * @var int
     *
     * @ORM\Column(name="sitecode_id", type="integer", nullable=true)
     * @Assert\Type("integer")
     */
    private $sitecodeId;

    /**
     * @var TradusUser
     *
     * @ORM\ManyToOne(targetEntity="TradusUser")
     *
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true)
     * @Assert\Type("object")
     *
     * @Exclude
     */
    private $userId;

    /** @ORM\Column(name="useragent", type="string", length=500, nullable=true) */
    private $userAgent;

    /**
     * @var DateTime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime",options={"default": "CURRENT_TIMESTAMP"})
     * @Assert\DateTime()
     */
    protected $created_at;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="welcomed_at", type="datetime",options={"default": NULL})
     * @Assert\DateTime()
     */
    protected $welcomedAt;

    /**
     * @var int
     *
     * @Assert\Type("integer")
     * @Assert\NotBlank(message = "The status must be set.")
     *
     * @ORM\Column(name="status", type="integer")
     */
    private $status;

    public function __construct()
    {
        $sitecodeService = new SitecodeService();
        $this->lang = $sitecodeService->getDefaultLocale();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * Set user.
     *
     * @param TradusUser|null $user
     *
     * @return $this
     */
    public function setUserId(?TradusUser $userId = null)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get user.
     *
     * @return TradusUser|null
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set Device id.
     *
     * @param string $deviceid
     *
     * @return Deviceid
     */
    public function setDeviceId($deviceid)
    {
        $this->deviceid = $deviceid;

        return $this;
    }

    /**
     * Get Device id.
     *
     * @return string
     */
    public function getDeviceId()
    {
        return $this->deviceid;
    }

    /**
     * Set Platform.
     *
     * @param string $platform
     *
     * @return Platform
     */
    public function setPlatform($platform)
    {
        $this->platform = $platform;

        return $this;
    }

    /**
     * Get Platform.
     *
     * @return string
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * Set Device OS.
     *
     * @param string $device
     *
     * @return DeviceOS
     */
    public function setDeviceOS($deviceOs)
    {
        $this->deviceOs = $deviceOs;

        return $this;
    }

    /**
     * Get Device OS.
     *
     * @return string
     */
    public function getDeviceOs()
    {
        return $this->deviceOs;
    }

    /**
     * Set Pushtoken.
     *
     * @param string $pushtoken
     *
     * @return Pushtoken
     */
    public function setPushToken($pushtoken)
    {
        $this->pushtoken = $pushtoken;

        return $this;
    }

    /**
     * Get Pushtoken.
     *
     * @return string
     */
    public function getPushToken()
    {
        return $this->pushtoken;
    }

    /**
     * Set User Agent.
     *
     * @param string $userAgent
     *
     * @return userAgent
     */
    public function setUserAgent($userAgent)
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    /**
     * Get User Agent.
     *
     * @return string
     */
    public function getUserAgent()
    {
        return $this->userAgent;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->created_at;
    }

    /**
     * @param DateTime $created_at
     */
    public function setCreatedAt(DateTime $created_at): void
    {
        $this->created_at = $created_at;
    }

    /**
     * Set status.
     *
     * @param int|null $status
     *
     * @return AppData
     */
    public function setStatus($status = null)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return int|null
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return mixed
     */
    public function getLang()
    {
        return $this->lang;
    }

    /**
     * @param mixed $lang
     */
    public function setLang($lang): void
    {
        $this->lang = $lang;
    }

    /**
     * @return DateTime
     */
    public function getWelcomedAt(): DateTime
    {
        return $this->welcomedAt;
    }

    /**
     * @param DateTime $welcomedAt
     */
    public function setWelcomedAt(DateTime $welcomedAt): void
    {
        $this->welcomedAt = $welcomedAt;
    }

    /**
     * @return int
     */
    public function getSitecodeId(): int
    {
        return $this->sitecodeId;
    }

    /**
     * @param int $sitecodeId
     */
    public function setSitecodeId(int $sitecodeId): void
    {
        $this->sitecodeId = $sitecodeId;
    }
}

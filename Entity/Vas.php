<?php

namespace TradusBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Vas.
 *
 * @ORM\Table(name="vas")
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\VasRepository")
 */
class Vas
{
    public const STATUS_ONLINE = 100;
    public const STATUS_OFFLINE = -100;
    public const HOMEPAGE = 1;
    public const TOP_AD = 2;
    public const HIGHLIGHTS = 3;
    public const DAY_OFFER = 4;
    public const BUMP_UP = 5;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="integer", nullable=false, options={"default"="100"})
     */
    private $status = self::STATUS_ONLINE;

    /**
     * @var int
     *
     * @ORM\Column(name="sitecode_id", type="integer", nullable=false, options={"default"="1"})
     */
    private $sitecodeId;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return Vas
     */
    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     * @return Vas
     */
    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @param int $status
     * @return Vas
     */
    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return int
     */
    public function getSitecodeId(): int
    {
        return $this->sitecodeId ? $this->sitecodeId : Sitecodes::SITECODE_TRADUS;
    }

    /**
     * @param int $sitecodeId
     */
    public function setSitecodeId($sitecodeId = null): void
    {
        $this->sitecodeId = $sitecodeId ? $sitecodeId : Sitecodes::SITECODE_TRADUS;
    }
}

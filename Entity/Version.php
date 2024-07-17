<?php

namespace TradusBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * Versions.
 *
 * @ORM\Table(name="versions")
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\VersionRepository")
 */
class Version
{
    public const STATUS_ACTIVE = 1;
    public const REDIS_NAMESPACE_VERSIONS = 'versions:';
    public const REDIS_EXPIRATION_VERSIONS = 7200;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false, options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="version_name", type="string", length=255, nullable=false)
     */
    private $versionName;

    /**
     * @var string
     *
     * @ORM\Column(name="version_slug", type="string", length=255, nullable=false)
     */
    private $versionSlug;

    /**
     * @var int|null
     *
     * @ORM\Column(name="model_id", type="integer", nullable=true)
     */
    private $modelId;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="status", type="boolean", nullable=true, options={"default"="1"})
     */
    private $status = '1';

    /**
     * @var DateTime|null
     *
     * @ORM\Column(name="deleted_at", type="datetime", nullable=true)
     */
    private $deletedAt;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     */
    private $updatedAt;

    /**
     * @return string
     */
    public function getVersionName(): string
    {
        return $this->versionName;
    }

    /**
     * @param string $versionName
     * @return Version
     */
    public function setVersionName(string $versionName): self
    {
        $this->versionName = $versionName;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getStatus(): ?bool
    {
        return $this->status;
    }

    /**
     * @param bool|null $status
     * @return Version
     */
    public function setStatus(?bool $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getDeletedAt(): ?DateTime
    {
        return $this->deletedAt;
    }

    /**
     * @param DateTime|null $deletedAt
     * @return Version
     */
    public function setDeletedAt(?DateTime $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime|null $createdAt
     * @return Version
     */
    public function setCreatedAt(?DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @param DateTime|null $updatedAt
     * @return Version
     */
    public function setUpdatedAt(?DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return int
     */
    public function getId(): int
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
     * @return string
     */
    public function getVersionSlug(): string
    {
        return $this->versionSlug;
    }

    /**
     * @param string $versionSlug
     */
    public function setVersionSlug(string $versionSlug): void
    {
        $this->versionSlug = $versionSlug;
    }

    /**
     * @return int|null
     */
    public function getModelId(): ?int
    {
        return $this->modelId;
    }

    /**
     * @param int|null $modelId
     */
    public function setModelId(?int $modelId): void
    {
        $this->modelId = $modelId;
    }
}

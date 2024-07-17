<?php

namespace TradusBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="buyer_experience")
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\BuyerExperienceRepository")
 */
class BuyerExperience
{
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
     * @ORM\Column(name="types", type="string", length=255, nullable=true)
     */
    private $types;

    /**
     * @var string|null
     *
     * @ORM\Column(name="categories", type="string", length=255, nullable=true)
     */
    private $categories;

    /**
     * @var string|null
     *
     * @ORM\Column(name="goals", type="string", length=255, nullable=true)
     */
    private $goals;

    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    private $userId;

    /**
     * @var int
     *
     * @ORM\Column(name="sitecode_id", type="integer", nullable=false, options={"default"="1"})
     */
    private $sitecodeId;

    /**
     * @var DateTime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="update")
     */
    private $updatedAt;

    /**
     * @return array|null
     */
    public function getTypes(): ?array
    {
        return json_decode($this->types);
    }

    /**
     * @return array|null
     */
    public function getCategories(): ?array
    {
        return json_decode($this->categories);
    }

    /**
     * @return array|null
     */
    public function getGoals(): ?array
    {
        return json_decode($this->goals);
    }

    /**
     * @return int|null
     */
    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /**
     * @return int|null
     */
    public function getSitecodeId(): ?int
    {
        return $this->sitecodeId;
    }

    /**
     * @param string|null $types
     */
    public function setTypes(?string $types): void
    {
        $this->types = $types;
    }

    /**
     * @param int $userId
     */
    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    /**
     * @param string|null $goals
     */
    public function setGoals(?string $goals): void
    {
        $this->goals = $goals;
    }

    /**
     * @param DateTime $createdAt
     */
    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @param string|null $categories
     */
    public function setCategories(?string $categories): void
    {
        $this->categories = $categories;
    }

    /**
     * @param int $sitecodeId
     */
    public function setSitecodeId(int $sitecodeId): void
    {
        $this->sitecodeId = $sitecodeId;
    }

    /**
     * @param DateTime $updatedAt
     */
    public function setUpdatedAt(DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}

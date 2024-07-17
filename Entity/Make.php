<?php

namespace TradusBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;

/**
 * Make.
 *
 * @ORM\Table(name="makes")
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\MakeRepository")
 */
class Make
{
    public function __toString()
    {
        return $this->getName();
    }

    const STATUS_ONLINE = 100;
    const STATUS_OFFLINE = -10;
    const STATUS_DELETED = -200;

    /* Constant for Other Make Value */
    const OTHERS_VALUE_LIST = ['other'];

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(length=128, unique=false, nullable=true)
     */
    private $slug;

    /**
     * @var int
     *
     * @ORM\Column(name="v1_id", type="integer", nullable=true)
     * @Exclude()
     */
    private $v1_id;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="integer", nullable=true)
     * @Exclude
     */
    private $status;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Make
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set slug.
     *
     * @param string $slug
     *
     * @return Make
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug.
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set status.
     *
     * @param int $status
     *
     * @return Make
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set v1Id.
     *
     * @param int|null $v1Id
     *
     * @return Make
     */
    public function setV1Id($v1Id = null)
    {
        $this->v1_id = $v1Id;

        return $this;
    }

    /**
     * Get v1Id.
     *
     * @return int|null
     */
    public function getV1Id()
    {
        return $this->v1_id;
    }
}

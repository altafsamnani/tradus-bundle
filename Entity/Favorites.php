<?php

namespace TradusBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation\Exclude;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\FavoritesRepository")
 * @ORM\Table(name="favorites")
 */
class Favorites implements OfferInterface
{
    public const STATUS_FAVORITE = 10;
    public const STATUS_UNFAVORITE = -10;
    public const STATUS_INVALID_OFFER = -5;

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var TradusUser
     *
     * @ORM\ManyToOne(targetEntity="TradusBundle\Entity\TradusUser")
     *
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * @Assert\Type("object")
     * @Assert\NotBlank(message = "The user must be set.")
     *
     * @Exclude
     */
    private $user;

    /**
     * @var Offer
     *
     * @ORM\ManyToOne(targetEntity="TradusBundle\Entity\Offer")
     *
     * @ORM\JoinColumn(name="offer_id", referencedColumnName="id")
     * @Assert\Type("object")
     * @Assert\NotBlank(message = "The offer must be set.")
     *
     * @Exclude
     */
    private $offer;

    /**
     * @var int
     *
     * @Assert\Type("integer")
     * @Assert\NotBlank(message = "The status must be set.")
     *
     * @ORM\Column(name="status", type="integer")
     */
    private $status;

    /**
     * @var int
     *
     * @ORM\Column(name="sitecode_id", type="integer", nullable=false, options={"default"="1"})
     * @Assert\Type("integer")
     */
    private $sitecodeId;

    /**
     * @var DateTime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime")
     * @Assert\DateTime()
     */
    protected $created_at;

    /**
     * @var DateTime
     * @Assert\DateTime()
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private $updated_at;

    /**
     * Set id.
     *
     * @param int $id
     *
     * @return Favorites
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

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
     * Set user.
     *
     * @param TradusUser|null $user
     *
     * @return $this
     */
    public function setUser(?TradusUser $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     *
     * @return TradusUser|null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set offer.
     *
     * @param Offer|null $offer
     *
     * @return Favorites
     */
    public function setOffer(?Offer $offer = null)
    {
        $this->offer = $offer;

        return $this;
    }

    /**
     * Get offer.
     *
     * @return Offer|null
     */
    public function getOffer()
    {
        return $this->offer;
    }

    /**
     * Set status.
     *
     * @param int|null $status
     *
     * @return Favorites
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
     * Set createdAt.
     *
     * @param DateTime $createdAt
     *
     * @return Favorites
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;

        return $this;
    }

    /**
     * Get createdAt.
     *
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set updatedAt.
     *
     * @param DateTime $updatedAt
     *
     * @return Favorites
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updated_at = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt.
     *
     * @return DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
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

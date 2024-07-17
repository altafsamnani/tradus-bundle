<?php

namespace TradusBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * SellerReview.
 *
 * @ORM\Table(name="seller_reviews", indexes={@ORM\Index(name="seller_id_index", columns={"seller_id"}), @ORM\Index(name="user_id_index", columns={"user_id"}), @ORM\Index(name="offer_id_index", columns={"offer_id"})})
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\SellerReviewRepository")
 */
class SellerReview
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="bigint", nullable=false, options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * Rating 0 is for when the seller did not respond.
     *
     * @var int
     *
     * @ORM\Column(name="rating", type="integer", nullable=false)
     */
    private $rating;

    /**
     * @var string|null
     *
     * @ORM\Column(name="review_items", type="string", length=255, nullable=true)
     */
    private $reviewItems;

    /**
     * @var string|null
     *
     * @ORM\Column(name="extra", type="text", length=0, nullable=true)
     */
    private $extra;

    /**
     * @var string|null
     *
     * @ORM\Column(name="comment", type="text", length=0, nullable=true)
     */
    private $comment;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $createdAt = 'CURRENT_TIMESTAMP';

    /**
     * @var TradusUser
     *
     * @ORM\ManyToOne(targetEntity="TradusUser")
     * @ORM\JoinColumns({
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * })
     */
    private $user;

    /**
     * @var Offer
     *
     * @ORM\ManyToOne(targetEntity="Offer")
     * @ORM\JoinColumns({
     * @ORM\JoinColumn(name="offer_id", referencedColumnName="id")
     * })
     */
    private $offer;

    /**
     * @var Seller
     *
     * @ORM\ManyToOne(targetEntity="Seller")
     * @ORM\JoinColumns({
     * @ORM\JoinColumn(name="seller_id", referencedColumnName="id")
     * })
     */
    private $seller;

    /**
     * @var string
     *
     * @ORM\Column(name="locale", type="string", length=255, nullable=false)
     */
    private $locale;

    /**
     * @var int
     *
     * @ORM\Column(name="sitecode_id", type="integer", nullable=false, options={"default"=1})
     */
    private $sitecode;

    /**
     * @return int
     */
    public function getRating(): int
    {
        return $this->rating;
    }

    /**
     * @param int $rating
     */
    public function setRating(int $rating): void
    {
        $this->rating = $rating;
    }

    /**
     * @return string|null
     */
    public function getReviewItems(): ?string
    {
        return $this->reviewItems;
    }

    /**
     * @param string $reviewItems
     */
    public function setReviewItems(?string $reviewItems): void
    {
        $this->reviewItems = $reviewItems;
    }

    /**
     * @return string|null
     */
    public function getExtra(): ?string
    {
        return $this->extra;
    }

    /**
     * @param string|null $extra
     */
    public function setExtra(?string $extra): void
    {
        $this->extra = $extra;
    }

    /**
     * @return string|null
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    /**
     * @param string|null $comment
     */
    public function setComment(?string $comment): void
    {
        $this->comment = $comment;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     */
    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return TradusUser
     */
    public function getUser(): TradusUser
    {
        return $this->user;
    }

    /**
     * @param TradusUser $user
     */
    public function setUser(TradusUser $user): void
    {
        $this->user = $user;
    }

    /**
     * @return Offer
     */
    public function getOffer(): Offer
    {
        return $this->offer;
    }

    /**
     * @param Offer $offer
     */
    public function setOffer(Offer $offer): void
    {
        $this->offer = $offer;
    }

    /**
     * @return Seller
     */
    public function getSeller(): Seller
    {
        return $this->seller;
    }

    /**
     * @param Seller $seller
     */
    public function setSeller(Seller $seller): void
    {
        $this->seller = $seller;
    }

    /**
     * @return string
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * @param string $locale
     */
    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    /**
     * @return int
     */
    public function getSitecode(): int
    {
        return $this->sitecode;
    }

    /**
     * @param int $sitecode
     */
    public function setSitecode(int $sitecode): void
    {
        $this->sitecode = $sitecode;
    }

    public function toArray()
    {
        return [
            'seller_id' => $this->getSeller()->getId(),
            'offer_id' => $this->getOffer()->getId(),
            'user_id' => $this->getUser()->getId(),
            'rating' => $this->getRating(),
            'review_items' => json_decode($this->getReviewItems()),
            'extra' => $this->getExtra(),
            'comment' => $this->getComment(),
            'locale' => $this->getLocale(),
            'created_at' => $this->getCreatedAt(),
            'sitecode_id' => $this->getSitecode(),
        ];
    }
}

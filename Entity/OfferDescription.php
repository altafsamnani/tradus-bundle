<?php

namespace TradusBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation\AccessorOrder;
use JMS\Serializer\Annotation\Exclude;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\OfferDescriptionRepository")
 * @ORM\Table(
 *     name="offer_descriptions",
 *     uniqueConstraints={@ORM\UniqueConstraint(name="locale_title_slug_idx", columns={"locale", "title_slug"})},
 *     uniqueConstraints={@ORM\UniqueConstraint(name="offer_id_locale_idx", columns={"offer_id", "locale"})},
 * )
 * @AccessorOrder("custom", custom = {"locale", "description"})
 */
class OfferDescription implements OfferDescriptionInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Exclude
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     * @Assert\Type("string")
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     * @Assert\Type("string")
     * @Assert\NotBlank(message = OfferDescriptionInterface::FIELD_TITLE_BLANK_ERROR)
     * @Assert\Length(min=2, max=255)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="title_slug", type="string", length=255, nullable=false)
     * @Assert\Type("string")
     * @Assert\NotBlank(message = OfferDescriptionInterface::FIELD_TITLE_SLUG_BLANK_ERROR)
     * @Assert\Length(min=2, max=255)
     */
    private $title_slug;

    /**
     * @var string
     *
     * @ORM\Column(name="locale", type="string", length=5)
     * @Assert\Type("string")
     * @Assert\NotBlank(message = OfferDescriptionInterface::FIELD_LOCALE_BLANK_ERROR)
     * @Assert\Length(min=2, max=5)
     */
    private $locale;

    /**
     * @var \TradusBundle\Entity\Offer
     *
     * @ORM\ManyToOne(targetEntity="TradusBundle\Entity\Offer", inversedBy="descriptions")
     *
     * @ORM\JoinColumn(name="offer_id", referencedColumnName="id")
     * @Assert\Type("object")
     * @Assert\NotBlank(message = OfferDescriptionInterface::FIELD_OFFER_BLANK_ERROR)
     *
     * @Exclude
     */
    private $offer;

    /**
     * @var \DateTime datetime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime")
     * @Exclude
     */
    private $created_at;

    /**
     * @var \DateTime datetime
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="updated_at", type="datetime")
     * @Exclude
     */
    private $updated_at;

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
     * Set description.
     *
     * @param string $description
     *
     * @return OfferDescription
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
     * Set locale.
     *
     * @param string $locale
     *
     * @return OfferDescription
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Get locale.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set offer.
     *
     * @param \TradusBundle\Entity\Offer $offer
     *
     * @return OfferDescription
     */
    public function setOffer(Offer $offer = null)
    {
        $this->offer = $offer;

        return $this;
    }

    /**
     * Get offer.
     *
     * @return \TradusBundle\Entity\Offer
     */
    public function getOffer()
    {
        return $this->offer;
    }

    /**
     * Set createdAt.
     *
     * @param \DateTime $createdAt
     *
     * @return OfferDescription
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;

        return $this;
    }

    /**
     * Get createdAt.
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set updatedAt.
     *
     * @param \DateTime $updatedAt
     *
     * @return OfferDescription
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updated_at = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt.
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * Set title.
     *
     * @param string|null $title
     *
     * @return OfferDescription
     */
    public function setTitle($title = null)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string|null
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set title_slug.
     *
     * @param string|null $title_slug
     *
     * @return OfferDescription
     */
    public function setTitleSlug($title_slug = null)
    {
        $this->title_slug = $title_slug;

        return $this;
    }

    /**
     * Get title_slug.
     *
     * @return string|null
     */
    public function getTitleSlug()
    {
        return $this->title_slug;
    }
}

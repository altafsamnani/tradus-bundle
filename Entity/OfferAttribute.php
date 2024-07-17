<?php

namespace TradusBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;

/**
 * OfferAttribute.
 *
 * @ORM\Table(name="offer_attributes")
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\OfferAttributeRepository")
 */
class OfferAttribute
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Exclude()
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="TradusBundle\Entity\Offer", inversedBy="attributes")
     * @ORM\JoinColumn(name="offer_id", referencedColumnName="id")
     * @Exclude()
     */
    private $offer;

    /**
     * @ORM\ManyToOne(targetEntity="TradusBundle\Entity\Attribute")
     * @ORM\JoinColumn(name="attribute_id", referencedColumnName="id")
     */
    private $attribute;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="string", length=255, nullable=true)
     */
    private $content;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="integer", nullable=true)
     * @Exclude
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="tamer_status", type="integer", nullable=true)
     * @Exclude
     */
    private $tamerStatus;

    /**
     * @var int
     *
     * @ORM\Column(name="option_id", type="integer", nullable=true)
     * @Exclude
     */
    private $optionId;

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
     * Set offer.
     *
     * @param \TradusBundle\Entity\Offer|null $offer
     *
     * @return OfferAttribute
     */
    public function setOffer(\TradusBundle\Entity\Offer $offer = null)
    {
        $this->offer = $offer;

        return $this;
    }

    /**
     * Get offer.
     *
     * @return \TradusBundle\Entity\Offer|null
     */
    public function getOffer()
    {
        return $this->offer;
    }

    /**
     * Set attribute.
     *
     * @param \TradusBundle\Entity\Attribute|null $attribute
     *
     * @return OfferAttribute
     */
    public function setAttribute(\TradusBundle\Entity\Attribute $attribute = null)
    {
        $this->attribute = $attribute;

        return $this;
    }

    /**
     * Get attribute.
     *
     * @return \TradusBundle\Entity\Attribute|null
     */
    public function getAttribute()
    {
        return $this->attribute;
    }

    /**
     * Set content.
     *
     * @param string|null $content
     *
     * @return OfferAttribute
     */
    public function setContent($content = null)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content.
     *
     * @return string|null
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set status.
     *
     * @param int $status
     *
     * @return OfferAttribute
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return int | null
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set tamer status.
     *
     * @param int $tamerStatus
     *
     * @return OfferAttribute
     */
    public function setTamerStatus($tamerStatus)
    {
        $this->tamerStatus = $tamerStatus;

        return $this;
    }

    /**
     * Get tamer status.
     *
     * @return int | null
     */
    public function getTamerStatus()
    {
        return $this->tamerStatus;
    }

    /**
     * @return int | null
     */
    public function getOptionId()
    {
        return $this->optionId;
    }

    /**
     * @param $optionId
     * @return OfferAttribute
     */
    public function setOptionId($optionId)
    {
        $this->optionId = $optionId;

        return $this;
    }
}

<?php

namespace TradusBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;

/**
 * SellerPreference.
 *
 * @ORM\Table(name="seller_preference")
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\SellerPreferenceRepository")
 */
class SellerPreference
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
     * @ORM\Column(name="language_options", type="string", length=255)
     */
    private $languageOptions;

    /**
     * @var Seller
     *
     * @ORM\OneToOne(targetEntity="Seller", inversedBy="preference")
     * @ORM\JoinColumn(name="seller_id", referencedColumnName="id")
     * @Exclude
     */
    private $seller;

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
     * Set sellerId.
     *
     * @param int $sellerId
     *
     * @return SellerPreference
     */
    public function setSellerId($sellerId)
    {
        $this->sellerId = $sellerId;

        return $this;
    }

    /**
     * Get sellerId.
     *
     * @return int
     */
    public function getSellerId()
    {
        return $this->sellerId;
    }

    /**
     * Set languageOptions.
     *
     * @param array $languageOptions
     *
     * @return SellerPreference
     */
    public function setLanguageOptions(array $languageOptions)
    {
        $this->languageOptions = json_encode($languageOptions);

        return $this;
    }

    /**
     * Get languageOptions.
     *
     * @return string
     */
    public function getLanguageOptions()
    {
        return json_decode($this->languageOptions, true);
    }

    /**
     * Set seller.
     *
     * @param Seller|null $seller
     *
     * @return Seller
     */
    public function setSeller(Seller $seller)
    {
        $this->seller = $seller;

        return $this;
    }

    /**
     * Get seller.
     *
     * @return Seller|null
     */
    public function getSeller()
    {
        return $this->seller;
    }
}

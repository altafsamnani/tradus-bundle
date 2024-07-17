<?php

namespace TradusBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;

/**
 * SellerAdditionalServices.
 *
 * @ORM\Entity()
 * @ORM\Table(name="seller_additional_service")
 */
class SellerAdditionalServices
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
     * @ORM\ManyToOne(targetEntity="TradusBundle\Entity\AdditionalServices", inversedBy="service", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="service_id", referencedColumnName="id")
     * @Exclude
     */
    private $service;

    /**
     * @ORM\ManyToOne(targetEntity="TradusBundle\Entity\Seller", inversedBy="additionalService", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="seller_id", referencedColumnName="id")
     * @Exclude
     */
    private $seller;

    /**
     * @var int
     *
     * @ORM\Column(name="seller_id", type="integer", nullable=false)
     */
    private $sellerId;

    /**
     * @var int
     *
     * @ORM\Column(name="service_id", type="integer", nullable=false)
     */
    private $serviceId;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return AdditionalServices
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * @param AdditionalServices $service
     */
    public function setService(AdditionalServices $service)
    {
        $this->service = $service;
    }

    /**
     * @return mixed
     */
    public function getSeller()
    {
        return $this->seller;
    }

    /**
     * @param mixed $seller
     */
    public function setSeller($seller): void
    {
        $this->seller = $seller;
    }

    /**
     * @return int
     */
    public function getSellerId()
    {
        return $this->sellerId;
    }

    /**
     * @param int $sellerId
     */
    public function setSellerId($sellerId)
    {
        $this->sellerId = $sellerId;
    }

    /**
     * @return int
     */
    public function getServiceId()
    {
        return $this->serviceId;
    }

    /**
     * @param int $serviceId
     */
    public function setServiceId($serviceId)
    {
        $this->serviceId = $serviceId;
    }
}

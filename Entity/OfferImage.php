<?php

namespace TradusBundle\Entity;

use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\ORMException;
use Exception;
use FasterImage\FasterImage;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation\Exclude;

/**
 * OfferImage.
 *
 * @ORM\Table(name="offer_images")
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\OfferImageRepository")
 */
class OfferImage implements OfferImageInterface
{
    public const  LARGE_IMAGE_SIZE_FRAME_RATE = ['width' => 933, 'height' => 700];
    public const  MEDIUM_IMAGE_SIZE_FRAME_RATE = ['width' => 261, 'height' => 196];
    public const  SMALL_IMAGE_SIZE_FRAME_RATE = ['width' => 94, 'height' => 71];

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
     * @ORM\ManyToOne(targetEntity="TradusBundle\Entity\Offer", inversedBy="images")
     * @ORM\JoinColumn(name="offer_id", referencedColumnName="id", nullable=true)
     * @Exclude
     */
    private $offer;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255, nullable=true)
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(name="image_text_found", type="string", length=255, nullable=true)
     */
    private $image_text_found;

    /**
     * @var string
     *
     * @ORM\Column(name="sizes", type="text", nullable=true)
     */
    private $sizes;

    /**
     * @var string
     *
     * @ORM\Column(name="sort_order", type="integer", nullable=true)
     */
    private $sort_order;

    /**
     * @var int
     *
     * @ORM\Column(name="sort_order_pose", type="integer", columnDefinition="TINYINT DEFAULT 0")
     */
    private $sort_order_pose;

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
     * @ORM\Column(name="low_quality", type="integer", nullable=true)
     */
    private $lowQuality;

    /**
     * @var int
     *
     * @ORM\Column(name="size_status", type="integer", columnDefinition="TINYINT DEFAULT 0")
     * @Exclude
     */
    private $sizeStatus = 0;

    /**
     * @var DateTime datetime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime")
     * @Exclude
     */
    private $created_at;

    /**
     * @var DateTime datetime
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
     * Set url.
     *
     * @param string $url
     *
     * @return OfferImage
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set image_text_found.
     *
     * @param string $image_text_found
     *
     * @return OfferImage
     */
    public function setImageTextFound($image_text_found)
    {
        $this->image_text_found = $image_text_found;

        return $this;
    }

    /**
     * Get image_text_found.
     *
     * @return string
     */
    public function getImageTextFound()
    {
        return $this->image_text_found;
    }

    /**
     * Set sortOrder.
     *
     * @param int $sortOrder
     *
     * @return OfferImage
     */
    public function setSortOrder($sortOrder)
    {
        $this->sort_order = $sortOrder;

        return $this;
    }

    /**
     * Set sortOrderPose.
     *
     * @param int $sortOrderPose
     *
     * @return OfferImage
     */
    public function setsortOrderPose($sortOrderPose)
    {
        $this->sort_order_pose = $sortOrderPose;

        return $this;
    }

    /**
     * Get sortOrder.
     *
     * @return int
     */
    public function getSortOrder()
    {
        return $this->sort_order;
    }

    /**
     * Set status.
     *
     * @param int $status
     *
     * @return OfferImage
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
     * Set lowQuality status.
     * @param int $lowQuality
     * @return OfferImage
     */
    public function setLowQuality($lowQuality)
    {
        $this->lowQuality = $lowQuality;

        return $this;
    }

    /**
     * Get lowQuality.
     *
     * @return int
     */
    public function getLowQuality()
    {
        return $this->lowQuality;
    }

    /**
     * Set size_status.
     *
     * @param int $status
     *
     * @return OfferImage
     */
    public function setSizeStatus($status = 0)
    {
        $this->sizeStatus = $status;

        return $this;
    }

    /**
     * Get size_status.
     *
     * @return int
     */
    public function getSizeStatus()
    {
        return $this->sizeStatus;
    }

    /**
     * Set offer.
     *
     * @param Offer $offer
     *
     * @return OfferImage
     */
    public function setOffer(?Offer $offer = null)
    {
        $this->offer = $offer;

        return $this;
    }

    /**
     * Get offer.
     *
     * @return Offer
     */
    public function getOffer()
    {
        return $this->offer;
    }

    /**
     * Set sizes.
     *
     * @param string $sizes
     *
     * @return OfferImage
     */
    public function setSizes($sizes)
    {
        $this->sizes = $sizes;

        return $this;
    }

    /**
     * Get sizes.
     *
     * @return string
     */
    public function getSizes()
    {
        return $this->sizes;
    }

    /**
     * Set createdAt.
     *
     * @param DateTime $createdAt
     *
     * @return OfferImage
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
     * @return OfferImage
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
     * Function for loading the image sizes if not set.
     *
     * @throws Exception
     * @throws ORMException
     */
    public function loadImageSizes(EntityManager $entityManager)
    {
        $client = new FasterImage();
        $sizes = [];
        $urls = [];

        foreach (OfferImageInterface::IMAGE_SIZES as $image_size) {
            $urls[] = $this->getUrl().OfferImageInterface::IMAGE_SIZE_PRESETS[$image_size];
        }

        foreach ($client->batch([$urls]) as $key => $image_new) {
            [$width, $height] = $image_new['size'];
            $sizes[$key] = [
                'width' => $width,
                'height' => $height,
            ];
        }

        $this->setSizes(json_encode($sizes));
        $entityManager->persist($this);
        $entityManager->flush();
    }
}

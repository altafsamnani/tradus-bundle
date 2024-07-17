<?php

namespace TradusBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Apollo.
 *
 * @ORM\Table(name="tradus_apollo")
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\ApolloRepository")
 */
class Apollo
{
    const STATUS_PENDING_DOWNLOAD = 0;
    const STATUS_RETRY_DOWNLOAD = 1;
    const STATUS_PENDING = 5;
    const STATUS_PROCESSING = 10;
    const STATUS_DONE = 100;
    const STATUS_TO_DELETE = -1;
    const STATUS_DELETED = -10;
    const STATUS_ERROR_DOWNLOADING = -100;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="reference_id", type="integer")
     */
    private $reference_id;

    /**
     * @var string
     *
     * @ORM\Column(name="source_url", type="string", length=255, nullable=true)
     */
    private $source_url;

    /**
     * @var string
     *
     * @ORM\Column(name="s3_url", type="string", length=255, nullable=true)
     */
    private $s3_url;

    /**
     * @var string
     *
     * @ORM\Column(name="final_url", type="string", length=255, nullable=true)
     */
    private $final_url;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="integer", nullable=true)
     */
    private $status;

    /**
     * @var int
     *
     * @ORM\Column(name="apollo_filename", type="string", length=255, nullable=true)
     */
    private $apollo_filename;

    /**
     * @var int
     *
     * @ORM\Column(name="mime_type", type="string", length=255, nullable=true)
     */
    private $mime_type;

    /**
     * @var int
     *
     * @ORM\Column(name="height", type="string", length=255, nullable=true)
     */
    private $height;

    /**
     * @var int
     *
     * @ORM\Column(name="width", type="string", length=255, nullable=true)
     */
    private $width;

    /**
     * @var text
     *
     * @ORM\Column(name="sizes", type="text", nullable=true)
     */
    private $sizes;

    /**
     * @var int
     *
     * @ORM\Column(name="display_order", type="integer")
     */
    private $display_order;

    /**
     * @var datetime
     *
     * @ORM\Column(name="expire_at", type="datetime")
     */
    private $expire_at;

    /**
     * @var datetime
     *
     * @ORM\Column(name="delete_at", type="datetime", nullable=true)
     */
    private $delete_at;

    /**
     * @var datetime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $created_at;

    /**
     * @var datetime
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private $updated_at;

    /**
     * @var int
     *
     * @ORM\Column(name="download_attempts", type="integer", options={"default" : 0})
     */
    private $download_attempts;

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
     * Set sourceUrl.
     *
     * @param string $sourceUrl
     *
     * @return Apollo
     */
    public function setSourceUrl($sourceUrl)
    {
        $this->source_url = $sourceUrl;

        return $this;
    }

    /**
     * Get sourceUrl.
     *
     * @return string
     */
    public function getSourceUrl()
    {
        return $this->source_url;
    }

    /**
     * Set finalUrl.
     *
     * @param string $finalUrl
     *
     * @return Apollo
     */
    public function setFinalUrl($finalUrl)
    {
        $this->final_url = $finalUrl;

        return $this;
    }

    /**
     * Get finalUrl.
     *
     * @return string
     */
    public function getFinalUrl()
    {
        return $this->final_url;
    }

    /**
     * Set status.
     *
     * @param int $status
     *
     * @return Apollo
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
     * Set apolloId.
     *
     * @param string $apolloId
     *
     * @return Apollo
     */
    public function setApolloId($apolloId)
    {
        $this->apollo_id = $apolloId;

        return $this;
    }

    /**
     * Get apolloId.
     *
     * @return string
     */
    public function getApolloId()
    {
        return $this->apollo_id;
    }

    /**
     * Set displayOrder.
     *
     * @param int $displayOrder
     *
     * @return Apollo
     */
    public function setDisplayOrder($displayOrder)
    {
        $this->display_order = $displayOrder;

        return $this;
    }

    /**
     * Get displayOrder.
     *
     * @return int
     */
    public function getDisplayOrder()
    {
        return $this->display_order;
    }

    /**
     * Set expireAt.
     *
     * @param \DateTime $expireAt
     *
     * @return Apollo
     */
    public function setExpireAt($expireAt)
    {
        $this->expire_at = $expireAt;

        return $this;
    }

    /**
     * Get expireAt.
     *
     * @return \DateTime
     */
    public function getExpireAt()
    {
        return $this->expire_at;
    }

    /**
     * Set deleteAt.
     *
     * @param \DateTime $deleteAt
     *
     * @return Apollo
     */
    public function setDeleteAt($deleteAt)
    {
        $this->delete_at = $deleteAt;

        return $this;
    }

    /**
     * Get deleteAt.
     *
     * @return \DateTime
     */
    public function getDeleteAt()
    {
        return $this->delete_at;
    }

    /**
     * Set createdAt.
     *
     * @param \DateTime $createdAt
     *
     * @return Apollo
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
     * @return Apollo
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
     * Set apolloFilename.
     *
     * @param string $apolloFilename
     *
     * @return Apollo
     */
    public function setApolloFilename($apolloFilename)
    {
        $this->apollo_filename = $apolloFilename;

        return $this;
    }

    /**
     * Get apolloFilename.
     *
     * @return string
     */
    public function getApolloFilename()
    {
        return $this->apollo_filename;
    }

    /**
     * Set mimeType.
     *
     * @param string $mimeType
     *
     * @return Apollo
     */
    public function setMimeType($mimeType)
    {
        $this->mime_type = $mimeType;

        return $this;
    }

    /**
     * Get mimeType.
     *
     * @return string
     */
    public function getMimeType()
    {
        return $this->mime_type;
    }

    /**
     * Set s3Url.
     *
     * @param string $s3Url
     *
     * @return Apollo
     */
    public function setS3Url($s3Url)
    {
        $this->s3_url = $s3Url;

        return $this;
    }

    /**
     * Get s3Url.
     *
     * @return string
     */
    public function getS3Url()
    {
        return $this->s3_url;
    }

    /**
     * Set referenceId.
     *
     * @param int $referenceId
     *
     * @return Apollo
     */
    public function setReferenceId($referenceId)
    {
        $this->reference_id = $referenceId;

        return $this;
    }

    /**
     * Get referenceId.
     *
     * @return int
     */
    public function getReferenceId()
    {
        return $this->reference_id;
    }

    /**
     * Set height.
     *
     * @param string $height
     *
     * @return Apollo
     */
    public function setHeight($height)
    {
        $this->height = $height;

        return $this;
    }

    /**
     * Get height.
     *
     * @return string
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Set width.
     *
     * @param string $width
     *
     * @return Apollo
     */
    public function setWidth($width)
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Get width.
     *
     * @return string
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Set sizes.
     *
     * @param string $sizes
     *
     * @return Apollo
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
     * Set downloadAttempts.
     *
     * @param int $downloadAttempts
     *
     * @return Apollo
     */
    public function setDownloadAttempts($downloadAttempts)
    {
        $this->download_attempts = $downloadAttempts;

        return $this;
    }

    /**
     * Get downloadAttempts.
     *
     * @return int
     */
    public function getDownloadAttempts()
    {
        return $this->download_attempts;
    }

    /**
     * Method for checking if the file has been (soft)deleted.
     *
     * @return bool
     */
    public function isDeleted()
    {
        $status = $this->getStatus();

        return (
            $status === self::STATUS_TO_DELETE ||
            $status === self::STATUS_DELETED
        ) ? true : false;
    }
}

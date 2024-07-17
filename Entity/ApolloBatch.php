<?php

namespace TradusBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Apollo.
 *
 * @ORM\Table(name="tradus_apollo_batch")
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\ApolloBatchRepository")
 */
class ApolloBatch
{
    const STATUS_PENDING = 0;
    const STATUS_PROCESSING = 10;
    const STATUS_DONE = 100;
    const STATUS_ERROR = -100;

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
     * @ORM\Column(name="batch_id", type="integer")
     */
    private $batch_id;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="integer")
     */
    private $status;

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
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set batchId.
     *
     * @param int $batchId
     *
     * @return ApolloBatch
     */
    public function setBatchId($batchId)
    {
        $this->batch_id = $batchId;

        return $this;
    }

    /**
     * Get batchId.
     *
     * @return int
     */
    public function getBatchId()
    {
        return $this->batch_id;
    }

    /**
     * Set status.
     *
     * @param int $status
     *
     * @return ApolloBatch
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
     * Set createdAt.
     *
     * @param \DateTime $createdAt
     *
     * @return ApolloBatch
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
     * @return ApolloBatch
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
}

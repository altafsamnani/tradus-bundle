<?php

namespace TradusBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * EmailConversation.
 *
 * @ORM\Table(name="emails_conversations")
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\EmailConversationRepository")
 */
class EmailConversation
{
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
     * @ORM\ManyToOne(targetEntity="TradusBundle\Entity\Email")
     * @ORM\JoinColumn(name="email_id", referencedColumnName="id")
     */
    private $emailId;

    /**
     * @var int
     *
     * @ORM\Column(name="is_read", type="boolean", nullable=false)
     */
    private $isRead;

    /**
     * @var int
     *
     * @ORM\Column(name="first_parent_id", type="integer", nullable=false)
     */
    private $firstParentId;

    /**
     * @var int
     *
     * @ORM\Column(name="from_inbox", type="integer", nullable=true)
     */
    private $fromInbox;

    /**
     * @var \DateTime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     */
    private $updatedAt;

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
     * Set emailId.
     *
     * @param int $emailId
     *
     * @return EmailConversation
     */
    public function setEmailId($emailId)
    {
        $this->emailId = $emailId;

        return $this;
    }

    /**
     * Get emailId.
     *
     * @return int
     */
    public function getEmailId()
    {
        return $this->emailId;
    }

    /**
     * Set isRead.
     *
     * @param bool $isRead
     *
     * @return EmailConversation
     */
    public function setIsRead($isRead)
    {
        $this->isRead = $isRead;

        return $this;
    }

    /**
     * Get isRead.
     *
     * @return bool
     */
    public function getIsRead()
    {
        return $this->isRead;
    }

    /**
     * Set firstParentId.
     *
     * @param int|null $firstParentId
     *
     * @return EmailConversation
     */
    public function setFirstParentId($firstParentId = null)
    {
        $this->firstParentId = $firstParentId;

        return $this;
    }

    /**
     * Get firstParentId.
     *
     * @return int|null
     */
    public function getFirstParentId()
    {
        return $this->firstParentId;
    }

    /**
     * Set fromInbox.
     *
     * @param int|null $fromInbox
     *
     * @return EmailConversation
     */
    public function setFromInbox($fromInbox = null)
    {
        $this->fromInbox = $fromInbox;

        return $this;
    }

    /**
     * Get fromInbox.
     *
     * @return int|null
     */
    public function getFromInbox()
    {
        return $this->fromInbox;
    }

    /**
     * Set createdAt.
     *
     * @param \DateTime $createdAt
     *
     * @return EmailConversation
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt.
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set updatedAt.
     *
     * @param \DateTime $updatedAt
     *
     * @return EmailConversation
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt.
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }
}

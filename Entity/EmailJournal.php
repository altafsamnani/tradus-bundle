<?php

namespace TradusBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * EmailJournal.
 *
 * @ORM\Table(name="emails_journal", uniqueConstraints={
 * @ORM\UniqueConstraint(name="sg_event_id", columns={"sg_event_id"})})
 * })
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\EmailJournalRepository")
 *
 * @UniqueEntity(fields={"sg_event_id"})
 */
class EmailJournal
{
    public const EVENT_BOUNCE = 'bounce';       // Receiving server could not or would not accept the message. If a recipient has previously unsubscribed from your emails, the message is bounced.
    public const EVENT_DROPPED = 'dropped';      // You may see the following drop reasons: Invalid SMTPAPI header, Spam Content (if Spam Checker app is enabled), Unsubscribed Address, Bounced Address, Spam Reporting Address, Invalid, Recipient List over Package Quota
    public const EVENT_SPAM = 'spamreport';   // Recipient marked message as spam.
    public const EVENT_DELIVERED = 'delivered';    // Message has been successfully delivered to the receiving server.
    public const EVENT_PROCESSED = 'processed';    // Message has been received and is ready to be delivered.
    public const EVENT_OPEN = 'open';         // Recipient has opened the HTML message. Open Tracking needs to be enabled for this type of event.
    public const EVENT_CLICK = 'click';        // Recipient clicked on a link within the message. Click Tracking needs to be enabled for this type of event.
    public const EVENT_DEFERRED = 'deferred';     // Receiving server temporarily rejected the message.
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="TradusBundle\Entity\Email", inversedBy="emails")
     * @ORM\JoinColumn(name="email_id", referencedColumnName="id")
     */
    private $mails;

    /**
     * @var string
     * @ORM\Column(name="email", type="string", length=255, nullable=false)
     */
    private $email;

    /**
     * @var DateTime
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $created_at;

    /**
     * custom tags that you set for the purpose of organizing your emails.
     * @var string
     * @ORM\Column(name="category", type="string", length=255, nullable=true)
     */
    private $category;

    /**
     * The event type. Possible values are processed, dropped, delivered, deferred, bounce, open, click, spam report, unsubscribe, group unsubscribe, and group resubscribe.
     * @var string
     * @ORM\Column(name="event", type="string", length=255, nullable=true)
     */
    private $event;

    /**
     * any sort of error response returned by the receiving server that describes the reason this event type was triggered.
     * @var string
     * @ORM\Column(name="reason", type="string", length=255, nullable=true)
     */
    private $reason;

    /**
     * a unique ID to this event that you can use for deduplication purposes. These IDs are either 22 or 48 characters long.
     * @var string
     * @ORM\Column(name="sg_event_id", type="string", length=255, nullable=true, unique=true)
     */
    private $sg_event_id;

    /**
     * Unique, internal SendGrid ID for the message. The first half of this is pulled from the smtp-id.
     * @var string
     * @ORM\Column(name="sg_message_id", type="string", length=255, nullable=true)
     */
    private $sg_message_id;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getMails()
    {
        return $this->mails;
    }

    /**
     * @param mixed $mails
     */
    public function setMails($mails): void
    {
        $this->mails = $mails;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->created_at;
    }

    /**
     * @param DateTime $created_at
     */
    public function setCreatedAt(DateTime $created_at): void
    {
        $this->created_at = $created_at;
    }

    /**
     * @return string
     */
    public function getCategory(): string
    {
        return $this->category;
    }

    /**
     * @param $category
     */
    public function setCategory($category): void
    {
        if (is_array($category)) {
            $this->category = $category[0];
        } else {
            $this->category = $category;
        }
    }

    /**
     * @return string
     */
    public function getEvent(): string
    {
        return $this->event;
    }

    /**
     * @param string $event
     */
    public function setEvent(string $event): void
    {
        $this->event = $event;
    }

    /**
     * @return string
     */
    public function getSgEventId(): string
    {
        return $this->sg_event_id;
    }

    /**
     * @param string $sg_event_id
     */
    public function setSgEventId(string $sg_event_id): void
    {
        $this->sg_event_id = $sg_event_id;
    }

    /**
     * @return string
     */
    public function getSgMessageId(): string
    {
        return $this->sg_message_id;
    }

    /**
     * @param string $sg_message_id
     */
    public function setSgMessageId(?string $sg_message_id): void
    {
        $this->sg_message_id = $sg_message_id;
    }

    /**
     * @return string
     */
    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * @param string $reason
     */
    public function setReason(string $reason): void
    {
        $this->reason = $reason;
    }
}

<?php

namespace TradusBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Email.
 *
 * @ORM\Table(name="emails")
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\EmailRepository")
 */
class Email
{
    public const EMAIL_MAX_CAPACITY_BYTES = 17825792; //17 MB leaving 2Mb for headers
    public const STATUS_SENT = 100;
    public const STATUS_PENDING = -10;
    public const STATUS_ERROR = -100;

    public const EMAIL_TYPE_FORM_EMAIL_TO_SELLER = 0;
    public const EMAIL_TYPE_FORM_EMAIL_TO_BUYER = 1;
    public const EMAIL_TYPE_FORM_EMAIL_RESPONSE_BUYER = 3;
    public const EMAIL_TYPE_FORM_EMAIL_RESPONSE = 4;
    public const EMAIL_TYPE_PERFORMANCE_TO_SELLER = 5;
    public const EMAIL_TYPE_SIMILAR_OFFERS_ALERT = 6;
    public const EMAIL_TYPE_CONTACT_TRANSPORT_WHEELS = 7;
    public const EMAIL_TYPE_WEEKLY_SEARCH_ANALYTICS = 8;
    public const EMAIL_TYPE_FAVORITE_OFFERS_REMOVED = 9;
    public const EMAIL_TYPE_BACKEND_MONITORING = 9;
    public const EMAIL_TYPE_CALLBACK_TO_SELLER = 11;
    public const EMAIL_HASH_LENGTH_FOR_DISPLAY = 10;
    public const EMAIL_TYPE_REPORT_ABUSE_REPLY = 12;
    public const EMAIL_TYPE_FORM_REMINDER_EMAIL_TO_SELLER = 13;
    public const EMAIL_TYPE_BUYER_SURVEY = 14;
    public const EMAIL_TYPE_BUYER_NPS_SURVEY = 15;

    public const EMAIL_PLACEHOLDER_FOR_NOTIFICATION = '[tradus.reply.email]';
    public const EMAIL_CATEGORIES = [
        self::EMAIL_TYPE_SIMILAR_OFFERS_ALERT => ['SIMILAR_OFFERS_ALERT'],
        self::EMAIL_TYPE_FORM_EMAIL_TO_BUYER => ['LEAD_FORM_TO_BUYER'],
        self::EMAIL_TYPE_FORM_EMAIL_TO_SELLER => ['LEAD_FORM_TO_SELLER'],
        self::EMAIL_TYPE_CALLBACK_TO_SELLER => ['CALLBACK_FORM_TO_SELLER'],
        self::EMAIL_TYPE_FORM_EMAIL_RESPONSE => ['LEAD_FORM_RESPONSE'],
        self::EMAIL_TYPE_PERFORMANCE_TO_SELLER => ['PERFORMANCE_EMAIL_TO_SELLER'],
        self::EMAIL_TYPE_FORM_EMAIL_RESPONSE_BUYER => ['LEAD_FORM_RESPONSE'],
        self::EMAIL_TYPE_CONTACT_TRANSPORT_WHEELS => ['CONTACT_TRANSPORT_WHEELS'],
        self::EMAIL_TYPE_WEEKLY_SEARCH_ANALYTICS => ['WEEKLY_SEARCH_ANALYTICS'],
        self::EMAIL_TYPE_FAVORITE_OFFERS_REMOVED => ['CONTACT_FAVORITES_USERS'],
        self::EMAIL_TYPE_BACKEND_MONITORING => ['EMAIL_TYPE_BACKEND_MONITORING'],
        self::EMAIL_TYPE_REPORT_ABUSE_REPLY => ['EMAIL_TYPE_REPORT_ABUSE_REPLY'],
    ];

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->attachments = new ArrayCollection();
    }

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="TradusBundle\Entity\Seller", inversedBy="emails")
     * @ORM\JoinColumn(name="seller_id", referencedColumnName="id")
     */
    private $to_seller;

    /**
     * @ORM\ManyToOne(targetEntity="TradusBundle\Entity\Offer", inversedBy="emails")
     * @ORM\JoinColumn(name="offer_id", referencedColumnName="id")
     */
    private $offer;

    /**
     * @ORM\ManyToOne(targetEntity="TradusBundle\Entity\EmailTemplate")
     * @ORM\JoinColumn(name="email_template_id", referencedColumnName="id")
     */
    private $email_template;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="integer", nullable=true)
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="to_offer_id", type="integer", nullable=true)
     */
    private $to_offer_id;

    /**
     * @var string
     *
     * @ORM\Column(name="to_seller_id", type="integer", nullable=true)
     */
    private $to_seller_id;

    /**
     * @var string
     *
     * @ORM\Column(name="user_agent", type="string", length=255, nullable=true)
     */
    private $user_agent;

    /**
     * @var string
     *
     * @ORM\Column(name="ip", type="string", length=255, nullable=true)
     */
    private $ip;

    /**
     * @var string
     *
     * @ORM\Column(name="email_from", type="string", length=255, nullable=true)
     */
    private $email_from;

    /**
     * @var string
     *
     * @ORM\Column(name="email_to", type="string", length=255, nullable=true)
     */
    private $email_to;

    /**
     * @var string
     *
     * @ORM\Column(name="subject", type="string", length=255, nullable=true)
     */
    private $subject;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="text", nullable=true)
     */
    private $message;

    /**
     * @var string
     *
     * @ORM\Column(name="body", type="text", nullable=true)
     */
    private $body;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="sent_at", type="datetime", nullable=true)
     */
    private $sent_at;

    /**
     * @var DateTime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    private $created_at;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="update")
     */
    private $updated_at;

    /**
     * @var string
     *
     * @ORM\Column(name="reply_To", type="string", length=255, nullable=true)
     */
    private $reply_To;

    /**
     * @var string
     *
     * @ORM\Column(name="email_type", type="integer", nullable=true)
     */
    private $email_type;

    /**
     * @var string
     *
     * @ORM\Column(name="message_id", type="string", length=255, nullable=true)
     */
    private $message_id;

    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="integer", nullable=true)
     */
    private $user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="reference_id", type="string", length=255, nullable=true)
     */
    private $reference_id;

    /**
     * @var string
     *
     * @ORM\Column(name="predefined_question", type="string", length=50, nullable=true)
     */
    private $predefinedQuestion;

    /**
     * @var string
     *
     * @ORM\Column(name="predefined_question_id", type="string", length=500, nullable=true)
     */
    private $predefinedQuestionId;

    /**
     * @var int
     *
     * @ORM\Column(name="sitecode_id", type="integer")
     */
    private $sitecode_id;

    /**
     * @ORM\ManyToOne(targetEntity="TradusBundle\Entity\Sitecodes", inversedBy="email")
     * @ORM\JoinColumn(name="sitecode_id", referencedColumnName="id")
     */
    private $sitecode;

    /** @ORM\OneToMany(targetEntity="TradusBundle\Entity\EmailAttachment", mappedBy="email") */
    private $attachments;

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
     * @return string
     */
    public function getMessageId()
    {
        return $this->message_id;
    }

    /**
     * @param string $messageId
     */
    public function setMessageId(string $messageId)
    {
        $this->message_id = $messageId;
    }

    /**
     * Set status.
     *
     * @param int|null $status
     *
     * @return Email
     */
    public function setStatus($status = null)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return int|null
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set emailFrom.
     *
     * @param string|null $emailFrom
     *
     * @return Email
     */
    public function setEmailFrom($emailFrom = null)
    {
        $this->email_from = $emailFrom;

        return $this;
    }

    /**
     * Get emailFrom.
     *
     * @return string|null
     */
    public function getEmailFrom()
    {
        return $this->email_from;
    }

    /**
     * Set emailTo.
     *
     * @param string|null $emailTo
     *
     * @return Email
     */
    public function setEmailTo($emailTo = null)
    {
        $this->email_to = $emailTo;

        return $this;
    }

    /**
     * Get emailTo.
     *
     * @return string|null
     */
    public function getEmailTo()
    {
        return $this->email_to;
    }

    /**
     * Set subject.
     *
     * @param string|null $subject
     *
     * @return Email
     */
    public function setSubject($subject = null)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get subject.
     *
     * @return string|null
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set body.
     *
     * @param string|null $body
     *
     * @return Email
     */
    public function setBody($body = null)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Get body.
     *
     * @return string|null
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Set sentAt.
     *
     * @param DateTime|null $sentAt
     *
     * @return Email
     */
    public function setSentAt($sentAt = null)
    {
        $this->sent_at = $sentAt;

        return $this;
    }

    /**
     * Get sentAt.
     *
     * @return DateTime|null
     */
    public function getSentAt()
    {
        return $this->sent_at;
    }

    /**
     * Set createdAt.
     *
     * @param DateTime|null $createdAt
     *
     * @return Email
     */
    public function setCreatedAt($createdAt = null)
    {
        $this->created_at = $createdAt;

        return $this;
    }

    /**
     * Get createdAt.
     *
     * @return DateTime|null
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set updatedAt.
     *
     * @param DateTime|null $updatedAt
     *
     * @return Email
     */
    public function setUpdatedAt($updatedAt = null)
    {
        $this->updated_at = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt.
     *
     * @return DateTime|null
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * Set toSeller.
     *
     * @param Seller|null $toSeller
     *
     * @return Email
     */
    public function setToSeller(?Seller $toSeller = null)
    {
        $this->to_seller = $toSeller;

        return $this;
    }

    /**
     * Get toSeller.
     *
     * @return Seller|null
     */
    public function getToSeller()
    {
        return $this->to_seller;
    }

    /**
     * Set emailTemplate.
     *
     * @param EmailTemplate|null $emailTemplate
     *
     * @return Email
     */
    public function setEmailTemplate(?EmailTemplate $emailTemplate = null)
    {
        $this->email_template = $emailTemplate;

        return $this;
    }

    /**
     * Get emailTemplate.
     *
     * @return EmailTemplate|null
     */
    public function getEmailTemplate()
    {
        return $this->email_template;
    }

    /**
     * Set message.
     *
     * @param string|null $message
     *
     * @return Email
     */
    public function setMessage($message = null)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message.
     *
     * @return string|null
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set offer.
     *
     * @param Offer|null $offer
     *
     * @return Email
     */
    public function setOffer(?Offer $offer = null)
    {
        $this->offer = $offer;

        return $this;
    }

    /**
     * Get offer.
     *
     * @return Offer|null
     */
    public function getOffer()
    {
        return $this->offer;
    }

    /**
     * Set toOfferId.
     *
     * @param int|null $toOfferId
     *
     * @return Email
     */
    public function setToOfferId($toOfferId = null)
    {
        $this->to_offer_id = $toOfferId;

        return $this;
    }

    /**
     * Get toOfferId.
     *
     * @return int|null
     */
    public function getToOfferId()
    {
        return $this->to_offer_id;
    }

    /**
     * Set toSellerId.
     *
     * @param int|null $toSellerId
     *
     * @return Email
     */
    public function setToSellerId($toSellerId = null)
    {
        $this->to_seller_id = $toSellerId;

        return $this;
    }

    /**
     * Get toSellerId.
     *
     * @return int|null
     */
    public function getToSellerId()
    {
        return $this->to_seller_id;
    }

    /**
     * Set userAgent.
     *
     * @param string|null $userAgent
     *
     * @return Email
     */
    public function setUserAgent($userAgent = null)
    {
        $this->user_agent = $userAgent;

        return $this;
    }

    /**
     * Get userAgent.
     *
     * @return string|null
     */
    public function getUserAgent()
    {
        return $this->user_agent;
    }

    /**
     * Set ip.
     *
     * @param string|null $ip
     *
     * @return Email
     */
    public function setIp($ip = null)
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * Get ip.
     *
     * @return string|null
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * Set replyTo.
     *
     * @param string|null $replyTo
     *
     * @return Email
     */
    public function setReplyTo($replyTo = null)
    {
        $this->reply_To = $replyTo;

        return $this;
    }

    /**
     * Get replyTo.
     *
     * @return string|null
     */
    public function getReplyTo()
    {
        return $this->reply_To;
    }

    /**
     * Set emailType.
     *
     * @param int|null $emailType
     *
     * @return Email
     */
    public function setEmailType($emailType = null)
    {
        $this->email_type = $emailType;

        return $this;
    }

    /**
     * Get emailType.
     *
     * @return int|null
     */
    public function getEmailType()
    {
        return $this->email_type;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * @param int $userId
     */
    public function setUserId($userId = null)
    {
        $this->user_id = $userId;
    }

    /**
     * Set referenceId.
     *
     * @param string|null $referenceId
     *
     * @return Email
     */
    public function setReferenceId($referenceId = null)
    {
        $this->reference_id = $referenceId;

        return $this;
    }

    /**
     * Get referenceId.
     *
     * @return string|null
     */
    public function getReferenceId()
    {
        return $this->reference_id;
    }

    /**
     * Will return a array of categories to identify the email.
     * @return bool|array
     */
    public function getCategoryName()
    {
        $emailType = $this->getEmailType();
        if (isset(self::EMAIL_CATEGORIES[$emailType])) {
            return self::EMAIL_CATEGORIES[$emailType];
        }

        return false;
    }

    /**
     * @return string
     */
    public function getPredefinedQuestion(): string
    {
        return json_decode($this->predefinedQuestion, true);
    }

    /**
     * @param $predefinedQuestion
     */
    public function setPredefinedQuestion($predefinedQuestion): void
    {
        if (is_array($predefinedQuestion)) {
            $predefinedQuestion = json_encode(array_values($predefinedQuestion));
        }
        $this->predefinedQuestion = $predefinedQuestion;
    }

    /**
     * @return string
     */
    public function getPredefinedQuestionId(): string
    {
        return json_decode($this->predefinedQuestionId, true);
    }

    /**
     * @param $predefinedQuestionId
     */
    public function setPredefinedQuestionId($predefinedQuestionId): void
    {
        if (is_array($predefinedQuestionId)) {
            $predefinedQuestionId = json_encode($predefinedQuestionId);
        }
        $this->predefinedQuestionId = $predefinedQuestionId;
    }

    /**
     * @return int
     */
    public function getSitecodeId()
    {
        return $this->sitecode_id;
    }

    /**
     * @return Sitecodes
     */
    public function getSitecode()
    {
        return $this->sitecode ? $this->sitecode : Sitecodes::SITECODE_TRADUS;
    }

    /**
     * @param mixed $sitecode
     */
    public function setSitecode(Sitecodes $sitecode): void
    {
        $this->sitecode = $sitecode ? $sitecode : Sitecodes::SITECODE_TRADUS;
    }

    /**
     * @param $domain string emaildomain
     *
     * @return string
     */
    public function getNotificationEmailAddress($domain = 'mail.tradus.com')
    {
        return 'notification_'.$this->getID().'_'.self::getHashNotification().'@'.$domain;
    }

    public function getHashNotification()
    {
        $sha1 = sha1($this->getEmailTo().$this->getCreatedAt()->format('YmdHis'));

        return substr($sha1, strlen($sha1) - self::EMAIL_HASH_LENGTH_FOR_DISPLAY, self::EMAIL_HASH_LENGTH_FOR_DISPLAY);
    }

    /**
     * @return Collection|EmailAttachment[]
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    public function addAttachment(EmailAttachment $attachment): self
    {
        if (! $this->attachments->contains($attachment)) {
            $this->attachments[] = $attachment;
            $attachment->setEmail($this);
        }

        return $this;
    }

    public function removeAttachment(EmailAttachment $attachment): self
    {
        if ($this->attachments->contains($attachment)) {
            $this->attachments->removeElement($attachment);

            if ($attachment->getEmail() === $this) {
                $attachment->setEmail(null);
            }
        }

        return $this;
    }
}

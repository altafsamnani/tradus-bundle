<?php

namespace TradusBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EmailTemplate.
 *
 * @ORM\Table(name="email_templates")
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\EmailTemplateRepository")
 */
class EmailTemplate
{
    const STATUS_ONLINE = 100;
    const STATUS_DELETED = -200;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="email_type", type="string", length=255, nullable=true)
     */
    private $email_type;

    /**
     * @ORM\Column(name="subject", type="string", length=255, nullable=true)
     */
    private $subject;

    /**
     * @ORM\Column(name="content", type="text", nullable=true)
     */
    private $content;

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
     * Set content.
     *
     * @param string|null $content
     *
     * @return EmailTemplate
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
     * Set emailType.
     *
     * @param string|null $emailType
     *
     * @return EmailTemplate
     */
    public function setEmailType($emailType = null)
    {
        $this->email_type = $emailType;

        return $this;
    }

    /**
     * Get emailType.
     *
     * @return string|null
     */
    public function getEmailType()
    {
        return $this->email_type;
    }

    /**
     * Set subject.
     *
     * @param string|null $subject
     *
     * @return EmailTemplate
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
}

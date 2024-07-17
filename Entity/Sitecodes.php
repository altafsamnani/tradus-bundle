<?php

namespace TradusBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;

/**
 * Sitecodes.
 *
 * @ORM\Table(name="sitecodes")
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\SitecodeRepository")
 */
class Sitecodes
{
    public const SITECODE_FIELD_CONFIG = 'sitecode';
    public const SITECODE_FIELD_HEAD_META = 'head_meta';
    public const SITECODE_FIELD_ID_CONFIG = 'site_id';
    public const SITECODE_FIELD_KEY_CONFIG = 'site_key';
    public const SITECODE_FIELD_TITLE_CONFIG = 'site_title';
    public const SITECODE_FIELD_DOMAIN_CONFIG = 'domain';
    public const SITECODE_FIELD_SHORT_DOMAIN_CONFIG = 'short_domain';
    public const SITECODE_FIELD_DOMAIN_DEV_CONFIG = 'domain_dev';
    public const SITECODE_TITLE_TRADUS = 'Tradus';
    public const SITECODE_TITLE_AUTOTRADER = 'AutoTraderCommercial';
    public const SITECODE_TRADUS = 1;
    public const SITECODE_OTOMOTOPROFI = 2;
    public const SITECODE_AUTOTRADER = 3;
    public const SITECODE_KEY_TRADUS = 'tradus';
    public const SITECODE_KEY_OTOMOTOPROFI = 'otomotoprofi';
    public const SITECODE_KEY_AUTOTRADER = 'autotrader';
    public const SITECODE_LOCALE_TRADUS = 'en';
    public const SITECODE_DOMAIN_TRADUS = 'https://www.tradus.com/';
    public const SITECODE_SHORT_DOMAIN_TRADUS = 'tradus.com';
    public const SITECODE_DOMAIN_DEV_TRADUS = 'https://tradus.dev/';

    public const STATUS_ONLINE = 1;
    public const LOCALE_CONFIG = 'locale';
    public const SUPPORTED_LOCALES = 'app.locales';

    public const SITECODE_TRADUS_PRO = 'Tradus Pro';

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false, options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="sitecode", type="string", length=255, nullable=false)
     */
    private $sitecode;

    /**
     * @var string
     *
     * @ORM\Column(name="domain", type="string", length=255, nullable=false)
     */
    private $domain;

    /**
     * @var string
     *
     * @ORM\Column(name="default_locale", type="string", length=45, nullable=false)
     */
    private $defaultLocale;

    /**
     * @var string
     *
     * @ORM\Column(name="default_currency", type="string", length=45, nullable=false)
     */
    private $defaultCurrency;

    /**
     * @var bool
     *
     * @ORM\Column(name="status", type="boolean", nullable=false, options={"default"="1"})
     */
    private $status = '1';

    /**
     * @var DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=false)
     */
    private $updatedAt;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(name="deleted_at", type="datetime", nullable=true)
     */
    private $deletedAt;

    /**
     * @var Journal
     *
     * @ORM\OneToMany(targetEntity="TradusBundle\Entity\Journal", mappedBy="sitecode", fetch="EXTRA_LAZY")
     * @Exclude
     */
    private $journal;

    /**
     * @var Email
     *
     * @ORM\OneToMany(targetEntity="TradusBundle\Entity\Email", mappedBy="sitecode", fetch="EXTRA_LAZY")
     * @Exclude
     */
    private $email;

    /**
     * @var SpamEmail
     *
     * @ORM\OneToMany(targetEntity="TradusBundle\Entity\SpamEmail", mappedBy="sitecode", fetch="EXTRA_LAZY")
     * @Exclude
     */
    private $spamEmail;

    /**
     * @return string
     */
    public function getSitecode(): string
    {
        return $this->sitecode;
    }

    /**
     * @param string $sitecode
     * @return Sitecodes
     */
    public function setSitecode(string $sitecode): self
    {
        $this->sitecode = $sitecode;

        return $this;
    }

    /**
     * @return bool
     */
    public function isStatus(): bool
    {
        return $this->status;
    }

    /**
     * @param bool $status
     * @return Sitecodes
     */
    public function setStatus(bool $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     * @return Sitecodes
     */
    public function setCreatedAt(DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @param DateTime $updatedAt
     * @return Sitecodes
     */
    public function setUpdatedAt(DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getDeletedAt(): ?DateTime
    {
        return $this->deletedAt;
    }

    /**
     * @param DateTime|null $deletedAt
     * @return Sitecodes
     */
    public function setDeletedAt(?DateTime $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * @param string $domain
     */
    public function setDomain(string $domain): void
    {
        $this->domain = $domain;
    }

    /**
     * @return string
     */
    public function getDefaultLocale(): string
    {
        return $this->defaultLocale;
    }

    /**
     * @param string $defaultLocale
     */
    public function setDefaultLocale(string $defaultLocale): void
    {
        $this->defaultLocale = $defaultLocale;
    }

    /**
     * @return string
     */
    public function getDefaultCurrency(): string
    {
        return $this->defaultCurrency;
    }

    /**
     * @param string $defaultCurrency
     */
    public function setDefaultCurrency(string $defaultCurrency): void
    {
        $this->defaultCurrency = $defaultCurrency;
    }

    /**
     * @return Journal
     */
    public function getJournal(): Journal
    {
        return $this->journal;
    }

    /**
     * @param Journal $journal
     */
    public function setJournal(Journal $journal): void
    {
        $this->journal = $journal;
    }

    /**
     * @return Email
     */
    public function getEmail(): Email
    {
        return $this->email;
    }

    /**
     * @param Email $email
     */
    public function setEmail(Email $email): void
    {
        $this->email = $email;
    }

    /**
     * @return SpamEmail
     */
    public function getSpamEmail(): SpamEmail
    {
        return $this->spamEmail;
    }

    /**
     * @param SpamEmail $spamEmail
     */
    public function setSpamEmail(SpamEmail $spamEmail): void
    {
        $this->spamEmail = $spamEmail;
    }
}

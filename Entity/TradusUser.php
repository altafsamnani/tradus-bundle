<?php

namespace TradusBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation\Exclude;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\TradusUserRepository")
 * @ORM\Table(name="tradus_users",
 *      uniqueConstraints={@ORM\UniqueConstraint(name="email_idx", columns={"email"})},
 * )
 * @ORM\HasLifecycleCallbacks()
 */
class TradusUser
{
    /* User has an active account with password */
    public const STATUS_ACTIVE = 100;
    /* User is been soft deleted */
    public const STATUS_DELETED = -200;
    /* User has requested a password/account but not confirmed yet */
    public const STATUS_PENDING = 10;
    /* User data has been recorded but has not requested an account */
    public const STATUS_NO_ACCOUNT = 20;
    /*User subscribed to receive e-mails about offers and services */
    public const STATUS_SUB_EMAIL_ACTIVE = 100;
    /*User un-subscribed to receive e-mails about offers and services */
    public const STATUS_SUB_EMAIL_INACTIVE = 200;
    public const USER_TYPE_PRIVATE = 1;
    public const USER_TYPE_COMPANY = 2;

    public const TPRO_SEND_GRID_POST = 'POST';
    public const TPRO_SEND_GRID_PUT = 'PUT';
    public const STATUS_ALLOWED = [self::STATUS_PENDING, self::STATUS_ACTIVE];

    public const FACEBOOK_FIELD_MAPPING = [
        'first_name' => 'first_name',
        'last_name' => 'last_name',
    ];

    public const GOOGLE_FIELD_MAPPING = [
        'first_name' => 'given_name',
        'last_name' => 'family_name',
    ];

    public const APPLE_FIELD_MAPPING = [
        'first_name' => 'given_name',
        'last_name' => 'family_name',
    ];

    public const AVAILABLE_FIELDS = [
        'switchboard_api_id',
        'email',
        'password',
        'id',
        'full_name',
        'first_name',
        'last_name',
        'userType',
        'user_segment',
        'phone',
        'postcode',
        'company',
        'vat_number',
        'country',
        'status',
        'ip',
        'send_alerts',
        'google_id',
        'facebook_id',
        'preferred_locale',
        'subscribe_emails_offers_services',
        'city',
        'geo_location',
        'sitecodeId',
    ];

    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(name="id", type="integer", unique=true)
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /** @ORM\Column(name="switchboard_api_id", type="integer") */
    protected $switchboardApiId;

    /**
     * @var string
     * @Assert\Email(message = "The email '{{ value }}' is not a valid email.")
     * @Assert\NotBlank(message = "email can not be empty")
     * @ORM\Column(name="email", type="string", length=255, unique=true, nullable=false)
     */
    protected $email;

    /**
     * @var string
     * @Assert\Type("string")
     * @Assert\NotBlank(message = "password can not be empty")
     * @ORM\Column(name="password", type="string", length=255, nullable=false)
     */
    protected $password;

    /**
     * @var string
     * @Assert\Type("string")
     * @ORM\Column(name="full_name", type="string", length=255, nullable=true)
     */
    protected $full_name;

    /**
     * @var string
     * @Assert\Type("string")
     * @ORM\Column(name="first_name", type="string", length=255, nullable=true)
     */
    protected $first_name;

    /**
     * @var string
     * @Assert\Type("string")
     * @ORM\Column(name="last_name", type="string", length=255, nullable=true)
     */
    protected $last_name;

    /**
     * @var string
     * @Assert\Type("string")
     * @ORM\Column(name="preferred_locale", type="string", length=10, nullable=true)
     */
    protected $preferred_locale;

    /**
     * @var string
     * @Assert\Type("string")
     * @ORM\Column(name="languages_spoken", type="text", nullable=true)
     */
    protected $languages_spoken;

    /**
     * @var string
     * @Assert\Type("string")
     * @ORM\Column(name="phone", type="string", length=255, nullable=true)
     */
    protected $phone;

    /**
     * @var string
     * @Assert\Type("string")
     * @ORM\Column(name="postcode", type="string", length=15, nullable=true)
     */
    protected $postcode;

    /**
     * @var string
     * @Assert\Type("string")
     * @ORM\Column(name="used_categories", type="text", nullable=true)
     */
    protected $usedCategories;

    /**
     * @var string
     * @Assert\Type("string")
     * @ORM\Column(name="country", type="string", length=255, nullable=true)
     */
    protected $country;

    /**
     * @var int
     *
     * @ORM\Column(name="user_type", type="integer",nullable=false, options={"default"="2"})
     * @Assert\Type("integer")
     * @Exclude
     */
    protected $userType = 2;

    /**
     * @var string
     * @Assert\Type("integer")
     * @ORM\Column(name="user_segment", type="integer", nullable=true)
     */
    protected $user_segment;

    /**
     * @var string
     * @ORM\Column(name="company", type="string", length=255, nullable=true)
     */
    protected $company;

    /**
     * @var string
     * @ORM\Column(name="company_website", type="string", length=255, nullable=true)
     */
    protected $company_website;

    /**
     * @var string
     * @Assert\Type("string")
     * @ORM\Column(name="company_phone", type="string", length=255, nullable=true)
     */
    protected $company_phone;

    /**
     * @var string
     * @Assert\Type("string")
     * @ORM\Column(name="vat_number", type="string", length=15, nullable=true)
     */
    protected $vat_number;

    /**
     * @var string
     * @Assert\Type("string")
     * @ORM\Column(name="confirmation_token", type="string", length=255, nullable=true)
     */
    protected $confirmation_token;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="integer", nullable=false)
     * @Assert\Type("integer")
     * @Assert\Choice(callback={"TradusBundle\Entity\TradusUser", "getValidStatusList"}, strict=true)
     * @Exclude
     */
    protected $status = self::STATUS_NO_ACCOUNT;

    /**
     * @var int
     *
     * @ORM\Column(name="invalid_email", type="integer", nullable=true)
     * @Assert\Type("integer")
     * @Exclude
     */
    protected $invalid_email = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="ip", type="string", length=255, nullable=true)
     */
    protected $ip;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="agreement_date", type="datetime", nullable=true)
     */
    protected $agreement_date;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="last_login", type="datetime", nullable=true)
     * @Assert\DateTime()
     */
    protected $last_login;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="accepted_alerts_date", type="datetime", nullable=true)
     * @Assert\DateTime()
     */
    protected $accepted_alerts_date;

    /**
     * @ORM\OneToMany(targetEntity="TradusBundle\Entity\SimilarOfferAlert", mappedBy="user", fetch="EXTRA_LAZY")
     * @Exclude
     */
    private $similar_offer_alerts;

    /** @ORM\OneToMany(targetEntity="TradusBundle\Entity\SearchAlert", mappedBy="user", fetch="EXTRA_LAZY") */
    private $search_alerts;

    /**
     * @var DateTime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime")
     * @Assert\DateTime()
     */
    protected $created_at;

    /**
     * @var DateTime
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="updated_at", type="datetime")
     * @Assert\DateTime()
     */
    protected $updated_at;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="anonymized_at", type="datetime")
     * @Assert\DateTime()
     */
    protected $anonymizedAt;

    /**
     * @var int
     *
     * @ORM\Column(name="deleted_by_sbo_admin", type="integer",nullable=false, options={"default"="0"})
     * @Assert\Type("integer")
     * @Exclude
     */
    protected $deleted_by_sbo_admin = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="facebook_id", type="string", nullable=true)
     * @Assert\Type("string")
     */
    protected $facebookID;

    /**
     * @var string
     *
     * @ORM\Column(name="google_id", type="string", nullable=true)
     * @Assert\Type("string")
     */
    protected $googleID;

    /**
     * @var string
     *
     * @ORM\Column(name="apple_id", type="string", nullable=true)
     * @Assert\Type("string")
     */
    protected $appleID;

    /**
     * @var int
     *
     * @ORM\Column(name="subscribe_emails_offers_services", type="integer",nullable=false, options={"default"="200"})
     * @Assert\Type("integer")
     * @Exclude
     */
    protected $subscribe_emails_offers_services = 200;

    /**
     * @var string
     *
     * @ORM\Column(name="old_emails", type="string", length=500, nullable=true)
     * @Assert\Type("string")
     */
    private $oldEmails;

    /**
     * @var string
     * @Assert\Type("string")
     * @ORM\Column(name="geo_location", type="string", length=255, nullable=true)
     */
    protected $geoLocation;

    /**
     * @var string
     * @Assert\Type("string")
     * @ORM\Column(name="city", type="string", length=255, nullable=true)
     */
    protected $city;

    /**
     * @var int
     * @Assert\Type("integer")
     * @ORM\Column(name="city_selected_by_user", type="integer", nullable=false, options={"default"="0"})
     */
    protected $citySelectedByUser;

    /**
     * @var int
     *
     * @ORM\Column(name="sitecode_id", type="integer", nullable=true)
     * @Assert\Type("integer")
     */
    private $sitecodeId;

    /**
     * @ORM\OneToMany(targetEntity="TradusBundle\Entity\SellerReview", mappedBy="user", fetch="EXTRA_LAZY")
     * @Exclude
     */
    private $sellerReview;

    /**
     * @ORM\PreUpdate
     * @ORM\PostUpdate
     */
    public function prePostUpdate()
    {
        $this->setUpdatedAt(new DateTime());
    }

    public function setFacebookId($facebookId)
    {
        $this->facebookID = $facebookId;
    }

    public function getFaceBookId()
    {
        return $this->facebookID;
    }

    public function setGoogleId($googleId)
    {
        $this->googleID = $googleId;
    }

    public function getGoogleId()
    {
        return $this->googleID;
    }

    public function setAppleId($appleId)
    {
        $this->appleID = $appleId;
    }

    public function getAppleId()
    {
        return $this->appleID;
    }

    /**
     * @return int
     */
    public function getId()
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
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email): void
    {
        $this->email = strtolower(trim($email));
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->first_name;
    }

    /**
     * @param string $firstName
     */
    public function setFirstName(string $firstName): void
    {
        $this->first_name = trim($firstName);
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->last_name;
    }

    /**
     * @param string $lastName
     */
    public function setLastName(string $lastName): void
    {
        $this->last_name = trim($lastName);
    }

    /**
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param string $phone
     */
    public function setPhone(string $phone): void
    {
        $this->phone = trim($phone);
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param string $country
     */
    public function setCountry(string $country): void
    {
        $this->country = trim($country);
    }

    /**
     * 1 = Private
     * 2 = Company.
     * @param int $userType
     */
    public function setUserType(int $userType)
    {
        $this->userType = $userType;
    }

    /**
     * @return int
     */
    public function getUserType()
    {
        return $this->userType;
    }

    /**
     * @return string
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @param string $company
     */
    public function setCompany(string $company): void
    {
        $this->company = trim($company);
    }

    /**
     * @return string
     */
    public function getCompanyPhone()
    {
        return $this->company_phone;
    }

    /**
     * @param string $cPhone
     */
    public function setCompanyPhone(string $cPhone): void
    {
        $this->company_phone = trim($cPhone);
    }

    /**
     * @return string
     */
    public function getCompanyWebsite()
    {
        return $this->company_website;
    }

    /**
     * @param string $cWebsite
     */
    public function setCompanyWebsite(string $cWebsite): void
    {
        $this->company_website = trim($cWebsite);
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $status
     */
    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    /**
     * 0 = valid email
     * 1 = invalid email.
     * @param int $invalidEmail
     */
    public function setInValidEmail(int $invalidEmail)
    {
        $this->invalid_email = $invalidEmail;
    }

    /**
     * @return int
     */
    public function getInValidEmail()
    {
        return $this->invalid_email;
    }

    /**
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     */
    public function setIp(string $ip): void
    {
        $this->ip = trim($ip);
    }

    /**
     * @return string
     */
    public function getConfirmationToken()
    {
        return $this->confirmation_token;
    }

    /**
     * @param string $confirmationToken
     */
    public function setConfirmationToken($confirmationToken): void
    {
        $this->confirmation_token = $confirmationToken;
    }

    /**
     * When did the user accepted the agreement, when this is set he accepted.
     * @param $agreementDate
     */
    public function setAgreementDate($agreementDate)
    {
        $this->agreement_date = $agreementDate;
    }

    /**
     * @return DateTime
     */
    public function getAgreementDate()
    {
        return $this->agreement_date;
    }

    /**
     * @param DateTime $lastLogin
     */
    public function setLastLogin(DateTime $lastLogin)
    {
        $this->last_login = $lastLogin;
    }

    /**
     * @return DateTime
     */
    public function getLastLogin()
    {
        return $this->last_login;
    }

    /**
     * @param DateTime $acceptedAlertsDate
     */
    public function setAcceptedAlertsDate(DateTime $acceptedAlertsDate)
    {
        $this->accepted_alerts_date = $acceptedAlertsDate;
    }

    /**
     * @return DateTime
     */
    public function getAcceptedAlertsDate()
    {
        return $this->accepted_alerts_date;
    }

    /**
     * Does this user opt-in for alert emails?
     * @return bool
     */
    public function canSendAlertEmails()
    {
        if ($this->accepted_alerts_date !== null && $this->accepted_alerts_date instanceof DateTime) {
            return true;
        }

        return false;
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
     * @return DateTime
     */
    public function getUpdatedAt(): DateTime
    {
        return $this->updated_at;
    }

    /**
     * @param DateTime $updatedAt
     */
    public function setUpdatedAt(DateTime $updatedAt): void
    {
        $this->updated_at = $updatedAt;
    }

    /**
     * @return DateTime
     */
    public function getAnonymizedAt(): DateTime
    {
        return $this->anonymizedAt;
    }

    /**
     * @param DateTime $anonymizedAt
     */
    public function setAnonymizedAt(DateTime $anonymizedAt): void
    {
        $this->anonymizedAt = $anonymizedAt;
    }

    /**
     * 0 = No user Admin.
     * @param int $deletedBySboAdmin
     */
    public function setDeletedBySboAdmin(int $deletedBySboAdmin)
    {
        $this->deleted_by_sbo_admin = $deletedBySboAdmin;
    }

    /**
     * @return int
     */
    public function getDeletedBySboAdmin()
    {
        return $this->deleted_by_sbo_admin;
    }

    /**
     * @return string
     */
    public function getPreferredLocale()
    {
        return $this->preferred_locale;
    }

    /**
     * @param $preferredLocale
     */
    public function setPreferredLocale($preferredLocale)
    {
        $this->preferred_locale = $preferredLocale;
    }

    /**
     * @return string
     */
    public function getLanguagesSpoken()
    {
        return $this->languages_spoken ? explode(',', $this->languages_spoken) : [];
    }

    /**
     * @param $languagesSpoken
     */
    public function setLanguagesSpoken($languagesSpoken = null)
    {
        if (is_array($languagesSpoken)) {
            $languagesSpoken = ! empty($languagesSpoken) ? implode(',', $languagesSpoken) : null;
        }

        $this->languages_spoken = $languagesSpoken;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return SimilarOfferAlert
     */
    public function getSimilarOfferAlerts()
    {
        return $this->similar_offer_alerts;
    }

    /**
     * @param string $password
     */
    public function setPassword(string $password): void
    {
        $this->password = $this->passwordHash($password);
    }

    /**
     * @return string|null
     */
    public function getCountryName()
    {
        return $this->getCountry() ? Intl::getRegionBundle()->getCountryName($this->getCountry()) : '';
    }

    /**
     * @return string
     */
    public function getCityCountryName()
    {
        $city = $this->getCity();

        return $city .= ($city && $this->getCountryName() ? ', '.$this->getCountryName() : '');
    }

    /**
     * @return array of valid statuses
     */
    public static function getValidStatusList()
    {
        return [
            self::STATUS_ACTIVE,
            self::STATUS_PENDING,
            self::STATUS_NO_ACCOUNT,
            self::STATUS_DELETED,
        ];
    }

    /**
     * Is this user active and not deleted or pending?
     * @return bool
     */
    public function isActiveUser()
    {
        if ($this->getStatus() == self::STATUS_ACTIVE || $this->getStatus() == self::STATUS_NO_ACCOUNT) {
            return true;
        }

        return false;
    }

    /**
     * Is this user active and not deleted or pending?
     * @return bool
     */
    public function isActiveAndPendingUser()
    {
        if (in_array(
            $this->getStatus(),
            [self::STATUS_ACTIVE, self::STATUS_NO_ACCOUNT, self::STATUS_PENDING]
        )) {
            return true;
        }

        return false;
    }

    public function needsFillInformation()
    {
        if (! $this->getFullName() ||
            ! $this->getCountry() ||
            ! $this->getPhone()
        ) {
            return true;
        }

        return false;
    }

    public function isDeleted()
    {
        if ($this->getStatus() == self::STATUS_DELETED) {
            return true;
        }

        return false;
    }

    /**
     * @param int $subscribeEmailsOffersServices
     */
    public function setSubscribeEmailsOffersServices(int $subscribeEmailsOffersServices)
    {
        $this->subscribe_emails_offers_services = $subscribeEmailsOffersServices;
    }

    /**
     * @return int
     */
    public function getSubscribeEmailsOffersServices()
    {
        return $this->subscribe_emails_offers_services;
    }

    /**
     * Set oldEmails.
     *
     * @param string|null $oldEmails
     * @return $this
     */
    public function setOldEmail($oldEmails)
    {
        $this->oldEmails = $oldEmails;

        return $this;
    }

    /**
     * Get oldEmails.
     *
     * @return string|null
     */
    public function getOldEmails()
    {
        return $this->oldEmails;
    }

    /**
     * @return string
     */
    public function getGeoLocation(): string
    {
        return $this->geoLocation ?? '';
    }

    /**
     * @param string $geoLocation
     */
    public function setGeoLocation(string $geoLocation): void
    {
        $this->geoLocation = $geoLocation;
    }

    /**
     * @return int
     */
    public function getCitySelectedByUser(): int
    {
        return ! $this->citySelectedByUser ? 0 : $this->citySelectedByUser;
    }

    /**
     * @return string
     */
    public function getCity(): string
    {
        return $this->city ?? '';
    }

    /**
     * @param string $city
     */
    public function setCity(?string $city = null): void
    {
        $this->city = $city ?? '';
    }

    /**
     * @param int $citySelectedByUser
     */
    public function setCitySelectedByUser(?int $citySelectedByUser = null): void
    {
        if (! $citySelectedByUser) {
            $citySelectedByUser = 0;
        }
        $this->citySelectedByUser = $citySelectedByUser;
    }

    /**
     * @return int
     */
    public function isSubscribeEmailsOffersServices()
    {
        if ($this->subscribe_emails_offers_services == self::STATUS_SUB_EMAIL_ACTIVE) {
            return true;
        }

        return false;
    }

    /**
     * Disables the alert emails to set the date to null.
     */
    public function disableAlertEmails()
    {
        $this->accepted_alerts_date = null;
    }

    /**
     * @return string
     */
    public function getFullName(): ?string
    {
        return $this->full_name;
    }

    /**
     * @param string $full_name
     * @return TradusUser
     */
    public function setFullName(string $full_name): self
    {
        $this->full_name = $full_name;

        return $this;
    }

    /**
     * @return string
     */
    public function getPostcode(): ?string
    {
        return $this->postcode;
    }

    /**
     * @param string $postcode
     * @return TradusUser
     */
    public function setPostcode(string $postcode): self
    {
        $this->postcode = $postcode;

        return $this;
    }

    /**
     * @return string
     */
    public function getUserSegment(): ?string
    {
        return $this->user_segment;
    }

    /**
     * @param string $user_segment
     * @return TradusUser
     */
    public function setUserSegment(string $user_segment): self
    {
        $this->user_segment = $user_segment;

        return $this;
    }

    /**
     * @return string
     */
    public function getVatNumber(): ?string
    {
        return $this->vat_number;
    }

    /**
     * @param string $vat_number
     * @return TradusUser
     */
    public function setVatNumber(string $vat_number): self
    {
        $this->vat_number = $vat_number;

        return $this;
    }

    /**
     * @return string
     */
    public function getUsedCategories(): ?string
    {
        return $this->usedCategories;
    }

    /**
     * @param string $usedCategories
     */
    public function setUsedCategories(string $usedCategories): void
    {
        $this->usedCategories = $usedCategories;
    }

    /**
     * @return string
     */
    public function generateToken()
    {
        $tokenData = $this->getId().$this->getEmail().$this->getPassword();

        return md5($tokenData);
    }

    /**
     * @param array $data
     * @return string
     */
    public static function generateTimeBasedCode(array $data)
    {
        $hashKey = 'fdslkPN8er0wD-q9e+rij!dflkj';
        $encryptionKey = 'PJ*gbhjuyt%432.kjYY';
        $data = array_merge(['timestamp' => time()], $data);
        // convert the array to json
        $jsonData = json_encode($data);
        // encrypt the data
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher = 'AES-128-CBC'));
        $encryptedData = openssl_encrypt($jsonData, $cipher, $encryptionKey, 0, $iv);
        // create a hash key for the data
        $hash = hash_hmac('sha256', $encryptedData, $hashKey, true);
        // return the encrypted data and hash
        return rtrim(strtr(base64_encode(($iv.$hash.$encryptedData)), '+/', '-_'), '=');
    }

    /**
     * @param string $ciphertext
     * @param int $lifetimeHours
     * @return bool|mixed|string
     * @throws Exception
     */
    public static function validateTimeBasedCode(string $ciphertext, $lifetimeHours = 168)
    {
        $hashKey = 'fdslkPN8er0wD-q9e+rij!dflkj';
        $encryptionKey = 'PJ*gbhjuyt%432.kjYY';

        $c = base64_decode(strtr(($ciphertext), '-_', '+/'));
        $ivlen = openssl_cipher_iv_length($cipher = 'AES-128-CBC');
        $iv = substr($c, 0, $ivlen);
        $hash = substr($c, $ivlen, $sha2len = 32);
        $data = substr($c, $ivlen + $sha2len);
        $original_plaintext = openssl_decrypt($data, $cipher, $encryptionKey, 0, $iv);
        $calcmac = hash_hmac('sha256', $data, $hashKey, true);
        if (! hash_equals($hash, $calcmac)) {
            //PHP 5.6+ timing attack safe comparison
            throw new Exception('Hash check failed!', 73100);
        }
        $data = json_decode($original_plaintext, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            throw new Exception('Invalid data decrypted / JSON decode failed!', 73101);
        }
        // verify that an array has been received
        if (! is_array($data)) {
            throw new Exception('Decrypted data is not an array!', 73102);
        }
        // check for timeout / prevent replay attacks
        if ($lifetimeHours !== 0) {
            $tokenAgeInSeconds = intval(time()) - intval($data['timestamp']);
            if (! isset($data['timestamp'])) {
                throw new Exception('Timeout check failed, no timestamp available!', 73103);
            } elseif ($tokenAgeInSeconds > ($lifetimeHours * 3600)) {
                throw new Exception('Timeout, possibly replay attack!', 73104);
            }
        }

        return $data;
    }

    /**
     * @param string $password
     * @return bool|string
     */
    public function passwordHash(string $password)
    {
        $options['cost'] = 10;

        return password_hash($password, PASSWORD_BCRYPT, $options);
    }

    /**
     * @param string $password
     * @return bool
     */
    public function passwordValidate(string $password)
    {
        return password_verify($password, $this->getPassword());
    }

    /**
     * Get information about the password hash. Returns an array of the information
     * that was used to generate the password hash.
     *
     * @return array
     */
    public function passwordInformation()
    {
        return password_get_info($this->getPassword());
    }

    /**
     * @param int $length
     * @return bool|string
     */
    public function generatePassword(int $length = 8)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ23456789';
        $numbers = '23456789';
        $special = '!@#$%&*_-=+';

        $password = substr(str_shuffle($chars), 0, $length - 2);
        $password .= substr(str_shuffle($numbers), 0, 1);
        $password .= substr(str_shuffle($special), 0, 1);

        return $password;
    }

    /**
     * Transforms the social request payload data into array.
     *
     * @param array $payload
     * @param Request $request request data
     *
     * @return array
     */
    public static function transformSocialPayload(array $payload, Request $request)
    {
        $fieldMappingVariable = self::FACEBOOK_FIELD_MAPPING;
        if (isset($payload['sub'])) {
            $fieldMappingVariable = self::GOOGLE_FIELD_MAPPING;
        } elseif (isset($payload['user_identifier'])) {
            $fieldMappingVariable = self::APPLE_FIELD_MAPPING;
        }

        $firstName = null;
        $lastName = null;
        $fullName = null;

        if ($payload[$fieldMappingVariable['first_name']]) {
            $fullName = $firstName = $payload[$fieldMappingVariable['first_name']];
        }
        if ($payload[$fieldMappingVariable['last_name']]) {
            $lastName = $payload[$fieldMappingVariable['last_name']];
            $fullName = $firstName ? $firstName.' '.$lastName : $lastName;
        }

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'full_name' => $fullName,
            'email' => $payload['email'],
            'preferred_locale' => $payload['locale'] ?? Sitecodes::SITECODE_LOCALE_TRADUS,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'google_id' => $data['sub'] ?? null,
            'facebook_id' => $data['id'] ?? null,
            'apple_id' => $data['user_identifier'] ?? null,
            'social' => true,
            'sitecodeId' => $payload['sitecodeId'],
        ];
    }

    /**
     * Transforms object data into array.
     *
     * @param TradusUser $user
     * @return array
     */
    public static function transform(self $user)
    {
        return [
            'id' => $user->getId(),
            'switchboard_api_id' => $user->getSwitchboardApiId(),
            'email' => $user->getEmail(),
            'password' => $user->getPassword(),
            'full_name' => $user->getFullName(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'phone' => $user->getPhone(),
            'geo_location' => $user->getGeoLocation(),
            'postcode' => $user->getPostcode(),
            'city' => $user->getCity(),
            'city_selected_by_user' => $user->getCitySelectedByUser(),
            'country' => $user->getCountry(),
            'company' => $user->getCompany(),
            'vat_number' => $user->getVatNumber(),
            'company_phone' => $user->getCompanyPhone(),
            'company_website' => $user->getCompanyWebsite(),
            'status' => $user->getStatus(),
            'ip' => $user->getIp(),
            'agreement_date' => $user->getAgreementDate(),
            'accepted_alerts_date' => $user->getAcceptedAlertsDate(),
            'created_at' => $user->getCreatedAt(),
            'updated_at' => $user->getUpdatedAt(),
            'google_id' => $user->getGoogleId(),
            'facebook_id' => $user->getFaceBookId(),
            'apple_id' => $user->getAppleId(),
            'confirmation_token' => $user->getConfirmationToken(),
            'last_login' => $user->getLastLogin(),
            'preferred_locale' => $user->getPreferredLocale(),
            'languages_spoken' => $user->getLanguagesSpoken(),
            'userType' => $user->getUserType(),
            'user_segment' => $user->getUserSegment(),
            'subscribe_emails_offers_services' => $user->getSubscribeEmailsOffersServices(),
        ];
    }

    /**
     * Helper function to set variables by array.
     *
     * @param array $params
     * @throws Exception
     */
    public function setValues(array $params)
    {
        if (isset($params['id'])) {
            $this->setId($params['id']);
        }

        if (isset($params['switchboard_api_id'])) {
            $this->setSwitchboardApiId($params['switchboard_api_id']);
        }

        if (isset($params['email'])) {
            $this->setEmail($params['email']);
        }

        if (isset($params['password'])) {
            $this->password = $params['password'];
        }

        if (isset($params['agreement_date'])) {
            $date = $params['agreement_date'];
            if (is_array($date) && isset($date['date'])) {
                $date = $date['date'];
            }
            $this->setAgreementDate(new DateTime($date));
        }

        if (isset($params['accepted_alerts_date'])) {
            $date = $params['accepted_alerts_date'];
            if (is_array($date) && isset($date['date'])) {
                $date = $date['date'];
            }
            $this->setAcceptedAlertsDate(new DateTime($date));
        }

        if (isset($params['created_at'])) {
            $date = $params['created_at'];
            if (is_array($date) && isset($date['date'])) {
                $date = $date['date'];
            }
            $this->setCreatedAt(new DateTime($date));
        }

        $this->setUpdatedAt(new DateTime());

        if (isset($params['full_name'])) {
            $this->setFullName($params['full_name']);
        }

        if (isset($params['first_name'])) {
            $this->setFirstName($params['first_name']);
        }

        if (isset($params['last_name'])) {
            $this->setLastName($params['last_name']);
        }

        if (isset($params['country'])) {
            $this->setCountry($params['country']);
        }

        if (isset($params['company'])) {
            $this->setCompany($params['company']);
        }

        if (isset($params['vat_number'])) {
            $this->setVatNumber($params['vat_number']);
        }

        if (isset($params['company_phone'])) {
            $this->setCompanyPhone($params['company_phone']);
        }

        if (isset($params['company_website'])) {
            $this->setCompanyWebsite($params['company_website']);
        }

        if (isset($params['userType'])) {
            $this->setUserType($params['userType']);
        }

        if (isset($params['user_segment'])) {
            $this->setUserSegment($params['user_segment']);
        }

        if (isset($params['phone'])) {
            $this->setPhone($params['phone']);
        }

        if (isset($params['postcode'])) {
            $this->setPostcode($params['postcode']);
        }

        if (isset($params['ip'])) {
            $this->setIp($params['ip']);
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
            /*            $ip = isset($_SERVER['HTTP_CLIENT_IP']) ?
                                $_SERVER['HTTP_CLIENT_IP'] : isset($_SERVER['HTTP_X_FORWARDED_FOR']) ?
                                $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];*/
            $this->setIp($ip);
        }

        if (isset($params['status'])) {
            $this->setStatus($params['status']);
        }

        if (isset($params['facebook_id'])) {
            $this->setFacebookId($params['facebook_id']);
        }

        if (isset($params['google_id'])) {
            $this->setGoogleId($params['google_id']);
        }

        if (isset($params['city'])) {
            $this->setCity($params['city']);
        }

        if (isset($params['city_selected_by_user'])) {
            $this->setCitySelectedByUser($params['city_selected_by_user']);
        }

        if (isset($params['geo_location'])) {
            $this->setGeoLocation($params['geo_location']);
        }

        if (isset($params['sitecodeId'])) {
            $this->setSitecodeId($params['sitecodeId']);
        }

        if (isset($params['last_login'])) {
            $this->setLastLogin(new DateTime());
        }
        if (isset($params['preferred_locale'])) {
            $this->setPreferredLocale($params['preferred_locale']);
        }
        if (isset($params['languages_spoken'])) {
            $this->setLanguagesSpoken($params['languages_spoken']);
        }

        if (isset($params['subscribe_emails_offers_services'])) {
            if ($params['subscribe_emails_offers_services'] == 100) {
                $this->setSubscribeEmailsOffersServices(self::STATUS_SUB_EMAIL_ACTIVE);
            }
        }
        if (isset($params['send_alerts'])) {
            if ($params['send_alerts'] == true) {
                $acceptDate = new DateTime();
                $this->setAcceptedAlertsDate($acceptDate);
            } else {
                $this->disableAlertEmails();
            }
        }
    }

    /**
     * @return int
     */
    public function getSitecodeId(): int
    {
        return $this->sitecodeId;
    }

    /**
     * @param int $sitecodeId
     */
    public function setSitecodeId(int $sitecodeId): void
    {
        $this->sitecodeId = $sitecodeId;
    }

    /**
     * @return mixed
     */
    public function getSwitchboardApiId()
    {
        return $this->switchboardApiId;
    }

    /**
     * @param mixed $switchboardApiId
     */
    public function setSwitchboardApiId($switchboardApiId): void
    {
        $this->switchboardApiId = $switchboardApiId;
    }
}

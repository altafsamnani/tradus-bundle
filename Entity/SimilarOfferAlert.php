<?php

namespace TradusBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * OfferAnalytics.
 *
 * @ORM\Table(name="similar_offer_alerts")
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\SimilarOfferAlertRepository")
 */
class SimilarOfferAlert
{
    // Statuses.
    public const STATUS_SUBSCRIBED = 100;
    public const STATUS_UNSUBSCRIBED = -1;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="TradusBundle\Entity\TradusUser", inversedBy="similar_offer_alerts")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="TradusBundle\Entity\Offer")
     * @ORM\JoinColumn(name="offer_id", referencedColumnName="id")
     */
    private $offer;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="integer", nullable=true)
     */
    private $status = self::STATUS_SUBSCRIBED;

    /**
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
     * @ORM\ManyToOne(targetEntity="TradusBundle\Entity\Alerts", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="alert_id", referencedColumnName="id")
     */
    private $alert;

    /**
     * @var string
     *
     * @ORM\Column(name="search_url", type="string", nullable=true)
     */
    private $searchUrl;

    /**
     * @var int
     *
     * @ORM\Column(name="sitecode_id", type="integer", nullable=true)
     */
    private $sitecodeId;

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
     * @return TradusUser
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param Alerts $alert
     */
    public function setAlert(Alerts $alert): void
    {
        $this->alert = $alert;
    }

    /**
     * @return Alerts
     */
    public function getAlert()
    {
        return $this->alert;
    }

    /**
     * @param TradusUser $user
     */
    public function setUser(TradusUser $user): void
    {
        $this->user = $user;
    }

    /**
     * @return Offer
     */
    public function getOffer()
    {
        return $this->offer;
    }

    /**
     * @param Offer $user
     */
    public function setOffer(Offer $offer): void
    {
        $this->offer = $offer;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param $status
     */
    public function setStatus($status): void
    {
        $this->status = $status;
    }

    /**
     * @return datetime
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * @param $created_at
     */
    public function setCreatedAt($created_at): void
    {
        $this->created_at = $created_at;
    }

    /**
     * @return datetime
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * @param $updated_at
     */
    public function setUpdatedAt($updated_at): void
    {
        $this->updated_at = $updated_at;
    }

    /**
     * @return string
     */
    public function getSearchUrl()
    {
        return $this->searchUrl;
    }

    /**
     * @param $searchUrl
     */
    public function setSearchUrl($searchUrl): void
    {
        $this->searchUrl = $searchUrl;
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
}

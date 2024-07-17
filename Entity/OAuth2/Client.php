<?php

namespace TradusBundle\Entity\OAuth2;

use Doctrine\ORM\Mapping as ORM;
use FOS\OAuthServerBundle\Entity\Client as BaseClient;

/**
 * @ORM\Entity
 * @ORM\Table(name="oauth_clients")
 */
class Client extends BaseClient
{
    const OAUTH_TRADUS_PRO_CLIENT_ID = 14;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\OneToOne(targetEntity="TradusBundle\Entity\ApiUser", inversedBy="client")
     */
    protected $user;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Set user.
     *
     * @param \TradusBundle\Entity\ApiUser|null $user
     *
     * @return Client
     */
    public function setUser(\TradusBundle\Entity\ApiUser $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     *
     * @return \TradusBundle\Entity\ApiUser|null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }
}

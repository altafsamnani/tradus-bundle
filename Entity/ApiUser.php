<?php

namespace TradusBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as BaseUser;

/**
 * @ORM\Entity
 * @ORM\Table(name="api_users")
 */
class ApiUser extends BaseUser
{
    const STATUS_ONLINE = 100;
    const STATUS_DELETED = -200;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="name", type="string", length=50, nullable=false)
     */
    private $name;

    /**
     * @ORM\Column(name="company", type="string", length=50, nullable=false)
     */
    private $company;

    /**
     * @ORM\OneToOne(targetEntity="TradusBundle\Entity\OAuth2\Client", mappedBy="user", cascade={"remove"})
     */
    protected $client;

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return ApiUser
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set company.
     *
     * @param string $company
     *
     * @return ApiUser
     */
    public function setCompany($company)
    {
        $this->company = $company;

        return $this;
    }

    /**
     * Get company.
     *
     * @return string
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * Set client.
     *
     * @param \TradusBundle\Entity\OAuth2\Client|null $client
     *
     * @return ApiUser
     */
    public function setClient(\TradusBundle\Entity\OAuth2\Client $client = null)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Get client.
     *
     * @return \TradusBundle\Entity\OAuth2\Client|null
     */
    public function getClient()
    {
        return $this->client;
    }
}

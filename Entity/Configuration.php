<?php

namespace TradusBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Configuration.
 *
 * @ORM\Table(name="configuration")
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\ConfigurationRepository")
 */
class Configuration
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="`name`", type="string", length=255, nullable=false)
     * @Assert\Type("string")
     * @Assert\NotBlank(message = "Name can not be empty")
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="`group`", type="string", length=255, nullable=false)
     * @Assert\Type("string")
     * @Assert\NotBlank(message = "Group can not be empty")
     */
    private $group;

    /**
     * @var string
     *
     * @ORM\Column(name="`value`", type="string", length=255, nullable=false)
     * @Assert\Type("string")
     * @Assert\NotBlank(message = "Value can not be empty")
     */
    private $value;

    /**
     * @var int
     *
     * @ORM\Column(name="sitecode_id", type="integer", nullable=false)
     */
    private $sitecodeId;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param string $group
     */
    public function setGroup(string $group)
    {
        $this->group = $group;
    }

    /**
     * @return string| mixed
     */
    public function getValue()
    {
        $value = @json_decode($this->value, true);
        if (json_last_error() === 0) {
            return $value;
        }
    }

    /**
     * @param string|mixed $value
     */
    public function setValue($value)
    {
        $this->value = json_encode($value);
    }

    /**
     * @return int
     */
    public function getSitecodeId(): int
    {
        return $this->sitecodeId ? $this->sitecodeId : Sitecodes::SITECODE_TRADUS;
    }

    /**
     * @param int $sitecodeId
     */
    public function setSitecodeId(?int $sitecodeId = null): void
    {
        $this->sitecodeId = $sitecodeId ? $sitecodeId : Sitecodes::SITECODE_TRADUS;
    }
}

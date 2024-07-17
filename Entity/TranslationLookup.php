<?php

namespace TradusBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

/**
 * TranslationLookup.
 *
 * @ORM\Table(name="translation_lookup", indexes={
 *     @Index(name="hash_langcode_index", columns={"hash", "langcode"})
 * })
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\TranslationLookupRepository")
 */
class TranslationLookup
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
     * @var string
     *
     * @ORM\Column(name="langcode", type="string", length=2)
     */
    private $langcode;

    /**
     * @var string
     *
     * @ORM\Column(name="hash", type="string", length=255, unique=true)
     */
    private $hash;

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
     * Set langcode.
     *
     * @param string $langcode
     *
     * @return TranslationLookup
     */
    public function setLangcode($langcode)
    {
        $this->langcode = $langcode;

        return $this;
    }

    /**
     * Get langcode.
     *
     * @return string
     */
    public function getLangcode()
    {
        return $this->langcode;
    }

    /**
     * Set hash.
     *
     * @param string $hash
     *
     * @return TranslationLookup
     */
    public function setHash($hash)
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * Get hash.
     *
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }
}

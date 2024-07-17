<?php

namespace TradusBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Translation.
 *
 * @ORM\Table(name="translation")
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\TranslationRepository")
 */
class Translation
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
     * @var int
     * @ORM\ManyToOne(targetEntity="TranslationLookup", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="translation_lookup_id", referencedColumnName="id")
     */
    private $translationLookupId;

    /**
     * @var string
     *
     * @ORM\Column(name="translation", type="text")
     */
    private $translation;

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
     * @return Translation
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
     * Set translationLookupId.
     *
     * @param int $translationLookupId
     *
     * @return Translation
     */
    public function setTranslationLookupId($translationLookupId)
    {
        $this->translationLookupId = $translationLookupId;

        return $this;
    }

    /**
     * Get translationLookupId.
     *
     * @return int
     */
    public function getTranslationLookupId()
    {
        return $this->translationLookupId;
    }

    /**
     * Set translation.
     *
     * @param string $translation
     *
     * @return Translation
     */
    public function setTranslation($translation)
    {
        $this->translation = $translation;

        return $this;
    }

    /**
     * Get translation.
     *
     * @return string
     */
    public function getTranslation()
    {
        return $this->translation;
    }
}

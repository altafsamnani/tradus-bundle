<?php

namespace TradusBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CategoryRedirect.
 *
 * @ORM\Table(name="category_redirects")
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\CategoryRedirectRepository")
 */
class CategoryRedirect
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="category_from_l1", type="integer", nullable=false)
     */
    private $categoryFromL1;

    /**
     * @ORM\Column(name="category_to_l1", type="integer", nullable=false)
     */
    private $categoryToL1;

    /**
     * @ORM\Column(name="category_from_l2", type="integer", nullable=false)
     */
    private $categoryFromL2;

    /**
     * @ORM\Column(name="category_to_l2", type="integer", nullable=false)
     */
    private $categoryToL2;

    /**
     * @ORM\Column(name="category_from_l3", type="integer", nullable=true)
     */
    private $categoryFromL3;

    /**
     * @ORM\Column(name="category_to_l3", type="integer", nullable=true)
     */
    private $categoryToL3;

    /**
     * @ORM\Column(name="level_from_change", type="integer", nullable=false)
     */
    private $levelFromChange;

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
     * Set categoryFromL1.
     *
     * @param int $categoryFromL1
     *
     * @return CategoryRedirect
     */
    public function setCategoryFromL1($categoryFromL1 = null)
    {
        $this->categoryFromL1 = $categoryFromL1;

        return $this;
    }

    /**
     * Get categoryToL1.
     *
     * @return int
     */
    public function getCategoryToL1()
    {
        return $this->categoryToL1;
    }

    /**
     * Set categoryFromL2.
     *
     * @param int $categoryFromL2
     *
     * @return CategoryRedirect
     */
    public function setCategoryFromL2($categoryFromL2 = null)
    {
        $this->categoryFromL2 = $categoryFromL2;

        return $this;
    }

    /**
     * Get categoryToL2.
     *
     * @return int
     */
    public function getCategoryToL2()
    {
        return $this->categoryToL2;
    }

    /**
     * Set categoryFromL3.
     *
     * @param int|null $categoryFromL3
     *
     * @return CategoryRedirect
     */
    public function setCategoryFromL3($categoryFromL3 = null)
    {
        $this->categoryFromL3 = $categoryFromL3;

        return $this;
    }

    /**
     * Get categoryToL3.
     *
     * @return int|null
     */
    public function getCategoryToL3()
    {
        return $this->categoryToL3;
    }

    /**
     * Set levelFromChange.
     *
     * @param int $levelFromChange
     *
     * @return CategoryRedirect
     */
    public function setLevelFromChange($levelFromChange)
    {
        $this->levelFromChange = $levelFromChange;

        return $this;
    }

    /**
     * Get levelFromChange.
     *
     * @return int
     */
    public function getLevelFromChange()
    {
        return $this->levelFromChange;
    }
}

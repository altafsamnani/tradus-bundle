<?php

namespace TradusBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * SearchAnalytics.
 *
 * @ORM\Table(name="search_analytics")
 * @ORM\Entity(repositoryClass="TradusBundle\Repository\SearchAnalyticsRepository")
 */
class SearchAnalytics
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
     * @ORM\Column(name="country", type="string", length=50, nullable=true)
     */
    private $country;

    /**
     * @var string
     *
     * @ORM\Column(name="query_string", type="string", length=255, nullable=true)
     */
    private $queryString;

    /**
     * @var string
     *
     * @ORM\Column(name="search_url", type="string", length=255, nullable=true)
     */
    private $searchUrl;

    /**
     * @var string
     *
     * @ORM\Column(name="keyword", type="string", length=50, nullable=true)
     */
    private $keyword;

    /**
     * @var int
     *
     * @ORM\Column(name="cat_l1", type="integer", length=11, nullable=true)
     */
    private $categoryL1Id;

    /**
     * @var int
     *
     * @ORM\Column(name="cat_l2", type="integer", length=11, nullable=true)
     */
    private $categoryL2Id;

    /**
     * @var int
     *
     * @ORM\Column(name="cat_l3", type="integer", length=11, nullable=true)
     */
    private $categoryL3Id;

    /**
     * @var int
     *
     * @ORM\Column(name="result_count", type="integer", nullable=true)
     */
    private $resultCount;

    /**
     * @var int
     *
     * @ORM\Column(name="hits", type="integer", nullable=true)
     */
    private $hits;

    /**
     * @var int
     *
     * @ORM\Column(name="hits0", type="integer", nullable=true)
     */
    private $hits0;

    /**
     * @var int
     *
     * @ORM\Column(name="hits1", type="integer", nullable=true)
     */
    private $hits1;

    /**
     * @var int
     *
     * @ORM\Column(name="hits7", type="integer", nullable=true)
     */
    private $hits7;

    /**
     * @var int
     *
     * @ORM\Column(name="hits30", type="integer", nullable=true)
     */
    private $hits30;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private $updatedAt;

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
     * @param string|null $country
     * @return $this
     */
    public function setCountry($country = null)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country.
     *
     * @return string|null
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param string|null $queryString
     * @return $this
     */
    public function setQueryString($queryString)
    {
        $this->queryString = $queryString;

        return $this;
    }

    /**
     * Get QueryString.
     *
     * @return string|null
     */
    public function getQueryString()
    {
        return $this->queryString;
    }

    /**
     * @param string|null $searchUrl
     * @return $this
     */
    public function setSearchUrl($searchUrl)
    {
        $this->searchUrl = $searchUrl;

        return $this;
    }

    /**
     * Get searchUrl.
     *
     * @return string|null
     */
    public function getSearchUrl()
    {
        return $this->searchUrl;
    }

    /**
     * @param string|null $keyword
     * @return $this
     */
    public function setKeyword($keyword = null)
    {
        $this->keyword = $keyword;

        return $this;
    }

    /**
     * Get keyword.
     *
     * @return string|null
     */
    public function getKeyword()
    {
        return $this->keyword;
    }

    /**
     * @param $categoryL1Id
     * @return $this
     */
    public function setCategoryL1Id($categoryL1Id)
    {
        $this->categoryL1Id = $categoryL1Id;

        return $this;
    }

    /**
     * Get CategoryL1Id.
     *
     * @return int|null
     */
    public function getCategoryL1Id()
    {
        return $this->categoryL1Id;
    }

    /**
     * @param $categoryL2Id
     * @return $this
     */
    public function setCategoryL2Id($categoryL2Id)
    {
        $this->categoryL2Id = $categoryL2Id;

        return $this;
    }

    /**
     * Get CategoryL2Id.
     *
     * @return int|null
     */
    public function getCategoryL2Id()
    {
        return $this->categoryL2Id;
    }

    /**
     * @param $categoryL3Id
     * @return $this
     */
    public function setCategoryL3Id($categoryL3Id)
    {
        $this->categoryL3Id = $categoryL3Id;

        return $this;
    }

    /**
     * Get CategoryL3Id.
     *
     * @return int|null
     */
    public function getCategoryL3Id()
    {
        return $this->categoryL3Id;
    }

    /**
     * @param int|null $resultCount
     * @return $this
     */
    public function setResultCount($resultCount = null)
    {
        $this->resultCount = $resultCount;

        return $this;
    }

    /**
     * Get resultCount.
     *
     * @return int|null
     */
    public function getResultCount()
    {
        return $this->resultCount;
    }

    /**
     * @param int|null $hits
     * @return $this
     */
    public function setHits($hits)
    {
        $this->hits = $hits;

        return $this;
    }

    /**
     * Get hits.
     *
     * @return int|null
     */
    public function getHits()
    {
        return $this->hits;
    }

    /**
     * @param int|null $hits0
     * @return $this
     */
    public function setHitsForToday($hits0)
    {
        $this->hits0 = $hits0;

        return $this;
    }

    /**
     * Get HitsForToday.
     *
     * @return int|null
     */
    public function getHitsForToday()
    {
        return $this->hits0;
    }

    /**
     * @param int|null $hits1
     * @return $this
     */
    public function setHitsForYesterday($hits1)
    {
        $this->hits1 = $hits1;

        return $this;
    }

    /**
     * Get HitsForYesterday.
     *
     * @return int|null
     */
    public function getHitsForYesterday()
    {
        return $this->hits1;
    }

    /**
     * @param int|null $hits7
     * @return $this
     */
    public function setHitsForWeek($hits7)
    {
        $this->hits7 = $hits7;

        return $this;
    }

    /**
     * Get HitsForWeek.
     *
     * @return int|null
     */
    public function getHitsForWeek()
    {
        return $this->hits7;
    }

    /**
     * @param int|null $hits30
     * @return $this
     */
    public function setHitsForMonth($hits30)
    {
        $this->hits30 = $hits30;

        return $this;
    }

    /**
     * Get HitsForMonth.
     *
     * @return int|null
     */
    public function getHitsForMonth()
    {
        return $this->hits30;
    }

    /**
     * @param \DateTime $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt.
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt.
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
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
    public function setSitecodeId(int $sitecodeId): void
    {
        $this->sitecodeId = $sitecodeId ? $sitecodeId : Sitecodes::SITECODE_TRADUS;
    }
}

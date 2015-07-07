<?php

namespace ICup\Bundle\PublicSiteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\NewsForm
 */
class NewsForm
{
    /**
     * @var integer $id
     */
    private $id;

    /**
     * @var integer $pid
     * Relation to Tournament - pid=tournament.id 
     */
    private $pid;

    /**
     * @var string $date
     * Date for this event to happen - YYYYMMDD
     */
    private $date;

    /**
     * @var integer $mid
     * Relation to Match - mid=match.id
     */
    private $mid;

    /**
     * @var integer $cid
     * Relation to Team - cid=team.id
     */
    private $cid;

    /**
     * @var integer $newstype
     */
    private $newstype;

    /**
     * @var integer $newsno
     */
    private $newsno;

    /**
     * @var string $language
     */
    private $language;

    /**
     * @var string $title
     */
    private $title;

    /**
     * @var string $context
     */
    private $context;

    /**
     * @param mixed $id
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set parent id - related tournament
     *
     * @param integer $pid
     * @return Category
     */
    public function setPid($pid)
    {
        $this->pid = $pid;
    
        return $this;
    }

    /**
     * Get parent id - related tournament
     *
     * @return integer 
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * Set date
     *
     * @param string $date
     * @return Match
     */
    public function setDate($date)
    {
        $this->date = $date;
    
        return $this;
    }

    /**
     * @return int
     */
    public function getMid() {
        return $this->mid;
    }

    /**
     * @param int $mid
     */
    public function setMid($mid) {
        $this->mid = $mid;
    }

    /**
     * @return int
     */
    public function getCid() {
        return $this->cid;
    }

    /**
     * @param int $cid
     */
    public function setCid($cid) {
        $this->cid = $cid;
    }

    /**
     * @return int
     */
    public function getNewstype() {
        return $this->newstype;
    }

    /**
     * @param int $newstype
     */
    public function setNewstype($newstype) {
        $this->newstype = $newstype;
    }

    /**
     * @return int
     */
    public function getNewsno() {
        return $this->newsno;
    }

    /**
     * @param int $newsno
     */
    public function setNewsno($newsno) {
        $this->newsno = $newsno;
    }

    /**
     * @return string
     */
    public function getLanguage() {
        return $this->language;
    }

    /**
     * @param string $language
     */
    public function setLanguage($language) {
        $this->language = $language;
    }

    /**
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title) {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getContext() {
        return $this->context;
    }

    /**
     * @param string $context
     */
    public function setContext($context) {
        $this->context = $context;
    }

    /**
     * Get date
     *
     * @return string 
     */
    public function getDate()
    {
        return $this->date;
    }
}
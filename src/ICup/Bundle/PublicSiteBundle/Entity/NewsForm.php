<?php

namespace ICup\Bundle\PublicSiteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;

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
     * @var Tournament $tournament
     * Relation to Tournament - pid=tournament.id 
     */
    private $tournament;

    /**
     * @var string $date
     * Date for this event to happen - YYYYMMDD
     */
    private $date;

    /**
     * @var Match $match
     * Relation to Match - mid=match.id
     */
    private $match;

    /**
     * @var Team $team
     * Relation to Team - cid=team.id
     */
    private $team;

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
     * @return Tournament
     */
    public function getTournament() {
        return $this->tournament;
    }

    /**
     * @param Tournament $tournament
     * @return NewsForm
     */
    public function setTournament($tournament) {
        $this->tournament = $tournament;
        return $this;
    }

    /**
     * @return Match
     */
    public function getMatch() {
        return $this->match;
    }

    /**
     * @param Match $match
     * @return NewsForm
     */
    public function setMatch($match) {
        $this->match = $match;
        return $this;
    }

    /**
     * @return Team
     */
    public function getTeam() {
        return $this->team;
    }

    /**
     * @param Team $team
     * @return NewsForm
     */
    public function setTeam($team) {
        $this->team = $team;
        return $this;
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

    /**
     * Set date
     *
     * @param string $date
     * @return NewsForm
     */
    public function setDate($date)
    {
        $this->date = $date;
    
        return $this;
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

}
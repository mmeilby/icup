<?php

namespace ICup\Bundle\PublicSiteBundle\Entity;

class MatchSearchForm
{
    /**
     * @var integer $tournament
     * Relation to tournament - tournament=tournament.id
     */
    private $tournament;
    
    /**
     * @var integer $matchno
     * Official match no
     */
    private $matchno;

    /**
     * @var String $date
     * Event type - match played, home team disqualified, away team disqualified
     */
    private $date;

    /**
     * @var integer $category
     * Scored goals by home team
     */
    private $category;

    /**
     * @var integer $group
     * Scored goals by home team
     */
    private $group;
    
    /**
     * @var integer $playground
     * Scored goals by away team
     */
    private $playground;

    /**
     * @return int
     */
    public function getTournament() {
        return $this->tournament;
    }

    /**
     * @param int $tournament
     * @return MatchSearchForm
     */
    public function setTournament($tournament) {
        $this->tournament = $tournament;
        return $this;
    }

    /**
     * @return int
     */
    public function getMatchno() {
        return $this->matchno;
    }

    /**
     * @param int $matchno
     * @return MatchSearchForm
     */
    public function setMatchno($matchno) {
        $this->matchno = $matchno;
        return $this;
    }

    /**
     * @return String
     */
    public function getDate() {
        return $this->date;
    }

    /**
     * @param String $date
     * @return MatchSearchForm
     */
    public function setDate($date) {
        $this->date = $date;
        return $this;
    }

    /**
     * @return int
     */
    public function getCategory() {
        return $this->category;
    }

    /**
     * @param int $category
     * @return MatchSearchForm
     */
    public function setCategory($category) {
        $this->category = $category;
        return $this;
    }

    /**
     * @return int
     */
    public function getGroup() {
        return $this->group;
    }

    /**
     * @param int $group
     * @return MatchSearchForm
     */
    public function setGroup($group) {
        $this->group = $group;
        return $this;
    }

    /**
     * @return int
     */
    public function getPlayground() {
        return $this->playground;
    }

    /**
     * @param int $playground
     * @return MatchSearchForm
     */
    public function setPlayground($playground) {
        $this->playground = $playground;
        return $this;
    }
}
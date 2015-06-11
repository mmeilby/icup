<?php
namespace ICup\Bundle\PublicSiteBundle\Entity;

class TeamStat {

    public $id;
    public $club;
    public $name;
    public $country;
    public $group;

    public $matches = 0;
    public $score = 0;
    public $goals = 0;
    public $diff = 0;
    public $points = 0;
    public $tiepoints = 0;
    
    public $won = 0;
    public $maxscore = 0;
    public $maxdiff = 0;

    /**
     * @return mixed
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getClub() {
        return $this->club;
    }

    /**
     * @param mixed $club
     */
    public function setClub($club) {
        $this->club = $club;
    }

    /**
     * @return mixed
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getCountry() {
        return $this->country;
    }

    /**
     * @param mixed $country
     */
    public function setCountry($country) {
        $this->country = $country;
    }

    /**
     * @return mixed
     */
    public function getGroup() {
        return $this->group;
    }

    /**
     * @param mixed $group
     */
    public function setGroup($group) {
        $this->group = $group;
    }

    /**
     * @return int
     */
    public function getMatches() {
        return $this->matches;
    }

    /**
     * @param int $matches
     */
    public function setMatches($matches) {
        $this->matches = $matches;
    }

    /**
     * @return int
     */
    public function getScore() {
        return $this->score;
    }

    /**
     * @param int $score
     */
    public function setScore($score) {
        $this->score = $score;
    }

    /**
     * @return int
     */
    public function getGoals() {
        return $this->goals;
    }

    /**
     * @param int $goals
     */
    public function setGoals($goals) {
        $this->goals = $goals;
    }

    /**
     * @return int
     */
    public function getDiff() {
        return $this->diff;
    }

    /**
     * @param int $diff
     */
    public function setDiff($diff) {
        $this->diff = $diff;
    }

    /**
     * @return int
     */
    public function getPoints() {
        return $this->points;
    }

    /**
     * @param int $points
     */
    public function setPoints($points) {
        $this->points = $points;
    }

    /**
     * @return int
     */
    public function getTiepoints() {
        return $this->tiepoints;
    }

    /**
     * @param int $tiepoints
     */
    public function setTiepoints($tiepoints) {
        $this->tiepoints = $tiepoints;
    }

    /**
     * @return int
     */
    public function getWon() {
        return $this->won;
    }

    /**
     * @param int $won
     */
    public function setWon($won) {
        $this->won = $won;
    }

    /**
     * @return int
     */
    public function getMaxscore() {
        return $this->maxscore;
    }

    /**
     * @param int $maxscore
     */
    public function setMaxscore($maxscore) {
        $this->maxscore = $maxscore;
    }

    /**
     * @return int
     */
    public function getMaxdiff() {
        return $this->maxdiff;
    }

    /**
     * @param int $maxdiff
     */
    public function setMaxdiff($maxdiff) {
        $this->maxdiff = $maxdiff;
    }
}
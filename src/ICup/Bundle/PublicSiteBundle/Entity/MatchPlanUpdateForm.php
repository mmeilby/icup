<?php

namespace ICup\Bundle\PublicSiteBundle\Entity;

class MatchPlanUpdateForm
{
    /**
     * @var String $date
     */
    protected $date;

    /**
     * @var String $matchtime
     */
    protected $matchtime;

    /**
     * @var integer $timeslot
     */
    protected $timeslot;
    
    /**
     * @var integer $venue
     */
    protected $venue;

    /**
     * @return String
     */
    public function getDate() {
        return $this->date;
    }

    /**
     * @param String $date
     * @return MatchPlanUpdateForm
     */
    public function setDate($date) {
        $this->date = $date;
        return $this;
    }

    /**
     * @return String
     */
    public function getMatchtime() {
        return $this->matchtime;
    }

    /**
     * @param String $matchtime
     * @return MatchPlanUpdateForm
     */
    public function setMatchtime($matchtime) {
        $this->matchtime = $matchtime;
        return $this;
    }

    /**
     * @return int
     */
    public function getTimeslot() {
        return $this->timeslot;
    }

    /**
     * @param int $timeslot
     * @return MatchPlanUpdateForm
     */
    public function setTimeslot($timeslot) {
        $this->timeslot = $timeslot;
        return $this;
    }

    /**
     * @return int
     */
    public function getVenue() {
        return $this->venue;
    }

    /**
     * @param int $venue
     * @return MatchPlanUpdateForm
     */
    public function setVenue($venue) {
        $this->venue = $venue;
        return $this;
    }
}
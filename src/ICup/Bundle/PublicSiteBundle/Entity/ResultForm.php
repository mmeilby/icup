<?php

namespace ICup\Bundle\PublicSiteBundle\Entity;

class ResultForm
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
     * @var integer $event
     * Event type - match played, home team disqualified, away team disqualified
     */
    private $event;

    /**
     * @var integer $scoreA
     * Scored goals by home team
     */
    private $scoreA;
    
    /**
     * @var integer $scoreB
     * Scored goals by away team
     */
    private $scoreB;

    /* Event type - match is played */
    public static $EVENT_MATCH_PLAYED = 0;
    /* Event type - home team was disqualified - did not show up/used illegal players */
    public static $EVENT_HOME_DISQ = 1;
    /* Event type - away team was disqualified - did not show up/used illegal players */
    public static $EVENT_AWAY_DISQ = 2;
    /* Event type - the match was not played */
    public static $EVENT_NOT_PLAYED = 3;

    /**
     * Set tournament relation
     *
     * @param integer $tournament
     * @return ResultForm
     */
    public function setTournament($tournament)
    {
        $this->tournament = $tournament;
    
        return $this;
    }

    /**
     * Get tournament relation
     *
     * @return integer 
     */
    public function getTournament()
    {
        return $this->tournament;
    }

    /**
     * Set match no
     *
     * @param integer $matchno
     * @return ResultForm
     */
    public function setMatchno($matchno)
    {
        $this->matchno = $matchno;
    
        return $this;
    }

    /**
     * Get match no
     *
     * @return integer 
     */
    public function getMatchno()
    {
        return $this->matchno;
    }

    /**
     * Set event type
     * Event type - match played, home team disqualified, away team disqualified
     *
     * @param integer $event
     * @return ResultForm
     */
    public function setEvent($event)
    {
        $this->event = $event;
    
        return $this;
    }

    /**
     * Get event type
     * Event type - match played, home team disqualified, away team disqualified
     *
     * @return integer 
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * Set scored goals by home team
     *
     * @param integer $scoreA
     * @return ResultForm
     */
    public function setScoreA($scoreA)
    {
        $this->scoreA = $scoreA;
    
        return $this;
    }

    /**
     * Get scored goals by home team
     *
     * @return integer 
     */
    public function getScoreA()
    {
        return $this->scoreA;
    }

    /**
     * Set scored goals by away team
     *
     * @param integer $scoreB
     * @return ResultForm
     */
    public function setScoreB($scoreB)
    {
        $this->scoreB = $scoreB;
    
        return $this;
    }

    /**
     * Get scored goals by away team
     *
     * @return integer 
     */
    public function getScoreB()
    {
        return $this->scoreB;
    }
}
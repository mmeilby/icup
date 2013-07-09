<?php

namespace ICup\Bundle\PublicSiteBundle\Entity;

class Match
{
    /**
     * @var integer $pid
     * Relation to group - pid=group.id
     */
    private $pid;

    /**
     * @var integer $playground
     * Relation to playground - playground=playground.id
     */
    private $playground;
    
    /**
     * @var string $time
     * Match start time - HH:MM
     */
    private $time;

    /**
     * @var string $date
     * Match start date - DD/MM/YYYY
     */
    private $date;

    /**
     * @var integer $matchno
     * Official match no
     */
    private $matchno;

    /**
     * @var integer $teamA
     * Relation to home team
     */
    private $teamA;
    
    /**
     * @var integer $teamB
     * Relation to away team
     */
    private $teamB;

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
     * Set parent id - related group
     *
     * @param integer $pid
     * @return Match
     */
    public function setPid($pid)
    {
        $this->pid = $pid;
    
        return $this;
    }

    /**
     * Get parent id - related group
     *
     * @return integer 
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * Set playground relation
     *
     * @param integer $playground
     * @return Match
     */
    public function setPlayground($playground)
    {
        $this->playground = $playground;
    
        return $this;
    }

    /**
     * Get playground relation
     *
     * @return integer 
     */
    public function getPlayground()
    {
        return $this->playground;
    }

    /**
     * Set time
     *
     * @param string $time
     * @return Match
     */
    public function setTime($time)
    {
        $this->time = $time;
    
        return $this;
    }

    /**
     * Get time
     *
     * @return string 
     */
    public function getTime()
    {
        return $this->time;
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
     * Get date
     *
     * @return string 
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set match no
     *
     * @param integer $matchno
     * @return Match
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
     * Set home team
     *
     * @param integer $teamA
     * @return Match
     */
    public function setTeamA($teamA)
    {
        $this->teamA = $teamA;
    
        return $this;
    }

    /**
     * Get home team
     *
     * @return integer 
     */
    public function getTeamA()
    {
        return $this->teamA;
    }

    /**
     * Set away team
     *
     * @param integer $teamB
     * @return Match
     */
    public function setTeamB($teamB)
    {
        $this->teamB = $teamB;
    
        return $this;
    }

    /**
     * Get away team
     *
     * @return integer 
     */
    public function getTeamB()
    {
        return $this->teamB;
    }
}
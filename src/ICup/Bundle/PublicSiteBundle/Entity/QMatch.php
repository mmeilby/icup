<?php

namespace ICup\Bundle\PublicSiteBundle\Entity;

class QMatch
{
    /**
     * @var integer $id
     * Id for this match
     */
    private $id;

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
     * @var integer $groupA
     * Relation to home group
     */
    private $groupA;
    
    /**
     * @var integer $groupB
     * Relation to away group
     */
    private $groupB;

    /**
     * @var integer $rankA
     * Relation to home rank
     */
    private $rankA;
    
    /**
     * @var integer $rankB
     * Relation to away rank
     */
    private $rankB;

    /**
     * Set id
     *
     * @param integer $id
     * @return Match
     */
    public function setId($id)
    {
        $this->id = $id;
    
        return $this;
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
     * @param integer $groupA
     * @return Match
     */
    public function setGroupA($groupA)
    {
        $this->groupA = $groupA;
    
        return $this;
    }

    /**
     * Get home team
     *
     * @return integer 
     */
    public function getGroupA()
    {
        return $this->groupA;
    }

    /**
     * Set away team
     *
     * @param integer $groupB
     * @return Match
     */
    public function setGroupB($groupB)
    {
        $this->groupB = $groupB;
    
        return $this;
    }

    /**
     * Get away team
     *
     * @return integer 
     */
    public function getGroupB()
    {
        return $this->groupB;
    }
    
    /**
     * Set home team
     *
     * @param integer $rankA
     * @return Match
     */
    public function setRankA($rankA)
    {
        $this->rankA = $rankA;
    
        return $this;
    }

    /**
     * Get home team
     *
     * @return integer 
     */
    public function getRankA()
    {
        return $this->rankA;
    }

    /**
     * Set away team
     *
     * @param integer $rankB
     * @return Match
     */
    public function setRankB($rankB)
    {
        $this->rankB = $rankB;
    
        return $this;
    }

    /**
     * Get away team
     *
     * @return integer 
     */
    public function getRankB()
    {
        return $this->rankB;
    }
}
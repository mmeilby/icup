<?php

namespace ICup\Bundle\PublicSiteBundle\Entity;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Playground;
use ICup\Bundle\PublicSiteBundle\Entity\TeamInfo;
use DateTime;

class MatchPlan
{
    /**
     * @var Category $id
     * Category object
     */
    private $category;

    /**
     * @var Group $pid
     * Group object
     */
    private $group;

    /**
     * @var Playground $playground
     * Playground object
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
     * @var TeamInfo $teamA
     * Relation to home team
     */
    private $teamA;
    
    /**
     * @var TeamInfo $teamB
     * Relation to away team
     */
    private $teamB;

    /**
     * @var boolean $fixed
     * Indicates this match to be fixed - not available for change
     */
    private $fixed;

    /**
     * Set category
     *
     * @param Category $category
     * @return MatchPlanning
     */
    public function setCategory($category)
    {
        $this->category = $category;
    
        return $this;
    }

    /**
     * Get category
     *
     * @return Category 
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set parent id - related group
     *
     * @param Group $group
     * @return MatchPlanning
     */
    public function setGroup($group)
    {
        $this->group = $group;
    
        return $this;
    }

    /**
     * Get parent id - related group
     *
     * @return Group 
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * Set playground relation
     *
     * @param Playground $playground
     * @return MatchPlanning
     */
    public function setPlayground($playground)
    {
        $this->playground = $playground;
    
        return $this;
    }

    /**
     * Get playground relation
     *
     * @return Playground 
     */
    public function getPlayground()
    {
        return $this->playground;
    }

    /**
     * Set time
     *
     * @param string $time
     * @return MatchPlanning
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
     * @return MatchPlanning
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
     * @return MatchPlanning
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
     * @param TeamInfo $teamA
     * @return MatchPlanning
     */
    public function setTeamA($teamA)
    {
        $this->teamA = $teamA;
    
        return $this;
    }

    /**
     * Get home team
     *
     * @return TeamInfo
     */
    public function getTeamA()
    {
        return $this->teamA;
    }

    /**
     * Set away team
     *
     * @param TeamInfo $teamB
     * @return MatchPlanning
     */
    public function setTeamB($teamB)
    {
        $this->teamB = $teamB;
    
        return $this;
    }

    /**
     * Get away team
     *
     * @return TeamInfo
     */
    public function getTeamB()
    {
        return $this->teamB;
    }

    /**
     * @return boolean
     */
    public function isFixed()
    {
        return $this->fixed;
    }

    /**
     * @param boolean $fixed
     */
    public function setFixed($fixed)
    {
        $this->fixed = $fixed;
    }

    public function getSchedule() {
        return Date::getDateTime($this->date, $this->time);
    }
}
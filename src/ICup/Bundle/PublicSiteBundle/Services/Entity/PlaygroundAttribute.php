<?php

namespace ICup\Bundle\PublicSiteBundle\Services\Entity;

use DateTime;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Timeslot;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Playground;

class PlaygroundAttribute
{
    /**
     * @var integer $id
     * Id for this attribute
     */
    private $id;

    /**
     * @var integer $pid
     * Relation to Playground - pid=playground.id
     */
    private $playground;

    /**
     * @var integer $timeslot
     * Relation to Timeslot - pid=timeslot.id 
     */
    private $timeslot;

    /**
     * @var array $categories
     * List of categories related to playground attribute
     */
    private $categories;

    /**
     * @var string $schedule
     * Date for this calendar event
     */
    private $schedule;

    /**
     * @var string $timeleft
     * Calendar event start time - HH:MM
     */
    private $timeleft;
    
    private $matchlist;

    /**
     * Set id
     *
     * @param integer $id
     * @return PlaygroundAttribute
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
     * Set parent id - related tournament
     *
     * @param Playground $playground
     * @return PlaygroundAttribute
     */
    public function setPlayground($playground)
    {
        $this->playground = $playground;
    
        return $this;
    }

    /**
     * Get parent id - related tournament
     *
     * @return Playground 
     */
    public function getPlayground()
    {
        return $this->playground;
    }

    /**
     * Set child id - related timeslot
     *
     * @param Timeslot $timeslot
     * @return PlaygroundAttribute
     */
    public function setTimeslot($timeslot)
    {
        $this->timeslot = $timeslot;
    
        return $this;
    }

    /**
     * Get child id - related timeslot
     *
     * @return Timeslot
     */
    public function getTimeslot()
    {
        return $this->timeslot;
    }

    /**
     * Get related categories
     *
     * @return array 
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * Set related categories
     *
     * @param array $categories
     * @return PlaygroundAttribute
     */
    public function setCategories($categories)
    {
        $this->categories = $categories;
    
        return $this;
    }
    
    /**
     * Set date
     *
     * @param DateTime $date
     * @return PlaygroundAttribute
     */
    public function setSchedule($schedule)
    {
        $this->schedule = $schedule;
    
        return $this;
    }

    /**
     * Get date
     *
     * @return DateTime
     */
    public function getSchedule()
    {
        return $this->schedule;
    }

    /**
     * Set start time
     *
     * @param integer $start
     * @return PlaygroundAttribute
     */
    public function setTimeleft($timeleft)
    {
        $this->timeleft = $timeleft;
    
        return $this;
    }

    /**
     * Get start time
     *
     * @return integer 
     */
    public function getTimeleft()
    {
        return $this->timeleft;
    }
    
    public function setMatchlist($matchlist) {
        $this->matchlist = $matchlist;
        
        return $this;
    }
    
    public function getMatchlist() {
        return $this->matchlist;
    }
}
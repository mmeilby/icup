<?php

namespace ICup\Bundle\PublicSiteBundle\Entity;

class Event
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
     * Date for this event to happen - DD/MM/YYYY
     */
    private $date;

    /**
     * @var integer $event
     * Event:
     *    1: Tournament is open for enrollment
     *    2: Enrollment is closed
     *    3: Tournament is started
     *    4: Tournament is over - hall of fame is visual
     *    9: Tournament archived - only visual for editor administrators
     */
    private $event;

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
     * Get date
     *
     * @return string 
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set event
     *
     * @param integer $event
     * @return Event
     */
    public function setEvent($event)
    {
        $this->event = $event;
    
        return $this;
    }

    /**
     * Get event
     *
     * @return integer 
     */
    public function getEvent()
    {
        return $this->event;
    }
}
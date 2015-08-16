<?php

namespace ICup\Bundle\PublicSiteBundle\Entity;

class PAttrForm
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
    private $pid;

    /**
     * @var integer $timeslot
     * Relation to Timeslot - pid=timeslot.id 
     */
    private $timeslot;

    /**
     * @var string $date
     * Date for this calendar event - DD/MM/YYYY
     */
    private $date;

    /**
     * @var string $start
     * Calendar event start time - HH:MM
     */
    private $start;

    /**
     * @var string $end
     * Calendar event end time - HH:MM
     */
    private $end;

    /**
     * @var boolean $finals
     * Indicates this timeslot is restricted to finals
     */
    private $finals;

    /**
     * @var array $categories
     * List of categories related to playground attribute
     */
    private $categories;

    /**
     * Set id
     *
     * @param integer $id
     * @return PAttrForm
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
     * @return PAttrForm
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
     * Set child id - related timeslot
     *
     * @param integer $timeslot
     * @return PAttrForm
     */
    public function setTimeslot($timeslot)
    {
        $this->timeslot = $timeslot;
    
        return $this;
    }

    /**
     * Get child id - related timeslot
     *
     * @return integer 
     */
    public function getTimeslot()
    {
        return $this->timeslot;
    }
    
    /**
     * Set date
     *
     * @param string $date
     * @return PAttrForm
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
     * Set start time
     *
     * @param string $start
     * @return PAttrForm
     */
    public function setStart($start)
    {
        $this->start = $start;
    
        return $this;
    }

    /**
     * Get start time
     *
     * @return string 
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Set end time
     *
     * @param string $end
     * @return PAttrForm
     */
    public function setEnd($end)
    {
        $this->end = $end;
    
        return $this;
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
     * @return boolean
     */
    public function isFinals() {
        return $this->finals;
    }

    /**
     * @param boolean $finals
     * @return PAttrForm
     */
    public function setFinals($finals) {
        $this->finals = $finals;
        return $this;
    }

    /**
     * Set related categories
     *
     * @param array $categories
     * @return PAttrForm
     */
    public function setCategories($categories)
    {
        $this->categories = $categories;
    
        return $this;
    }

    /**
     * Get end time
     *
     * @return string 
     */
    public function getEnd()
    {
        return $this->end;
    }
}
<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\ORM\Mapping as ORM;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Playground
 *
 * @ORM\Table(name="timeslots",uniqueConstraints={@ORM\UniqueConstraint(name="IdxByNo", columns={"no", "pid"})})
 * @ORM\Entity
 */
class Timeslot
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer $pid
     * Relation to Tournament - pid=tournament.id 
     * @ORM\Column(name="pid", type="integer", nullable=false)
     */
    private $pid;

    /**
     * @var string $name
     * Timeslot name used in lists
     * @ORM\Column(name="name", type="string", length=50, nullable=false)
     */
    private $name;

    /**
     * @var integer $capacity
     * No of matches a team can participate per day for this timeslot
     * @ORM\Column(name="capacity", type="integer", nullable=false)
     */
    private $capacity;

    /**
     * @var integer $restperiod
     * Amount of time in minutes required between two matches for a team in this timeslot
     * Note: makes only sense when capacity is greater than 1
     *       this amount of time should be aligned with the match time used
     * @ORM\Column(name="restperiod", type="integer", nullable=false)
     */
    private $restperiod;

    /**
     * @var string $penalty
     * Penalty for assigning a team at different sites in this timeslot - Y=Yes, N=No
     * Note: makes only sense when capacity is greater than 1
     *       however penalty in general means that teams assigned to more sites are discouraged
     * @ORM\Column(name="penalty", type="string", length=1, nullable=false)
     */
    private $penalty;

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
     * @return Timeslot
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
     * Set name
     *
     * @param string $name
     * @return Timeslot
     */
    public function setName($name)
    {
        $this->name = $name;
    
        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * Set timeslot capacity
     *
     * @param integer $capacity
     * @return Timeslot
     */
    public function setCapacity($capacity)
    {
        $this->capacity = $capacity;
    
        return $this;
    }

    /**
     * Get timeslot capacity
     *
     * @return integer 
     */
    public function getCapacity()
    {
        return $this->capacity;
    }

    /**
     * Set timeslot restperiod
     *
     * @param integer $restperiod
     * @return Timeslot
     */
    public function setRestperiod($restperiod)
    {
        $this->restperiod = $restperiod;
    
        return $this;
    }

    /**
     * Get timeslot restperiod
     *
     * @return integer 
     */
    public function getRestperiod()
    {
        return $this->restperiod;
    }

    /**
     * Set timeslot penalty
     *
     * @param integer $penalty
     * @return Timeslot
     */
    public function setPenalty($penalty)
    {
        $this->penalty = $penalty;
    
        return $this;
    }

    /**
     * Get timeslot penalty
     *
     * @return integer 
     */
    public function getPenalty()
    {
        return $this->penalty;
    }
}
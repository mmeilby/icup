<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use JsonSerializable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Playground
 *
 * @ORM\Table(name="timeslots",uniqueConstraints={@ORM\UniqueConstraint(name="TimeslotNameConstraint", columns={"name", "pid"})})
 * @ORM\Entity
 */
class Timeslot implements JsonSerializable
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var Tournament $tournament
     * Relation to Tournament
     * @ORM\ManyToOne(targetEntity="Tournament", inversedBy="timeslots")
     * @ORM\JoinColumn(name="pid", referencedColumnName="id")
     */
    protected $tournament;

    /**
     * @var string $name
     * Timeslot name used in lists
     * @ORM\Column(name="name", type="string", length=50, nullable=false)
     */
    protected $name;

    /**
     * @var integer $capacity
     * No of matches a team can participate per day for this timeslot
     * @ORM\Column(name="capacity", type="integer", nullable=false)
     */
    protected $capacity;

    /**
     * @var integer $restperiod
     * Amount of time in minutes required between two matches for a team in this timeslot
     * Note: makes only sense when capacity is greater than 1
     *       this amount of time should be aligned with the match time used
     * @ORM\Column(name="restperiod", type="integer", nullable=false)
     */
    protected $restperiod;

    /**
     * @var string $penalty
     * Penalty for assigning a team at different sites in this timeslot - Y=Yes, N=No
     * Note: makes only sense when capacity is greater than 1
     *       however penalty in general means that teams assigned to more sites are discouraged
     * @ORM\Column(name="penalty", type="string", length=1, nullable=false)
     */
    protected $penalty;

    /**
     * @var ArrayCollection $playgroundattributes
     * Collection of playground relations to playground attributes
     * @ORM\OneToMany(targetEntity="PlaygroundAttribute", mappedBy="timeslot", cascade={"persist", "remove"})
     */
    protected $playgroundattributes;

    /**
     * Playground constructor.
     */
    public function __construct() {
        $this->playgroundattributes = new ArrayCollection();
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
     * @return Tournament
     */
    public function getTournament() {
        return $this->tournament;
    }

    /**
     * @param Tournament $tournament
     * @return Timeslot
     */
    public function setTournament($tournament) {
        $this->tournament = $tournament;
        return $this;
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
     * @param boolean $penalty
     * @return Timeslot
     */
    public function setPenalty($penalty)
    {
        $this->penalty = $penalty ? "Y" : "N";
    
        return $this;
    }

    /**
     * Get timeslot penalty
     *
     * @return boolean
     */
    public function getPenalty()
    {
        return $this->penalty == "Y";
    }

    /**
     * @return ArrayCollection
     */
    public function getPlaygroundattributes() {
        return $this->playgroundattributes;
    }

    public function __toString() {
        return $this->getName();
    }

    /**
     * (PHP 5 &gt;= 5.4.0)<br/>
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     */
    function jsonSerialize() {
        return array(
            "objectType" => "Timeslot",
            "id" => $this->getId(), "name" => $this->getName(), "capacity" => $this->getCapacity(),
            "restperiod" => $this->getRestperiod(), "penalty" => $this->getPenalty()
        );
    }
}
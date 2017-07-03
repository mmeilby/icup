<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use JsonSerializable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use DateTime;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category
 *
 * @ORM\Table(name="playgroundattributes")
 * @ORM\Entity
 */
class PlaygroundAttribute implements JsonSerializable
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
     * @var Playground $playground
     * Relation to Playground
     * @ORM\ManyToOne(targetEntity="Playground", inversedBy="playgroundattributes")
     * @ORM\JoinColumn(name="pid", referencedColumnName="id")
     */
    protected $playground;

    /**
     * @var ArrayCollection $matchscheduleplans
     * Collection of relations to match schedule plans
     * @ORM\OneToMany(targetEntity="MatchSchedulePlan", mappedBy="playgroundAttribute", cascade={"persist"})
     */
    protected $matchscheduleplans;

    /**
     * @var ArrayCollection $categories
     * @ORM\ManyToMany(targetEntity="Category", inversedBy="playgroundattributes")
     * @ORM\JoinTable(name="parelations",
     *      joinColumns={@ORM\JoinColumn(name="pid", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="cid", referencedColumnName="id", onDelete="CASCADE")}
     *      )
     **/
    protected $categories;

    /**
     * @var Timeslot $timeslot
     * Relation to Timeslot
     * @ORM\ManyToOne(targetEntity="Timeslot", inversedBy="playgroundattributes")
     * @ORM\JoinColumn(name="timeslot", referencedColumnName="id")
     */
    protected $timeslot;

    /**
     * @var string $date
     * Date for this calendar event - DD/MM/YYYY
     * @ORM\Column(name="date", type="string", length=10, nullable=false)
     */
    protected $date;

    /**
     * @var string $start
     * Calendar event start time - HH:MM
     * @ORM\Column(name="start", type="string", length=5, nullable=false)
     */
    protected $start;

    /**
     * @var string $end
     * Calendar event end time - HH:MM
     * @ORM\Column(name="end", type="string", length=5, nullable=false)
     */
    protected $end;

    /**
     * @var boolean $finals
     * Indicates this timeslot is restricted to finals
     * @ORM\Column(name="finals", type="boolean", nullable=false)
     */
    protected $finals;

    /**
     * @var integer $classification
     * Indicates this timeslot is restricted to a specific classification (only valid for timeslots restricted to finals)
     * @ORM\Column(name="classification", type="integer", nullable=true)
     */
    protected $classification;

    /**
     * PlaygroundAttribute constructor.
     */
    public function __construct() {
        $this->categories = new ArrayCollection();
        $this->matchscheduleplans = new ArrayCollection();
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
     * @return Playground
     */
    public function getPlayground() {
        return $this->playground;
    }

    /**
     * @param Playground $playground
     * @return PlaygroundAttribute
     */
    public function setPlayground($playground) {
        $this->playground = $playground;
        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getMatchscheduleplans() {
        return $this->matchscheduleplans;
    }

    /**
     * @return ArrayCollection
     */
    public function getCategories() {
        return $this->categories;
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
     * Set date
     *
     * @param string $date
     * @return PlaygroundAttribute
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
     * @return PlaygroundAttribute
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
     * @return PlaygroundAttribute
     */
    public function setEnd($end)
    {
        $this->end = $end;
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

    public function setStartSchedule(DateTime $startdate) {
        $this->date = Date::getDate($startdate);
        $this->start = Date::getTime($startdate);
    }

    public function getStartSchedule() {
        return Date::getDateTime($this->date, $this->start);
    }

    public function setEndSchedule(DateTime $enddate) {
        $this->end = Date::getTime($enddate);
    }

    public function getEndSchedule() {
        return Date::getDateTime($this->date, $this->end);
    }

    /**
     * Set timeslot restriction
     *
     * @param boolean $final true if timeslot should be restricted to finals only
     * @return PlaygroundAttribute
     */
    public function setFinals($final)
    {
        $this->finals = $final;
        return $this;
    }

    /**
     * Get timeslot restriction
     *
     * @return boolean true if timeslot is restricted to finals only
     */
    public function getFinals()
    {
        return $this->finals;
    }

    /**
     * Get classification restriction
     * @return int restriction level - 0 = none
     */
    public function getClassification() {
        return $this->classification;
    }

    /**
     * Set classification restriction
     * @param int $classification restriction level - 0 = none
     * @return PlaygroundAttribute
     */
    public function setClassification($classification) {
        $this->classification = $classification;
        return $this;
    }

    public function __toString() {
        return $this->getPlayground()->getNo().". ".$this->getPlayground()->getName()." [".$this->getDate().": ".$this->getStart()."-".$this->getEnd()."]";
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
            "id" => $this->getId(),
            "timeslot" => $this->getTimeslot(),
            "classification" => $this->getClassification(),
            "finals" => $this->getFinals(),
            "categories" => $this->getCategories()->toArray(),
            'date' => Date::jsonDateSerialize($this->getDate()),
            'start' => Date::jsonTimeSerialize($this->getStart()),
            'end' => Date::jsonTimeSerialize($this->getEnd())
        );
    }
}
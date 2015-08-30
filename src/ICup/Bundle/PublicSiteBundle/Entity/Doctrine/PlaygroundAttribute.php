<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use DateTime;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category
 *
 * @ORM\Table(name="playgroundattributes")
 * @ORM\Entity
 */
class PlaygroundAttribute
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
     * @var ArrayCollection $categories
     * @ORM\ManyToMany(targetEntity="Category", inversedBy="playgroundattributes")
     * @ORM\JoinTable(name="parelations",
     *      joinColumns={@ORM\JoinColumn(name="pid", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="cid", referencedColumnName="id")}
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
     * PlaygroundAttribute constructor.
     */
    public function __construct() {
        $this->categories = new ArrayCollection();
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
     * Set match level
     *
     * @param boolean $final
     * @return PlaygroundAttribute
     */
    public function setFinals($final)
    {
        $this->finals = $final;
        return $this;
    }

    /**
     * Get match level
     *
     * @return boolean
     */
    public function getFinals()
    {
        return $this->finals;
    }
}
<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match
 *
 * @ORM\Table(name="matches",uniqueConstraints={@ORM\UniqueConstraint(name="IdxByGroup", columns={"pid", "id"})})
 * @ORM\Entity
 */
class Match implements JsonSerializable
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
     * @var Group $group
     * Relation to Group
     * @ORM\ManyToOne(targetEntity="Group", inversedBy="matches", cascade={"persist"})
     * @ORM\JoinColumn(name="pid", referencedColumnName="id")
     */
    protected $group;

    /**
     * @var Playground $playground
     * Relation to Playground
     * @ORM\ManyToOne(targetEntity="Playground", inversedBy="matches", cascade={"persist"})
     * @ORM\JoinColumn(name="playground", referencedColumnName="id")
     */
    protected $playground;

    /**
     * @var ArrayCollection $matchrelation
     * Collection of match relations to teams
     * @ORM\OneToMany(targetEntity="MatchRelation", mappedBy="match", cascade={"persist", "remove"})
     * @ORM\OrderBy({"awayteam" = "asc"})
     */
    protected $matchrelation;

    /**
     * @var ArrayCollection $qmatchrelation
     * Collection of match relations to qualifying prerequisites
     * @ORM\OneToMany(targetEntity="QMatchRelation", mappedBy="match", cascade={"persist", "remove"})
     * @ORM\OrderBy({"awayteam" = "asc"})
     */
    protected $qmatchrelation;

    /**
     * @var string $time
     * Match start time - Hi
     * @ORM\Column(name="time", type="string", length=5, nullable=false)
     */
    protected $time;

    /**
     * @var string $date
     * Match start date - Ymd
     * @ORM\Column(name="date", type="string", length=10, nullable=false)
     */
    protected $date;

    /**
     * @var integer $matchno
     * Official match no
     * @ORM\Column(name="matchno", type="integer", nullable=false)
     */
    protected $matchno;

    /**
     * Match constructor.
     */
    public function __construct() {
        $this->matchrelation = new ArrayCollection();
        $this->qmatchrelation = new ArrayCollection();
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
     * @param Group $group
     */
    public function setGroup(Group $group) {
        $this->group = $group;
    }

    /**
     * @return Group
     */
    public function getGroup() {
        return $this->group;
    }

    /**
     * Set playground relation
     *
     * @param Playground $playground
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
     * @return Playground
     */
    public function getPlayground()
    {
        return $this->playground;
    }

    /**
     * @return ArrayCollection
     */
    public function getMatchRelations() {
        return $this->matchrelation;
    }

    /**
     * @return ArrayCollection
     */
    public function getQMatchRelations() {
        return $this->qmatchrelation;
    }

    /**
     * @param MatchRelation|QMatchRelation $matchRelation
     * @return Match
     */
    public function addMatchRelation($matchRelation) {
        if ($matchRelation instanceof MatchRelation) {
            $this->matchrelation->add($matchRelation);
        }
        else {
            $this->qmatchrelation->add($matchRelation);
        }
        $matchRelation->setMatch($this);
        return $this;
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

    public function getSchedule() {
        return Date::getDateTime($this->date, $this->time);
    }

    public function __toString() {
        $txt =  $this->getDate()."  ".$this->getTime()."  ".
                $this->getGroup()->getCategory()->getName()."|".$this->getGroup()->getClassification().":".$this->getGroup()->getName()."  ".
                $this->getPlayground()->getName();
        if ($this->getQMatchRelations()->count() == 2) {
            $txt .= "  [".$this->getQMatchRelations()->first()." - ".$this->getQMatchRelations()->last()."]";
        }
        if ($this->getMatchRelations()->count() == 2) {
            $txt .= "  ".$this->getMatchRelations()->first()." - ".$this->getMatchRelations()->last();
        }
        return $txt;
    }

    /**
     * (PHP 5 &gt;= 5.4.0)<br/>
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     */
    function jsonSerialize() {
        $matchtype = "M";
        $qhome = array("id" => 0);
        $qaway = array("id" => 0);
        foreach ($this->getQMatchRelations() as $qmatchRelation) {
            /* @var $qmatchRelation QMatchRelation */
            if ($qmatchRelation->getAwayteam()) {
                $qaway = $qmatchRelation->jsonSerialize();
            }
            else {
                $qhome = $qmatchRelation->jsonSerialize();
            }
            $matchtype ="Q";
        }
        $home = array("id" => 0);
        $away = array("id" => 0);
        foreach ($this->getMatchRelations() as $matchRelation) {
            /* @var $matchRelation MatchRelation */
            if ($matchRelation->getAwayteam()) {
                $away = $matchRelation->jsonSerialize();
            }
            else {
                $home = $matchRelation->jsonSerialize();
            }

        }
        return array(
            "id" => $this->id,
            'matchno' => $this->matchno,
            "matchtype" => $matchtype,
            'date' => array(
                'raw' => $this->date,
                'js' => $this->date ? date_format($this->getSchedule(), "m/d/Y") : ''),
            'time' => array(
                'raw' => $this->time,
                'js' => $this->time ? date_format($this->getSchedule(), "H:i") : ''),
            'group' => $this->group->jsonSerialize(),
            'venue' => $this->playground->jsonSerialize(),
            'home' => array("qrel" => $qhome, "rel" => $home),
            'away' => array("qrel" => $qaway, "rel" => $away)
        );
    }
}
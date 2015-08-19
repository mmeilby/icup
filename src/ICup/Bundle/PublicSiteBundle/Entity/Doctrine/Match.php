<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Intl\Exception\MethodNotImplementedException;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match
 *
 * @ORM\Table(name="matches",uniqueConstraints={@ORM\UniqueConstraint(name="IdxByGroup", columns={"pid", "id"})})
 * @ORM\Entity
 */
class Match
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
     * @var Group $group
     * Relation to Group
     * @ORM\ManyToOne(targetEntity="Group", inversedBy="id")
     * @ORM\JoinColumn(name="pid", referencedColumnName="id")
     */
    private $group;

    /**
     * @var integer $playground
     * Relation to playground - playground=playground.id
     * @ORM\Column(name="playground", type="integer", nullable=false)
     */
    private $playground;

    /**
     * @var ArrayCollection $matchRelation
     * Collection of match relations to teams
     * @ORM\OneToMany(targetEntity="MatchRelation", mappedBy="match", cascade={"persist", "remove"})
     */
    private $matchRelation;

    /**
     * @var ArrayCollection $qmatchRelation
     * Collection of match relations to qualifying prerequisites
     * @ORM\OneToMany(targetEntity="QMatchRelation", mappedBy="match", cascade={"persist", "remove"})
     */
    private $qmatchRelation;

    /**
     * @var string $time
     * Match start time - Hi
     * @ORM\Column(name="time", type="string", length=5, nullable=false)
     */
    private $time;

    /**
     * @var string $date
     * Match start date - Ymd
     * @ORM\Column(name="date", type="string", length=10, nullable=false)
     */
    private $date;

    /**
     * @var integer $matchno
     * Official match no
     * @ORM\Column(name="matchno", type="integer", nullable=false)
     */
    private $matchno;

    /**
     * Match constructor.
     */
    public function __construct() {
        $this->matchRelation = new ArrayCollection();
        $this->qmatchRelation = new ArrayCollection();
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
     * @return ArrayCollection
     */
    public function getMatchRelations() {
        return $this->matchRelation;
    }

    /**
     * @return ArrayCollection
     */
    public function getQMatchRelations() {
        return $this->qmatchRelation;
    }

    /**
     * @param MatchRelation|QMatchRelation $matchRelation
     * @return Match
     */
    public function addMatchRelation($matchRelation) {
        if ($matchRelation instanceof MatchRelation) {
            $this->matchRelation->add($matchRelation);
        }
        else {
            $this->qmatchRelation->add($matchRelation);
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
}
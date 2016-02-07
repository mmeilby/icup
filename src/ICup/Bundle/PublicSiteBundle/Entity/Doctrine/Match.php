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
    protected $id;

    /**
     * @var Group $group
     * Relation to Group
     * @ORM\ManyToOne(targetEntity="Group", inversedBy="matches")
     * @ORM\JoinColumn(name="pid", referencedColumnName="id")
     */
    protected $group;

    /**
     * @var Playground $playground
     * Relation to Playground
     * @ORM\ManyToOne(targetEntity="Playground", inversedBy="matches")
     * @ORM\JoinColumn(name="playground", referencedColumnName="id")
     */
    protected $playground;

    /**
     * @var ArrayCollection $matchrelation
     * Collection of match relations to teams
     * @ORM\OneToMany(targetEntity="MatchRelation", mappedBy="match", cascade={"persist", "remove"})
     */
    protected $matchrelation;

    /**
     * @var ArrayCollection $qmatchrelation
     * Collection of match relations to qualifying prerequisites
     * @ORM\OneToMany(targetEntity="QMatchRelation", mappedBy="match", cascade={"persist", "remove"})
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
}
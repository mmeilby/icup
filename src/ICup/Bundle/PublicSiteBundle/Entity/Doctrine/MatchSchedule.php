<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchSchedule
 *
 * @ORM\Table(name="matchschedules")
 * @ORM\Entity
 */
class MatchSchedule
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
     * @var Tournament $tournament
     * Relation to Tournament
     * @ORM\ManyToOne(targetEntity="Tournament", inversedBy="id")
     * @ORM\JoinColumn(name="pid", referencedColumnName="id")
     */
    private $tournament;

    /**
     * @var Group $group
     * Relation to Group
     * @ORM\ManyToOne(targetEntity="Group", inversedBy="id")
     * @ORM\JoinColumn(name="gid", referencedColumnName="id")
     */
    private $group;

    /**
     * @var PlaygroundAttribute $tournament
     * Relation to PlaygroundAttribute
     * @ORM\ManyToOne(targetEntity="PlaygroundAttribute", inversedBy="id")
     * @ORM\JoinColumn(name="paid", referencedColumnName="id")
     */
    private $playgroundAttribute;

    /**
     * @var ArrayCollection $matchRelation
     * Collection of match relations to teams
     * @ORM\OneToMany(targetEntity="MatchScheduleRelation", mappedBy="matchSchedule", cascade={"persist", "remove"})
     */
    private $matchRelation;

    /**
     * @var ArrayCollection $qmatchRelation
     * Collection of match relations to qualifying prerequisites
     * @ORM\OneToMany(targetEntity="QMatchScheduleRelation", mappedBy="matchSchedule", cascade={"persist", "remove"})
     */
    private $qmatchRelation;

    /**
     * @var string $matchstart
     * Scheduled match start - format Hi
     * @ORM\Column(name="matchstart", type="string", length=4, nullable=false)
     */
    private $matchstart;

    /**
     * @var string $unscheduled
     * Indicates this record has not yet been scheduled
     * @ORM\Column(name="unscheduled", type="boolean", nullable=false)
     */
    private $unscheduled;

    /**
     * @var string $fixed
     * Indicates this record holds a fixed schedule (not allowed to change)
     * @ORM\Column(name="fixed", type="boolean", nullable=false)
     */
    private $fixed;

    /**
     * MatchSchedule constructor.
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
     * @return Tournament
     */
    public function getTournament() {
        return $this->tournament;
    }

    /**
     * @param Tournament $tournament
     */
    public function setTournament(Tournament $tournament) {
        $this->tournament = $tournament;
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
     * @return PlaygroundAttribute
     */
    public function getPlaygroundAttribute() {
        return $this->playgroundAttribute;
    }

    /**
     * @param PlaygroundAttribute $playgroundAttribute
     */
    public function setPlaygroundAttribute(PlaygroundAttribute $playgroundAttribute) {
        $this->playgroundAttribute = $playgroundAttribute;
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
     * @param MatchScheduleRelation|QMatchScheduleRelation $matchRelation
     * @return MatchSchedule
     */
    public function addMatchRelation($matchRelation) {
        if ($matchRelation instanceof MatchScheduleRelation) {
            $this->matchRelation->add($matchRelation);
        }
        else {
            $this->qmatchRelation->add($matchRelation);
        }
        $matchRelation->setMatchSchedule($this);
        return $this;
    }

    /**
     * Get match start - scheduled time of start
     *
     * @return string
     */
    public function getMatchstart()
    {
        return $this->matchstart;
    }

    /**
     * Set match start - scheduled time of start
     *
     * @param string $matchstart
     * @return MatchSchedule
     */
    public function setMatchstart($matchstart)
    {
        $this->matchstart = $matchstart;
        return $this;
    }

    /**
     * Get unscheduled state - true if this record is not yet scheduled
     *
     * @return string
     */
    public function isUnscheduled()
    {
        return $this->unscheduled;
    }

    /**
     * Set unscheduled state
     *
     * @param string $unscheduled
     * @return MatchSchedule
     */
    public function setUnscheduled($unscheduled)
    {
        $this->unscheduled = $unscheduled;
        return $this;
    }

    /**
     * Check if match schedule is fixed
     *
     * @return string
     */
    public function isFixed()
    {
        return $this->fixed;
    }

    /**
     * Set the match schedule to be fixed
     *
     * @param string $fixed
     * @return MatchSchedule
     */
    public function setFixed($fixed)
    {
        $this->fixed = $fixed;
        return $this;
    }
}

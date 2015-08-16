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
     * @var MatchSchedulePlan $plan
     * Relation to MatchSchedulePlan
     * @ORM\OneToOne(targetEntity="MatchSchedulePlan", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    private $plan;

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
     * @return MatchSchedulePlan
     */
    public function getPlan() {
        return $this->plan;
    }

    /**
     * @param MatchSchedulePlan $plan
     */
    public function setPlan(MatchSchedulePlan $plan) {
        $this->plan = $plan;
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
}

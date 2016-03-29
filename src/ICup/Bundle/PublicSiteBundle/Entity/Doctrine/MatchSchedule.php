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
    protected $id;

    /**
     * @var Tournament $tournament
     * Relation to Tournament
     * @ORM\ManyToOne(targetEntity="Tournament")
     * @ORM\JoinColumn(name="pid", referencedColumnName="id", nullable=true)
     */
    protected $tournament;

    /**
     * @var Group $group
     * Relation to Group
     * @ORM\ManyToOne(targetEntity="Group")
     * @ORM\JoinColumn(name="gid", referencedColumnName="id")
     */
    protected $group;

    /**
     * @var MatchSchedulePlan $plan
     * Relation to MatchSchedulePlan
     * @ORM\OneToOne(targetEntity="MatchSchedulePlan", cascade={"persist", "remove"})
     */
    protected $plan;

    /**
     * @var ArrayCollection $matchrelation
     * Collection of match relations to teams
     * @ORM\OneToMany(targetEntity="MatchScheduleRelation", mappedBy="matchSchedule", cascade={"persist", "remove"})
     */
    protected $matchrelation;

    /**
     * MatchSchedule constructor.
     */
    public function __construct() {
        $this->matchrelation = new ArrayCollection();
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
        return $this->matchrelation;
    }

    /**
     * @param MatchScheduleRelation $matchRelation
     * @return MatchSchedule
     */
    public function addMatchRelation($matchRelation) {
        $this->matchrelation->add($matchRelation);
        $matchRelation->setMatchSchedule($this);
        return $this;
    }

    /**
     * @return Category
     */
    public function getCategory() {
        return $this->group->getCategory();
    }
}

<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\QMatchSchedule
 *
 * @ORM\Table(name="qmatchschedules")
 * @ORM\Entity
 */
class QMatchSchedule
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
     * @var Category $category
     * Relation to Category
     * @ORM\ManyToOne(targetEntity="Category")
     * @ORM\JoinColumn(name="category", referencedColumnName="id")
     */
    protected $category;

    /**
     * @var string $branch
     * The branch for this relation (A or B according to acheived end game)
     * @ORM\Column(name="branch", type="string", length=1, nullable=false)
     */
    protected $branch;

    /**
     * @var integer $classification
     * The classification for the qualifying group referenced by this relation
     * @ORM\Column(name="classification", type="integer", nullable=false)
     */
    protected $classification;

    /**
     * @var integer $litra
     * The litra for the qualifying group referenced by this relation
     * @ORM\Column(name="litra", type="integer", nullable=false)
     */
    protected $litra;

    /**
     * @var MatchSchedulePlan $plan
     * Relation to MatchSchedulePlan
     * @ORM\OneToOne(targetEntity="MatchSchedulePlan", cascade={"persist", "remove"})
     */
    protected $plan;

    /**
     * @var ArrayCollection $qmatchrelation
     * Collection of match relations to qualifying prerequisites
     * @ORM\OneToMany(targetEntity="QMatchScheduleRelation", mappedBy="matchSchedule", cascade={"persist", "remove"})
     */
    protected $qmatchrelation;

    /**
     * QMatchSchedule constructor.
     */
    public function __construct() {
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
     * @return Category
     */
    public function getCategory() {
        return $this->category;
    }

    /**
     * @param Category $category
     * @return QMatchSchedule
     */
    public function setCategory($category) {
        $this->category = $category;
        return $this;
    }

    /**
     * @return string
     */
    public function getBranch() {
        return $this->branch;
    }

    /**
     * @param string $branch
     * @return QMatchSchedule
     */
    public function setBranch($branch) {
        $this->branch = $branch;
        return $this;
    }

    /**
     * @return int
     */
    public function getClassification() {
        return $this->classification;
    }

    /**
     * @param int $classification
     * @return QMatchSchedule
     */
    public function setClassification($classification) {
        $this->classification = $classification;
        return $this;
    }

    /**
     * @return int
     */
    public function getLitra() {
        return $this->litra;
    }

    /**
     * @param int $litra
     * @return QMatchSchedule
     */
    public function setLitra($litra) {
        $this->litra = $litra;
        return $this;
    }

    /**
     * @return MatchSchedulePlan
     */
    public function getPlan() {
        return $this->plan;
    }

    /**
     * @param MatchSchedulePlan $plan
     * @return QMatchSchedule
     */
    public function setPlan($plan) {
        $this->plan = $plan;
        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getQMatchRelations() {
        return $this->qmatchrelation;
    }

    /**
     * @param QMatchScheduleRelation $matchRelation
     * @return MatchSchedule
     */
    public function addQMatchRelation(QMatchScheduleRelation $matchRelation) {
        $this->qmatchrelation->add($matchRelation);
        $matchRelation->setMatchSchedule($this);
        return $this;
    }
}

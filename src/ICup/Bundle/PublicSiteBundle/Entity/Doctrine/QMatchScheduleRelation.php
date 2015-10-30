<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\ORM\Mapping as ORM;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\QMatchScheduleRelation
 *
 * @ORM\Table(name="qmatchschedulerelations")
 * @ORM\Entity
 */
class QMatchScheduleRelation
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
     * @var QMatchSchedule $matchSchedule
     * Relation to QMatchSchedule
     * @ORM\ManyToOne(targetEntity="QMatchSchedule", inversedBy="qmatchrelation", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="matchschedule", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $matchSchedule;

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
     * @var integer $rank
     * The rank required by the team in qualifying group - 1=first place, 2=second place, ...
     * @ORM\Column(name="rank", type="integer", nullable=false)
     */
    protected $rank;

    /**
     * @var string $awayteam
     * Indicates this record is related to the away team
     * @ORM\Column(name="awayteam", type="boolean", nullable=false)
     */
    protected $awayteam;

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
     * @return string
     */
    public function getBranch() {
        return $this->branch;
    }

    /**
     * @param string $branch
     * @return QMatchScheduleRelation
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
     * @return QMatchScheduleRelation
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
     * @return QMatchScheduleRelation
     */
    public function setLitra($litra) {
        $this->litra = $litra;
        return $this;
    }

    /**
     * Set rank
     *
     * @param integer $rank
     * @return QMatchScheduleRelation
     */
    public function setRank($rank)
    {
        $this->rank = $rank;
        return $this;
    }

    /**
     * Get rank
     *
     * @return integer
     */
    public function getRank()
    {
        return $this->rank;
    }

    /**
     * Set awayteam
     *
     * @param boolean $away
     * @return QMatchScheduleRelation
     */
    public function setAwayteam($away)
    {
        $this->awayteam = $away;
        return $this;
    }

    /**
     * Get awayteam
     *
     * @return boolean 
     */
    public function getAwayteam()
    {
        return $this->awayteam;
    }

    /**
     * @param QMatchSchedule $matchSchedule
     * @return QMatchScheduleRelation
     */
    public function setMatchSchedule(QMatchSchedule $matchSchedule) {
        $this->matchSchedule = $matchSchedule;
        return $this;
    }

    /**
     * @return QMatchSchedule
     */
    public function getMatchSchedule() {
        return $this->matchSchedule;
    }

    public function __toString() {
        return $this->getClassification().":".$this->getLitra().$this->getBranch()."#".$this->getRank();
    }
}
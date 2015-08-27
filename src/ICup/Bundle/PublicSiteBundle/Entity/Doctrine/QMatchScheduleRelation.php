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
    private $id;

    /**
     * @var MatchSchedule $matchSchedule
     * Relation to MatchSchedule
     * @ORM\ManyToOne(targetEntity="MatchSchedule", inversedBy="qmatchrelation", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="pid", referencedColumnName="id", onDelete="CASCADE")
     */
    private $matchSchedule;

    /**
     * @var Group $group
     * Relation to Group
     * @ORM\ManyToOne(targetEntity="Group")
     * @ORM\JoinColumn(name="gid", referencedColumnName="id")
     */
    private $group;

    /**
     * @var integer $rank
     * The rank required by the team in qualifying group - 1=first place, 2=second place, ...
     * @ORM\Column(name="rank", type="integer", nullable=false)
     */
    private $rank;

    /**
     * @var string $awayteam
     * Indicates this record is related to the away team
     * @ORM\Column(name="awayteam", type="boolean", nullable=false)
     */
    private $awayteam;

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
     * @return QMatchScheduleRelation
     */
    public function setGroup(Group $group) {
        $this->group = $group;
        return $this;
    }

    /**
     * @return Group
     */
    public function getGroup() {
        return $this->group;
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
     * @param MatchSchedule $matchSchedule
     * @return QMatchScheduleRelation
     */
    public function setMatchSchedule(MatchSchedule $matchSchedule) {
        $this->matchSchedule = $matchSchedule;
        return $this;
    }

    /**
     * @return MatchSchedule
     */
    public function getMatchSchedule() {
        return $this->matchSchedule;
    }
}
<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\ORM\Mapping as ORM;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchScheduleRelation
 *
 * @ORM\Table(name="matchschedulerelations")
 * @ORM\Entity
 */
class MatchScheduleRelation
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
     * @ORM\ManyToOne(targetEntity="MatchSchedule", inversedBy="id", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="pid", referencedColumnName="id", onDelete="CASCADE")
     */
    private $matchSchedule;

    /**
     * @var Team $team
     * Relation to Team
     * @ORM\ManyToOne(targetEntity="Team", inversedBy="id")
     * @ORM\JoinColumn(name="cid", referencedColumnName="id")
     */
    private $team;

    /**
     * @var boolean $awayteam
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
     * @param MatchSchedule $matchSchedule
     * @return MatchScheduleRelation
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

    /**
     * @param Team $team
     * @return MatchScheduleRelation
     */
    public function setTeam(Team $team) {
        $this->team = $team;
        return $this;
    }

    /**
     * @return Team
     */
    public function getTeam() {
        return $this->team;
    }

    /**
     * Set awayteam
     *
     * @param boolean $away
     * @return MatchScheduleRelation
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
}
<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\ORM\Mapping as ORM;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchSchedule
 *
 * @ORM\Table(name="matchschedules",uniqueConstraints={@ORM\UniqueConstraint(name="IdxByMatch", columns={"pid", "paid", "matchstart", "id"})})
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
     * @var integer $pid
     * Relation to Tournament - pid=tournament.id
     * @ORM\Column(name="pid", type="integer", nullable=false)
     */
    private $pid;

    /**
     * @var integer $paid
     * Relation to PlaygroundAttribute - paid=pattr.id
     * @ORM\Column(name="paid", type="integer", nullable=false)
     */
    private $paid;

    /**
     * @var integer $thid
     * Relation to home Team - thid=team.id
     * @ORM\Column(name="thid", type="integer", nullable=false)
     */
    private $thid;

    /**
     * @var integer $taid
     * Relation to away Team - taid=team.id
     * @ORM\Column(name="taid", type="integer", nullable=false)
     */
    private $taid;

    /**
     * @var string $matchstart
     * Scheduled match start - format Hi
     * @ORM\Column(name="matchstart", type="string", length=4, nullable=false)
     */
    private $matchstart;

    /**
     * @var string $unscheduled
     * Indicates this record has not yet been scheduled - Y=Yes, N=No
     * @ORM\Column(name="unscheduled", type="string", length=1, nullable=false)
     */
    private $unscheduled;

    /**
     * @var string $fixed
     * Indicates this record holds a fixed schedule (not allowed to change) - Y=Yes, N=No
     * @ORM\Column(name="fixed", type="string", length=1, nullable=false)
     */
    private $fixed;

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
     * Set parent id - related tournament
     *
     * @param integer $pid
     * @return MatchSchedule
     */
    public function setPid($pid)
    {
        $this->pid = $pid;

        return $this;
    }

    /**
     * Get parent id - related tournament
     *
     * @return integer 
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * Get playground attribute id - related playground attribute
     *
     * @return int
     */
    public function getPaid()
    {
        return $this->paid;
    }

    /**
     * Set playground attribute id - related playground attribute
     *
     * @param int $paid
     * @return MatchSchedule
     */
    public function setPaid($paid)
    {
        $this->paid = $paid;
        return $this;
    }

    /**
     * Check if match schedule is fixed
     *
     * @return string
     */
    public function isFixed()
    {
        return $this->fixed == "Y";
    }

    /**
     * Set the match schedule to be fixed
     *
     * @param string $fixed
     * @return MatchSchedule
     */
    public function setFixed($fixed)
    {
        $this->fixed = $fixed ? "Y" : "N";
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
     * Get home team
     *
     * @return int
     */
    public function getThid()
    {
        return $this->thid;
    }

    /**
     * Set home team
     *
     * @param int $thid
     * @return MatchSchedule
     */
    public function setThid($thid)
    {
        $this->thid = $thid;
        return $this;
    }

    /**
     * Get away team
     *
     * @return int
     */
    public function getTaid()
    {
        return $this->taid;
    }

    /**
     * Set away team
     *
     * @param int $taid
     * @return MatchSchedule
     */
    public function setTaid($taid)
    {
        $this->taid = $taid;
        return $this;
    }

    /**
     * Get unscheduled state - true if this record is not yet scheduled
     *
     * @return string
     */
    public function isUnscheduled()
    {
        return $this->unscheduled == 'Y';
    }

    /**
     * Set unscheduled state
     *
     * @param string $unscheduled
     * @return MatchSchedule
     */
    public function setUnscheduled($unscheduled)
    {
        $this->unscheduled = $unscheduled ? "Y" : "N";
        return $this;
    }
}

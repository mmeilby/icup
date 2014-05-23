<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\ORM\Mapping as ORM;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\QMatchRelation
 *
 * @ORM\Table(name="qmatchrelations",uniqueConstraints={@ORM\UniqueConstraint(name="IdxByMatch", columns={"pid", "cid", "rank"})})
 * @ORM\Entity
 */
class QMatchRelation
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
     * Relation to Match - pid=match.id 
     * @ORM\Column(name="pid", type="integer", nullable=false)
     */
    private $pid;

    /**
     * @var integer $cid
     * Relation to qualifying Group - cid=group.id
     * @ORM\Column(name="cid", type="integer", nullable=false)
     */
    private $cid;
    
    /**
     * @var integer $rank
     * The rank required by the team in qualifying group - 1=first place, 2=second place, ...
     * @ORM\Column(name="rank", type="integer", nullable=false)
     */
    private $rank;

    /**
     * @var string $awayteam
     * Indicates this record is related to the away team - Y=Yes, N=No
     * @ORM\Column(name="awayteam", type="string", length=1, nullable=false)
     */
    private $awayteam;

    /**
     * @var string $assigned
     * Indicates that the MatchRelation table holds an assigned team for this position - Y=Yes, N=No
     * @ORM\Column(name="assigned", type="string", length=1, nullable=false)
     */
    private $assigned;

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
     * Set parent id - related match
     *
     * @param integer $pid
     * @return MatchRelation
     */
    public function setPid($pid)
    {
        $this->pid = $pid;
    
        return $this;
    }

    /**
     * Get parent id - related match
     *
     * @return integer 
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * Set child id - related team
     *
     * @param integer $cid
     * @return MatchRelation
     */
    public function setCid($cid)
    {
        $this->cid = $cid;
    
        return $this;
    }

    /**
     * Get child id - related team
     *
     * @return integer 
     */
    public function getCid()
    {
        return $this->cid;
    }

    /**
     * Set rank
     *
     * @param integer $rank
     * @return MatchRelation
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
     * @return MatchRelation
     */
    public function setAwayteam($away)
    {
        $this->awayteam = $away ? 'Y' : 'N';
    
        return $this;
    }

    /**
     * Get awayteam
     *
     * @return boolean 
     */
    public function getAwayteam()
    {
        return $this->awayteam == 'Y';
    }

    /**
     * Set assigned
     *
     * @param boolean $assigned
     * @return MatchRelation
     */
    public function setAssigned($assigned)
    {
        $this->assigned = $assigned ? 'Y' : 'N';
    
        return $this;
    }

    /**
     * Get assigned
     *
     * @return boolean 
     */
    public function getAssigned()
    {
        return $this->assigned == 'Y';
    }
}
<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Intl\Exception\MethodNotImplementedException;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchRelation
 *
 * @ORM\Table(name="matchrelations",uniqueConstraints={@ORM\UniqueConstraint(name="IdxByMatch", columns={"pid", "cid"})})
 * @ORM\Entity
 */
class MatchRelation
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
     * @var Match $match
     * Relation to Match
     * @ORM\ManyToOne(targetEntity="Match", inversedBy="id", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="pid", referencedColumnName="id", onDelete="CASCADE")
     */
    private $match;

    /**
     * @var integer $cid
     * Relation to Team - cid=team.id
     * @ORM\Column(name="cid", type="integer", nullable=false)
     */
    private $cid;
    
    /**
     * @var string $awayteam
     * Indicates this record is related to the away team - Y=Yes, N=No
     * @ORM\Column(name="awayteam", type="string", length=1, nullable=false)
     */
    private $awayteam;

    /**
     * @var string $scorevalid
     * Indicates this record holds a valid score - Y=Yes, N=No
     * @ORM\Column(name="scorevalid", type="string", length=1, nullable=false)
     */
    private $scorevalid;

    /**
     * @var integer $score
     * The score achieved by the team in this match
     * @ORM\Column(name="score", type="integer", nullable=false)
     */
    private $score;

    /**
     * @var integer $points
     * The match points achieved by the team in this match
     * @ORM\Column(name="points", type="integer", nullable=false)
     */
    private $points;


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
     * @param Match $match
     * @return MatchRelation
     */
    public function setMatch(Match $match) {
        $this->match = $match;
        return $this;
    }

    /**
     * @return Match
     */
    public function getMatch() {
        return $this->match;
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
     * Set scorevalid
     *
     * @param boolean $valid
     * @return MatchRelation
     */
    public function setScorevalid($valid)
    {
        $this->scorevalid = $valid ? 'Y' : 'N';
    
        return $this;
    }

    /**
     * Get scorevalid
     *
     * @return boolean 
     */
    public function getScorevalid()
    {
        return $this->scorevalid == 'Y';
    }
    
    /**
     * Set score
     *
     * @param integer $score
     * @return MatchRelation
     */
    public function setScore($score)
    {
        $this->score = $score;
    
        return $this;
    }

    /**
     * Get score
     *
     * @return integer 
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * Set match points
     *
     * @param integer $points
     * @return MatchRelation
     */
    public function setPoints($points)
    {
        $this->points = $points;
    
        return $this;
    }

    /**
     * Get match points
     *
     * @return integer 
     */
    public function getPoints()
    {
        return $this->points;
    }
}
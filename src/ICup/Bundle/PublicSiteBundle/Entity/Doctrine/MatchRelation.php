<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchRelation
 *
 * @ORM\Table(name="matchrelations",uniqueConstraints={@ORM\UniqueConstraint(name="TeamMatchConstraint", columns={"pid", "cid"})})
 * @ORM\Entity
 */
class MatchRelation implements JsonSerializable
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
     * @var Match $match
     * Relation to Match
     * @ORM\ManyToOne(targetEntity="Match", inversedBy="matchrelation", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="pid", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $match;

    /**
     * @var Team $team
     * Relation to Team
     * @ORM\ManyToOne(targetEntity="Team", inversedBy="matchrelations")
     * @ORM\JoinColumn(name="cid", referencedColumnName="id")
     */
    protected $team;
    
    /**
     * @var string $awayteam
     * Indicates this record is related to the away team - Y=Yes, N=No
     * @ORM\Column(name="awayteam", type="string", length=1, nullable=false)
     */
    protected $awayteam;

    /**
     * @var string $scorevalid
     * Indicates this record holds a valid score - Y=Yes, N=No
     * @ORM\Column(name="scorevalid", type="string", length=1, nullable=false)
     */
    protected $scorevalid;

    /**
     * @var integer $score
     * The score achieved by the team in this match
     * @ORM\Column(name="score", type="integer", nullable=false)
     */
    protected $score;

    /**
     * @var integer $points
     * The match points achieved by the team in this match
     * @ORM\Column(name="points", type="integer", nullable=false)
     */
    protected $points;


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
     * @return Team
     */
    public function getTeam() {
        return $this->team;
    }

    /**
     * @param Team $team
     * @return MatchRelation
     */
    public function setTeam($team) {
        $this->team = $team;
        return $this;
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

    public function __toString() {
        return $this->team.($this->getScorevalid() ? " <".$this->score.">" : "");
    }

    /**
     * (PHP 5 &gt;= 5.4.0)<br/>
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     */
    function jsonSerialize() {
        return array(
            "id" => $this->id,
            'relation' => $this->getAwayteam() ? "away" : "home",
            "team" => $this->team->jsonSerialize(),
            'result' => $this->getScorevalid() ? array("score" => $this->score, "points" => $this->points, "valid" => true) : array("score" => '', "points" => '', "valid" => false)
        );
    }
}
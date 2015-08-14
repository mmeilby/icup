<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Intl\Exception\MethodNotImplementedException;

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
     * @var Match $match
     * Relation to Match
     * @ORM\ManyToOne(targetEntity="Match", inversedBy="id", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="pid", referencedColumnName="id", onDelete="CASCADE")
     */
    private $match;

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
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set parent id
     *
     * @param integer $pid
     * @return QMatchRelation
     * @deprecated
     */
    public function setPid($pid)
    {
        throw new MethodNotImplementedException();
    }

    /**
     * Get parent id
     *
     * @return integer
     * @deprecated
     */
    public function getPid()
    {
        return $this->match->getId();
    }

    /**
     * @param Match $match
     * @return QMatchRelation
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
     * @return QMatchRelation
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
     * @return QMatchRelation
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
     * @return QMatchRelation
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
}
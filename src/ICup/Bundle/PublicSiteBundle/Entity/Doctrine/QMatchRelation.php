<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Intl\Exception\MethodNotImplementedException;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\QMatchRelation
 *
 * @ORM\Table(name="qmatchrelations",uniqueConstraints={@ORM\UniqueConstraint(name="QTeamMatchConstraint", columns={"pid", "cid", "rank"})})
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
    protected $id;

    /**
     * @var Match $match
     * Relation to Match
     * @ORM\ManyToOne(targetEntity="Match", inversedBy="qmatchrelation", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="pid", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $match;

    /**
     * @var Group $group
     * Relation to Group
     * @ORM\ManyToOne(targetEntity="Group")
     * @ORM\JoinColumn(name="cid", referencedColumnName="id")
     */
    protected $group;
    
    /**
     * @var integer $rank
     * The rank required by the team in qualifying group - 1=first place, 2=second place, ...
     * @ORM\Column(name="rank", type="integer", nullable=false)
     */
    protected $rank;

    /**
     * @var string $awayteam
     * Indicates this record is related to the away team - Y=Yes, N=No
     * @ORM\Column(name="awayteam", type="string", length=1, nullable=false)
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
     * @return Group
     */
    public function getGroup() {
        return $this->group;
    }

    /**
     * @param Group $group
     */
    public function setGroup($group) {
        $this->group = $group;
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
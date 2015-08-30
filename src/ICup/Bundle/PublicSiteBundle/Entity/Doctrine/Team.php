<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team
 *
 * @ORM\Table(name="teams")
 * @ORM\Entity
 */
class Team
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
     * @var Club $club
     * Relation to Club
     * @ORM\ManyToOne(targetEntity="Club", inversedBy="teams")
     * @ORM\JoinColumn(name="pid", referencedColumnName="id")
     */
    protected $club;

    /**
     * @var string $name
     * Team name used in lists
     * @ORM\Column(name="name", type="string", length=50, nullable=false)
     */
    protected $name;

    /**
     * @var string $color
     * Color of the jersey of the team
     * @ORM\Column(name="color", type="string", length=10, nullable=false)
     */
    protected $color;

    /**
     * @var string $division
     * Team division - typically A, B or 1, 2
     * @ORM\Column(name="division", type="string", length=10, nullable=false)
     */
    protected $division;

    /**
     * @var string $vacant
     * Vacant team placeholder
     * @ORM\Column(name="vacant", type="string", length=1, nullable=false)
     */
    protected $vacant;

    /**
     * @var ArrayCollection $matchrelations
     * Collection of team relations to matchrelations
     * @ORM\OneToMany(targetEntity="MatchRelation", mappedBy="team", cascade={"persist", "remove"})
     */
    protected $matchrelations;

    /**
     * @var ArrayCollection $grouporder
     * Collection of team relation to grouporder
     * @ORM\OneToMany(targetEntity="GroupOrder", mappedBy="team", cascade={"persist", "remove"})
     */
    protected $grouporder;

    /**
     * @var ArrayCollection $enrollments
     * Collection of team relations to enrollments
     * @ORM\OneToMany(targetEntity="Enrollment", mappedBy="team", cascade={"persist", "remove"})
     */
    protected $enrollments;

    /**
     * Group constructor.
     */
    public function __construct() {
        $this->matchrelations = new ArrayCollection();
        $this->grouporder = new ArrayCollection();
        $this->enrollments = new ArrayCollection();
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
     * @return Club
     */
    public function getClub() {
        return $this->club;
    }

    /**
     * @param Club $club
     */
    public function setClub($club) {
        $this->club = $club;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Team
     */
    public function setName($name)
    {
        $this->name = $name;
    
        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get team name
     *
     * @return string
     */
    public function getTeamName()
    {
        $teamname = $this->name;
        if ($this->division != '') {
            $teamname.= ' "'.$this->division.'"';
        }
        return $teamname;
    }

    /**
     * Set color
     *
     * @param string $color
     * @return Team
     */
    public function setColor($color)
    {
        $this->color = $color;
    
        return $this;
    }

    /**
     * Get color
     *
     * @return string 
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Set division
     *
     * @param string $division
     * @return Team
     */
    public function setDivision($division)
    {
        $this->division = $division;
    
        return $this;
    }

    /**
     * Get division
     *
     * @return string 
     */
    public function getDivision()
    {
        return $this->division;
    }

    /**
     * @return boolean
     */
    public function isVacant() {
        return $this->vacant == 'Y';
    }

    /**
     * @param boolean $vacant
     */
    public function setVacant($vacant) {
        $this->vacant = $vacant ? 'Y' : 'N';
    }

    /**
     * @return ArrayCollection
     */
    public function getMatchRelations() {
        return $this->matchrelations;
    }

    /**
     * @return ArrayCollection
     */
    public function getMatches() {
        $matches = array();
        /* @var MatchRelation $matchrelation */
        foreach ($this->matchrelations as $matchrelation) {
            $matches[] = $matchrelation->getMatch();
        }
        return $matches;
    }

    /**
     * @return ArrayCollection
     */
    public function getGroupOrder() {
        return $this->grouporder;
    }

    /**
     * @return ArrayCollection
     */
    public function getGroups() {
        $groups = array();
        /* @var GroupOrder $grouporder */
        foreach ($this->grouporder as $grouporder) {
            $groups[] = $grouporder->getGroup();
        }
        return $groups;
    }

    /**
     * @return ArrayCollection
     */
    public function getEnrollments() {
        return $this->enrollments;
    }

    /**
     * @return Category
     */
    public function getCategory() {
        if ($this->enrollments->count() > 1) {
            throw new ValidationException("INVALIDENROLLMENT", "A team can not be enrolled for more than one category - team id=".$this->id);
        }
        if ($this->enrollments->count() == 1) {
            return $this->enrollments->first()->getCategory();
        }
        return null;
    }
}
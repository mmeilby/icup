<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category
 *
 * @ORM\Table(name="categories",uniqueConstraints={@ORM\UniqueConstraint(name="CategoryNameConstraint", columns={"name", "pid"})})
 * @ORM\Entity
 */
class Category
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
     * @var Tournament $tournament
     * Relation to Tournament
     * @ORM\ManyToOne(targetEntity="Tournament", inversedBy="categories")
     * @ORM\JoinColumn(name="pid", referencedColumnName="id")
     */
    protected $tournament;

    /**
     * @var string $name
     *
     * @ORM\Column(name="name", type="string", length=50, nullable=false)
     */
    protected $name;

    /**
     * @var string $gender
     * Gender requirements for this category: F-female, M-male, X-mix sex
     * @ORM\Column(name="gender", type="string", length=1, nullable=false)
     */
    protected $gender;

    /**
     * @var string $classification
     * Age classification for this category: U-players must be youger, O-players should be older
     * @ORM\Column(name="classification", type="string", length=1, nullable=false)
     */
    protected $classification;

    /**
     * @var string $age
     * Age requirement/limit for players enrolled this category
     * @ORM\Column(name="age", type="string", length=3, nullable=false)
     */
    protected $age;

    /**
     * @var integer $trophys
     * Number of trophys available for the best teams in this category
     * This would normally be 3 for first, second and third place
     * @ORM\Column(name="trophys", type="integer", nullable=false)
     */
    protected $trophys;

    /**
     * @var integer $topteams
     * The number of teams ranked for "A" elimination games. The other teams in a group qualify for "B" elimination games.
     * @ORM\Column(name="topteams", type="integer", nullable=false)
     */
    protected $topteams;

    /**
     * @var integer $strategy
     * Group planning strategy:
     *   When no groups:
     *     0: Gruppens hold spiller slutspil. A-slutspils seedede hold sidder over til senere runder
     *   For one group:
     *     0: De bedst placerede hold går til A-slutspil, resten går til B-slutspil
     *     1: Fire bedst placerede hold går til semifinale
     *     2: To bedst placerede hold går til finale
     *   For 2 groups:
     *     0: De bedst placerede hold går til A-slutspil, resten går til B-slutspil
     *     1: Fire bedst placerede hold går til kvartfinale
     *     2: To bedst placerede hold går til semifinale
     *     3: Puljevindere går til finale
     *   For 3 groups:
     *     0: De bedst placerede hold går til A-slutspil, resten går til B-slutspil
     *     1: Puljevindere går til semifinale, pulje 2-ere mødes i playoff
     *     2: To bedst placerede hold går til kvartfinale, pulje 3-ere og 4-ere går i playoff
     *   For 4 and more groups:
     *     0: De bedst placerede hold går til A-slutspil, resten går til B-slutspil
     *     1: To bedst placerede hold går til kvartfinale
     *     2: Puljevindere går til semifinale
     * @ORM\Column(name="strategy", type="integer", nullable=false)
     */
    protected $strategy;

    /**
     * @var integer $matchtime
     * Matches played in this category durate for the specified time in minutes
     * Note: this amount includes all breaks - before, during, and after the match
     * @ORM\Column(name="matchtime", type="integer", nullable=false)
     */
    protected $matchtime;

    /**
     * @var ArrayCollection $groups
     * Collection of category relations to groups
     * @ORM\OneToMany(targetEntity="Group", mappedBy="category", cascade={"persist", "remove"})
     * @ORM\OrderBy({"classification" = "desc", "name" = "asc"})
     */
    protected $groups;

    /**
     * @var ArrayCollection $enrollments
     * Collection of category relations to enrollments
     * @ORM\OneToMany(targetEntity="Enrollment", mappedBy="category", cascade={"persist", "remove"})
     */
    protected $enrollments;

    /**
     * @var ArrayCollection $playgroundattributes
     * @ORM\ManyToMany(targetEntity="PlaygroundAttribute", mappedBy="categories")
     **/
    protected $playgroundattributes;

    /**
     * @var ArrayCollection $champions
     * Collection of category champion requirements
     * @ORM\OneToMany(targetEntity="Champion", mappedBy="category", cascade={"persist", "remove"})
     * @ORM\OrderBy({"champion" = "asc"})
     */
    protected $champions;

    /**
     * Category constructor.
     */
    public function __construct() {
        $this->groups = new ArrayCollection();
        $this->enrollments = new ArrayCollection();
        $this->playgroundattributes = new ArrayCollection();
        $this->champions = new ArrayCollection();
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
     * @return Tournament
     */
    public function getTournament() {
        return $this->tournament;
    }

    /**
     * @param Tournament $tournament
     * @return Category
     */
    public function setTournament($tournament) {
        $this->tournament = $tournament;
        return $this;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Category
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
     * Set gender
     *
     * @param string $gender
     * @return Category
     */
    public function setGender($gender)
    {
        $this->gender = $gender;
    
        return $this;
    }

    /**
     * Get gender
     *
     * @return string 
     */
    public function getGender()
    {
        return $this->gender;
    }
    
    /**
     * Set classification
     *
     * @param string $classification
     * @return Category
     */
    public function setClassification($classification)
    {
        $this->classification = $classification;
    
        return $this;
    }

    /**
     * Get classification
     *
     * @return string 
     */
    public function getClassification()
    {
        return $this->classification;
    }
    
    /**
     * Set age limit
     *
     * @param string $age
     * @return Category
     */
    public function setAge($age)
    {
        $this->age = $age;
    
        return $this;
    }

    /**
     * Get age limit
     *
     * @return string 
     */
    public function getAge()
    {
        return $this->age;
    }
    
    /**
     * Set category default match time
     *
     * @param integer $matchtime
     * @return Category
     */
    public function setMatchtime($matchtime)
    {
        $this->matchtime = $matchtime;
    
        return $this;
    }

    /**
     * Get category default match time
     *
     * @return integer 
     */
    public function getMatchtime()
    {
        return $this->matchtime;
    }

    /**
     * @return int
     */
    public function getTrophys() {
        return $this->trophys;
    }

    /**
     * @param int $trophys
     * @return Category
     */
    public function setTrophys($trophys) {
        $this->trophys = $trophys;
        return $this;
    }

    /**
     * @return int
     */
    public function getTopteams() {
        return $this->topteams;
    }

    /**
     * @param int $topteams
     * @return Category
     */
    public function setTopteams($topteams) {
        $this->topteams = $topteams;
        return $this;
    }

    /**
     * @return int
     */
    public function getStrategy() {
        return $this->strategy;
    }

    /**
     * @param int $strategy
     * @return Category
     */
    public function setStrategy($strategy) {
        $this->strategy = $strategy;
        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getGroups() {
        return $this->groups;
    }

    /**
     * @return ArrayCollection
     */
    public function getGroupsClassified($classification) {
        $groups = $this->groups->filter(function (Group $group) use ($classification) {
            return $group->getClassification() == $classification;
        });
        return $groups;
    }

    /**
     * @return ArrayCollection
     */
    public function getGroupsPlayoff() {
        $groups = $this->groups->filter(function (Group $group) {
            return $group->getClassification() == Group::$PLAYOFF;
        });
        return $groups;
    }

    /**
     * @return ArrayCollection
     */
    public function getGroupsFinals() {
        $groups = $this->groups->filter(function (Group $group) {
            return $group->getClassification() > Group::$PLAYOFF;
        });
        return $groups;
    }

    /**
     * @return Group
     */
    public function getNthGroup($nth) {
        $groups = $this->getGroupsClassified(Group::$PRE)->getValues();
        return isset($groups[$nth-1]) ? $groups[$nth-1] : null;
    }

    /**
     * @return ArrayCollection
     */
    public function getEnrollments() {
        return $this->enrollments;
    }

    /**
     * @return ArrayCollection
     */
    public function getPlaygroundattributes() {
        return $this->playgroundattributes;
    }

    /**
     * @return ArrayCollection
     */
    public function getChampions() {
        return $this->champions;
    }
}
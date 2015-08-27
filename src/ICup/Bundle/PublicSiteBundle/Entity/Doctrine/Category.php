<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category
 *
 * @ORM\Table(name="categories",uniqueConstraints={@ORM\UniqueConstraint(name="IdxByName", columns={"name", "pid"})})
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
    private $id;

    /**
     * @var Tournament $tournament
     * Relation to Tournament
     * @ORM\ManyToOne(targetEntity="Tournament", inversedBy="categories")
     * @ORM\JoinColumn(name="pid", referencedColumnName="id")
     */
    private $tournament;

    /**
     * @var string $name
     *
     * @ORM\Column(name="name", type="string", length=50, nullable=false)
     */
    private $name;

    /**
     * @var string $gender
     *
     * @ORM\Column(name="gender", type="string", length=1, nullable=false)
     */
    private $gender;

    /**
     * @var string $classification
     *
     * @ORM\Column(name="classification", type="string", length=10, nullable=false)
     */
    private $classification;

    /**
     * @var string $age
     *
     * @ORM\Column(name="age", type="string", length=10, nullable=false)
     */
    private $age;

    /**
     * @var integer $matchtime
     * Matches played in this category durate for the specified time in minutes
     * Note: this amount includes all breaks - before, during, and after the match
     * @ORM\Column(name="matchtime", type="integer", nullable=false)
     */
    private $matchtime;

    /**
     * @var ArrayCollection $groups
     * Collection of category relations to groups
     * @ORM\OneToMany(targetEntity="Group", mappedBy="category", cascade={"persist", "remove"})
     * @ORM\OrderBy({"name" = "ASC"})
     */
    private $groups;

    /**
     * @var ArrayCollection $enrollments
     * Collection of category relations to enrollments
     * @ORM\OneToMany(targetEntity="Enrollment", mappedBy="category", cascade={"persist", "remove"})
     */
    private $enrollments;

    /**
     * Category constructor.
     */
    public function __construct() {
        $this->groups = new ArrayCollection();
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
     * @return ArrayCollection
     */
    public function getGroups() {
        return $this->groups;
    }

    /**
     * @return array
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
    public function getEnrollments() {
        return $this->enrollments;
    }
}
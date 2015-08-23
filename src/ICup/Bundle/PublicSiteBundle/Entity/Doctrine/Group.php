<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group
 *
 * @ORM\Table(name="groups",uniqueConstraints={@ORM\UniqueConstraint(name="IdxByCatAndName", columns={"pid", "name"})})
 * @ORM\Entity
 */
class Group implements JsonSerializable
{
    public static $FINAL = 10;
    public static $BRONZE = 9;
    public static $SEMIFINAL = 8;
    public static $PLAYOFF = 1;
    public static $PRE = 0;

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Category $category
     * Relation to Category
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="id")
     * @ORM\JoinColumn(name="pid", referencedColumnName="id")
     */
    private $category;

    /**
     * @var string $name
     *
     * @ORM\Column(name="name", type="string", length=50, nullable=false)
     */
    private $name;

    /**
     * @var integer $classification
     *   0: Grundspil
     *   1: Playoff
     *   6: 1/8 finale
     *   7: 1/4 finale
     *   8: Semifinale
     *   9: Bronzekamp
     *  10: Finale
     * @ORM\Column(name="classification", type="integer", nullable=false)
     */
    private $classification;

    /**
     * @var ArrayCollection $matches
     * Collection of group relations to matches
     * @ORM\OneToMany(targetEntity="Match", mappedBy="group", cascade={"persist", "remove"})
     */
    private $matches;

    /**
     * @var ArrayCollection $grouporder
     * Collection of group relations to grouporder
     * @ORM\OneToMany(targetEntity="GroupOrder", mappedBy="group", cascade={"persist", "remove"})
     */
    private $grouporder;

    /**
     * Group constructor.
     */
    public function __construct() {
        $this->matches = new ArrayCollection();
        $this->grouporder = new ArrayCollection();
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
     * @return Category
     */
    public function getCategory() {
        return $this->category;
    }

    /**
     * @param Category $category
     */
    public function setCategory($category) {
        $this->category = $category;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Group
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
     * Set classification
     *
     * @param integer $classification
     * @return Group
     */
    public function setClassification($classification)
    {
        $this->classification = $classification;
    
        return $this;
    }

    /**
     * Get classification
     *
     * @return integer 
     */
    public function getClassification()
    {
        return $this->classification;
    }

    /**
     * @return ArrayCollection
     */
    public function getMatches() {
        return $this->matches;
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
    public function getTeams() {
        $teams = array();
        /* @var GroupOrder $grouporder */
        foreach ($this->grouporder as $grouporder) {
            $teams[] = $grouporder->getTeam();
        }
        return $teams;
    }

    /**
     * (PHP 5 &gt;= 5.4.0)<br/>
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     */
    function jsonSerialize() {
        $classes = array('ELIMINATION','PLAYOFF', '1/128', '1/64', '1/32', '1/16', '1/8', '1/4', 'SEMIFINAL', '3/4', 'FINAL');
        return array("id" => $this->id, "name" => $this->name, "classification" => $classes[$this->classification]);
    }
}
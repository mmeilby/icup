<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use JsonSerializable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Playground
 *
 * @ORM\Table(name="playgrounds",uniqueConstraints={@ORM\UniqueConstraint(name="PlaygroundNoConstraint", columns={"no", "pid"})})
 * @ORM\Entity
 */
class Playground implements JsonSerializable
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
     * @var Site $site
     * Relation to Site
     * @ORM\ManyToOne(targetEntity="Site", inversedBy="playgrounds")
     * @ORM\JoinColumn(name="pid", referencedColumnName="id")
     */
    protected $site;

    /**
     * @var integer $no
     * Playground number for ordering in lists
     * @ORM\Column(name="no", type="integer", nullable=false)
     */
    protected $no;

    /**
     * @var string $name
     * Playground name used in lists
     * @ORM\Column(name="name", type="string", length=50, nullable=false)
     */
    protected $name;

    /**
     * @var string $location
     * Playground location for map support
     * @ORM\Column(name="location", type="string", length=50, nullable=false)
     */
    protected $location;

    /**
     * @var integer $weight
     * Playground weight used for planning - lower weights are preferred when planning
     * @ORM\Column(name="weight", type="integer", nullable=false)
     */
    protected $weight;

    /**
     * @var ArrayCollection $matches
     * Collection of playground relations to matches
     * @ORM\OneToMany(targetEntity="Match", mappedBy="playground", cascade={"persist", "remove"})
     */
    protected $matches;

    /**
     * @var ArrayCollection $playgroundattributes
     * Collection of playground relations to playground attributes
     * @ORM\OneToMany(targetEntity="PlaygroundAttribute", mappedBy="playground", cascade={"persist", "remove"})
     * @ORM\OrderBy({"date" = "asc", "start" = "asc"})
     */
    protected $playgroundattributes;

    /**
     * Playground constructor.
     */
    public function __construct() {
        $this->matches = new ArrayCollection();
        $this->playgroundattributes = new ArrayCollection();
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
     * @return Site
     */
    public function getSite() {
        return $this->site;
    }

    /**
     * @param Site $site
     * @return Playground
     */
    public function setSite($site) {
        $this->site = $site;
        return $this;
    }

    /**
     * Set playground no
     *
     * @param integer $no
     * @return Playground
     */
    public function setNo($no)
    {
        $this->no = $no;
    
        return $this;
    }

    /**
     * Get playground no
     *
     * @return integer 
     */
    public function getNo()
    {
        return $this->no;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Playground
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
     * Set location
     *
     * @param string $location
     * @return Playground
     */
    public function setLocation($location)
    {
        $this->location = $location;
        return $this;
    }

    /**
     * Get location
     *
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @return int
     */
    public function getWeight() {
        return $this->weight;
    }

    /**
     * @param int $weight
     * @return Playground
     */
    public function setWeight($weight) {
        $this->weight = $weight;
        return $this;
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
    public function getPlaygroundAttributes() {
        return $this->playgroundattributes;
    }

    /**
     * @return ArrayCollection
     */
    public function getTimeslots() {
        $timeslots = array();
        /* @var PlaygroundAttribute $playgroundattribute */
        foreach ($this->playgroundattributes as $playgroundattribute) {
            $timeslots[$playgroundattribute->getTimeslot()->getId()] = $playgroundattribute->getTimeslot();
        }
        return $timeslots;
    }

    public function __toString() {
        return $this->getNo().". ".$this->getName()." [".$this->getSite()->getName()."]";
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
            "id" => $this->getId(),
            "name" => $this->getName(),
            "no" => $this->getNo(),
            "location" => $this->getLocation(),
            "weight" => $this->getWeight(),
            "site" => $this->getSite()->getName()
        );
    }
}
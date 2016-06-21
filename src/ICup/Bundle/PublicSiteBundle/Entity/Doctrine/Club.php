<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * Club entity
 * This is one of the three basic entities: Hosts, Users and Clubs
 * A club is a top level entity that can exist with several Hosts. Only one club can use a specific name in each country.
 *
 * @ORM\Table(name="clubs", uniqueConstraints={@ORM\UniqueConstraint(name="ClubNameConstraint", columns={"name", "country"})})
 * @ORM\Entity
 */
class Club implements JsonSerializable
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
     * @var string $name
     * Club name used in lists (and as template for team names)
     * @ORM\Column(name="name", type="string", length=50, nullable=false)
     */
    protected $name;

    /**
     * @var Country $country
     * Relation to Country - club residence - DNK for Denmark, DEU for Germany, FRA for France and so on...
     * @ORM\ManyToOne(targetEntity="Country")
     * @ORM\JoinColumn(name="country", referencedColumnName="country")
     */
    protected $country;

    /**
     * @var ArrayCollection $teams
     * Collection of teams associated with this club
     * @ORM\OneToMany(targetEntity="Team", mappedBy="club", cascade={"persist", "remove"})
     */
    protected $teams;

    /**
     * Fixed name and country code for placeholder club for vacant teams
     */
    public static $VACANT_CLUB_NAME = "VACANT";
    public static $VACANT_CLUB_COUNTRYCODE = "[V]";

    /**
     * Club constructor.
     */
    public function __construct() {
        $this->teams = new ArrayCollection();
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
     * Set name
     *
     * @param string $name
     * @return Club
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
     * Set country
     *
     * @param Country $country
     * @return Club
     */
    public function setCountry($country)
    {
        $this->country = $country;
    
        return $this;
    }

    /**
     * Get country record
     *
     * @return Country
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Get country code
     *
     * @return string 
     */
    public function getCountryCode()
    {
        return $this->country->getCountry();
    }

    /**
     * @return ArrayCollection
     */
    public function getTeams() {
        return $this->teams;
    }

    /**
     * Test if this club is placeholder for vacant teams
     * @return bool true if club is placeholder
     */
    public function isVacant() {
        return $this->name == static::$VACANT_CLUB_NAME && $this->country->getCountry() == static::$VACANT_CLUB_COUNTRYCODE;
    }
    
    public function __toString() {
        return $this->getName()." (".$this->getCountryCode().")";
    }

    /**
     * (PHP 5 &gt;= 5.4.0)<br/>
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     */
    function jsonSerialize() {
        return array("id" => $this->id, "name" => $this->name, "country_code" => $this->country->getCountry(), "flag" => $this->country->getFlag());
    }
}
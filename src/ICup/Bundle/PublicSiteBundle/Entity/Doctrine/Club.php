<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Club entity
 * This is one of the three basic entities: Hosts, Users and Clubs
 * A club is a top level entity that can exist with several Hosts. Only one club can use a specific name in each country.
 *
 * @ORM\Table(name="clubs", uniqueConstraints={@ORM\UniqueConstraint(name="ClubNameConstraint", columns={"name", "country"})})
 * @ORM\Entity
 */
class Club
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
     * @var string $country
     * Country code for club residence - DNK for Denmark, DEU for Germany, FRA for France and so on...
     * @ORM\Column(name="country", type="string", length=3, nullable=false)
     */
    protected $country;

    /**
     * @var ArrayCollection $teams
     * Collection of teams associated with this club
     * @ORM\OneToMany(targetEntity="Team", mappedBy="club", cascade={"persist", "remove"})
     */
    protected $teams;

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
     * @param string $country
     * @return Club
     */
    public function setCountry($country)
    {
        $this->country = $country;
    
        return $this;
    }

    /**
     * Get country
     *
     * @return string 
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @return ArrayCollection
     */
    public function getTeams() {
        return $this->teams;
    }
}
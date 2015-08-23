<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club
 *
 * @ORM\Table(name="clubs", uniqueConstraints={@ORM\UniqueConstraint(name="IdxByName", columns={"name", "country"})})
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
    private $id;

    /**
     * @var string $name
     *
     * @ORM\Column(name="name", type="string", length=50, nullable=false)
     */
    private $name;

    /**
     * @var string $country
     *
     * @ORM\Column(name="country", type="string", length=3, nullable=false)
     */
    private $country;

    /**
     * @var ArrayCollection $teams
     * Collection of club relations to teams
     * @ORM\OneToMany(targetEntity="Team", mappedBy="club", cascade={"persist", "remove"})
     */
    private $teams;

    /**
     * @var ArrayCollection $users
     * Collection of club relations to users
     * @ORM\OneToMany(targetEntity="User", mappedBy="club", cascade={"persist"})
     */
    private $users;

    /**
     * Club constructor.
     */
    public function __construct() {
        $this->teams = new ArrayCollection();
        $this->users = new ArrayCollection();
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

    /**
     * @return ArrayCollection
     */
    public function getUsers() {
        return $this->users;
    }
}
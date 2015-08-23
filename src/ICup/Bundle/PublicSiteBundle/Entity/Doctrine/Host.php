<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Host
 *
 * @ORM\Table(name="hosts", uniqueConstraints={@ORM\UniqueConstraint(name="IdxByName", columns={"name"})})
 * @ORM\Entity
 */
class Host
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
     * Host name used in lists
     * @ORM\Column(name="name", type="string", length=50, nullable=false)
     */
    private $name;

    /**
     * @var ArrayCollection $tournaments
     * Collection of host relations to tournaments
     * @ORM\OneToMany(targetEntity="Tournament", mappedBy="host", cascade={"persist", "remove"})
     */
    private $tournaments;

    /**
     * @var ArrayCollection $users
     * Collection of host relations to users
     * @ORM\OneToMany(targetEntity="User", mappedBy="host", cascade={"persist"})
     */
    private $users;

    /**
     * Host constructor.
     */
    public function __construct() {
        $this->tournaments = new ArrayCollection();
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
     * @return Host
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
     * @return ArrayCollection
     */
    public function getTournaments() {
        return $this->tournaments;
    }

    /**
     * @return ArrayCollection
     */
    public function getUsers() {
        return $this->users;
    }
}
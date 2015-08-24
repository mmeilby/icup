<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Host entity
 * This is one of the three basic entities: Hosts, Users and Clubs
 * A host is a top level entity. Only one host can use a specific name in the system.
 *
 * @ORM\Table(name="hosts", uniqueConstraints={@ORM\UniqueConstraint(name="NameConstraint", columns={"name"})})
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
    protected $id;

    /**
     * @var string $name
     * Host name used in lists
     * @ORM\Column(name="name", type="string", length=50, nullable=false)
     */
    protected $name;

    /**
     * @var ArrayCollection $tournaments
     * Collection of tournaments created by this host
     * @ORM\OneToMany(targetEntity="Tournament", mappedBy="host", cascade={"persist", "remove"})
     */
    protected $tournaments;

    /**
     * @var ArrayCollection $users
     * Collection of users with access to this host
     * @ORM\OneToMany(targetEntity="User", mappedBy="host", cascade={"persist"})
     */
    protected $users;

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
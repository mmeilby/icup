<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Host entity
 * This is one of the three basic entities: Hosts, Users and Clubs
 * A host is a top level entity. Only one host can use a specific name in the system.
 *
 * @ORM\Table(name="hosts", uniqueConstraints={@ORM\UniqueConstraint(name="HostNameConstraint", columns={"name"})})
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
     * @var string $domain
     * Domain name used for URL detection
     * @ORM\Column(name="domain", type="string", length=128, nullable=false)
     */
    protected $domain;

    /**
     * @var string $alias
     * Domain alias used for page redirection
     * @ORM\Column(name="alias", type="string", length=10, nullable=false)
     */
    protected $alias;

    /**
     * @var HostPlan $hostplan
     * Relation to HostPlan - the level of service this host has signed up for
     * @ORM\ManyToOne(targetEntity="HostPlan", inversedBy="hosts")
     * @ORM\JoinColumn(name="hostplan", referencedColumnName="id")
     */
    protected $hostplan;

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
     * @ORM\OrderBy({"name" = "asc"})
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
     * @return string
     */
    public function getDomain() {
        return $this->domain;
    }

    /**
     * @param string $domain
     * @return Host
     */
    public function setDomain($domain) {
        $this->domain = $domain;
        return $this;
    }

    /**
     * @return string
     */
    public function getAlias() {
        return $this->alias;
    }

    /**
     * @param string $alias
     * @return Host
     */
    public function setAlias($alias) {
        $this->alias = $alias;
        return $this;
    }

    /**
     * @return HostPlan
     */
    public function getHostplan() {
        return $this->hostplan;
    }

    /**
     * @param HostPlan $hostplan
     * @return Host
     */
    public function setHostplan($hostplan) {
        $this->hostplan = $hostplan;
        return $this;
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

    /**
     * @return ArrayCollection
     */
    public function getEditors() {
        $editors = $this->users->filter(function (User $user) {
            return $user->isEditor();
        });
        return $editors;
    }
}
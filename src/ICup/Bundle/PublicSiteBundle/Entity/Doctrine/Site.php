<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Site
 *
 * @ORM\Table(name="sites")
 * @ORM\Entity
 */
class Site
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
     * @ORM\ManyToOne(targetEntity="Tournament", inversedBy="sites")
     * @ORM\JoinColumn(name="pid", referencedColumnName="id")
     */
    protected $tournament;

    /**
     * @var string $name
     * Site name used in lists
     * @ORM\Column(name="name", type="string", length=50, nullable=false)
     */
    protected $name;

    /**
     * @var ArrayCollection $playgrounds
     * Collection of group relations to playgrounds
     * @ORM\OneToMany(targetEntity="Playground", mappedBy="site", cascade={"persist", "remove"})
     */
    protected $playgrounds;

    /**
     * Site constructor.
     */
    public function __construct() {
        $this->playgrounds = new ArrayCollection();
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
     */
    public function setTournament($tournament) {
        $this->tournament = $tournament;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Site
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
    public function getPlaygrounds() {
        return $this->playgrounds;
    }
}
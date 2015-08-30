<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament
 *
 * @ORM\Table(name="tournaments", uniqueConstraints={@ORM\UniqueConstraint(name="KeyConstraint", columns={"keyname"})})
 * @ORM\Entity
 */
class Tournament
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
     * @var Host $host
     * Relation to Host
     * @ORM\ManyToOne(targetEntity="Host", inversedBy="tournaments")
     * @ORM\JoinColumn(name="pid", referencedColumnName="id")
     */
    protected $host;

    /**
     * @var string $key
     * Tournament key used in references
     * @ORM\Column(name="keyname", type="string", length=50, nullable=false, unique=true)
     */
    protected $key;

    /**
     * @var string $name
     * Tournament name for identifitcation in lists
     * @ORM\Column(name="name", type="string", length=50, nullable=false)
     */
    protected $name;

    /**
     * @var string $edition
     * Tournament edition like '2013' or '41th aniversary'
     * @ORM\Column(name="edition", type="string", length=50, nullable=false)
     */
    protected $edition;

    /**
     * @var string $description
     * Tournament description in short format
     * @ORM\Column(name="description", type="string", length=250, nullable=false)
     */
    protected $description;

    /**
     * @var TournamentOption $option
     * Relation to TournamentOption - option_id=tournamentoption.id
     * @ORM\OneToOne(targetEntity="TournamentOption", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected $option;

    /**
     * @var ArrayCollection $categories
     * Collection of tournament relations to categories
     * @ORM\OneToMany(targetEntity="Category", mappedBy="tournament", cascade={"persist", "remove"})
     * @ORM\OrderBy({"classification" = "desc", "age" = "desc", "gender" = "asc"})
     */
    protected $categories;

    /**
     * @var ArrayCollection $sites
     * Collection of tournament relations to sites
     * @ORM\OneToMany(targetEntity="Site", mappedBy="tournament", cascade={"persist", "remove"})
     * @ORM\OrderBy({"name" = "asc"})
     */
    protected $sites;

    /**
     * @var ArrayCollection $timeslots
     * Collection of tournament relations to timeslots
     * @ORM\OneToMany(targetEntity="Timeslot", mappedBy="tournament", cascade={"persist", "remove"})
     * @ORM\OrderBy({"name" = "asc"})
     */
    protected $timeslots;

    /**
     * @var ArrayCollection $events
     * Collection of tournament relations to events
     * @ORM\OneToMany(targetEntity="Event", mappedBy="tournament", cascade={"persist", "remove"})
     * @ORM\OrderBy({"date" = "asc"})
     */
    protected $events;

    /**
     * @var ArrayCollection $news
     * Collection of tournament relations to news
     * @ORM\OneToMany(targetEntity="News", mappedBy="tournament", cascade={"persist", "remove"})
     * @ORM\OrderBy({"date" = "asc"})
     */
    protected $news;

    /**
     * Tournament constructor.
     */
    public function __construct() {
        $this->option = new TournamentOption();
        $this->categories = new ArrayCollection();
        $this->sites = new ArrayCollection();
        $this->timeslots = new ArrayCollection();
        $this->events = new ArrayCollection();
        $this->news = new ArrayCollection();
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
     * @return Host
     */
    public function getHost() {
        return $this->host;
    }

    /**
     * @param Host $host
     * @return Tournament
     */
    public function setHost($host) {
        $this->host = $host;
        return $this;
    }

    /**
     * Set key
     *
     * @param string $key
     * @return Tournament
     */
    public function setKey($key)
    {
        $this->key = $key;
    
        return $this;
    }

    /**
     * Get key
     *
     * @return string 
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Tournament
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
     * Set edition
     *
     * @param string $edition
     * @return Tournament
     */
    public function setEdition($edition)
    {
        $this->edition = $edition;
    
        return $this;
    }

    /**
     * Get edition
     *
     * @return string 
     */
    public function getEdition()
    {
        return $this->edition;
    }
    
    /**
     * Set description
     *
     * @param string $description
     * @return Tournament
     */
    public function setDescription($description)
    {
        $this->description = $description;
    
        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return TournamentOption
     */
    public function getOption() {
        return $this->option;
    }

    /**
     * @param TournamentOption $option
     * @return Tournament
     */
    public function setOption($option) {
        $this->option = $option;
        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getCategories() {
        return $this->categories;
    }

    /**
     * @return ArrayCollection
     */
    public function getSites() {
        return $this->sites;
    }

    /**
     * @return ArrayCollection
     */
    public function getTimeslots() {
        return $this->timeslots;
    }

    /**
     * @return ArrayCollection
     */
    public function getEvents() {
        return $this->events;
    }

    /**
     * @return ArrayCollection
     */
    public function getNews() {
        return $this->news;
    }
}
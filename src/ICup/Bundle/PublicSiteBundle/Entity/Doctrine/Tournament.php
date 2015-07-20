<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\ORM\Mapping as ORM;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament
 *
 * @ORM\Table(name="tournaments")
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
    private $id;

    /**
     * @var integer $pid
     * Relation to Host - pid=host.id 
     * @ORM\Column(name="pid", type="integer", nullable=false)
     */
    private $pid;

    /**
     * @var string $key
     * Tournament key used in references
     * @ORM\Column(name="keyname", type="string", length=50, nullable=false, unique=true)
     */
    private $key;

    /**
     * @var string $name
     * Tournament name for identifitcation in lists
     * @ORM\Column(name="name", type="string", length=50, nullable=false)
     */
    private $name;

    /**
     * @var string $edition
     * Tournament edition like '2013' or '41th aniversary'
     * @ORM\Column(name="edition", type="string", length=50, nullable=false)
     */
    private $edition;

    /**
     * @var string $description
     * Tournament description in short format
     * @ORM\Column(name="description", type="string", length=250, nullable=false)
     */
    private $description;

    /**
     * @var TournamentOption $option
     * Relation to TournamentOption - option_id=tournamentoption.id
     * @ORM\OneToOne(targetEntity="TournamentOption", cascade={"persist", "remove"}, orphanRemoval=true)
     */

    private $option;

    /**
     * Tournament constructor.
     */
    public function __construct() {
        $this->option = new TournamentOption();
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
     * Set parent id
     *
     * @param integer $pid
     * @return Tournament
     */
    public function setPid($pid)
    {
        $this->pid = $pid;
    
        return $this;
    }

    /**
     * Get parent id - related host
     *
     * @return integer 
     */
    public function getPid()
    {
        return $this->pid;
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
}
<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\ORM\Mapping as ORM;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group
 *
 * @ORM\Table(name="groups",uniqueConstraints={@ORM\UniqueConstraint(name="IdxByCatAndName", columns={"pid", "name"})})
 * @ORM\Entity
 */
class Group
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
     *
     * @ORM\Column(name="pid", type="integer", nullable=false)
     */
    private $pid;

    /**
     * @var string $name
     *
     * @ORM\Column(name="name", type="string", length=50, nullable=false)
     */
    private $name;

    /**
     * @var integer $playingtime
     *
     * @ORM\Column(name="playingtime", type="integer", nullable=false)
     */
    private $playingtime;

    /**
     * @var integer $classification
     *
     * @ORM\Column(name="classification", type="integer", nullable=false)
     */
    private $classification;



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
     * Set parent id - related category
     *
     * @param integer $pid
     * @return Group
     */
    public function setPid($pid)
    {
        $this->pid = $pid;
    
        return $this;
    }

    /**
     * Get parent id - related category
     *
     * @return integer 
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Group
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
     * Set playing time
     *
     * @param integer $playingtime
     * @return Group
     */
    public function setPlayingtime($playingtime)
    {
        $this->playingtime = $playingtime;
    
        return $this;
    }

    /**
     * Get playing time
     *
     * @return integer 
     */
    public function getPlayingtime()
    {
        return $this->playingtime;
    }

    /**
     * Set classification
     *
     * @param integer $classification
     * @return Group
     */
    public function setClassification($classification)
    {
        $this->classification = $classification;
    
        return $this;
    }

    /**
     * Get classification
     *
     * @return integer 
     */
    public function getClassification()
    {
        return $this->classification;
    }
}
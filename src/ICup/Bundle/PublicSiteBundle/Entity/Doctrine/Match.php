<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\ORM\Mapping as ORM;
use DateTime;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match
 *
 * @ORM\Table(name="matches",uniqueConstraints={@ORM\UniqueConstraint(name="IdxByGroup", columns={"pid", "id"})})
 * @ORM\Entity
 */
class Match
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
     * Relation to group - pid=group.id
     * @ORM\Column(name="pid", type="integer", nullable=false)
     */
    private $pid;

    /**
     * @var integer $playground
     * Relation to playground - playground=playground.id
     * @ORM\Column(name="playground", type="integer", nullable=false)
     */
    private $playground;
    
    /**
     * @var string $time
     * Match start time - HH:MM
     * @ORM\Column(name="time", type="string", length=5, nullable=false)
     */
    private $time;

    /**
     * @var string $date
     * Match start date - DD/MM/YYYY
     * @ORM\Column(name="date", type="string", length=10, nullable=false)
     */
    private $date;

    /**
     * @var integer $matchno
     * Official match no
     * @ORM\Column(name="matchno", type="integer", nullable=false)
     */
    private $matchno;



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
     * Set parent id - related group
     *
     * @param integer $pid
     * @return Match
     */
    public function setPid($pid)
    {
        $this->pid = $pid;
    
        return $this;
    }

    /**
     * Get parent id - related group
     *
     * @return integer 
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * Set playground relation
     *
     * @param integer $playground
     * @return Match
     */
    public function setPlayground($playground)
    {
        $this->playground = $playground;
    
        return $this;
    }

    /**
     * Get playground relation
     *
     * @return integer 
     */
    public function getPlayground()
    {
        return $this->playground;
    }

    /**
     * Set time
     *
     * @param string $time
     * @return Match
     */
    public function setTime($time)
    {
        $this->time = $time;
    
        return $this;
    }

    /**
     * Get time
     *
     * @return string 
     */
    public function getTime()
    {
        return $this->time;
    }
    
    /**
     * Set date
     *
     * @param string $date
     * @return Match
     */
    public function setDate($date)
    {
        $this->date = $date;
    
        return $this;
    }

    /**
     * Get date
     *
     * @return string 
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set match no
     *
     * @param integer $matchno
     * @return Match
     */
    public function setMatchno($matchno)
    {
        $this->matchno = $matchno;
    
        return $this;
    }

    /**
     * Get match no
     *
     * @return integer 
     */
    public function getMatchno()
    {
        return $this->matchno;
    }
}
<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\ORM\Mapping as ORM;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Enrollment
 *
 * @ORM\Table(name="matchalternatives",uniqueConstraints={@ORM\UniqueConstraint(name="IdxByPAttr", columns={"pid", "paid"})})
 * @ORM\Entity
 */
class MatchAlternative
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
     * Relation to MatchSchedule - pid=matchschedule.id
     * @ORM\Column(name="pid", type="integer", nullable=false)
     */
    private $pid;

    /**
     * @var integer $paid
     * Relation to PlaygroundAttribute - pid=playgroundattribute.id
     * @ORM\Column(name="paid", type="integer", nullable=false)
     */
    private $paid;

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
     * Set parent id - related matchschedule
     *
     * @param integer $pid
     * @return MatchAlternative
     */
    public function setPid($pid)
    {
        $this->pid = $pid;
    
        return $this;
    }

    /**
     * Get parent id - related matchschedule
     *
     * @return integer 
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * Set child id - related playground attributes
     *
     * @param integer $paid
     * @return MatchAlternative
     */
    public function setPaid($paid)
    {
        $this->paid = $paid;
    
        return $this;
    }

    /**
     * Get child id - related playground attributes
     *
     * @return integer 
     */
    public function getPaid()
    {
        return $this->paid;
    }
}
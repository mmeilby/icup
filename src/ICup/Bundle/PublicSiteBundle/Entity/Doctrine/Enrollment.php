<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\ORM\Mapping as ORM;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Enrollment
 *
 * @ORM\Table(name="enrollments",uniqueConstraints={@ORM\UniqueConstraint(name="IdxByCat", columns={"pid", "id"})})
 * @ORM\Entity
 */
class Enrollment
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
     * Relation to Category - pid=category.id 
     * @ORM\Column(name="pid", type="integer", nullable=false)
     */
    private $pid;

    /**
     * @var integer $cid
     * Relation to Team - cid=team.id 
     * @ORM\Column(name="cid", type="integer", nullable=false)
     */
    private $cid;

    /**
     * @var integer $uid
     * Relation to User - uid=user.id 
     * @ORM\Column(name="uid", type="integer", nullable=false)
     */
    private $uid;

    /**
     * @var string $date
     * Enrollment date - DD/MM/YYYY
     * @ORM\Column(name="date", type="string", length=10, nullable=false)
     */
    private $date;

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
     * @return Enrollment
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
     * Set child id - related team
     *
     * @param integer $cid
     * @return Enrollment
     */
    public function setCid($cid)
    {
        $this->cid = $cid;
    
        return $this;
    }

    /**
     * Get child id - related team
     *
     * @return integer 
     */
    public function getCid()
    {
        return $this->cid;
    }
    
    /**
     * Set user id - related user
     *
     * @param integer $uid
     * @return Enrollment
     */
    public function setUid($uid)
    {
        $this->uid = $uid;
    
        return $this;
    }

    /**
     * Get user id - related user
     *
     * @return integer 
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * Set date
     *
     * @param string $date
     * @return Enrollment
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
}
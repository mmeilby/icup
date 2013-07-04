<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\ORM\Mapping as ORM;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\GroupOrder
 *
 * @ORM\Table(name="grouporders")
 * @ORM\Entity
 */
class GroupOrder
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
     * Relation to Group - pid=group.id 
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
     * @return GroupOrder
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
     * Set child id - related team
     *
     * @param integer $cid
     * @return GroupOrder
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
}
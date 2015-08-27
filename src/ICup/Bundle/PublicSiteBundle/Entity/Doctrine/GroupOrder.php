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
     * @var Group $group
     * Relation to Group
     * @ORM\ManyToOne(targetEntity="Group", inversedBy="grouporder")
     * @ORM\JoinColumn(name="pid", referencedColumnName="id")
     */
    private $group;

    /**
     * @var Team $team
     * Relation to Team
     * @ORM\ManyToOne(targetEntity="Team", inversedBy="grouporder")
     * @ORM\JoinColumn(name="cid", referencedColumnName="id")
     */
    private $team;

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
     * @return Group
     */
    public function getGroup() {
        return $this->group;
    }

    /**
     * @param Group $group
     * @return GroupOrder
     */
    public function setGroup($group) {
        $this->group = $group;
        return $this;
    }

    /**
     * @return Team
     */
    public function getTeam() {
        return $this->team;
    }

    /**
     * @param Team $team
     * @return GroupOrder
     */
    public function setTeam($team) {
        $this->team = $team;
        return $this;
    }
}
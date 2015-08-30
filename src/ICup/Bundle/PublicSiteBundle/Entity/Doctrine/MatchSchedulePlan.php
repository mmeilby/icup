<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchSchedulePlan
 *
 * @ORM\Table(name="matchscheduleplans")
 * @ORM\Entity
 */
class MatchSchedulePlan
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
     * @var PlaygroundAttribute $tournament
     * Relation to PlaygroundAttribute
     * @ORM\ManyToOne(targetEntity="PlaygroundAttribute")
     * @ORM\JoinColumn(name="paid", referencedColumnName="id")
     */
    protected $playgroundAttribute;

    /**
     * @var string $matchstart
     * Scheduled match start - format Hi
     * @ORM\Column(name="matchstart", type="string", length=4, nullable=false)
     */
    protected $matchstart;

    /**
     * @var string $fixed
     * Indicates this record holds a fixed schedule (not allowed to change)
     * @ORM\Column(name="fixed", type="boolean", nullable=false)
     */
    protected $fixed;

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
     * @return PlaygroundAttribute
     */
    public function getPlaygroundAttribute() {
        return $this->playgroundAttribute;
    }

    /**
     * @param PlaygroundAttribute $playgroundAttribute
     */
    public function setPlaygroundAttribute(PlaygroundAttribute $playgroundAttribute) {
        $this->playgroundAttribute = $playgroundAttribute;
    }

    /**
     * Get match start - scheduled time of start
     *
     * @return string
     */
    public function getMatchstart()
    {
        return $this->matchstart;
    }

    /**
     * Set match start - scheduled time of start
     *
     * @param string $matchstart
     * @return MatchSchedule
     */
    public function setMatchstart($matchstart)
    {
        $this->matchstart = $matchstart;
        return $this;
    }

    /**
     * Check if match schedule is fixed
     *
     * @return string
     */
    public function isFixed()
    {
        return $this->fixed;
    }

    /**
     * Set the match schedule to be fixed
     *
     * @param string $fixed
     * @return MatchSchedule
     */
    public function setFixed($fixed)
    {
        $this->fixed = $fixed;
        return $this;
    }
}

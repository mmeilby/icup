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
    protected $id;

    /**
     * @var MatchSchedule $matchschedule
     * Relation to MatchSchedule
     * @ORM\ManyToOne(targetEntity="MatchSchedule", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="pid", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $matchschedule;

    /**
     * @var PlaygroundAttribute $playgroundAttribute
     * Relation to PlaygroundAttribute
     * @ORM\ManyToOne(targetEntity="PlaygroundAttribute")
     * @ORM\JoinColumn(name="paid", referencedColumnName="id")
     */
    protected $playgroundAttribute;

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
     * @return MatchSchedule
     */
    public function getMatchSchedule() {
        return $this->matchschedule;
    }

    /**
     * @param MatchSchedule $matchschedule
     * @return MatchAlternative
     */
    public function setMatchSchedule($matchschedule) {
        $this->matchschedule = $matchschedule;
        return $this;
    }

    /**
     * @return PlaygroundAttribute
     */
    public function getPlaygroundAttribute() {
        return $this->playgroundAttribute;
    }

    /**
     * @param PlaygroundAttribute $playgroundAttribute
     * @return MatchAlternative
     */
    public function setPlaygroundAttribute($playgroundAttribute) {
        $this->playgroundAttribute = $playgroundAttribute;
        return $this;
    }
}
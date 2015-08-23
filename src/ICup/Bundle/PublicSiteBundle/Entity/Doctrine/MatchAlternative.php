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
     * @var MatchSchedule $matchSchedule
     * Relation to MatchSchedule
     * @ORM\ManyToOne(targetEntity="MatchSchedule", inversedBy="id", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="pid", referencedColumnName="id", onDelete="CASCADE")
     */
    private $matchSchedule;

    /**
     * @var PlaygroundAttribute $tournament
     * Relation to PlaygroundAttribute
     * @ORM\ManyToOne(targetEntity="PlaygroundAttribute", inversedBy="id")
     * @ORM\JoinColumn(name="paid", referencedColumnName="id")
     */
    private $playgroundAttribute;

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
        return $this->matchSchedule;
    }

    /**
     * @param MatchSchedule $matchSchedule
     * @return MatchAlternative
     */
    public function setMatchSchedule($matchSchedule) {
        $this->matchSchedule = $matchSchedule;
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
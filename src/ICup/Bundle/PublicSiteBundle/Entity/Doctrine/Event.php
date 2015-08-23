<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\ORM\Mapping as ORM;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category
 *
 * @ORM\Table(name="events",uniqueConstraints={@ORM\UniqueConstraint(name="IdxByDate", columns={"pid", "date"})})
 * @ORM\Entity
 */
class Event
{
    /* Tournament is open for team enrollment */
    public static $ENROLL_START = 1;
    /* Tournament is closed for new team enrollment */
    public static $ENROLL_STOP = 2;
    /* Tournament is started */
    public static $MATCH_START = 3;
    /* Tournament is over - hall of fame is visual */
    public static $MATCH_STOP = 4;
    /* Tournament is archived and only visual for editor administrators */
    public static $TOURNAMENT_ARCHIVED = 9;

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Tournament $tournament
     * Relation to Tournament
     * @ORM\ManyToOne(targetEntity="Tournament", inversedBy="id")
     * @ORM\JoinColumn(name="pid", referencedColumnName="id")
     */
    private $tournament;

    /**
     * @var string $date
     * Date for this event to happen - DD/MM/YYYY
     * @ORM\Column(name="date", type="string", length=10, nullable=false)
     */
    private $date;

    /**
     * @var integer $event
     * Event:
     *    1: Tournament is open for enrollment
     *    2: Enrollment is closed
     *    3: Tournament is started
     *    4: Tournament is over - hall of fame is visual
     *    9: Tournament archived - only visual for editor administrators
     * @ORM\Column(name="event", type="integer", nullable=false)
     */
    private $event;

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
     * @return Tournament
     */
    public function getTournament() {
        return $this->tournament;
    }

    /**
     * @param Tournament $tournament
     * @return Event
     */
    public function setTournament($tournament) {
        $this->tournament = $tournament;
        return $this;
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
     * Set event
     *
     * @param integer $event
     * @return Event
     */
    public function setEvent($event)
    {
        $this->event = $event;
    
        return $this;
    }

    /**
     * Get event
     *
     * @return integer 
     */
    public function getEvent()
    {
        return $this->event;
    }
}
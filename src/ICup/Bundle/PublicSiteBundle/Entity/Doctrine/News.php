<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\News
 *
 * @ORM\Table(name="news",uniqueConstraints={@ORM\UniqueConstraint(name="NewsNoConstraint", columns={"pid", "newsno", "language"})})
 * @ORM\Entity
 */
class News implements JsonSerializable
{
    /* Information is permanent - will not out date */
    public static $TYPE_PERMANENT = 1;
    /* Infomration is visible for a short time after the date stamp */
    public static $TYPE_TIMELIMITED = 2;
    /* Information is permanent shown on the frontpage - will not out date */
    public static $TYPE_FRONTPAGE_PERMANENT = 3;
    /* Infomration is visible for a short time after the date stamp on the frontpage */
    public static $TYPE_FRONTPAGE_TIMELIMITED = 4;

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var Tournament $tournament
     * Relation to Tournament
     * @ORM\ManyToOne(targetEntity="Tournament", inversedBy="news")
     * @ORM\JoinColumn(name="pid", referencedColumnName="id")
     */
    protected $tournament;

    /**
     * @var string $date
     * Date for this event to happen - YYYYMMDD
     * @ORM\Column(name="date", type="string", length=8, nullable=false)
     */
    protected $date;

    /**
     * @var Match $match
     * Relation to Match
     * @ORM\ManyToOne(targetEntity="Match")
     * @ORM\JoinColumn(name="mid", referencedColumnName="id")
     */
    protected $match;

    /**
     * @var Team $team
     * Relation to Team
     * @ORM\ManyToOne(targetEntity="Team")
     * @ORM\JoinColumn(name="cid", referencedColumnName="id")
     */
    protected $team;

    /**
     * @var integer $newstype
     * @ORM\Column(name="newstype", type="integer", nullable=false)
     */
    protected $newstype;

    /**
     * @var integer $newsno
     * @ORM\Column(name="newsno", type="integer", nullable=false)
     */
    protected $newsno;

    /**
     * @var string $language
     *
     * @ORM\Column(name="language", type="string", length=2, nullable=false)
     */
    protected $language;

    /**
     * @var string $title
     *
     * @ORM\Column(name="title", type="string", length=50, nullable=false)
     */
    protected $title;

    /**
     * @var string $context
     *
     * @ORM\Column(name="context", type="text", nullable=false)
     */
    protected $context;

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
     * @return News
     */
    public function setTournament($tournament) {
        $this->tournament = $tournament;
        return $this;
    }

    /**
     * Set date
     *
     * @param string $date
     * @return News
     */
    public function setDate($date)
    {
        $this->date = $date;
    
        return $this;
    }

    /**
     * @return Match
     */
    public function getMatch() {
        return $this->match;
    }

    /**
     * @param Match $match
     * @return News
     */
    public function setMatch($match) {
        $this->match = $match;
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
     * @return News
     */
    public function setTeam($team) {
        $this->team = $team;
        return $this;
    }

    /**
     * @return int
     */
    public function getNewstype() {
        return $this->newstype;
    }

    /**
     * @param int $newstype
     */
    public function setNewstype($newstype) {
        $this->newstype = $newstype;
    }

    /**
     * @return int
     */
    public function getNewsno() {
        return $this->newsno;
    }

    /**
     * @param int $newsno
     * @return News
     */
    public function setNewsno($newsno) {
        $this->newsno = $newsno;
        return $this;
    }

    /**
     * @return string
     */
    public function getLanguage() {
        return $this->language;
    }

    /**
     * @param string $language
     * @return News
     */
    public function setLanguage($language) {
        $this->language = $language;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * @param string $title
     * @return News
     */
    public function setTitle($title) {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getContext() {
        return $this->context;
    }

    /**
     * @param string $context
     * @return News
     */
    public function setContext($context) {
        $this->context = $context;
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

    public function getSchedule() {
        return Date::getDateTime($this->date);
    }

    public function __toString() {
        return $this->getNewsno().": ".$this->getTitle()." (".$this->getLanguage().", ".$this->getDate().")";
    }

    /**
     * (PHP 5 &gt;= 5.4.0)<br/>
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     */
    function jsonSerialize() {
        return array(
            "objectType" => "News",
            "id" => $this->id, "newsno" => $this->newsno, "language" => $this->language, "newstype" => $this->newstype,
            'date' => Date::jsonDateSerialize($this->date),
            "title" => $this->title, "context" => $this->context,
            "match" => $this->match ? $this->match->jsonSerialize() : array("id" => 0),
            "category" => $this->team ? $this->team->getCategory()->jsonSerialize() : array("id" => 0),
            "team" => $this->team ? $this->team->jsonSerialize() : array("id" => 0)
        );
    }
}
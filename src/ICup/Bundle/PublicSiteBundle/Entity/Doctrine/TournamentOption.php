<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\TournamentOption
 *
 * @ORM\Table(name="tournamentoptions")
 * @ORM\Entity
 */
class TournamentOption implements JsonSerializable
{
    const MATCH_POINTS = "MATCH_POINTS", TIE_SCORE_DIFF = "TIE_SCORE_DIFF", MATCH_SCORE_DIFF = "MATCH_SCORE_DIFF", MATCH_SCORE = "MATCH_SCORE", MAX_GOALS = "MAX_GOALS";

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var boolean $drr
     * Double Round Robin tournament desired?
     * @ORM\Column(name="drr", type="boolean", nullable=false, options={"default":false})
     */
    protected $drr = false;

    /**
     * @var boolean $svd
     * Chosen strategy for team match planning (Same Venue Desired):
     *   false: distribute matches for a team between venues (no restriction)
     *   true: gather matches for a team on the same vuenue
     * @ORM\Column(name="svd", type="boolean", nullable=false, options={"default":false})
     */
    protected $svd = false;

    /**
     * @var boolean $er
     * Eliminating rounds used in this tournament?
     * @ORM\Column(name="er", type="boolean", nullable=false, options={"default":true})
     */
    protected $er = true;

    /**
     * @var integer $strategy
     * Not used
     * @ORM\Column(name="strategy", type="integer", nullable=false, options={"default":0})
     */
    protected $strategy = 0;

    /**
     * @var integer $wpoints
     * Number of points assigned to the winning team
     * @ORM\Column(name="wpoints", type="integer", nullable=false, options={"unsigned":true, "default":2})
     */
    protected $wpoints = 2;

    /**
     * @var integer $tpoints
     * Number of points assigned to tie teams
     * @ORM\Column(name="tpoints", type="integer", nullable=false, options={"unsigned":true, "default":1})
     */
    protected $tpoints = 1;

    /**
     * @var integer $lpoints
     * Number of points assigned to the loosing team
     * @ORM\Column(name="lpoints", type="integer", nullable=false, options={"unsigned":true, "default":0})
     */
    protected $lpoints = 0;

    /**
     * @var integer $dscore
     * Number of goals assigned to the winning team if opponent is disqualified
     * @ORM\Column(name="dscore", type="integer", nullable=false, options={"unsigned":true, "default":10})
     */
    protected $dscore = 10;

    /**
     * @var string $order
     * Number of goals assigned to the winning team if opponent is disqualified
     * @ORM\Column(name="tieorder", type="string", length=250, nullable=false)
     */
    protected $order = "";

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return boolean
     */
    public function isDrr() {
        return $this->drr;
    }

    /**
     * @param boolean $drr
     * @return TournamentOption
     */
    public function setDrr($drr) {
        $this->drr = $drr;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isSvd() {
        return $this->svd;
    }

    /**
     * @param boolean $svd
     * @return TournamentOption
     */
    public function setSvd($svd) {
        $this->svd = $svd;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isEr() {
        return $this->er;
    }

    /**
     * @param boolean $er
     * @return TournamentOption
     */
    public function setEr($er) {
        $this->er = $er;
        return $this;
    }

    /**
     * @return int
     */
    public function getStrategy() {
        return $this->strategy;
    }

    /**
     * @param int $strategy
     * @return TournamentOption
     */
    public function setStrategy($strategy) {
        $this->strategy = $strategy;
        return $this;
    }

    /**
     * @return int
     */
    public function getWpoints() {
        return $this->wpoints;
    }

    /**
     * @param int $wpoints
     * @return TournamentOption
     */
    public function setWpoints($wpoints) {
        $this->wpoints = $wpoints;
        return $this;
    }

    /**
     * @return int
     */
    public function getTpoints() {
        return $this->tpoints;
    }

    /**
     * @param int $tpoints
     * @return TournamentOption
     */
    public function setTpoints($tpoints) {
        $this->tpoints = $tpoints;
        return $this;
    }

    /**
     * @return int
     */
    public function getLpoints() {
        return $this->lpoints;
    }

    /**
     * @param int $lpoints
     * @return TournamentOption
     */
    public function setLpoints($lpoints) {
        $this->lpoints = $lpoints;
        return $this;
    }

    /**
     * @return int
     */
    public function getDscore() {
        return $this->dscore;
    }

    /**
     * @param int $dscore
     * @return TournamentOption
     */
    public function setDscore($dscore) {
        $this->dscore = $dscore;
        return $this;
    }

    /**
     * @return array
     */
    public function getOrder() {
        return $this->order == '' ? array(static::MATCH_POINTS, static::TIE_SCORE_DIFF, static::MATCH_SCORE_DIFF, static::MATCH_SCORE, static::MAX_GOALS) : json_decode($this->order);
    }

    /**
     * @param array $order
     * @return TournamentOption
     */
    public function setOrder($order) {
        $this->order = json_encode($order);
        return $this;
    }

    public function __toString() {
        return "TmntOptions".json_encode($this->jsonSerialize());
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
            "objectType" => "TournamentOption",
            'drr' => $this->drr,
            'svd' => $this->svd,
            'er' => $this->er,
            'strategy' => $this->strategy,
            'wpoints' => $this->wpoints,
            'tpoints' => $this->tpoints,
            'lpoints' => $this->lpoints,
            'dscore' => $this->dscore,
            'order' => $this->order == '' ? array(static::MATCH_POINTS, static::TIE_SCORE_DIFF, static::MATCH_SCORE_DIFF, static::MATCH_SCORE, static::MAX_GOALS) : json_decode($this->order)
        );
    }
}
<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\ORM\Mapping as ORM;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\TournamentOption
 *
 * @ORM\Table(name="tournamentoptions")
 * @ORM\Entity
 */
class TournamentOption
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
     * @var boolean $drr
     * Double Round Robin tournament desired?
     * @ORM\Column(name="drr", type="boolean", nullable=false, options={"default":false})
     */
    protected $drr = false;

    /**
     * @var integer $strategy
     * Chosen strategy for match planning:
     *   0: plan matches - distribute over playgrounds
     *   1: plan matches - gather matches on same playground
     * @ORM\Column(name="strategy", type="integer", nullable=false, options={"default":0})
     */
    protected $strategy = 0;

    /**
     * @var integer $wpoints
     * Number of points assigned to the winning team
     * @ORM\Column(name="wpoints", type="integer", nullable=false, options={"unsigned":true, "default":2})
     */
    protected $wpoints = 3;

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
     * @ORM\Column(name="dscore", type="integer", nullable=false, options={"unsigned":true, "default":6})
     */

    protected $dscore = 6;

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
}
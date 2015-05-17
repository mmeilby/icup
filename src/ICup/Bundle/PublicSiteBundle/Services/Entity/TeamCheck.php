<?php
namespace ICup\Bundle\PublicSiteBundle\Services\Entity;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Timeslot;
use ICup\Bundle\PublicSiteBundle\Entity\MatchPlan;
use ICup\Bundle\PublicSiteBundle\Entity\TeamInfo;

class TeamCheck
{
    private $teams;
    
    public function __construct()
    {
        $this->teams = array();
    }

    public function isTeamCapacity(TeamInfo $team, $date, Timeslot $timeslot) {
        $key = $this->makeKey($team, $date, $timeslot);
        if (array_key_exists($key, $this->teams)) {
            return $this->teams[$key] < $timeslot->getCapacity();
        }
        $this->teams[$key] = 0;
        return $timeslot->getCapacity() > 0;
                
    }

    public function isCapacity(MatchPlan $match, $date, Timeslot $timeslot) {
        $tac = $this->isTeamCapacity($match->getTeamA(), $date, $timeslot);
        $tab = $this->isTeamCapacity($match->getTeamB(), $date, $timeslot);
        return $tac && $tab;
    }

    public function reserveTeamCapacity(TeamInfo $team, $date, Timeslot $timeslot) {
        $key = $this->makeKey($team, $date, $timeslot);
        $this->teams[$key]++;
    }

    public function reserveCapacity(MatchPlan $match, $date, Timeslot $timeslot) {
        $this->reserveTeamCapacity($match->getTeamA(), $date, $timeslot);
        $this->reserveTeamCapacity($match->getTeamB(), $date, $timeslot);
    }

    public function freeTeamCapacity(TeamInfo $team, $date, Timeslot $timeslot) {
        $key = $this->makeKey($team, $date, $timeslot);
        $this->teams[$key]--;
    }

    public function freeCapacity(MatchPlan $match, $date, Timeslot $timeslot) {
        $this->freeTeamCapacity($match->getTeamA(), $date, $timeslot);
        $this->freeTeamCapacity($match->getTeamB(), $date, $timeslot);
    }

    private function makeKey(TeamInfo $team, $date, Timeslot $timeslot) {
        return $timeslot->getId()."-".
               $team->getId()."-".
               $date;
    }
}
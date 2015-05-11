<?php
namespace ICup\Bundle\PublicSiteBundle\Services\Entity;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Timeslot;
use ICup\Bundle\PublicSiteBundle\Entity\TeamInfo;

class TeamCheck
{
    private $teams;
    
    public function __construct()
    {
        $this->teams = array();
    }

    public function isMoreCapacity(TeamInfo $team, $date, Timeslot $timeslot) {
        $key = $this->makeKey($team, $date, $timeslot);
        if (array_key_exists($key, $this->teams)) {
            return $this->teams[$key] < $timeslot->getCapacity();
        }
        $this->addTeam($team, $date, $timeslot);
        return $timeslot->getCapacity() > 0;
                
    }

    public function reserveCapacity(TeamInfo $team, $date, Timeslot $timeslot) {
        $key = $this->makeKey($team, $date, $timeslot);
        $this->teams[$key]++;
    }
    
    public function freeCapacity(TeamInfo $team, $date, Timeslot $timeslot) {
        $key = $this->makeKey($team, $date, $timeslot);
        $this->teams[$key]--;
    }
    
    private function addTeam(TeamInfo $team, $date, Timeslot $timeslot) {
        $key = $this->makeKey($team, $date, $timeslot);
        $this->teams[$key] = 0;
    }
    
    private function makeKey(TeamInfo $team, $date, Timeslot $timeslot) {
        return $timeslot->getId()."-".
               $team->id."-".
               $date;
    }
}
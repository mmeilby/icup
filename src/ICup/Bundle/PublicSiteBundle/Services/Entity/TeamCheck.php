<?php
namespace ICup\Bundle\PublicSiteBundle\Services\Entity;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Playground;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Timeslot;
use ICup\Bundle\PublicSiteBundle\Entity\MatchPlan;
use DateInterval;
use DateTime;

class TeamCheck
{
    private $teams;

    public function __construct()
    {
        $this->teams = array();
    }

    /**
     * Test team for schedule availability
     * Different rules may apply:
     *  1. within each timeslot a team may play one or more times each day
     *  2. if timeslot allows more games each day then a rest period must be allowed between each match
     *  3. if timeslot allows more games each day and penalty for different sites is selected only one site is allowed for these matches
     *  4. No two matches can be played at the same time
     * @param Team $team the team to be verified
     * @param DateTime $slotschedule the schedule to test for availablity
     * @param Playground $playground the planned playground for the scheduled match
     * @param Timeslot $timeslot the planned timeslot for the scheduled match
     * @return bool true if schedule is available for the planned playground and timeslot
     */
    private function isTeamCapacity(Team $team, DateTime $slotschedule, Playground $playground, Timeslot $timeslot) {
        $date = Date::getDate($slotschedule);
        $time = Date::getTime($slotschedule);
        $key = $this->makeKey($team, $date, $timeslot);
        if (isset($this->teams[$key])) {
            if (count($this->teams[$key]) < $timeslot->getCapacity() && !isset($this->teams[$key][$time])) {
                /* @var $match MatchPlan */
                foreach ($this->teams[$key] as $match) {
                    /* @var $diff DateInterval */
                    $diff = $match->getSchedule()->diff($slotschedule);
                    if ($diff->h*60 + $diff->i < $team->getCategory()->getMatchtime() + $timeslot->getRestperiod()) {
                        return false;
                    }
                    if ($timeslot->getPenalty() &&
                        $match->getPlayground()->getSite()->getId() != $playground->getSite()->getId()) {
                        return false;
                    }
                }
                return true;
            }
            return false;
        }
        $this->teams[$key] = array();
        return $timeslot->getCapacity() > 0;
    }

    /**
     * Test match for schedule availability
     * Different rules may apply:
     *  1. within each timeslot teams may play one or more times each day
     *  2. if timeslot allows more games each day then a rest period must be allowed between each match
     *  3. if timeslot allows more games each day and penalty for different sites is selected only one site is allowed for these matches
     *  4. No two matches can be played at the same time
     * @param MatchPlan $match match to be verified - each team for this match will be verified individually
     * @param DateTime $slotschedule the schedule to test for availablity
     * @param Playground $playground the planned playground for the scheduled match
     * @param Timeslot $timeslot the planned timeslot for the scheduled match
     * @return bool true if schedule is available for the planned playground and timeslot
     */
    public function isCapacity(MatchPlan $match, DateTime $slotschedule, Playground $playground, Timeslot $timeslot) {
        $tac = $this->isTeamCapacity($match->getTeamA(), $slotschedule, $playground, $timeslot);
        $tab = $this->isTeamCapacity($match->getTeamB(), $slotschedule, $playground, $timeslot);
        return $tac && $tab;
    }

    private function reserveTeamCapacity(Team $team, MatchPlan $match, Timeslot $timeslot) {
        $date = Date::getDate($match->getSchedule());
        $time = Date::getTime($match->getSchedule());
        $key = $this->makeKey($team, $date, $timeslot);
        $this->teams[$key][$time] = $match;
    }

    /**
     * Reserve match schedule for timeslot
     * @param MatchPlan $match
     * @param Timeslot $timeslot
     */
    public function reserveCapacity(MatchPlan $match, Timeslot $timeslot) {
        $this->reserveTeamCapacity($match->getTeamA(), $match, $timeslot);
        $this->reserveTeamCapacity($match->getTeamB(), $match, $timeslot);
    }

    private function freeTeamCapacity(Team $team, MatchPlan $match, Timeslot $timeslot) {
        $date = Date::getDate($match->getSchedule());
        $time = Date::getTime($match->getSchedule());
        $key = $this->makeKey($team, $date, $timeslot);
        unset($this->teams[$key][$time]);
    }

    /**
     * Free match schedule for timeslot
     * @param MatchPlan $match
     * @param Timeslot $timeslot
     */
    public function freeCapacity(MatchPlan $match, Timeslot $timeslot) {
        $this->freeTeamCapacity($match->getTeamA(), $match, $timeslot);
        $this->freeTeamCapacity($match->getTeamB(), $match, $timeslot);
    }

    private function makeKey(Team $team, $date, Timeslot $timeslot) {
        return $timeslot->getId()."-".$team->getId()."-".$date;
    }
}
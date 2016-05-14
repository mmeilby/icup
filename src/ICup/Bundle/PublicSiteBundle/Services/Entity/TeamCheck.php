<?php
namespace ICup\Bundle\PublicSiteBundle\Services\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Playground;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Site;
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
        if (isset($this->teams[$team->getId()][$key])) {
            if (count($this->teams[$team->getId()][$key]) < $timeslot->getCapacity() && !isset($this->teams[$team->getId()][$key][$time])) {
                /* @var $match MatchPlan */
                foreach ($this->teams[$team->getId()] as $calendar) {
                    foreach ($calendar as $match) {
                        /* @var $diff DateInterval */
                        $diff = $match->getSchedule()->diff($slotschedule, true);
                        if ($diff->d*24*60 + $diff->h*60 + $diff->i < $team->getCategory()->getMatchtime() + $timeslot->getRestperiod()) {
                            return false;
                        }
                        if ($timeslot->getPenalty() &&
                            $match->getPlayground()->getSite()->getId() != $playground->getSite()->getId()) {
                            return false;
                        }
                    }
                }
                return true;
            }
            return false;
        }
        $this->teams[$team->getId()][$key] = array();
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
        $this->teams[$team->getId()][$key][$time] = $match;
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
        unset($this->teams[$team->getId()][$key][$time]);
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

    /**
     * Get minimum rest time for a team
     * @param Team $team team to search for
     * @return mixed number of minutes of minimum rest time for the team
     */
    private function getTeamMinRestTime(Team $team, DateTime $slotschedule) {
        $schedules = array($slotschedule);
        if (isset($this->teams[$team->getId()])) {
            foreach ($this->teams[$team->getId()] as $calendar) {
                /* @var $match MatchPlan */
                foreach ($calendar as $match) {
                    $schedules[] = $match->getSchedule();
                }
            }
            usort($schedules, function (DateTime $s1, DateTime $s2) {
                return $s1 == $s2 ? 0 : ($s1 < $s2 ? -1 : 1);
            });
        }
        $minRestTime = 24*60;
        /* @var $slotschedule DateTime */
        for ($idx = 1; $idx < count($schedules); $idx++) {
            /* @var $diff DateInterval */
            $diff = $schedules[$idx-1]->diff($schedules[$idx]);
            $difftime = $diff->d*24*60 + $diff->h*60 + $diff->i;
            $minRestTime = $idx == 1 ? $difftime : min($minRestTime, $difftime); 
        }
        return $minRestTime;
    }

    public function getMinRestTime(MatchPlan $match, DateTime $slotschedule) {
        return min($this->getTeamMinRestTime($match->getTeamA(), $slotschedule), $this->getTeamMinRestTime($match->getTeamB(), $slotschedule));
    }

    private function travelTeamPenalty(Team $team, Site $site) {
        if (isset($this->teams[$team->getId()])) {
            foreach ($this->teams[$team->getId()] as $calendar) {
                /* @var $match MatchPlan */
                foreach ($calendar as $match) {
                    if ($match->getPlayground()->getSite()->getId() != $site->getId()) {
                        return 1;
                    }
                }
            }
        }
        return 0;
    }

    public function travelPenalty(MatchPlan $match, Site $site) {
        return $this->travelTeamPenalty($match->getTeamA(), $site) + $this->travelTeamPenalty($match->getTeamB(), $site);
    }

    private function timeslotTeamPenalty(Team $team, DateTime $slotschedule, Timeslot $timeslot) {
        $key = $this->makeKey($team, Date::getDate($slotschedule), $timeslot);
        if (isset($this->teams[$team->getId()][$key])) {
            if (count($this->teams[$team->getId()][$key]) < $timeslot->getCapacity()) {
                return 0;
            }
            return count($this->teams[$team->getId()][$key]) - $timeslot->getCapacity() + 1;
        }
        $this->teams[$team->getId()][$key] = array();
        return $timeslot->getCapacity() ? 0 : 1;
    }

    public function timeslotPenalty(MatchPlan $match, DateTime $slotschedule, Timeslot $timeslot) {
        return $this->timeslotTeamPenalty($match->getTeamA(), $slotschedule, $timeslot) + $this->timeslotTeamPenalty($match->getTeamB(), $slotschedule, $timeslot);
    }
}
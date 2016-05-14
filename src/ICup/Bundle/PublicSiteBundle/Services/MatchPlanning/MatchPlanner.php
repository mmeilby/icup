<?php
/**
 * Created by PhpStorm.
 * User: mm
 * Date: 16/10/15
 * Time: 17.54
 */

namespace ICup\Bundle\PublicSiteBundle\Services\MatchPlanning;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
use ICup\Bundle\PublicSiteBundle\Entity\MatchPlan;
use ICup\Bundle\PublicSiteBundle\Services\Entity\MatchPlanningError;
use ICup\Bundle\PublicSiteBundle\Services\Entity\PlaygroundAttribute as PA;
use ICup\Bundle\PublicSiteBundle\Services\Entity\PlanningResults;
use DateTime;
use DateInterval;
use Monolog\Logger;

class MatchPlanner
{
    /* @var $logger Logger */
    protected $logger;

    /*
        Matches	Timeslots	Matches/day	Teams
        45	    9	        5	        10
        36	    9	        4	        9
        28	    7	        4	        8
        21	    7	        3	        7
        15	    5	        3	        6
        10	    5	        2	        5
        6	    3	        2	        4
        3	    3	        1	        3
        1	    1	        1	        2
     */
    protected $statistics;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
        $this->statistics = array();
    }

    /**
     * Plan matches (preliminary rounds) for tournament
     * @param PlanningResults $result
     */
    public function plan(PlanningResults $result) {
        $this->statistics['plan']['unresolved before'] = $result->unresolved();
        $unplaceable = array();
        $result->shuffleUnresolved();
        while ($match = $result->nextUnresolved()) {
            $slot_found = $this->planMatch($result, $match);
            if (!$slot_found) {
                // if this was not possible register the match as finally unassigned
                $unplaceable[] = $match;
            }
        }
        foreach ($unplaceable as $match) {
            $result->appendUnresolved($match);
        }
        $this->statistics['plan']['unresolved after'] = $result->unresolved();
//        $this->replanSwapSchedules($result);
    }

    /**
     * @param PlanningResults $result
     * @param MatchPlan $match
     * @return boolean
     */
    private function planMatch(PlanningResults $result, MatchPlan $match) {
        $searchTree = array();
        $result->mark();
        /* @var $pa PA */
        while ($pa = $result->cycleTimeslot()) {
            /* @var $slotschedule DateTime */
            $slotschedule = $pa->getSchedule();
            $e = $this->dE($result, $pa, $match, $slotschedule);
            $searchTree[] = new MatchPlanningError($pa, $slotschedule, $e);
        }

        if (count($searchTree)) {
            usort($searchTree, function (MatchPlanningError $r1, MatchPlanningError $r2) {
                return $r1->getError() > $r2->getError() ? 1 : -1;
            });
            /* @var $mpe MatchPlanningError *
            foreach ($searchTree as $mpe) {
                echo Date::getDate($mpe->getSlotschedule())." ".Date::getTime($mpe->getSlotschedule())." ".$mpe->getPA()->getPlayground()->getNo().": ".$mpe->getError()."\n";
            }
            echo "\n"; */
            /* @var $mpe MatchPlanningError */
            $mpe = reset($searchTree);            
            $slotschedule = $mpe->getSlotschedule();
            $pa = $mpe->getPA();
            $match->setDate(Date::getDate($slotschedule));
            $match->setTime(Date::getTime($slotschedule));
            $match->setPlayground($pa->getPlayground());
            $match->setPlaygroundAttribute($pa->getPA());
            $slotschedule->add(new DateInterval('PT'.$match->getCategory()->getMatchtime().'M'));
            $pa->setSchedule($slotschedule);
            $matchlist = $pa->getMatchlist();
            $matchlist[] = $match;
            $pa->setMatchlist($matchlist);
            $result->getTeamCheck()->reserveCapacity($match, $pa->getTimeslot());
        }
        return count($searchTree);
    }

    /**
     * Error function - calculates the score for the current planning result.
     * A value closer to zero is better - any score will be positive or zero
     * @param PlanningResults $result
     * @return float The score for the current planning result. Closer to zero is better.
     */

    const IMPOSSIBLE = 10000.0;
    const TIMESLOT_CAPACITY_PENALTY = 100.0;
    const CATEGORY_PENALTY = 10.0;
    const TIMESLOT_EXCESS_PENALTY = 10.0;
    const SITE_PENALTY = 5.0;
    const REST_PENALTY = 5.0;           // multiplied with the number of matches between two matches (3 is max)
    const VENUE_PENALTY = 0.1;          // multiplied with the no of a venue
    const TIME_LEFT_PENALTY = 0.01;     // multiplied with minutes left in a timeslot
    
    private function dE(PlanningResults $result, PA $pa, MatchPlan $match, DateTime $slotschedule) {
        $dE = 100.0;
        $excess = $pa->getTimeleft();
        // test for excess of the timeslot - if time limit is broken there is no need to test further - return a big dE
        if ($excess > 0) {
            if (count($pa->getMatchlist()) > 0) {
                $dE -= $match->getCategory()->getMatchtime()*MatchPlanner::TIME_LEFT_PENALTY;
            }
            else {
                $dE += ($excess - $match->getCategory()->getMatchtime())*MatchPlanner::TIME_LEFT_PENALTY;
            }
            $dE += max($match->getCategory()->getMatchtime() - $excess, 0)*MatchPlanner::TIMESLOT_EXCESS_PENALTY;
            $rest = $result->getTeamCheck()->getMinRestTime($match, $slotschedule);
            $restPenalty = $pa->getTimeslot()->getRestperiod() - min($pa->getTimeslot()->getRestperiod(), $rest);
            $dE += $restPenalty*MatchPlanner::REST_PENALTY;
            $dE += $pa->getPlayground()->getNo()*MatchPlanner::VENUE_PENALTY;
            $dE += $pa->isCategoryAllowed($match->getCategory()) ? 0 : MatchPlanner::CATEGORY_PENALTY;
            if ($pa->getTimeslot()->getPenalty()) {
                $dE += $result->getTeamCheck()->travelPenalty($match, $pa->getPlayground()->getSite())*MatchPlanner::SITE_PENALTY;
            }
            $dE += $result->getTeamCheck()->timeslotPenalty($match, $slotschedule, $pa->getTimeslot())*MatchPlanner::TIMESLOT_CAPACITY_PENALTY;
        }
        else {
            $dE = MatchPlanner::IMPOSSIBLE;
        }
        return $dE;
    }

    /**
     * Replan swapping schedules that share the same team
     * @param PlanningResults $result
     */
    private function replanSwapSchedules(PlanningResults $result) {
        $this->statistics['swap']['unresolved before'] = $result->unresolved();
        $result->mark();
        /* @var $pa PA */
        while ($pa = $result->cycleTimeslot()) {
            /* Find a candidate for replacement */
            $matchlist = $pa->getMatchlist();
            /* @var $replan_match MatchPlan */
            foreach ($matchlist as $idx => $match) {
                // search for matches that are not yet tried to swap and share the same team
                if (!$replan_match->isFixed() && $this->teamsMatch($match, $replan_match)) {
                    // another match with same team was found - try release the match schedule and see if that makes some space
                    $result->getTeamCheck()->freeCapacity($replan_match, $pa->getTimeslot());
                    // does this make room for assignment?
                    if ($result->getTeamCheck()->isCapacity($match, $replan_match->getSchedule(), $replan_match->getPlayground(), $pa->getTimeslot())) {
                        // yes - try this schedule and see if puzzle is solved...
                        $match->setDate($replan_match->getDate());
                        $match->setTime($replan_match->getTime());
                        $match->setPlayground($replan_match->getPlayground());
                        $match->setPlaygroundAttribute($pa->getPA());
                        $match->setFixed(true);
                        $matchlist[$idx] = $match;
                        $pa->setMatchlist($matchlist);
                        $result->getTeamCheck()->reserveCapacity($match, $pa->getTimeslot());
                        $result->rewind();
                        return $replan_match;
                    }
                    else {
                        // nope - redo the release and try another match schedule
                        $result->getTeamCheck()->reserveCapacity($replan_match, $pa->getTimeslot());
                    }
                }
            }
        }
        $this->statistics['swap']['unresolved after'] = $result->unresolved();
    }

    /**
     * Check if two match records share the same team
     * @param MatchPlan $match
     * @param MatchPlan $replanMatch
     * @return bool
     */
    private function teamsMatch(MatchPlan $match, MatchPlan $replanMatch){
        return
            $match->getTeamA()->getId() == $replanMatch->getTeamA()->getId() ||
            $match->getTeamA()->getId() == $replanMatch->getTeamB()->getId() ||
            $match->getTeamB()->getId() == $replanMatch->getTeamA()->getId() ||
            $match->getTeamB()->getId() == $replanMatch->getTeamB()->getId();
    }
}
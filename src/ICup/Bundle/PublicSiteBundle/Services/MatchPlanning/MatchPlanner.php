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
        while ($match = $result->nextUnresolved()) {
            $mpe = $this->planMatch($result, $match);
            if ($mpe === false) {
                // if this was not possible register the match as finally unassigned
                $unplaceable[] = $match;
            }
            else {
                $this->placeMatch($result, $mpe);
            }
        }
        $result->setUnresolved($unplaceable);
        $this->statistics['plan']['unresolved after'] = $result->unresolved();
    }

    /**
     * @param PlanningResults $result
     * @param MatchPlan $match
     * @return MatchPlanningError
     */
    private function planMatch(PlanningResults $result, MatchPlan $match) {
        $searchTree = array();
        $result->mark();
        /* @var $pa PA */
        while ($pa = $result->cycleTimeslot()) {
            /* @var $slotschedule DateTime */
            $slotschedule = $pa->getSchedule();
            $e = $this->dE($result, $pa, $match, $slotschedule);
            $searchTree[] = new MatchPlanningError($match, $pa, $slotschedule, $e);
        }
        usort($searchTree, function (MatchPlanningError $r1, MatchPlanningError $r2) {
            return $r1->getError() > $r2->getError() ? 1 : -1;
        });
        return reset($searchTree);
    }

    /**
     * @param PlanningResults $result
     * @param MatchPlanningError $mpe
     */
    private function placeMatch(PlanningResults $result, MatchPlanningError $mpe) {
        $slotschedule = $mpe->getSlotschedule();
        $pa = $mpe->getPA();
        $match = $mpe->getMatch();
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
    /**
     * Error function - calculates the score for the current planning result.
     * A value closer to zero is better - any score will be positive or zero
     * @param PlanningResults $result
     * @return float The score for the current planning result. Closer to zero is better.
     */

    const IMPOSSIBLE = 10000.0;                 // penalty given for the impossible match schedule (simultanious matches, matches outside a timeslot)
    const TIMESLOT_CAPACITY_PENALTY = 100.0;    // multiplied with the number of undesired matches if the timeslot capacity is broken
    const CATEGORY_PENALTY = 10.0;              // penalty given for playing at a venue reserved for other categories
    const TIMESLOT_EXCESS_PENALTY = 10.0;       // multiplied with the number of minutes the timeslot upper limit is excessed
    const SITE_PENALTY = 5.0;                   // penalty given for playing at different sites
    const REST_PENALTY = 5.0;                   // multiplied with the number of minutes between two matches - less than required rest period
    const VENUE_PENALTY = 0.1;                  // multiplied with the wieght of a venue
    const TIME_LEFT_PENALTY = 0.01;             // multiplied with minutes left in a timeslot
    
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
            $rest = $result->getTeamCheck()->getMinRestTime($match, $slotschedule) - $match->getCategory()->getMatchtime();
            if ($rest >= 0) {
                $dE += ($pa->getTimeslot()->getRestperiod() - min($pa->getTimeslot()->getRestperiod(), $rest))*MatchPlanner::REST_PENALTY;
                $dE += $pa->getPlayground()->getWeight()*MatchPlanner::VENUE_PENALTY;
                $dE += $pa->isCategoryAllowed($match->getCategory()) ? 0 : MatchPlanner::CATEGORY_PENALTY;
                if ($pa->getTimeslot()->getPenalty()) {
                    $dE += $result->getTeamCheck()->travelPenalty($match, $pa->getPlayground()->getSite())*MatchPlanner::SITE_PENALTY;
                }
                $dE += $result->getTeamCheck()->timeslotPenalty($match, $slotschedule, $pa->getTimeslot())*MatchPlanner::TIMESLOT_CAPACITY_PENALTY;
            }
            else {
                $dE = MatchPlanner::IMPOSSIBLE;
            }
        }
        else {
            $dE = MatchPlanner::IMPOSSIBLE;
        }
        return $dE;
    }
}
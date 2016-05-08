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
use ICup\Bundle\PublicSiteBundle\Services\Entity\PlaygroundAttribute as PA;
use ICup\Bundle\PublicSiteBundle\Services\Entity\PlanningResults;
use DateTime;
use DateInterval;
use Monolog\Logger;

class MatchPlanner
{
    /* @var $logger Logger */
    protected $logger;

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
        $unplaceable = array();
        // sort criteria - anything more than two hours of free planning time is not considered
        $maxTimeLeft = 120;
        // Sort playground attributes - order:
        //   1. level of restriction on Category - ascending
        //   2. Planning time left (up to two hours) - descending
        //   3. Schedule - ascending
        $result->mark(function (PA $ats1, PA $ats2) use ($maxTimeLeft) {
            $p1 = (count($ats1->getPA()->getCategories()) ? count($ats1->getPA()->getCategories()) : 100)
                - (count($ats2->getPA()->getCategories()) ? count($ats2->getPA()->getCategories()) : 100);
            $p2 = min($ats2->getTimeleft(), $maxTimeLeft) - min($ats1->getTimeleft(), $maxTimeLeft);
            $p3 = $ats1->getPA()->getStartSchedule()->getTimestamp() - $ats2->getPA()->getStartSchedule()->getTimestamp();
            $test = min(1, max(-1, $p1))*4 + min(1, max(-1, $p2))*2 + min(1, max(-1, $p3));
            return min(1, max(-1, $test));
        });
        $result->rewind();
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
            /* Both teams must be allowed to play now */
            if ($result->getTeamCheck()->isCapacity($match, $slotschedule, $pa->getPlayground(), $pa->getTimeslot())) {
                $e = $this->dE($result, $pa, $match, $slotschedule);
                $searchTree[] = array('schedule' => $slotschedule, 'pa' => $pa, 'error' => $e);
            }
        }

        if (count($searchTree) > 0) {
            usort($searchTree, function ($r1, $r2) {
                return $r1['error'] > $r2['error'] ? 1 : -1;
            });
            /* @var $slotschedule DateTime */
            $slotschedule = $searchTree[0]['schedule'];
            $pa = $searchTree[0]['pa'];
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
        return count($searchTree) > 0;
    }

    /**
     * Error function - calculates the score for the current planning result.
     * A value closer to zero is better - any score will be positive or zero
     * @param PlanningResults $result
     * @return float The score for the current planning result. Closer to zero is better.
     */

    const CATEGORY_PENALTY = 60.0;
    const TIMESLOT_EXCESS_PENALTY = 2.0;
    const REST_PENALTY = 2.0;           // multiplied with the number of matches between two matches (3 is max)
    const VENUE_PENALTY = 0.1;          // multiplied with the no of a venue
    const TIME_LEFT_PENALTY = 0.01;     // multiplied with minutes left in a timeslot
    
    private function dE(PlanningResults $result, PA $pa, MatchPlan $match, DateTime $mschedule) {
        $dE = 100.0;
        $excess = $pa->getTimeleft()-$match->getCategory()->getMatchtime();
        if (count($pa->getMatchlist()) > 0) {
            if ($excess >= 0) {
                $dE -= $match->getCategory()->getMatchtime()*MatchPlanner::TIME_LEFT_PENALTY;
            }
        }
        else {
            $dE += ($pa->getTimeleft()-$match->getCategory()->getMatchtime())*MatchPlanner::TIME_LEFT_PENALTY;
        }
        $dE += max(-$excess, 0)*MatchPlanner::TIMESLOT_EXCESS_PENALTY;
        $dE += $pa->getPlayground()->getNo()*MatchPlanner::VENUE_PENALTY;
        $dE += $pa->isCategoryAllowed($match->getCategory()) ? 0 : MatchPlanner::CATEGORY_PENALTY;
        return $dE;
    }

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
}
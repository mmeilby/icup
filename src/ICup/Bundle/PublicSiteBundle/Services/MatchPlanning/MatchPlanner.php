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
use ICup\Bundle\PublicSiteBundle\Services\Entity\PlanningResults;
use DateTime;
use DateInterval;
use ICup\Bundle\PublicSiteBundle\Services\Entity\PlaygroundAttribute;
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


    /**
     * Plan matches (preliminary rounds) for tournament
     * @param PlanningResults $result
     */
    public function plan(PlanningResults $result) {
        $unplaceable = array();
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
/*
        if ($result->unresolved() > 0) {
            $this->replan_1run($result);
        }
*/
        if ($result->unresolved() > 0) {
            $this->replan_2run($result);
        }

        if ($result->unresolved() > 0) {
            $this->replan_3run($result);
        }

    }

    /**
     * @param PlanningResults $result
     * @param MatchPlan $match
     * @return boolean
     */
    private function planMatch(PlanningResults $result, MatchPlan $match) {
        $matchtime = $match->getCategory()->getMatchtime();
        // Sort playground attributes and prepare for inspection
        $result->mark(function (PlaygroundAttribute $ats1, PlaygroundAttribute $ats2) use ($matchtime) {
            $p1 = (count($ats1->getPA()->getCategories()) ? count($ats1->getPA()->getCategories()) : 100)
                - (count($ats2->getPA()->getCategories()) ? count($ats2->getPA()->getCategories()) : 100);
            $p2 = min($ats2->getTimeleft(), 2*$matchtime)  - min($ats1->getTimeleft(), 2*$matchtime);
            $p3 = $ats1->getPA()->getStartSchedule()->getTimestamp() - $ats2->getPA()->getStartSchedule()->getTimestamp();
            $test = min(1, max(-1, $p1))*4 + min(1, max(-1, $p2))*2 + min(1, max(-1, $p3));
            return min(1, max(-1, $test));
        });
        /* @var $pa PlaygroundAttribute */
        while ($pa = $result->cycleTimeslot()) {
            if ($pa->isCategoryAllowed($match->getCategory()) && $matchtime <= $pa->getTimeleft()) {
                /* @var $slotschedule DateTime */
                $slotschedule = $pa->getSchedule();
                /* Both teams must be allowed to play now */
                if ($result->getTeamCheck()->isCapacity($match, $slotschedule, $pa->getPlayground(), $pa->getTimeslot())) {
                    $match->setDate(Date::getDate($slotschedule));
                    $match->setTime(Date::getTime($slotschedule));
                    $match->setPlayground($pa->getPlayground());
                    $match->setPlaygroundAttribute($pa->getPA());
                    $slotschedule->add(new DateInterval('PT'.$matchtime.'M'));
                    $pa->setSchedule($slotschedule);
                    $matchlist = $pa->getMatchlist();
                    $matchlist[] = $match;
                    $pa->setMatchlist($matchlist);
                    $result->getTeamCheck()->reserveCapacity($match, $pa->getTimeslot());
                    $result->rewind();
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Plan the unplanned matches if possible - now allowing teams to play at any timeslot
     * @param PlanningResults $result
     */
    private function replan_1run(PlanningResults $result) {
        $this->statistics['1run']['unresolved before'] = $result->unresolved();
        $unplaceable = array();
        while ($match = $result->nextUnresolved()) {
            $slot_found = $this->replanMatch_1run($result, $match);
            if (!$slot_found) {
                $unplaceable[] = $match;
            }
        }

        foreach ($unplaceable as $match) {
            $result->appendUnresolved($match);
        }
        $this->statistics['1run']['unresolved after'] = $result->unresolved();
    }

    /**
     * @param PlanningResults $result
     * @param MatchPlan $match
     * @return boolean
     */
    private function replanMatch_1run(PlanningResults $result, MatchPlan $match) {
        // Sort playground attributes and prepare for inspection
        $result->mark(function (PlaygroundAttribute $ats1, PlaygroundAttribute $ats2) {
            $p1 = $ats2->getTimeleft() - $ats1->getTimeleft();
            $p2 = $ats1->getPA()->getStartSchedule()->getTimestamp() - $ats2->getPA()->getStartSchedule()->getTimestamp();
            $test = min(1, max(-1, $p1))*2 + min(1, max(-1, $p2));
            return min(1, max(-1, $test));
        });
        $matchtime = $match->getCategory()->getMatchtime();
        while ($pa = $result->cycleTimeslot()) {
            /* @var $slotschedule DateTime */
            $slotschedule = $pa->getSchedule();
            $date = Date::getDate($slotschedule);
            $time = Date::getTime($slotschedule);
            $slot_time_left = $pa->getTimeleft();
            if ($matchtime <= $slot_time_left) {
                /* Both teams must be allowed to play now */
                if ($result->getTeamCheck()->isCapacity($match, $slotschedule, $pa->getPlayground(), $pa->getTimeslot())) {
                    $match->setDate($date);
                    $match->setTime($time);
                    $match->setPlayground($pa->getPlayground());
                    $match->setPlaygroundAttribute($pa->getPA());
                    $slotschedule->add(new DateInterval('PT'.$matchtime.'M'));
                    $pa->setSchedule($slotschedule);
                    $matchlist = $pa->getMatchlist();
                    $matchlist[] = $match;
                    $pa->setMatchlist($matchlist);
                    $result->getTeamCheck()->reserveCapacity($match, $pa->getTimeslot());
                    $result->rewind();
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Replan swapping matches that durate for the same time
     * @param PlanningResults $result
     */
    private function replan_2run(PlanningResults $result) {
        $this->statistics['2run']['unresolved before'] = $result->unresolved();
        $grace_max = $result->unresolved()*2;
        $cnt_last = -1;
        $grace = 0;
        $unplaceable = array();
        while ($match = $result->nextUnresolved()) {
            $cnt = $result->unresolved();
            if ($cnt == $cnt_last) {
                $grace++;
            }
            else {
                $cnt_last = $cnt;
                $grace = 0;
            }
            $slot_found = $this->replanMatch_1run($result, $match);
            if (!$slot_found) {
                $new_match = $this->replanMatch_2run($result, $match);
                if ($new_match) {
                    $this->statistics['2run']['swapped'][] = array($new_match, $match);
                    $result->appendUnresolved($new_match);
                } else {
                    $unplaceable[] = $match;
                }
            }
            if ($grace > $grace_max) {
                break;
            }
        }

        foreach ($unplaceable as $match) {
            $this->statistics['2run']['unplaceable'][] = $match;
            $result->appendUnresolved($match);
        }
        $this->statistics['2run']['unresolved after'] = $result->unresolved();
    }

    /**
     * Replace match schedule with other matches with same match time
     * @param PlanningResults $result
     * @param MatchPlan $match
     * @return MatchPlan|null
     */
    private function replanMatch_2run(PlanningResults $result, MatchPlan $match) {
        $result->mark(function (PlaygroundAttribute $ats1, PlaygroundAttribute $ats2) {
            $test = $ats2->getTimeleft() - $ats1->getTimeleft();
            return min(1, max(-1, $test));
        });
        while ($pa = $result->cycleTimeslot()) {
            /* Find a candidate for replacement */
            $matchlist = $pa->getMatchlist();
            /* @var $replan_match MatchPlan */
            foreach ($matchlist as $idx => $replan_match) {
                /* Both teams must be allowed to play now */
                if ($replan_match->getCategory()->getMatchtime() == $match->getCategory()->getMatchtime() &&
                    $result->getTeamCheck()->isCapacity($match, $replan_match->getSchedule(), $replan_match->getPlayground(), $pa->getTimeslot()))
                {
                    $match->setDate($replan_match->getDate());
                    $match->setTime($replan_match->getTime());
                    $match->setPlayground($replan_match->getPlayground());
                    $match->setPlaygroundAttribute($pa->getPA());
                    $matchlist[$idx] = $match;
                    $pa->setMatchlist($matchlist);
                    $result->getTeamCheck()->reserveCapacity($match, $pa->getTimeslot());
                    $result->getTeamCheck()->freeCapacity($replan_match, $pa->getTimeslot());
                    $result->rewind();
                    return $replan_match;
                }
            }
        }
        return null;
    }

    /**
     * Replan swapping matches that share the same team
     * @param PlanningResults $result
     */
    private function replan_3run(PlanningResults $result) {
        $this->statistics['3run']['unresolved before'] = $result->unresolved();
        $unplaceable = array();
        /* @var $match MatchPlan */
        while ($match = $result->nextUnresolved()) {
            $slot_found = $this->replanMatch_1run($result, $match);
            if (!$slot_found) {
                $new_match = $this->findAngel($result, $match);
                if ($new_match) {
                    $this->statistics['3run']['swapped'][] = array($new_match, $match);
                    $result->appendUnresolved($new_match);
                } else {
                    $unplaceable[] = $match;
                }
            }
        }

        foreach ($unplaceable as $match) {
            $this->statistics['3run']['unplaceable'][] = $match;
            $result->appendUnresolved($match);
        }
        $this->statistics['3run']['unresolved after'] = $result->unresolved();
    }

    /**
     * Replace match schedule with other matches with same team
     * @param PlanningResults $result
     * @param MatchPlan $match
     * @return MatchPlan|null
     */
    private function findAngel(PlanningResults $result, MatchPlan $match) {
        $result->mark();
        while ($pa = $result->cycleTimeslot()) {
            /* Find a candidate for replacement */
            $matchlist = $pa->getMatchlist();
            /* @var $replan_match MatchPlan */
            foreach ($matchlist as $idx => $replan_match) {
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
        return null;
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
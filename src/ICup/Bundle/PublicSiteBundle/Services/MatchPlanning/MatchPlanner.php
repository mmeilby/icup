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
        $matchtime = 60;
        // Sort playground attributes and prepare for inspection
        $result->mark(function (PA $ats1, PA $ats2) use ($matchtime) {
            $p1 = (count($ats1->getPA()->getCategories()) ? count($ats1->getPA()->getCategories()) : 100)
                - (count($ats2->getPA()->getCategories()) ? count($ats2->getPA()->getCategories()) : 100);
            $p2 = min($ats2->getTimeleft(), 2*$matchtime)  - min($ats1->getTimeleft(), 2*$matchtime);
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
/*
        if ($result->unresolved() > 0) {
            $this->replan_1run($result);
        }
*/
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
                $e = $this->dEd($result, $pa, $match, $slotschedule);
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
    
    private function dEd(PlanningResults $result, PA $pa, MatchPlan $match, DateTime $mschedule) {
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

    /**
     * Plan the unplanned matches if possible - now allowing teams to play at any timeslot
     * @param PlanningResults $result
     */
    private function replan_1run(PlanningResults $result) {
        $this->statistics['1run']['unresolved before'] = $result->unresolved();
        // Sort playground attributes and prepare for inspection
        $result->mark(function (PA $ats1, PA $ats2) {
            $p1 = $ats2->getTimeleft() - $ats1->getTimeleft();
            $p2 = $ats1->getPA()->getStartSchedule()->getTimestamp() - $ats2->getPA()->getStartSchedule()->getTimestamp();
            $test = min(1, max(-1, $p1))*2 + min(1, max(-1, $p2));
            return min(1, max(-1, $test));
        });
        $result->rewind();
        $unplaceable = array();
        while ($match = $result->nextUnresolved()) {
            $slot_found = $this->replanMatch_1run($result, $match);
            if (!$slot_found) {
                // if this was not possible to register the match as finally unassigned
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
        $result->mark();
        $matchtime = $match->getCategory()->getMatchtime();
        $searchTree = array();
        /* @var $pa PA */
        while ($pa = $result->cycleTimeslot()) {
            /* @var $slotschedule DateTime */
            $slotschedule = $pa->getSchedule();
            $slot_time_left = $pa->getTimeleft();
            if ($matchtime <= $slot_time_left) {
                /* Both teams must be allowed to play now */
                if ($result->getTeamCheck()->isCapacity($match, $slotschedule, $pa->getPlayground(), $pa->getTimeslot())) {
                    $e = $this->dEd($result, $pa, $match, $slotschedule);
                    $searchTree[] = array('schedule' => $slotschedule, 'pa' => $pa, 'error' => $e);
                }
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
            $slotschedule->add(new DateInterval('PT'.$matchtime.'M'));
            $pa->setSchedule($slotschedule);
            $matchlist = $pa->getMatchlist();
            $matchlist[] = $match;
            $pa->setMatchlist($matchlist);
            $result->getTeamCheck()->reserveCapacity($match, $pa->getTimeslot());
        }
        return count($searchTree) > 0;
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
        $result->mark(function (PA $ats1, PA $ats2) {
            $test = $ats2->getTimeleft() - $ats1->getTimeleft();
            return min(1, max(-1, $test));
        });
        /* @var $pa PA */
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
        /* @var $pa PA */
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


    const UNRESOLVED_PENALTY = 10.0;

    private function E(PlanningResults $result) {
        $e = $result->unresolved()*MatchPlanner::UNRESOLVED_PENALTY;
        $teams = array();
        $result->mark();
        /* @var $pa PA */
        while ($pa = $result->cycleTimeslot()) {
            if (count($pa->getMatchlist()) > 0) {
                $e += $pa->getTimeleft()*MatchPlanner::TIME_LEFT_PENALTY;
            }
            /* @var $match MatchPlan */
            foreach ($pa->getMatchlist() as $match) {
                $e += $pa->getPlayground()->getNo()*MatchPlanner::VENUE_PENALTY;
                $e += $pa->isCategoryAllowed($match->getCategory()) ? 0 : MatchPlanner::CATEGORY_PENALTY;
                $teams[$match->getTeamA()->getId()][] = $match;
                $teams[$match->getTeamB()->getId()][] = $match;
            }
        }
        foreach ($teams as $team_matches) {
            usort($team_matches, function (MatchPlan $m1, MatchPlan $m2) {
                /* @var $diff DateInterval */
                $diff = $m1->getSchedule()->diff($m2->getSchedule());
                if ($diff->d == 0 && $diff->h == 0 && $diff->i == 0) {
                    return 0;
                } else {
                    return $diff->invert === 1 ? -1 : 1;
                }
            });
        }
        foreach ($teams as $team_matches) {
            /* @var $schedule DateTime */
            $schedule = null;
            /* @var $match MatchPlan */
            foreach ($team_matches as $match) {
                if ($schedule) {
                    /* @var $diff DateInterval */
                    $diff = $schedule->diff($match->getSchedule());
                    $difftime = $diff->d*24*60 + $diff->h*60 + $diff->i;
                    if ($difftime < $match->getCategory()->getMatchtime()*3) {
                        $e += (3 - $difftime / $match->getCategory()->getMatchtime())*MatchPlanner::REST_PENALTY;
                    }
                }
                $schedule = $match->getSchedule();
            }
        }
        return $e;
    }

    private function dEs(PlanningResults $result, PA $pa, MatchPlan $match) {
        $dE = 0.0;
        if (count($pa->getMatchlist()) > 1) {
            $dE += $match->getCategory()->getMatchtime()*MatchPlanner::TIME_LEFT_PENALTY;
        }
        else {
            $dE -= $pa->getTimeleft()*MatchPlanner::TIME_LEFT_PENALTY;
        }
        $dE -= $pa->getPlayground()->getNo()*MatchPlanner::VENUE_PENALTY;
        $dE -= $pa->isCategoryAllowed($match->getCategory()) ? 0 : MatchPlanner::CATEGORY_PENALTY;
        return $dE;
    }
}
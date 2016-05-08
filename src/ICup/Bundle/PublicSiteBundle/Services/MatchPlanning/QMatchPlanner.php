<?php
/**
 * Created by PhpStorm.
 * User: mm
 * Date: 16/10/15
 * Time: 17.54
 */

namespace ICup\Bundle\PublicSiteBundle\Services\MatchPlanning;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;
use ICup\Bundle\PublicSiteBundle\Entity\QMatchPlan;
use ICup\Bundle\PublicSiteBundle\Services\Entity\PlaygroundAttribute as PA;
use ICup\Bundle\PublicSiteBundle\Services\Entity\PlanningResults;
use DateInterval;
use DateTime;
use ICup\Bundle\PublicSiteBundle\Services\Entity\QRelation;
use Monolog\Logger;

class QMatchPlanner
{
    /* @var $logger Logger */
    protected $logger;

    protected $statistics;
    
    /**
     * QMatchPlanner constructor.
     * @param Logger $logger
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
        $this->statistics = array();
    }

    /**
     * Plan matches (elimination rounds) for tournament
     * @param PlanningResults $result
     * @return array
     */
    public function plan(PlanningResults $result) {
        $unplaceable = array();
        $result->mark(function (PA $ats1, PA $ats2) {
            $p1 = (count($ats2->getPA()->getCategories()) ? 1 : 0) - (count($ats1->getPA()->getCategories()) ? 1 : 0);
            $p2 = $ats1->getPA()->getPlayground()->getId() - $ats2->getPA()->getPlayground()->getId();
            $p3 = $ats1->getPA()->getStartSchedule()->getTimestamp() - $ats2->getPA()->getStartSchedule()->getTimestamp();
            $p4 = $ats2->getTimeleft() - $ats1->getTimeleft();
            $test = min(1, max(-1, $p1))*8 + min(1, max(-1, $p2))*4 + min(1, max(-1, $p3))*2 + min(1, max(-1, $p4));
            return min(1, max(-1, $test));
        });
        $result->rewind();
        while ($match = $result->nextUnresolved()) {
            $slot_found = $this->planMatch($result, $match);
            if (!$slot_found) {
                // if this was not possible to register the match as finally unassigned
                $unplaceable[] = $match;
            }
        }
        foreach ($unplaceable as $match) {
            $result->appendUnresolved($match);
        }
    }

    /**
     * @param PlanningResults $result
     * @param QMatchPlan $match
     * @return bool
     */
    private function planMatch(PlanningResults $result, QMatchPlan $match) {
        $result->mark();
        $matchtime = $match->getCategory()->getMatchtime();
        $searchTree = array();
        /* @var $pa PA */
        while ($pa = $result->cycleTimeslot()) {
            $slotschedule = $pa->getSchedule();
            if ($result->isQScheduleAvailable($match, $slotschedule, $pa->getTimeslot())) {
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
            $slotschedule->add(new DateInterval('PT'.$matchtime.'M'));
            $pa->setSchedule($slotschedule);
            $matchlist = $pa->getMatchlist();
            $matchlist[] = $match;
            $pa->setMatchlist($matchlist);
            $result->setQSchedule($match, $match->getSchedule());
        }
        return count($searchTree) > 0;
    }

    /**
     * Error function - calculates the score for the current planning result.
     * A value closer to zero is better - any score will be positive or zero
     * @param PlanningResults $result
     * @return float The score for the current planning result. Closer to zero is better.
     */

    const CLASSIFICATION_PENALTY = 60.0;
    const CATEGORY_PENALTY = 60.0;
    const TIMESLOT_EXCESS_PENALTY = 2.0;
    const PLAYOFF_VENUE_PENALTY = 5.0;
    const REST_PENALTY = 2.0;
    const VENUE_PENALTY = 0.1;
    const TIME_LEFT_PENALTY = 0.01;

    private function dEd(PlanningResults $result, PA $pa, QMatchPlan $match, DateTime $mschedule) {
        $dE = 100.0;
        $excess = $pa->getTimeleft()-$match->getCategory()->getMatchtime();
        if (count($pa->getMatchlist()) > 0) {
            if ($excess >= 0) {
                $dE -= $match->getCategory()->getMatchtime()*QMatchPlanner::TIME_LEFT_PENALTY;
            }
        }
        else {
            $dE += ($pa->getTimeleft()-$match->getCategory()->getMatchtime())*QMatchPlanner::TIME_LEFT_PENALTY;
        }
        $dE += max(-$excess, 0)*QMatchPlanner::TIMESLOT_EXCESS_PENALTY;
        $dE += $pa->getPlayground()->getNo()*QMatchPlanner::VENUE_PENALTY;
        $dE += $pa->isCategoryAllowed($match->getCategory()) ? 0 : QMatchPlanner::CATEGORY_PENALTY;
        $dE += $pa->isClassificationAllowed($match->getClassification()) ? 0 : QMatchPlanner::CLASSIFICATION_PENALTY;

        /* @var $qm QMatchPlan */
        $qm = $result->getQMatchPlan($match->getClassification(), $match->getLitra());
        if ($qm) {
            // Playoff matches must be played at the same venue
            if ($qm->getPlayground()->getId() != $pa->getPlayground()->getId()) {
                $dE += QMatchPlanner::PLAYOFF_VENUE_PENALTY;
            }
        }

        /* Test for proper time line */
        $dE += $this->reldE($result, $match, $mschedule, $match->getRelA());
        $dE += $this->reldE($result, $match, $mschedule, $match->getRelB());

        return $dE;
    }

        /**
         * @param PlanningResults $result
         * @param QMatchPlan $match
         * @param QRelation $rel
         * @return float
         */
        private function reldE(PlanningResults $result, QMatchPlan $match, DateTime $mschedule, QRelation $rel) {
            if ($rel->getClassification() > Group::$PRE) {
                /* @var $schedule DateTime */
                $schedule = $result->getQSchedule($rel);
                if ($schedule) {
                    /* @var $diff DateInterval */
                    $diff = $mschedule->diff($schedule);
                    $e = 10 - ($diff->d * 24 * 60 + $diff->h * 60 + $diff->i) / $match->getCategory()->getMatchtime();
                    if ($e < 0) {
                        $e = 0;
                    }
                    return $e * QMatchPlanner::REST_PENALTY;
                }
            }
            return 0.0;
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
     * @param QMatchPlan $match
     * @return bool
     */
    private function replanMatch_1run(PlanningResults $result, QMatchPlan $match) {
        $result->mark();
        $matchtime = $match->getCategory()->getMatchtime();
        /* @var $pa PA */
        while ($pa = $result->cycleTimeslot()) {
            $slotschedule = $pa->getSchedule();
            $slot_time_left = $pa->getTimeleft();

            while ($matchtime <= $slot_time_left) {
                if ($result->isQScheduleAvailable($match, $slotschedule, $pa->getTimeslot())) {
                    $match->setDate(Date::getDate($slotschedule));
                    $match->setTime(Date::getTime($slotschedule));
                    $match->setPlayground($pa->getPlayground());
                    $match->setPlaygroundAttribute($pa->getPA());
                    $slotschedule->add(new DateInterval('PT'.$matchtime.'M'));
                    $pa->setSchedule($slotschedule);
                    $matchlist = $pa->getMatchlist();
                    $matchlist[] = $match;
                    $pa->setMatchlist($matchlist);
                    $result->setQSchedule($match, $match->getSchedule());
                    $result->rewind();
                    return true;
                }

                $slotschedule->add(new DateInterval('PT'.$matchtime.'M'));
                $slot_time_left -= $matchtime;
            }
        }
        return false;
    }

    const UNRESOLVED_PENALTY = 10.0;

    private function E(PlanningResults $result) {
        $e = $result->unresolved()*QMatchPlanner::UNRESOLVED_PENALTY;

        $result->mark();
        /* @var $pa PA */
        while ($pa = $result->cycleTimeslot()) {
            if (count($pa->getMatchlist()) > 0) {
                $e += $pa->getTimeleft()*QMatchPlanner::TIME_LEFT_PENALTY;
            }
            /* @var $match QMatchPlan */
            foreach ($pa->getMatchlist() as $match) {
                $e += $pa->getPlayground()->getNo()*QMatchPlanner::VENUE_PENALTY;

                /* @var $qm QMatchPlan */
                $qm = $result->getQMatchPlan($match->getClassification(), $match->getLitra());
                if ($qm) {
                    // For playoff matches save the latest match to verify the time line
                    if ($qm->getSchedule() < $match->getSchedule()) {
                        $result->setQSchedule($match, $match->getSchedule());
                    }
                    // Playoff matches must be played at the same venue
                    if ($qm->getPlayground()->getId() != $match->getPlayground()->getId()) {
                        $e += QMatchPlanner::PLAYOFF_VENUE_PENALTY;
                    }
                }
                else {
                    $result->setQSchedule($match, $match->getSchedule());
                }

                /* Test for proper time line */
                $e += $this->reldE($result, $match, $match->getSchedule(), $match->getRelA());
                $e += $this->reldE($result, $match, $match->getSchedule(), $match->getRelB());
            }
        }

        return $e;
    }

    private function dEs(PlanningResults $result, PA $pa, QMatchPlan $match) {
        $dE = 0.0;
        if (count($pa->getMatchlist()) > 1) {
            $dE += $match->getCategory()->getMatchtime()*QMatchPlanner::TIME_LEFT_PENALTY;
        }
        else {
            $dE -= $pa->getTimeleft()*QMatchPlanner::TIME_LEFT_PENALTY;
        }
        $dE -= $pa->getPlayground()->getNo()*QMatchPlanner::VENUE_PENALTY;

        /* @var $qm QMatchPlan */
        $qm = $result->getQMatchPlan($match->getClassification(), $match->getLitra());
        if ($qm) {
            // Playoff matches must be played at the same venue
            if ($qm->getPlayground()->getId() != $pa->getPlayground()->getId()) {
                $dE -= QMatchPlanner::PLAYOFF_VENUE_PENALTY;
            }
        }

        /* Test for proper time line */
        $dE -= $this->reldE($result, $match, $match->getSchedule(), $match->getRelA());
        $dE -= $this->reldE($result, $match, $match->getSchedule(), $match->getRelB());

        return $dE;
    }
}
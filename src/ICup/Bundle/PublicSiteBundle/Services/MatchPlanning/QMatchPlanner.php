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
use ICup\Bundle\PublicSiteBundle\Entity\QMatchPlan;
use ICup\Bundle\PublicSiteBundle\Services\Entity\PlaygroundAttribute as PA;
use ICup\Bundle\PublicSiteBundle\Services\Entity\PlanningResults;
use DateInterval;
use Monolog\Logger;

class QMatchPlanner
{
    /* @var $logger Logger */
    protected $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Plan matches (elimination rounds) for tournament
     * @param PlanningResults $result
     * @return array
     */
    public function plan(PlanningResults $result) {
        $unplaceable = array();
        while ($match = $result->nextUnresolved()) {
            $slot_found = $this->planMatch($result, $match);
            if (!$slot_found) {
                $slot_found = $this->replanMatch_1run($result, $match);
                if (!$slot_found) {
                    // if this was not possible to register the match as finally unassigned
                    $unplaceable[] = $match;
                }
            }
        }
        foreach ($unplaceable as $match) {
            $result->appendUnresolved($match);
        }

        if ($result->unresolved() > 0) {
//            $this->replan_1run($result);
        }
        if ($result->unresolved() > 0) {
//            $this->replan_2run($result);
        }
    }

    /**
     * @param PlanningResults $result
     * @param QMatchPlan $match
     * @param bool $replan
     * @return bool
     */
    private function planMatch(PlanningResults $result, QMatchPlan $match) {
        $result->mark(function (PA $ats1, PA $ats2) {
            $p1 = (count($ats2->getPA()->getCategories()) ? 1 : 0) - (count($ats1->getPA()->getCategories()) ? 1 : 0);
            $p2 = $ats1->getPA()->getPlayground()->getId() - $ats2->getPA()->getPlayground()->getId();
            $p3 = $ats1->getPA()->getStartSchedule()->getTimestamp() - $ats2->getPA()->getStartSchedule()->getTimestamp();
            $p4 = $ats2->getTimeleft() - $ats1->getTimeleft();
            $test = min(1, max(-1, $p1))*8 + min(1, max(-1, $p2))*4 + min(1, max(-1, $p3))*2 + min(1, max(-1, $p4));
            return min(1, max(-1, $test));
        });
        $matchtime = $match->getCategory()->getMatchtime();
        /* @var $pa PA */
        while ($pa = $result->cycleTimeslot()) {
            if ($pa->isCategoryAllowed($match->getCategory()) && $pa->isClassificationAllowed($match->getClassification())) {
                $slotschedule = $pa->getSchedule();
                $slot_time_left = $pa->getTimeleft();

                while ($matchtime <= $slot_time_left) {
                    if ($result->isQScheduleAvailable($match, $slotschedule, $pa->getTimeslot())) {
                        $match->setDate(Date::getDate($slotschedule));
                        $match->setTime(Date::getTime($slotschedule));
                        $match->setPlayground($pa->getPlayground());
                        $slotschedule->add(new DateInterval('PT'.$matchtime.'M'));
                        $pa->setSchedule($slotschedule);
                        $matchlist = $pa->getMatchlist();
                        $matchlist[] = $match;
                        $pa->setMatchlist($matchlist);
                        $result->setQSchedule($match, $match->getSchedule());
                        $result->rewind();
                        return true;
                    }
                    break;
                }
            }
        }
        return false;
    }

    /**
     * Plan the unplanned matches if possible
     * @param PlanningResults $result
     */
    private function replan_1run(PlanningResults $result) {
        $this->logger->addDebug("Q unresolved before 1run = ".$result->unresolved());
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
    }

    /**
     * @param PlanningResults $result
     * @param QMatchPlan $match
     * @param bool $replan
     * @return bool
     */
    private function replanMatch_1run(PlanningResults $result, QMatchPlan $match) {
        $result->mark(function (PA $ats1, PA $ats2) {
            $p1 = $ats2->getTimeleft() - $ats1->getTimeleft();
            $p2 = $ats1->getPA()->getStartSchedule()->getTimestamp() - $ats2->getPA()->getStartSchedule()->getTimestamp();
            $test = min(1, max(-1, $p1))*2 + min(1, max(-1, $p2));
            return min(1, max(-1, $test));
        });
        /*
                // Sort by playground asc, match start asc, time left desc,
        function (PlaygroundAttribute $ats1, PlaygroundAttribute $ats2) {
                    $p1 = $ats1->getPA()->getStartSchedule()->getTimestamp() - $ats2->getPA()->getStartSchedule()->getTimestamp();
                    $p2 = $ats2->getTimeleft() - $ats1->getTimeleft();
                    $p3 = $ats1->getPlayground()->getNo() - $ats2->getPlayground()->getNo();
                    $test = min(1, max(-1, $p1))*4 + min(1, max(-1, $p2))*2 + min(1, max(-1, $p3));
                    return min(1, max(-1, $test));
                }
         */
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

    /**
     * Replan swapping matches that durate for the same time
     * @param PlanningResults $result
     */
    private function replan_2run(PlanningResults $result) {
        $this->logger->addDebug("Q unresolved before 2run = ".$result->unresolved());
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
                    $this->logger->addDebug("Swapped #" . $new_match->getMatchno() . " with #" . $match->getMatchno());
                    $result->appendUnresolved($new_match);
                } else {
                    $this->logger->addDebug("Unplaceable #" . $match->getMatchno());
                    $unplaceable[] = $match;
                }
            }
            if ($grace > $grace_max) {
                break;
            }
        }

        foreach ($unplaceable as $match) {
            $result->appendUnresolved($match);
        }
    }

    /**
     * Replace match schedule with other matches with same match time
     * @param PlanningResults $result
     * @param QMatchPlan $match
     * @return MatchPlan|null
     */
    private function replanMatch_2run(PlanningResults $result, QMatchPlan $match) {
        $result->mark();
        while ($pa = $result->cycleTimeslot()) {
            /* Find a candidate for replacement */
            $matchlist = $pa->getMatchlist();
            /* @var $replan_match QMatchPlan */
            foreach ($matchlist as $idx => $replan_match) {
                /* Both teams must be allowed to play now */
                if ($replan_match->getCategory()->getMatchtime() == $match->getCategory()->getMatchtime() &&
                    $result->isQScheduleAvailable($match, $replan_match->getSchedule(), $pa->getTimeslot()))
                {
                    $match->setDate($replan_match->getDate());
                    $match->setTime($replan_match->getTime());
                    $match->setPlayground($replan_match->getPlayground());
                    $matchlist[$idx] = $match;
                    $pa->setMatchlist($matchlist);
                    $result->setQSchedule($match, $replan_match->getSchedule());
                    $result->resetQSchedule($replan_match);
                    $result->rewind();
                    return $replan_match;
                }
            }
        }
        return null;
    }
}
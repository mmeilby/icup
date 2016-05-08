<?php

namespace ICup\Bundle\PublicSiteBundle\Services\Entity;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Timeslot;
use ICup\Bundle\PublicSiteBundle\Entity\QMatchPlan;
use DateTime;
use DateInterval;

/**
 * PlanningResults
 */
class PlanningResults
{
    private $team_check;
    private $dependency_check;
    private $timeslots;
    private $unresolved;

    public function __construct()
    {
        $this->team_check = new TeamCheck();
        $this->dependency_check = array();
        $this->timeslots = array();
        $this->unresolved = array();
    }

    /**
     * @return TeamCheck
     */
    public function getTeamCheck()
    {
        return $this->team_check;
    }

    /**
     * @return mixed
     */
    public function getTimeslots()
    {
        return $this->timeslots;
    }

    public function mark($cmp_function = null)
    {
        if ($cmp_function) {
            usort($this->timeslots, $cmp_function);
        }
        array_push($this->timeslots, null);
    }

    public function cycleTimeslot()
    {
        $pa = array_shift($this->timeslots);
        if ($pa) {
            array_push($this->timeslots, $pa);
        }
        return $pa;
    }

    public function rewind()
    {
        $idx = array_search(null, $this->timeslots);
        if ($idx !== false) {
            unset($this->timeslots[$idx]);
        }
    }

    public function addTimeslot($pa)
    {
        $this->timeslots[] = $pa;
    }

    public function timeslots()
    {
        return count($this->timeslots);
    }

    /**
     * Get QMatchPlan for specific classification and litra
     * @param $classification
     * @param $litra
     * @return QMatchPlan
     */
    public function getQMatchPlan($classification, $litra) {
        $key = $classification.':'.$litra;
        return isset($this->dependency_check[$key]) ? $this->dependency_check[$key]['match'] : null;
    }

    /**
     * Get schedule for specific relation
     * @param QRelation $rel
     * @return DateTime
     */
    public function getQSchedule(QRelation $rel) {
        $key = $rel->getClassification().":".$rel->getLitra().$rel->getBranch();
        return isset($this->dependency_check[$key]) ? $this->dependency_check[$key]['schedule'] : null;
    }

    /**
     * Set schedule for specific match
     * @param QMatchPlan $match
     * @param DateTime $schedule
     */
    public function setQSchedule(QMatchPlan $match, DateTime $schedule) {
        $key = $match->getClassification().":".$match->getLitra();
        $this->dependency_check[$key] = array('match' => $match, 'schedule' => $schedule);
/*
        if ($match->getRelA()->getClassification() == Group::$PRE) {
            $keyh = $match->getRelA()->getClassification().":".$match->getRelA()->getLitra().$match->getRelA()->getBranch();
            $this->dependency_check[$keyh] = array('match' => $match, 'schedule' => $schedule);
        }
        if ($match->getRelB()->getClassification() == Group::$PRE) {
            $keya = $match->getRelB()->getClassification().":".$match->getRelB()->getLitra().$match->getRelB()->getBranch();
            $this->dependency_check[$keya] = array('match' => $match, 'schedule' => $schedule);
        }
*/
    }

    /**
     * Clear cached schedule for specific match
     * @param QMatchPlan $match
     */
    public function resetQSchedule(QMatchPlan $match) {
        $key = $match->getClassification().":".$match->getLitra();
        unset($this->dependency_check[$key]);
/*
        if ($match->getRelA()->getClassification() == Group::$PRE) {
            $keyh = $match->getRelA()->getClassification().":".$match->getRelA()->getLitra().$match->getRelA()->getBranch();
            unset($this->dependency_check[$keyh]);
        }
        if ($match->getRelB()->getClassification() == Group::$PRE) {
            $keya = $match->getRelB()->getClassification().":".$match->getRelB()->getLitra().$match->getRelB()->getBranch();
            unset($this->dependency_check[$keya]);
        }
*/
    }

    /**
     * Validate slotschedule for the specific match and timeslot
     * @param QMatchPlan $match
     * @param DateTime $slotschedule
     * @param Timeslot $timeslot
     * @return bool
     */
    public function isQScheduleAvailable(QMatchPlan $match, DateTime $slotschedule, Timeslot $timeslot) {
        // Group dependency must be respected when deciding match schedule
        // No schedule is expected for relations to preliminary groups
        if ($match->getRelA()->getClassification() > Group::$PRE) {
            $scheduleA = $this->getQSchedule($match->getRelA());
            if (!$scheduleA || !$this->testQSchedule($match, $scheduleA, $slotschedule, $timeslot)) {
                return false;
            }
        } else {
//            $scheduleA = $this->getQSchedule($match->getRelA());
//            if ($scheduleA) {
//                /* @var $diff DateInterval */
//                $diff = $slotschedule->diff($scheduleA);
//                if ($diff->d*24*60 + $diff->h*60 + $diff->i < $match->getCategory()->getMatchtime() + $timeslot->getRestperiod()) {
 //                   return false;
//                }
//            }
        }
        if ($match->getRelB()->getClassification() > Group::$PRE) {
            $scheduleB = $this->getQSchedule($match->getRelB());
            if (!$scheduleB || !$this->testQSchedule($match, $scheduleB, $slotschedule, $timeslot)) {
                return false;
            }
        } else {
//            $scheduleB = $this->getQSchedule($match->getRelB());
//            if ($scheduleB) {
//                /* @var $diff DateInterval */
//                $diff = $slotschedule->diff($scheduleB);
//                if ($diff->d*24*60 + $diff->h*60 + $diff->i < $match->getCategory()->getMatchtime() + $timeslot->getRestperiod()) {
//                    return false;
//                }
//            }
        }
        return true;
    }

    /**
     * Test if match can be scheduled for timeslot and slotschedule given the dependent schedule 
     * @param QMatchPlan $match
     * @param DateTime $schedule
     * @param DateTime $slotschedule
     * @param Timeslot $timeslot
     * @return bool
     */
    private function testQSchedule(QMatchPlan $match, DateTime $schedule, DateTime $slotschedule, Timeslot $timeslot) {
        if ($slotschedule < $schedule) {
            return false;
        }
        /* @var $diff DateInterval */
        $diff = $slotschedule->diff($schedule);
        if ($diff->d*24*60 + $diff->h*60 + $diff->i < $match->getCategory()->getMatchtime() + $timeslot->getRestperiod()) {
            return false;
        }
        return true;
    }

    /**
     * @return mixed
     */
    public function getUnresolved()
    {
        return $this->unresolved;
    }

    public function unresolved()
    {
        return count($this->unresolved);
    }

    public function nextUnresolved()
    {
        return array_shift($this->unresolved);
    }

    public function appendUnresolved($match)
    {
        array_push($this->unresolved, $match);
    }
}
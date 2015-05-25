<?php

namespace ICup\Bundle\PublicSiteBundle\Services\Entity;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
use ICup\Bundle\PublicSiteBundle\Services\Entity\TeamCheck;
use ICup\Bundle\PublicSiteBundle\Services\Entity\PlaygroundAttribute;
use ICup\Bundle\PublicSiteBundle\Entity\MatchPlan;

/**
 * PlanningResults
 */
class PlanningResults
{
    private $team_check;
    private $timeslots;
    private $unresolved;

    /* TODO: $plan is deprecated - replace with function getPlan() */
    private $plan;

    public function __construct()
    {
        $this->team_check = new TeamCheck();
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
    public function getPlan()
    {
        return $this->plan;
    }

    /**
     * @deprecated
     * @param mixed $plan
     */
    public function setPlan($plan)
    {
        $this->plan = $plan;
    }

    /**
     * @return mixed
     */
    public function getTimeslots()
    {
        return $this->timeslots;
    }

    public function mark()
    {
        usort($this->timeslots, function (PlaygroundAttribute $ats1, PlaygroundAttribute $ats2) {
            $p1 = $ats1->getTimeleft() - $ats2->getTimeleft();
            $p2 = $ats2->getPlayground()->getNo() - $ats1->getPlayground()->getNo();
            $p3 = $ats2->getTimeslot()->getId() - $ats1->getTimeslot()->getId();
            $p4 = 0;
            if ($p1 == 0 && $p2 == 0 && $p3 == 0 && $p4 == 0) {
                return 0;
            } elseif ($p1 < 0 || ($p1 == 0 && $p2 < 0) || ($p1 == 0 && $p2 == 0 && $p3 < 0) || ($p1 == 0 && $p2 == 0 && $p3 == 0 && $p4 < 0)) {
                return 1;
            } else {
                return -1;
            }
        });
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
            array_splice($this->timeslots, $idx, 1);
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

    public function sortTimeslots(){
        usort($this->timeslots, function (PlaygroundAttribute $ats1, PlaygroundAttribute $ats2) {
            $p1 = Date::getDate($ats2->getSchedule()) - Date::getDate($ats1->getSchedule());
            $p2 = $ats2->getPlayground()->getNo() - $ats1->getPlayground()->getNo();
            $p3 = $ats2->getTimeslot()->getId() - $ats1->getTimeslot()->getId();
            $p4 = 0;
            if ($p1 == 0 && $p2 == 0 && $p3 == 0 && $p4 == 0) {
                return 0;
            } elseif ($p1 < 0 || ($p1 == 0 && $p2 < 0) || ($p1 == 0 && $p2 == 0 && $p3 < 0) || ($p1 == 0 && $p2 == 0 && $p3 == 0 && $p4 < 0)) {
                return 1;
            } else {
                return -1;
            }
        });
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

    public function getUnresolvedByCategory()
    {
        $catcnt = array();
        /* @var $match MatchPlan */
        foreach ($this->unresolved as $match) {
            if (array_key_exists($match->getCategory()->getId(), $catcnt)) {
                $catcnt[$match->getCategory()->getId()]['matchcount']++;
            }
            else {
                $catcnt[$match->getCategory()->getId()] = array(
                    'category' => $match->getCategory(),
                    'matchcount' => 1
                );
            }
        }
        return $catcnt;
    }
}
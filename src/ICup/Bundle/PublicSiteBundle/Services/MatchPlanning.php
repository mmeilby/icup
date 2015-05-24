<?php
namespace ICup\Bundle\PublicSiteBundle\Services;

use DateInterval;
use DateTime;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
use ICup\Bundle\PublicSiteBundle\Entity\MatchPlan;
use ICup\Bundle\PublicSiteBundle\Entity\TeamInfo;
use ICup\Bundle\PublicSiteBundle\Services\Doctrine\BusinessLogic;
use ICup\Bundle\PublicSiteBundle\Services\Entity\PlanningOptions;
use ICup\Bundle\PublicSiteBundle\Services\Entity\PlanningResults;
use ICup\Bundle\PublicSiteBundle\Services\Entity\PlaygroundAttribute as PA;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\PlaygroundAttribute;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\PARelation;
use ICup\Bundle\PublicSiteBundle\Services\Entity\TeamCheck;
use Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerInterface;

/* TODO: Save planned matches in DB - use planning state record - clean up the code!! */

class MatchPlanning
{
    /* @var $container ContainerInterface */
    protected $container;
    /* @var $logic BusinessLogic */
    protected $logic;
    /* @var $logger Logger */
    protected $logger;

    public function __construct(ContainerInterface $container, Logger $logger)
    {
        $this->container = $container;
        $this->logic = $container->get('logic');
        $this->logger = $logger;
    }

    /**
     * @param $tournamentid
     * @param PlanningOptions $options
     * @return array
     */
    public function populateTournament($tournamentid, PlanningOptions $options) {
        $matchno = 1;
        $matchPlanList = array();
        $categories = $this->map($this->logic->listCategories($tournamentid));
        $groups = $this->listGroups($tournamentid);
        foreach ($groups as $group) {
            $matches = $this->populateGroup($group->getId(), $options);
            foreach ($matches as $match) {
                $match->setMatchno($matchno++);
                $match->setCategory($categories[$group->getPid()]);
                $match->setGroup($group);
                $matchPlanList[$match->getMatchno()] = $match;
            }
        }
        return $matchPlanList;
    }

    /**
     * @param $groupid
     * @param PlanningOptions $options
     * @return array
     */
    public function populateGroup($groupid, PlanningOptions $options) {
        $matches = array();
        $teams = $this->logic->listTeamsByGroup($groupid);
        $check = array();
        /* @var $teamA TeamInfo */
        foreach ($teams as $teamA) {
            $idx = 0;
            /* @var $teamB TeamInfo */
            foreach ($teams as $teamB) {
                if (($teamA->id != $teamB->id) && !array_key_exists($teamB->id, $check)) {
                    $switch = $idx%2 == 0 || $options->isDoublematch();
                    $match = new MatchPlan();
                    $match->setTeamA($switch ? $teamA : $teamB);
                    $match->setTeamB($switch ? $teamB : $teamA);
                    $matches[] = $match;
                    $idx++;
                }
            }
            if (!$options->isDoublematch()) {
                $check[$teamA->id] = $teamA;
            }
        }
        return $matches;
    }

    /**
     * @param $tournamentid
     * @param $matchList
     * @param PlanningOptions $options
     * @return array
     */
    public function planTournament($tournamentid, $matchList, PlanningOptions $options) {
        $result = $this->setupCriteria($tournamentid, $matchList, $options);
        $this->plan($result);

        if ($result->unresolved() > 0) {
            $this->replan_1run($result);
        }
        if ($result->unresolved() > 0) {
            $this->replan_2run($result);
        }
        if ($result->unresolved() > 0) {
            $this->replan_3run($result);
        }
//        echo "final unresolved=".$result->unresolved()."<br />";

        /* @var $pa PA */
        foreach ($result->getTimeslots() as $pa) {
            $categories = array();
            $categoryList = $this->logic->listPACategories($pa->getId());
            foreach ($categoryList as $category) {
                $categories[] = $category->getName();
            }
            $pa->setCategories($categories);
        }
        $result->sortTimeslots();

        $advice = array();
        foreach ($result->getUnresolved() as $match) {
            $advicelist = $this->listAlternatives($result, $match);
            $advice[] = array('match' => $match, 'alternatives' => $advicelist);
        }

        return array('plan' => $result->getPlan(),
                     'unassigned' => $result->getUnresolved(),
                     'unassigned_by_category' => $result->getUnresolvedByCategory(),
                     'advices' => $advice,
                     'available_timeslots' => $result->getTimeslots());
    }

    /**
     * @param $tournamentid
     * @param $finals
     * @return PlanningResults
     */
    private function setupCriteria($tournamentid, $matchList, PlanningOptions $options) {
        $result = new PlanningResults();
        $playgrounds = $this->map($this->logic->listPlaygroundsByTournament($tournamentid));
        $timeslots = $this->map($this->logic->listTimeslots($tournamentid));
        $pattrs = $this->logic->listPlaygroundAttributesByTournament($tournamentid);
        /* @var $pattr PlaygroundAttribute */
        foreach ($pattrs as $pattr) {
            $pa = new PA();
            $pa->setId($pattr->getId());
            $pa->setPlayground($playgrounds[$pattr->getPid()]);
            $pa->setTimeslot($timeslots[$pattr->getTimeslot()]);

            $slotschedule = $pattr->getStartSchedule();
            $pa->setSchedule($slotschedule);

            $slotend = $pattr->getEndSchedule();
            $diff = $slotschedule->diff($slotend);
            $slot_time_left = $diff->h*60 + $diff->i;
            $pa->setTimeleft($slot_time_left);
            
            $parels = array();
            foreach ($this->logic->listPARelations($pattr->getId()) as $parel) {
                if ($parel->getFinals() == $options->isFinals()) {
                    $parels[$parel->getCid()] = $parel;
                }
            }
            $pa->setCategories($parels);
            $pa->setMatchlist(array());
            $result->addTimeslot($pa);
        }

        foreach ($matchList as $match) {
            $result->appendUnresolved($match);
        }

        return $result;
    }

    /**
     * @param $matchList
     * @param $pattrList
     * @param $unassigned
     * @param $team_check
     * @return array
     */
    private function plan(PlanningResults $result) {
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
        $result->setPlan($this->getPlan($result));
    }

    /**
     * @param $pattrList
     * @param $match
     * @param TeamCheck $team_check
     * @return bool
     */
    private function planMatch(PlanningResults $result, $match, $replan = false) {
        $result->mark();
        while ($pa = $result->cycleTimeslot()) {
            $parels = $pa->getCategories();
            if ($replan || array_key_exists($match->getCategory()->getId(), $parels)) {
                if ($replan) {
                    $matchtime = $match->getCategory()->getMatchtime();
                }
                else {
                    $parel = $parels[$match->getCategory()->getId()];
                    $matchtime = $parel->getMatchtime();
                }
                $slotschedule = $pa->getSchedule();
                $date = Date::getDate($slotschedule);
                $time = Date::getTime($slotschedule);

                $slot_time_left = $pa->getTimeleft();
                if ($matchtime <= $slot_time_left) {
                    /* Both teams must be allowed to play now */
                    if ($result->getTeamCheck()->isCapacity($match, $date, $pa->getTimeslot())) {
                        $result->getTeamCheck()->reserveCapacity($match, $date, $pa->getTimeslot());
                        $match->setDate($date);
                        $match->setTime($time);
                        $match->setPlayground($pa->getPlayground());
                        $slotschedule->add(new DateInterval('PT'.$matchtime.'M'));
                        $pa->setSchedule($slotschedule);
                        $slot_time_left -= $matchtime;
                        $pa->setTimeleft($slot_time_left);
                        $matchlist = $pa->getMatchlist();
                        $matchlist[] = $match;
                        $pa->setMatchlist($matchlist);
                        $result->rewind();
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * @param $unassigned
     * @param $unplaceable
     * @param $pattrList
     * @param $team_check
     * @return array
     */
    private function replan_1run(PlanningResults $result) {
//        echo "unresolved=".$result->unresolved()."<br />";
        $unplaceable = array();
        while ($match = $result->nextUnresolved()) {
            $slot_found = $this->planMatch($result, $match, true);
            if (!$slot_found) {
                $unplaceable[] = $match;
            }
        }

        foreach ($unplaceable as $match) {
            $result->appendUnresolved($match);
        }

        $result->setPlan($this->getPlan($result));
    }

    private function replan_2run(PlanningResults $result) {
//        echo "unresolved=".$result->unresolved()."<br />";
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
            $slot_found = $this->planMatch($result, $match, true);
            if (!$slot_found) {
                $new_match = $this->replanMatch($result, $match);
                if ($new_match) {
                    //                    echo "Swapped #".$new_match->getMatchno()." with #".$match->getMatchno()."<br />";
                    $result->appendUnresolved($new_match);
                } else {
                    //                echo "Unplaceable #".$match->getMatchno()."<br />";
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

        $result->setPlan($this->getPlan($result));
    }

    private function replan_3run(PlanningResults $result) {
//        echo "unresolved=".$result->unresolved()."<br />";
        $grace_max = $result->unresolved()*10;
        $cnt_last = -1;
        $grace = 0;
        $unplaceable = array();
        /* @var $match MatchPlan */
        while ($match = $result->nextUnresolved()) {
            $cnt = $result->unresolved();
            if ($cnt == $cnt_last) {
                $grace++;
            }
            else {
                $cnt_last = $cnt;
                $grace = 0;
            }
            $slot_found = $this->planMatch($result, $match, true);
            if (!$slot_found) {
                $new_match = $this->findAngel($result, $match);
                if ($new_match) {
//                    echo "Swapped #" . $new_match->getMatchno() . " with #" . $match->getMatchno() . "<br />";
                    $result->appendUnresolved($new_match);
                } else {
//                    echo "Unplaceable #" . $match->getMatchno() . "<br />";
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

        $result->setPlan($this->getPlan($result));
    }

    private function replanMatch(PlanningResults $result, $match) {
        $result->mark();
        while ($pa = $result->cycleTimeslot()) {
            $parels = $pa->getCategories();
//            if (array_key_exists($match->getCategory()->getId(), $parels)) {
//                $parel = $parels[$match->getCategory()->getId()];
                $date = Date::getDate($pa->getSchedule());
                /* Both teams must be allowed to play now */
                if ($result->getTeamCheck()->isCapacity($match, $date, $pa->getTimeslot())) {
                    $matchlist = $pa->getMatchlist();
                    /* @var $replan_match MatchPlan */
                    foreach ($matchlist as $idx => $replan_match) {
//                        $rparel = $parels[$replan_match->getCategory()->getId()];
                        if ($replan_match->getCategory()->getMatchtime() == $match->getCategory()->getMatchtime()) {
                            $result->getTeamCheck()->reserveCapacity($match, $date, $pa->getTimeslot());
                            $match->setDate($date);
                            $match->setTime($replan_match->getTime());
                            $match->setPlayground($replan_match->getPlayground());
                            $matchlist[$idx] = $match;
                            $pa->setMatchlist($matchlist);
                            $result->getTeamCheck()->freeCapacity($replan_match, $date, $pa->getTimeslot());
                            $result->rewind();
                            return $replan_match;
                        }
                    }
                }
//            }
        }
        return null;
    }

    private function findAngel(PlanningResults $result, $match) {
        $result->mark();
        while ($pa = $result->cycleTimeslot()) {
            $date = Date::getDate($pa->getSchedule());
            /* Find a candidate for replacement */
            $matchlist = $pa->getMatchlist();
            /* @var $replan_match MatchPlan */
            foreach ($matchlist as $idx => $replan_match) {
                if (!$replan_match->isFixed() && $this->teamsMatch($match, $replan_match)) {
                    $result->getTeamCheck()->freeCapacity($replan_match, $date, $pa->getTimeslot());
                    if ($result->getTeamCheck()->isCapacity($match, $date, $pa->getTimeslot())) {
                        $result->getTeamCheck()->reserveCapacity($match, $date, $pa->getTimeslot());
                        $match->setDate($date);
                        $match->setTime($replan_match->getTime());
                        $match->setPlayground($replan_match->getPlayground());
                        $match->setFixed(true);
                        $matchlist[$idx] = $match;
                        $pa->setMatchlist($matchlist);
                        $result->rewind();
                        return $replan_match;
                    }
                    else {
                        $result->getTeamCheck()->reserveCapacity($replan_match, $date, $pa->getTimeslot());
                    }
                }
            }
//            }
        }
        return null;
    }

    private function teamsMatch(MatchPlan $match, MatchPlan $replanMatch){
        return
            $match->getTeamA()->getId() == $replanMatch->getTeamA()->getId() ||
            $match->getTeamA()->getId() == $replanMatch->getTeamB()->getId() ||
            $match->getTeamB()->getId() == $replanMatch->getTeamA()->getId() ||
            $match->getTeamB()->getId() == $replanMatch->getTeamB()->getId();
    }

    /**
     * @param $pattrList
     * @param $match
     * @param TeamCheck $team_check
     * @return bool
     */
    private function listAlternatives(PlanningResults $result, $match) {
        $altlist = array();
        $result->mark();
        while ($pa = $result->cycleTimeslot()) {
            /* Both teams must be allowed to play now */
            if ($result->getTeamCheck()->isCapacity($match, Date::getDate($pa->getSchedule()), $pa->getTimeslot())) {
                $altlist[date_format($pa->getSchedule(), "Y/m/d")][] = $pa;
            }
        }
        return $altlist;
    }

    private function getPlan(PlanningResults $result) {
        $plan = array_reduce($result->getTimeslots(), function ($array, PA $pa) {
            return array_merge($array, $pa->getMatchlist());
        }, array());

        usort($plan, function (MatchPlan $match1, MatchPlan $match2) {
            $p1 = $match2->getDate() - $match1->getDate();
            $p2 = $match2->getPlayground()->getNo() - $match1->getPlayground()->getNo();
            $p3 = $match2->getTime() - $match1->getTime();
            $p4 = $match2->getMatchno() - $match1->getMatchno();
            if ($p1==0 && $p2==0 && $p3==0 && $p4==0) {
                return 0;
            }
            elseif ($p1 < 0 || ($p1==0 && $p2 < 0) || ($p1==0 && $p2==0 && $p3 < 0) || ($p1==0 && $p2==0 && $p3==0 && $p4 < 0)) {
                return 1;
            }
            else {
                return -1;
            }
        });
        return $plan;
    }

    /**
     * Order teams in group by match results
     * @param Integer $tournamentid The group to sort
     * @return array A list of TeamStat objects ordered by match results and ordering
     */
    private function listGroups($tournamentid) {
        $groupList = array();
        $groups = $this->logic->listGroupsByTournament($tournamentid);
        foreach ($groups as $group) {
            if ($group->getClassification() > 0) {
                continue; 
            }
            $groupList[$group->getId()] = $group;
        }
        return $groupList;
    }

    private function map($records) {
        $recordList = array();
        foreach ($records as $record) {
            $recordList[$record->getId()] = $record;
        }
        return $recordList;
    }
}

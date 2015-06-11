<?php
namespace ICup\Bundle\PublicSiteBundle\Services;

use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManager;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchAlternative;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchSchedule;
use ICup\Bundle\PublicSiteBundle\Entity\MatchPlan;
use ICup\Bundle\PublicSiteBundle\Entity\TeamInfo;
use ICup\Bundle\PublicSiteBundle\Services\Doctrine\BusinessLogic;
use ICup\Bundle\PublicSiteBundle\Services\Entity\PlanningOptions;
use ICup\Bundle\PublicSiteBundle\Services\Entity\PlanningResults;
use ICup\Bundle\PublicSiteBundle\Services\Entity\PlaygroundAttribute as PA;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\PlaygroundAttribute;
use ICup\Bundle\PublicSiteBundle\Services\Entity\TeamCheck;
use Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MatchPlanning
{
    /* @var $container ContainerInterface */
    protected $container;
    /* @var $logic BusinessLogic */
    protected $logic;
    /* @var $em EntityManager */
    protected $em;
    /* @var $logger Logger */
    protected $logger;

    public function __construct(ContainerInterface $container, Logger $logger)
    {
        $this->container = $container;
        $this->logic = $container->get('logic');
        $this->em = $container->get('doctrine')->getManager();
        $this->logger = $logger;
    }

    /**
     * Plan tournament schedule
     * Schedule is saved in match planning tables
     * @param $tournamentid
     * @param $matchList
     * @param PlanningOptions $options
     * @return array
     */
    public function planTournament($tournamentid, PlanningOptions $options) {
        $options->setFinals(false);
        $matchList = $this->populateTournament($tournamentid, $options);
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

        $this->logic->removeMatchSchedules($tournamentid);

        /* @var $pa PA */
        foreach ($result->getTimeslots() as $pa) {
            /* @var $match MatchPlan */
            foreach ($pa->getMatchlist() as $match) {
                $ms = new MatchSchedule();
                $ms->setPid($tournamentid);
                $ms->setPaid($pa->getId());
                $ms->setThid($match->getTeamA()->getId());
                $ms->setTaid($match->getTeamB()->getId());
                $ms->setMatchstart($match->getTime());
                $ms->setUnscheduled(false);
                $ms->setFixed($match->isFixed());
                $this->em->persist($ms);
            }
        }

        /* @var $match MatchPlan */
        foreach ($result->getUnresolved() as $match) {
            $ms = new MatchSchedule();
            $ms->setPid($tournamentid);
            $ms->setPaid(0);
            $ms->setThid($match->getTeamA()->getId());
            $ms->setTaid($match->getTeamB()->getId());
            $ms->setMatchstart('');
            $ms->setUnscheduled(true);
            $ms->setFixed($match->isFixed());
            $this->em->persist($ms);
            $this->em->flush();

            /* @var $pa PA */
            foreach ($result->getTimeslots() as $pa) {
                /* Both teams must be allowed to play now */
                if ($result->getTeamCheck()->isCapacity($match, Date::getDate($pa->getSchedule()), $pa->getTimeslot())) {
                    $matchAlternative = new MatchAlternative();
                    $matchAlternative->setPid($ms->getId());
                    $matchAlternative->setPaid($pa->getId());
                    $this->em->persist($matchAlternative);
                }
            }
        }

        $this->em->flush();
    }

    /**
     * Return planned tournament schedule
     * The planTournament function must have been called to generate the match schedule
     * @param $tournamentid
     * @return array
     */
    public function getSchedule($tournamentid){
        $matchschedules = $this->logic->listMatchSchedules($tournamentid);
        $categories = $this->map($this->logic->listCategories($tournamentid));
        $groups = $this->map($this->logic->listGroupsByTournament($tournamentid));
        $teams = array();
        foreach ($groups as $group) {
            foreach ($this->logic->listTeamsByGroup($group->getId()) as $t) {
                $teams[$t->getId()] = $t;
            }
        }
        $playgrounds = $this->map($this->logic->listPlaygroundsByTournament($tournamentid));
        $timeslots = $this->map($this->logic->listTimeslots($tournamentid));
        $pattrs = $this->map($this->logic->listPlaygroundAttributesByTournament($tournamentid));

        $matches = array();
        $unassigned = array();
        $ts = array();
        $catcnt = array();
        $advice = array();

        /* @var $ms MatchSchedule */
        foreach ($matchschedules as $ms) {
            $match = new MatchPlan();
            $match->setTeamA($teams[$ms->getThid()]);
            $match->setTeamB($teams[$ms->getTaid()]);
            $match->setGroup($groups[$match->getTeamA()->getGroup()]);
            $match->setCategory($categories[$match->getGroup()->getPid()]);
            $match->setMatchno(0);
            if (!$ms->isUnscheduled()) {
                $match->setTime($ms->getMatchstart());
                $match->setFixed($ms->isFixed());
                /* @var $pattr PlaygroundAttribute */
                $pattr = $pattrs[$ms->getPaid()];
                $match->setPlayground($playgrounds[$pattr->getPid()]);
                $match->setDate($pattr->getDate());
                if (!array_key_exists($ms->getPaid(), $ts)) {
                    $pa = new PA();
                    $pa->setId($ms->getPaid());
                    $pa->setPlayground($match->getPlayground());
                    $pa->setTimeslot($timeslots[$pattr->getTimeslot()]);

                    $slotschedule = $pattr->getStartSchedule();
                    $pa->setSchedule($slotschedule);

                    $slotend = $pattr->getEndSchedule();
                    $diff = $slotschedule->diff($slotend);
                    $slot_time_left = $diff->h * 60 + $diff->i - $match->getCategory()->getMatchtime();
                    $pa->setTimeleft($slot_time_left);

                    $pa_categories = array();
                    $categoryList = $this->logic->listPACategories($pa->getId());
                    foreach ($categoryList as $category) {
                        $pa_categories[] = $category->getName();
                    }
                    $pa->setCategories($pa_categories);

                    $pa->setMatchlist(array($match));
                    $ts[$pa->getId()] = $pa;
                }
                else {
                    $pa = $ts[$ms->getPaid()];
                    $ml = $pa->getMatchList();
                    $ml[] = $match;
                    $pa->setMatchlist($ml);
                    $slot_time_left = $pa->getTimeleft();
                    $slot_time_left -= $match->getCategory()->getMatchtime();
                    $pa->setTimeleft($slot_time_left);
                }
                $matches[] = $match;
            }
            else {
                $match->setTime('');
                $match->setPlayground(null);
                $match->setDate('');
                $match->setFixed(false);
                $category = $match->getCategory();
                if (array_key_exists($category->getId(), $catcnt)) {
                    $catcnt[$category->getId()]['matchcount']++;
                } else {
                    $catcnt[$category->getId()] = array(
                        'category' => $category,
                        'matchcount' => 1
                    );
                }
                $unassigned[] = $match;
                $malts = array();
                /* @var $ma MatchAlternative */
                foreach ($this->logic->listMatchAlternatives($ms->getId()) as $ma) {
                    $malts[] = $ts[$ma->getPaid()];
                }
                usort($malts, function (PA $ats1, PA $ats2) {
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
                $alternatives = array();
                foreach ($malts as $alt) {
                    $alternatives[date_format($alt->getSchedule(), "Y/m/d")][] = $alt;
                }
                $advice[] = array('id' => $ms->getId(), 'match' => $match, 'alternatives' => $alternatives);
            }
        }
        usort($matches, function (MatchPlan $match1, MatchPlan $match2) {
            $p1 = $match2->getDate() - $match1->getDate();
            $p2 = $match2->getPlayground()->getNo() - $match1->getPlayground()->getNo();
            $p3 = $match2->getTime() - $match1->getTime();
            $p4 = 0;
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
        $mno = 1;
        /* @var $match MatchPlan */
        foreach ($matches as $match) {
            $match->setMatchno($mno);
            $mno++;
        }

        usort($ts, function (PA $ats1, PA $ats2) {
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
        return array(
            'matches' => $matches,
            'unassigned' => $unassigned,
            'timeslots' => $ts,
            'advices' => $advice,
            'unassigned_by_category' => $catcnt
        );
    }

    public function solveMatch($tournamentid, $matchid, $result) {
        foreach ($result['advices'] as $advice) {
            if ($advice['id'] == $matchid) {
                //
                break;
            }
        }
    }

    /**
     * @param $tournamentid
     * @param PlanningOptions $options
     * @return array
     */
    private function populateTournament($tournamentid, PlanningOptions $options) {
        $matchPlanList = array();
        $categories = $this->map($this->logic->listCategories($tournamentid));
        $groups = $this->listGroups($tournamentid);
        foreach ($groups as $group) {
            $matches = $this->populateGroup($group->getId(), $options);
            foreach ($matches as $match) {
                $match->setCategory($categories[$group->getPid()]);
                $match->setGroup($group);
                $matchPlanList[] = $match;
            }
        }
        return $matchPlanList;
    }

    /**
     * @param $groupid
     * @param PlanningOptions $options
     * @return array
     */
    private function populateGroup($groupid, PlanningOptions $options) {
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
        $this->logger->addDebug("unresolved=".$result->unresolved());
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
    }

    private function replan_2run(PlanningResults $result) {
        $this->logger->addDebug("unresolved=".$result->unresolved());
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

    private function replan_3run(PlanningResults $result) {
        $this->logger->addDebug("unresolved=".$result->unresolved());
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

    private function replanMatch(PlanningResults $result, $match) {
        $result->mark();
        while ($pa = $result->cycleTimeslot()) {
            $date = Date::getDate($pa->getSchedule());
            /* Both teams must be allowed to play now */
            if ($result->getTeamCheck()->isCapacity($match, $date, $pa->getTimeslot())) {
                $matchlist = $pa->getMatchlist();
                /* @var $replan_match MatchPlan */
                foreach ($matchlist as $idx => $replan_match) {
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
     * List groups assigned to eliminating rounds
     * @param Integer $tournamentid The tournament to list groups
     * @return array A list of Group objects from all categories assigned to eliminating rounds
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

    /**
     * Map any database object with its id
     * @param array $records List of objects to map
     * @return array A list of objects mapped with object ids (id => object)
     */
    private function map($records) {
        $recordList = array();
        foreach ($records as $record) {
            $recordList[$record->getId()] = $record;
        }
        return $recordList;
    }
}

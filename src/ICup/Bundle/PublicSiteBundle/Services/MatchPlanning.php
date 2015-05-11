<?php
namespace ICup\Bundle\PublicSiteBundle\Services;

use DateInterval;
use DateTime;
use ICup\Bundle\PublicSiteBundle\Entity\MatchPlan;
use ICup\Bundle\PublicSiteBundle\Entity\TeamInfo;
use ICup\Bundle\PublicSiteBundle\Services\Doctrine\BusinessLogic;
use ICup\Bundle\PublicSiteBundle\Services\Entity\PlaygroundAttribute as PA;
use ICup\Bundle\PublicSiteBundle\Services\Entity\TeamCheck;
use Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
     * Order teams in group by match results
     * @param Integer $tournamentid The group to sort
     * @return array A list of TeamStat objects ordered by match results and ordering
     */
    public function populateTournament($tournamentid, $doublematch = false) {
        $matchno = 1;
        $matchPlanList = array();
        $categories = $this->map($this->logic->listCategories($tournamentid));
        $groups = $this->listGroups($tournamentid);
        foreach ($groups as $group) {
            $matches = $this->populateGroup($group->getId(), $doublematch);
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
     * Order teams in group by match results
     * @param Integer $groupid The group to sort
     * @return array A list of TeamStat objects ordered by match results and ordering
     */
    public function populateGroup($groupid, $doublematch = false) {
        $matches = array();
        $teams = $this->logic->listTeamsByGroup($groupid);
        $check = array();
        /* @var $teamA TeamInfo */
        foreach ($teams as $teamA) {
            $idx = 0;
            /* @var $teamB TeamInfo */
            foreach ($teams as $teamB) {
                if (($teamA->id != $teamB->id) && !array_key_exists($teamB->id, $check)) {
                    $switch = $idx%2 == 0 || $doublematch;
                    $match = new MatchPlan();
                    $match->setTeamA($switch ? $teamA : $teamB);
                    $match->setTeamB($switch ? $teamB : $teamA);
                    $matches[] = $match;
                    $idx++;
                }
            }
            if (!$doublematch) {
                $check[$teamA->id] = $teamA;
            }
        }
        return $matches;
    }

    /**
     * Order teams in group by match results
     * @param Integer $tournamentid The group to sort
     * @return array A list of TeamStat objects ordered by match results and ordering
     */
    public function planTournament($tournamentid, $matchList, $finals = false, $preferPG = false) {
        $pattrList = $this->setupCriteria($tournamentid, $finals);
        $unassigned = array();
        $team_check = new TeamCheck();
        $plan = $this->plan($matchList, $pattrList, $unassigned, $team_check);
        if (count($unassigned) > 0) {
            $unplaceable = array();
            $plan = $this->replan($unassigned, $unplaceable, $pattrList, $team_check);
            array_merge($unassigned, $unplaceable);
        }
        
        $catcnt = array();
        foreach ($unassigned as $match) {
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
        
        $available_timeslots = array();
        foreach ($pattrList as $pa) {
            $categories = array();
            $categoryList = $this->logic->listPACategories($pa->getId());
            foreach ($categoryList as $category) {
                $categories[] = $category->getName();
            }
            $available_timeslots[] = array(
                'id' => $pa->getId(),
                'timeslot' => $pa->getTimeslot(),
                'playground' => $pa->getPlayground(),
                'slot_time_left' => $pa->getTimeleft(),
                'slotschedule' => $pa->getSchedule(),
                'categories' => $categories
            );
        }
        usort($available_timeslots, function ($ats1, $ats2) {
            $date1 = date_format($ats1['slotschedule'], $this->container->getParameter('db_date_format'));
            $date2 = date_format($ats2['slotschedule'], $this->container->getParameter('db_date_format'));
//            $time = date_format($slotschedule, $this->container->getParameter('db_time_format'));
            $p1 = $date2 - $date1;
            $p2 = $ats2['playground']->getNo() - $ats1['playground']->getNo();
            $p3 = $ats2['timeslot']->getId() - $ats1['timeslot']->getId();
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

        return array('plan' => $plan, 'unassigned' => $catcnt, 'available_timeslots' => $available_timeslots);
    }

    /**
     * @param $tournamentid
     * @param $finals
     * @return array
     */
    private function setupCriteria($tournamentid, $finals) {
        $playgrounds = $this->map($this->logic->listPlaygroundsByTournament($tournamentid));
        $timeslots = $this->map($this->logic->listTimeslots($tournamentid));
        $pattrList = array();
        $pattrs = $this->logic->listPlaygroundAttributesByTournament($tournamentid);
        foreach ($pattrs as $pattr) {
            $pa = new PA();
            $pa->setId($pattr->getId());
            $pa->setPlayground($playgrounds[$pattr->getPid()]);
            $pa->setTimeslot($timeslots[$pattr->getTimeslot()]);

            $slotschedule = DateTime::createFromFormat(
                                    $this->container->getParameter('db_date_format').
                                    '-'.
                                    $this->container->getParameter('db_time_format'),
                                    $pattr->getDate().'-'.$pattr->getStart());
            $slotend = DateTime::createFromFormat(
                                    $this->container->getParameter('db_date_format').
                                    '-'.
                                    $this->container->getParameter('db_time_format'),
                                    $pattr->getDate().'-'.$pattr->getEnd());
            $diff = $slotschedule->diff($slotend);
            $slot_time_left = $diff->h*60 + $diff->i;

            $pa->setSchedule($slotschedule);
            $pa->setTimeleft($slot_time_left);
            
            $parels = array();
            foreach ($this->logic->listPARelations($pattr->getId()) as $parel) {
                if ($parel->getFinals() == $finals) {
                    $parels[$parel->getCid()] = $parel;
                }
            }                    
            $pa->setCategories($parels);
            $pa->setMatchlist(array());
            $pattrList[$pattr->getId()] = $pa;
        }
        return $pattrList;
    }

    /**
     * @param $matchList
     * @param $pattrList
     * @param $unassigned
     * @param $team_check
     * @return array
     */
    private function plan($matchList, &$pattrList, &$unassigned, &$team_check) {
        foreach ($matchList as $match) {
            $slot_found = $this->planMatch($pattrList, $match, $team_check);
            if (!$slot_found) {
                // if this was not possible register the match as finally unassigned
                $unassigned[] = $match;
            }
        }
        
        $plan = array();
        foreach ($pattrList as $pa) {
            foreach ($pa->getMatchlist() as $match) {
                $plan[] = $match;
            }
        }

        uasort($plan, function (MatchPlan $match1, MatchPlan $match2) {
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
     * @param $pattrList
     * @param $match
     * @param TeamCheck $team_check
     * @return bool
     */
    private function planMatch(&$pattrList, $match, TeamCheck &$team_check, $replan = false) {
        $c = count($pattrList);
        $slot_found = false;
        while ($c > 0 && !$slot_found) {
            $pa = array_shift($pattrList);
            if ($pa == null) {
                break;
            }
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
                $date = date_format($slotschedule, $this->container->getParameter('db_date_format'));
                $time = date_format($slotschedule, $this->container->getParameter('db_time_format'));

                $slot_time_left = $pa->getTimeleft();
                if ($matchtime <= $slot_time_left) {
                    /* Team A must be allowed to play now */
                    $allowedA = $team_check->isMoreCapacity($match->getTeamA(), $date, $pa->getTimeslot());
                    /* Team B must be allowed to play now */
                    $allowedB = $team_check->isMoreCapacity($match->getTeamB(), $date, $pa->getTimeslot());
                    if ($allowedA && $allowedB) {
                        $team_check->reserveCapacity($match->getTeamA(), $date, $pa->getTimeslot());
                        $team_check->reserveCapacity($match->getTeamB(), $date, $pa->getTimeslot());
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
                        $slot_found = true;
                    }
                }
            }
            array_push($pattrList, $pa);
            $c--;
        }
        return $slot_found;
    }

    /**
     * @param $unassigned
     * @param $unplaceable
     * @param $pattrList
     * @param $team_check
     * @return array
     */
    private function replan(&$unassigned, &$unplaceable, &$pattrList, &$team_check) {
        $grace_max = count($unassigned)*2;
        $cnt_last = -1;
        $grace = 0;
        while ($match = array_shift($unassigned)) {
            $cnt = count($unassigned);
            if ($cnt == $cnt_last) {
                $grace++;
            }
            else {
                $cnt_last = $cnt;
                $grace = 0;
            }
            $slot_found = $this->planMatch($pattrList, $match, $team_check, true);
            if (!$slot_found) {
                $new_match = $this->replanMatch($pattrList, $match, $team_check);
                if ($new_match) {
                    array_push($unassigned, $new_match);
                }
                else {
                    $unplaceable[] = $match;
                }
            }
            if ($grace > $grace_max) {
                break;
            }
        }

        $plan = array();
        foreach ($pattrList as $pa) {
            foreach ($pa->getMatchlist() as $match) {
                $plan[] = $match;
            }
        }

        uasort($plan, function (MatchPlan $match1, MatchPlan $match2) {
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
    
    private function replanMatch(&$pattrList, $match, TeamCheck &$team_check) {
        $c = count($pattrList);
        $new_match = null;
        $slot_found = false;
        while ($c > 0 && !$slot_found) {
            $pa = array_shift($pattrList);
            $parels = $pa->getCategories();
            if (array_key_exists($match->getCategory()->getId(), $parels)) {
                $parel = $parels[$match->getCategory()->getId()];
                $date = date_format($pa->getSchedule(), $this->container->getParameter('db_date_format'));
                /* Team A must be allowed to play now */
                $allowedA = $team_check->isMoreCapacity($match->getTeamA(), $date, $pa->getTimeslot());
                /* Team B must be allowed to play now */
                $allowedB = $team_check->isMoreCapacity($match->getTeamB(), $date, $pa->getTimeslot());
                if ($allowedA && $allowedB) {
                    $matchlist = $pa->getMatchlist();
                    foreach ($matchlist as $idx => $replan_match) {
                        $rparel = $parels[$replan_match->getCategory()->getId()];
                        if ($rparel->getMatchtime() == $parel->getMatchtime()) {
                            $team_check->reserveCapacity($match->getTeamA(), $date, $pa->getTimeslot());
                            $team_check->reserveCapacity($match->getTeamB(), $date, $pa->getTimeslot());
                            $match->setDate($date);
                            $match->setTime($replan_match->getTime());
                            $match->setPlayground($replan_match->getPlayground());
                            $matchlist[$idx] = $match;
                            $pa->setMatchlist($matchlist);
                            $team_check->freeCapacity($replan_match->getTeamA(), $date, $pa->getTimeslot());
                            $team_check->freeCapacity($replan_match->getTeamB(), $date, $pa->getTimeslot());
                            $new_match = $replan_match;
                            $slot_found = true;
                            break;
                        }
                    }
                }
            }
            array_push($pattrList, $pa);
            $c--;
        }
        return $new_match;
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

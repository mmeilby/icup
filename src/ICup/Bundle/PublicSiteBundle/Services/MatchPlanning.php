<?php
namespace ICup\Bundle\PublicSiteBundle\Services;

use Symfony\Component\DependencyInjection\ContainerInterface;
use ICup\Bundle\PublicSiteBundle\Services\Doctrine\BusinessLogic;
use ICup\Bundle\PublicSiteBundle\Entity\TeamInfo;
use ICup\Bundle\PublicSiteBundle\Entity\MatchPlan;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Playground;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Timeslot;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\PlaygroundAttribute;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\PARelation;
use Monolog\Logger;
use DateTime;
use DateInterval;

class MatchPlanning
{
    /* @var $container ContainerInterface */
    protected $container;
    /* @var $logic BusinessLogic */
    protected $logic;
    /* @var $logger Logger */
    protected $logger;

    private $normal_sort_order;
    private $sort_by_playground;
    private $sort_by_date;
    
    public function __construct(ContainerInterface $container, Logger $logger)
    {
        $this->container = $container;
        $this->logic = $container->get('logic');
        $this->logger = $logger;
        $this->normal_sort_order = array("ICup\Bundle\PublicSiteBundle\Services\MatchPlanning", "reorder");
        $this->sort_by_playground = array("ICup\Bundle\PublicSiteBundle\Services\MatchPlanning", "sortByPlayground");
        $this->sort_by_date = array("ICup\Bundle\PublicSiteBundle\Services\MatchPlanning", "sortByDate");
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
    public function planTournament($tournamentid, $matchList, $finals = false, $preferPG = true) {
        $playgrounds = $this->map($this->logic->listPlaygroundsByTournament($tournamentid));
        $timeslots = $this->map($this->logic->listTimeslots($tournamentid));
        $strategy = $this->logic->listPlaygroundAttributesByTournament($tournamentid);
        usort($strategy, $preferPG ? $this->sort_by_playground : $this->sort_by_date);
        // make match plan - the masterplan
        $masterplan = array('plan' => array(), 'unassigned' => array(), 'available_timeslots' => array(), 'team_check' => array(), 'finals' => $finals);
        /* @var $pattr PlaygroundAttribute */
        foreach ($strategy as $pattr) {
            $masterplan['timeslot'] = $timeslots[$pattr->getTimeslot()];
            $masterplan['playground'] = $playgrounds[$pattr->getPid()];
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
            $this->fillTimeslot($masterplan, $pattr, $slotschedule, $slot_time_left, $matchList);
        }
        // count the unassigned matches that could not be scheduled in the tournament
        foreach ($matchList as $match) {
            if (!array_key_exists($match->getMatchno(), $masterplan['plan'])) {
                $masterplan['unassigned'][$match->getCategory()->getId()][] = $match->getCategory();
            }
        }
        uasort($masterplan['plan'], $this->normal_sort_order);
        return $masterplan;
    }
    
    private function fillTimeslot(&$masterplan, PlaygroundAttribute $pattr, $slotschedule, $slot_time_left, $matchList) {
        $parels = $this->logic->listPARelations($pattr->getId());
        /* fill the timeslot for the playground */
        while ($slot_time_left > 0) {
            $match_found = false;
            /* @var $parel PARelation */
            foreach ($parels as $parel) {
                $match_found = $this->searchMatches($masterplan, $parel, $slotschedule, $slot_time_left, $matchList);
                if ($match_found) {
                    break;
                }
            }
            // if no match was found leave the rest of the timeslot unassigned
            if (!$match_found) {
                // but collect the timeslot for remaining matches
                $masterplan['available_timeslots'][] = array(
                    'pattr' => $pattr,
                    'timeslot' => $masterplan['timeslot'],
                    'playground' => $masterplan['playground'],
                    'slot_time_left' => $slot_time_left,
                    'slotschedule' => $slotschedule
                );
                break;
            }
        }
    }
    
    private function searchMatches(&$masterplan, PARelation $parel, &$slotschedule, &$slot_time_left, $matchList) {
        /* Search for a match that fits this playground attribute relation */
        if ($parel->getFinals() != $masterplan['finals']) {
            // we need a relation fit for our search - a slot meant for
            // finals or preliminary rounds
            return false;
        }
        if ($parel->getMatchtime() > $slot_time_left) {
            // if this relation needs more time than we got - try another one...
            return false;
        }
        $date = date_format($slotschedule, $this->container->getParameter('db_date_format'));
        $time = date_format($slotschedule, $this->container->getParameter('db_time_format'));
        $match_found = false;
        /* @var $match MatchPlan */
        foreach ($matchList as $match) {
            /* Can not be previously assigned */ 
            if (array_key_exists($match->getMatchno(), $masterplan['plan'])) {
                continue;
            }
            /* Must be the right category */
            if ($match->getCategory()->getId() != $parel->getCid()) {
                continue;
            }
            /* Team A must be allowed to play now */
            if (!$this->checkTeam($match->getTeamA(), $date, $masterplan['timeslot'], $masterplan['team_check'])) {
                continue;
            }
            /* Team B must be allowed to play now */
            if (!$this->checkTeam($match->getTeamB(), $date, $masterplan['timeslot'], $masterplan['team_check'])) {
                continue;
            }
            $masterplan['team_check'][$masterplan['timeslot']->getId()][$match->getTeamA()->id][$date]++;
            $masterplan['team_check'][$masterplan['timeslot']->getId()][$match->getTeamB()->id][$date]++;
            $match->setDate($date);
            $match->setTime($time);
            $match->setPlayground($masterplan['playground']);
            $masterplan['plan'][$match->getMatchno()] = $match;
            $slotschedule->add(new DateInterval('PT'.$parel->getMatchtime().'M'));
            $slot_time_left -= $parel->getMatchtime();
            $match_found = true;
            break;
        }
        return $match_found;
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

    private function checkTeam(TeamInfo $team, $date, Timeslot $timeslot, array &$team_check) {
        if (array_key_exists($timeslot->getId(), $team_check) &&
            array_key_exists($team->id, $team_check[$timeslot->getId()]) &&
            array_key_exists($date, $team_check[$timeslot->getId()][$team->id])) {
                return $team_check[$timeslot->getId()][$team->id][$date] < $timeslot->getCapacity();
        }
        $team_check[$timeslot->getId()][$team->id][$date] = 0;
        return true;
    }
    
    private function map($records) {
        $recordList = array();
        foreach ($records as $record) {
            $recordList[$record->getId()] = $record;
        }
        return $recordList;
    }
    
    /*
     *
     * Matina
     *   6/7
     *     #1 CDF
     *     #2 BH
     *     #3 KLM
     *   7/7
     *     #1 KLM
     *     #2 IH
     * Pommerigio
     *   6/7
     *     #1 CDE
     *     #2 LM
     * 
     */
    
    static function sortByPlayground(PlaygroundAttribute $pattr1, PlaygroundAttribute $pattr2) {
        $p1 = $pattr2->getTimeslot() - $pattr1->getTimeslot();
        $p2 = $pattr2->getPid() - $pattr1->getPid();
        $p3 = $pattr2->getDate() - $pattr1->getDate();
        $p4 = $pattr2->getStart() - $pattr1->getStart();
        if ($p1==0 && $p2==0 && $p3==0 && $p4==0) {
            return 0;
        }
        elseif ($p1 < 0 || ($p1==0 && $p2 < 0) || ($p1==0 && $p2==0 && $p3 < 0) || ($p1==0 && $p2==0 && $p3==0 && $p4 < 0)) {
            return 1;
        }
        else {
            return -1;
        }
    }
    
    static function sortByDate(PlaygroundAttribute $pattr1, PlaygroundAttribute $pattr2) {
        $p1 = $pattr2->getTimeslot() - $pattr1->getTimeslot();
        $p2 = $pattr2->getDate() - $pattr1->getDate();
        $p3 = $pattr2->getPid() - $pattr1->getPid();
        $p4 = $pattr2->getStart() - $pattr1->getStart();
        if ($p1==0 && $p2==0 && $p3==0 && $p4==0) {
            return 0;
        }
        elseif ($p1 < 0 || ($p1==0 && $p2 < 0) || ($p1==0 && $p2==0 && $p3 < 0) || ($p1==0 && $p2==0 && $p3==0 && $p4 < 0)) {
            return 1;
        }
        else {
            return -1;
        }
    }
    
    static function reorder(MatchPlan $match1, MatchPlan $match2) {
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
    }
}

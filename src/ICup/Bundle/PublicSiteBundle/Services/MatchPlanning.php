<?php
namespace ICup\Bundle\PublicSiteBundle\Services;

use DateInterval;
use Doctrine\ORM\EntityManager;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchAlternative;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchSchedule;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchSchedulePlan;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchScheduleRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\QMatchSchedule;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\QMatchScheduleRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Timeslot;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\MatchPlan;
use ICup\Bundle\PublicSiteBundle\Entity\QMatchPlan;
use ICup\Bundle\PublicSiteBundle\Services\Doctrine\BusinessLogic;
use ICup\Bundle\PublicSiteBundle\Services\Entity\PlanningOptions;
use ICup\Bundle\PublicSiteBundle\Services\Entity\PlanningResults;
use ICup\Bundle\PublicSiteBundle\Services\Entity\PlaygroundAttribute as PA;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\PlaygroundAttribute;
use ICup\Bundle\PublicSiteBundle\Services\Entity\QRelation;
use ICup\Bundle\PublicSiteBundle\Services\MatchPlanning\MatchBuilder;
use ICup\Bundle\PublicSiteBundle\Services\MatchPlanning\MatchPlanner;
use ICup\Bundle\PublicSiteBundle\Services\MatchPlanning\MatchUtil;
use ICup\Bundle\PublicSiteBundle\Services\MatchPlanning\QMatchBuilder;
use ICup\Bundle\PublicSiteBundle\Services\MatchPlanning\QMatchPlanner;
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
     * @param Tournament $tournament
     * @param PlanningOptions $options
     * @return array
     */
    public function planTournament(Tournament $tournament, PlanningOptions $options) {
        $this->logic->removeMatchSchedules($tournament);
        $options->setFinals(false);
        $matchList = (new MatchBuilder())->populate($tournament, $options);
        $result = (new MatchUtil($this->em))->setupCriteria($tournament, $matchList, $options);
        (new MatchPlanner($this->logger))->plan($result);
        (new MatchUtil($this->em))->savePlan($tournament, $result);
        $options->setFinals(true);
        $matchListF = (new QMatchBuilder())->populate($tournament);
        $resultF = (new MatchUtil($this->em))->setupCriteria($tournament, $matchListF, $options);
        (new QMatchPlanner($this->logger))->plan($resultF);
        (new MatchUtil($this->em))->savePlan($tournament, $resultF);
        return array("preliminary" => $result, "elimination" => $resultF);
    }

    /**
     * Plan tournament schedule
     * Schedule is saved in match planning tables
     * @param Tournament $tournament
     * @param $level
     * @return array
     */
    public function planTournamentByStep(Tournament $tournament, $level) {
        $planningCard = array();
        switch ($level) {
            case 0: {
                $this->logic->removeMatchSchedules($tournament);
                $this->logic->removeQMatchSchedules($tournament);
                $planningCard['level'] = 10;
                break;
            }
            case 10: {
                $options = new PlanningOptions();
                $options->setDoublematch($tournament->getOption()->isDrr());
                $options->setPreferpg($tournament->getOption()->isSvd());
                $options->setFinals(false);
                $matchList = (new MatchBuilder())->populate($tournament, $options);
                $result = (new MatchUtil($this->em))->setupCriteria($tournament, $matchList, $options);
                (new MatchPlanner($this->logger))->plan($result);
                (new MatchUtil($this->em))->savePlan($tournament, $result);
                $planningCard['preliminary'] = $result;
                if ($tournament->getOption()->isEr()) {
                    $planningCard['level'] = 70;
                }
                else {
                    $planningCard['level'] = 100;
                }
                break;
            }
            case 70: {
                $options = new PlanningOptions();
                $options->setDoublematch($tournament->getOption()->isDrr());
                $options->setPreferpg($tournament->getOption()->isSvd());
                $options->setFinals(true);
                $matchListF = (new QMatchBuilder())->populate($tournament);
                $resultF = (new MatchUtil($this->em))->setupCriteria($tournament, $matchListF, $options);
                (new QMatchPlanner($this->logger))->plan($resultF);
                (new MatchUtil($this->em))->savePlan($tournament, $resultF);
                $planningCard['elimination'] = $resultF;
                $planningCard['level'] = 100;
                break;
            }
        }
        return $planningCard;
    }

    /**
     * Plan tournament schedule - preliminary rounds
     * Schedule is saved in match planning tables
     * @param Tournament $tournament
     * @param PlanningOptions $options
     * @param boolean $save
     * @return PlanningResults
     */
    public function planTournamentPre(Tournament $tournament, PlanningOptions $options, $save = true) {
        $options->setFinals(false);
        $matchList = (new MatchBuilder())->populate($tournament, $options);
        $result = (new MatchUtil($this->em))->setupCriteria($tournament, $matchList, $options);
        (new MatchPlanner($this->logger))->plan($result);
        if ($save) {
            $this->logic->removeMatchSchedules($tournament);
            (new MatchUtil($this->em))->savePlan($tournament, $result);
        }
        $timeslots = $result->getTimeslots();
        usort($timeslots, function (PA $ats1, PA $ats2) {
            $p1 = $ats1->getPA()->getStartSchedule()->getTimestamp() - $ats2->getPA()->getStartSchedule()->getTimestamp();
            $p2 = $ats1->getPlayground()->getNo() - $ats2->getPlayground()->getNo();
            $test = min(1, max(-1, $p1))*2 + min(1, max(-1, $p2));
            return min(1, max(-1, $test));
        });
        return $timeslots;
    }

    /**
     * Plan tournament schedule - finals
     * Schedule is saved in match planning tables
     * @param Tournament $tournament
     * @param PlanningOptions $options
     * @param boolean $save
     * @return PlanningResults
     */
    public function planTournamentFinals(Tournament $tournament, PlanningOptions $options, $save = true) {
        $options->setFinals(true);
        $matchListF = (new QMatchBuilder())->populate($tournament);
        $resultF = (new MatchUtil($this->em))->setupCriteria($tournament, $matchListF, $options);
        (new QMatchPlanner($this->logger))->plan($resultF);
        if ($save) {
            $this->logic->removeMatchSchedules($tournament);
            (new MatchUtil($this->em))->savePlan($tournament, $resultF);
        }
        $timeslots = $resultF->getTimeslots();
        usort($timeslots, function (PA $ats1, PA $ats2) {
            $p1 = $ats1->getPA()->getStartSchedule()->getTimestamp() - $ats2->getPA()->getStartSchedule()->getTimestamp();
            $p2 = $ats1->getPlayground()->getNo() - $ats2->getPlayground()->getNo();
            $test = min(1, max(-1, $p1))*2 + min(1, max(-1, $p2));
            return min(1, max(-1, $test));
        });
        return $timeslots;
    }

    /**
     * @param $tournamentid
     * @param $matchid
     * @param $result
     */
    public function solveMatch($tournamentid, $matchid, $result) {
        foreach ($result['advices'] as $advice) {
            if ($advice['id'] == $matchid) {
                //
                break;
            }
        }
    }

    /**
     * Return planned tournament schedule
     * The planTournament function must have been called to generate the match schedule
     * @param $tournament
     * @return array
     */
    public function getSchedule(Tournament $tournament){
        $matches = array();
        $unassigned = array();
        $ts = $this->makeTimeslotTable($tournament);
        $catcnt = array();
        $advice = array();

        $matchschedules = $this->logic->listMatchSchedules($tournament);
        /* @var $ms MatchSchedule */
        foreach ($matchschedules as $ms) {
            $match = new MatchPlan();
            $this->buildMatchPlan($ms, $match);
            if ($ms->getPlan()) {
                $this->prepareMatch($match, $ms->getPlan(), $ts);
                $matches[] = $match;
            }
            else {
                $match->setMatchno(0);
                $advice[] = $this->prepareUnassignedMatch($match, $ms->getId(), $ts, $catcnt);
                $unassigned[] = $match;
            }
        }
        $qmatchschedules = $this->logic->listQMatchSchedules($tournament);
        /* @var $qms QMatchSchedule */
        foreach ($qmatchschedules as $qms) {
            $match = new QMatchPlan();
            $this->buildQMatchPlan($qms, $match);
            if ($qms->getPlan()) {
                $this->prepareMatch($match, $qms->getPlan(), $ts);
                $matches[] = $match;
            }
            else {
                $match->setMatchno(0);
                $advice[] = $this->prepareUnassignedMatch($match, $qms->getId(), $ts, $catcnt);
                $unassigned[] = $match;
            }
        }

        usort($matches, function (MatchPlan $match1, MatchPlan $match2) {
            $p1 = $match1->getDate() - $match2->getDate();
            $p2 = $match1->getPlayground()->getNo() - $match2->getPlayground()->getNo();
            $p3 = $match1->getTime() - $match2->getTime();
            $test = min(1, max(-1, $p1))*4 + min(1, max(-1, $p2))*2 + min(1, max(-1, $p3));
            return min(1, max(-1, $test));
        });
        $mno = 1;
        /* @var $match MatchPlan */
        foreach ($matches as $match) {
            $match->setMatchno($mno);
            $mno++;
        }

        usort($ts, function (PA $ats1, PA $ats2) {
            $p1 = Date::getDate($ats1->getSchedule()) - Date::getDate($ats2->getSchedule());
            $p2 = $ats1->getPlayground()->getNo() - $ats2->getPlayground()->getNo();
            $p3 = $ats1->getTimeslot()->getId() - $ats2->getTimeslot()->getId();
            $test = min(1, max(-1, $p1))*4 + min(1, max(-1, $p2))*2 + min(1, max(-1, $p3));
            return min(1, max(-1, $test));
        });
        return array(
            'matches' => $matches,
            'unassigned' => $unassigned,
            'timeslots' => $ts,
            'advices' => $advice,
            'unassigned_by_category' => $catcnt
        );
    }

    private function makeTimeslotTable(Tournament $tournament) {
        $ts = array();
        $pattrs = array();
        $tournament->getTimeslots()->forAll(function ($idx, Timeslot $timeslot) use (&$pattrs) {
            $pattrs = array_merge($pattrs, $timeslot->getPlaygroundattributes()->toArray());
            return true;
        });
        /* @var $pattr PlaygroundAttribute */
        foreach ($pattrs as $pattr) {
            $pa = new PA();
            $pa->setPA($pattr);
            $pa->setSchedule($pattr->getStartSchedule());

            $categories = array();
            foreach ($pattr->getCategories() as $category) {
                $categories[$category->getId()] = $category;
            }
            $pa->setCategories($categories);
            $pa->setMatchlist(array());
            $ts[$pattr->getId()] = $pa;
        }
        return $ts;
    }

    private function buildMatchPlan(MatchSchedule $ms, MatchPlan $match) {
        $match->setGroup($ms->getGroup());
        $match->setCategory($ms->getGroup()->getCategory());
        /* @var $rel MatchScheduleRelation */
        foreach ($ms->getMatchRelations()->getValues() as $rel) {
            if ($rel->getAwayteam()) {
                $match->setTeamB($rel->getTeam());
            }
            else {
                $match->setTeamA($rel->getTeam());
            }
        }
    }

    private function buildQMatchPlan(QMatchSchedule $ms, QMatchPlan $match) {
        $match->setCategory($ms->getCategory());
        $match->setClassification($ms->getClassification());
        $match->setLitra($ms->getLitra());
        /* @var $qrel QMatchScheduleRelation */
        foreach ($ms->getQMatchRelations()->getValues() as $qrel) {
            $group = $qrel->getClassification() == Group::$PRE ? $ms->getCategory()->getNthGroup($qrel->getLitra()) : null;
            $qrelation = new QRelation($qrel->getClassification(), $qrel->getLitra(), $qrel->getRank(), $qrel->getBranch(), $group);
            if ($qrel->getAwayteam()) {
                $match->setRelB($qrelation);
            }
            else {
                $match->setRelA($qrelation);
            }
        }
    }

    /**
     * @param MatchPlan $match
     * @param MatchSchedulePlan $plan
     * @param $ts
     */
    private function prepareMatch(MatchPlan $match, MatchSchedulePlan $plan, &$ts) {
        $match->setTime($plan->getMatchstart());
        $match->setFixed($plan->isFixed());
        /* @var $pattr PlaygroundAttribute */
        $pattr = $plan->getPlaygroundAttribute();
        $match->setPlayground($pattr->getPlayground());
        $match->setDate($pattr->getDate());
        if (isset($ts[$pattr->getId()])) {
            /* @var $pa PA */
            $pa = $ts[$pattr->getId()];
            $ml = $pa->getMatchList();
            $ml[] = $match;
            $pa->setMatchlist($ml);
            $slotschedule = $match->getSchedule();
            $slotschedule->add(new DateInterval('PT' . $match->getCategory()->getMatchtime() . 'M'));
            $pa->setSchedule($slotschedule);
        }
    }

    private function prepareUnassignedMatch(MatchPlan $match, $msid, &$ts, &$catcnt) {
        $match->setTime('');
        $match->setPlayground(null);
        $match->setDate('');
        $match->setFixed(false);
        $category = $match->getCategory();
        if (isset($catcnt[$category->getId()])) {
            $catcnt[$category->getId()]['matchcount']++;
        } else {
            $catcnt[$category->getId()] = array(
                'category' => $category,
                'matchcount' => 1
            );
        }
        $malts = array();
        /* @var $ma MatchAlternative */
        foreach ($this->logic->listMatchAlternatives($msid) as $ma) {
            $pattr = $ma->getPlaygroundAttribute();
            if (isset($ts[$pattr->getId()])) {
                $malts[] = $ts[$pattr->getId()];
            }
        }
        usort($malts, function (PA $ats1, PA $ats2) {
            $p1 = $ats2->getTimeleft() - $ats1->getTimeleft();
            $p2 = $ats1->getPlayground()->getNo() - $ats2->getPlayground()->getNo();
            $p3 = $ats1->getTimeslot()->getId() - $ats2->getTimeslot()->getId();
            $test = min(1, max(-1, $p1))*4 + min(1, max(-1, $p2))*2 + min(1, max(-1, $p3));
            return min(1, max(-1, $test));
        });
        $alternatives = array();
        /* @var $alt PA */
        foreach ($malts as $alt) {
            $alternatives[date_format($alt->getSchedule(), "Y/m/d")][] = $alt;
        }
        return array('id' => $msid, 'match' => $match, 'alternatives' => $alternatives);
    }
}

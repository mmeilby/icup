<?php
namespace ICup\Bundle\PublicSiteBundle\Services;

use DateInterval;
use Doctrine\ORM\EntityManager;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Champion;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchAlternative;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchSchedule;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchSchedulePlan;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchScheduleRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Playground;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\QMatchRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\QMatchSchedule;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\QMatchScheduleRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Timeslot;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\MatchPlan;
use ICup\Bundle\PublicSiteBundle\Entity\QMatchPlan;
use ICup\Bundle\PublicSiteBundle\Services\Doctrine\BusinessLogic;
use ICup\Bundle\PublicSiteBundle\Services\Doctrine\MatchSupport;
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

    /**
     * MatchPlanning constructor.
     * @param ContainerInterface $container
     * @param Logger $logger
     */
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
            $match->setId($ms->getId());
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
            $match->setId($qms->getId());
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
            $p2 = $match1->getPlaygroundAttribute()->getStart() - $match2->getPlaygroundAttribute()->getStart();
            $p3 = $match1->getPlayground()->getNo() - $match2->getPlayground()->getNo();
            $p4 = $match1->getTime() - $match2->getTime();
            $test = min(1, max(-1, $p1))*8 + min(1, max(-1, $p2))*4 + min(1, max(-1, $p3))*2 + min(1, max(-1, $p4));
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

    /**
     * Publish planned matches as tournament schedule
     * Planned matches are copied to match schedule. Previous schedule is removed
     * Elimination groups and champion requirements are redefined
     * @param Tournament $tournament
     * @throws \Exception
     */
    public function publishSchedule(Tournament $tournament) {
        $result = $this->getSchedule($tournament);
        $matches = $result['matches'];

        $champions = array(
            Group::$FINAL => array(1 => 1, 2 => 2),
            Group::$BRONZE => array(1 => 3, 2 => 4),
        );

        $this->em->beginTransaction();
        $qgroups = array();
        try {
            $tournament->getCategories()->forAll(function ($n, Category $category) {
                foreach ($category->getGroups() as $idx => $group) {
                    /* @var $group Group */
                    if ($group->getClassification() > Group::$PRE) {
                        $category->getGroups()->remove($idx);
                        $this->em->remove($group);
                    }
                    else {
                        foreach ($group->getMatches() as $match) {
                            $this->em->remove($match);
                        }
                        $group->getMatches()->clear();
                    }
                }
                foreach ($category->getChampions() as $champion) {
                    $this->em->remove($champion);
                }
                $category->getChampions()->clear();
                return true;
            });
            $this->em->flush();

            usort($matches, function (MatchPlan $match1, MatchPlan $match2) {
                if ($match1 instanceof QMatchPlan && $match2 instanceof QMatchPlan) {
                    $p1 = $match1->getClassification() - $match2->getClassification();
                }
                else {
                    $p1 = ($match1 instanceof QMatchPlan ? 1 : 0) - ($match2 instanceof QMatchPlan ? 1 : 0);
                }
                $p2 = $match1->getDate() - $match2->getDate();
                $p3 = $match1->getPlayground()->getNo() - $match2->getPlayground()->getNo();
                $p4 = $match1->getTime() - $match2->getTime();
                $test = min(1, max(-1, $p1))*8 + min(1, max(-1, $p2))*4 + min(1, max(-1, $p3))*2 + min(1, max(-1, $p4));
                return min(1, max(-1, $test));
            });

            foreach ($matches as $match) {
                if ($match instanceof QMatchPlan) {
                    /* @var $match QMatchPlan */
                    $matchrec = new Match();
                    $matchrec->setMatchno($match->getMatchno());
                    $matchrec->setDate($match->getDate());
                    $matchrec->setTime($match->getTime());
                    if (isset($qgroups[$match->getCategory()->getId()."-".$match->getClassification()."-".$match->getLitra()])) {
                        $group = $qgroups[$match->getCategory()->getId()."-".$match->getClassification()."-".$match->getLitra()];
                    }
                    else {
                        $group = new Group();
                        $group->setName($match->getLitra());
                        $group->setCategory($match->getCategory());
                        $group->setClassification($match->getClassification());
                        $qgroups[$match->getCategory()->getId()."-".$match->getClassification()."-".$match->getLitra()] = $group;
                        $group->getCategory()->getGroups()->add($group);
                        $this->em->persist($group);
                    }
                    if (isset($champions[$match->getClassification()])) {
                        foreach ($champions[$match->getClassification()] as $rank => $champ) {
                            if ($champ <= $match->getCategory()->getTrophys()) {
                                $champion = new Champion();
                                $champion->setCategory($match->getCategory());
                                // if champion is found from the B finals then shift the rank below the A finalists
                                if (preg_match('/\d+B/', $match->getLitra())) {
                                    $champion->setChampion($champ + $match->getCategory()->getTrophys());
                                }
                                else {
                                    $champion->setChampion($champ);
                                }
                                $champion->setGroup($group);
                                $champion->setRank($rank);
                                $champion->getCategory()->getChampions()->add($champion);
                                $this->em->persist($champion);
                            }
                        }
                    }
                    $matchrec->setGroup($group);
                    $matchrec->setPlayground($match->getPlayground());

                    $resultreqA = new QMatchRelation();
                    $resultreqA->setAwayteam(MatchSupport::$HOME);
                    if ($match->getRelA()->getClassification() == Group::$PRE) {
                        $resultreqA->setGroup($match->getRelA()->getGroup());
                    }
                    else {
                        $group = $qgroups[$match->getCategory()->getId()."-".$match->getRelA()->getClassification()."-".$match->getRelA()->getLitra().$match->getRelA()->getBranch()];
                        $resultreqA->setGroup($group);
                    }
                    $resultreqA->setRank($match->getRelA()->getRank());
                    $matchrec->addMatchRelation($resultreqA);

                    $resultreqB = new QMatchRelation();
                    $resultreqB->setAwayteam(MatchSupport::$AWAY);
                    if ($match->getRelB()->getClassification() == Group::$PRE) {
                        $resultreqB->setGroup($match->getRelB()->getGroup());
                    }
                    else {
                        $group = $qgroups[$match->getCategory()->getId()."-".$match->getRelB()->getClassification()."-".$match->getRelB()->getLitra().$match->getRelB()->getBranch()];
                        $resultreqB->setGroup($group);
                    }
                    $resultreqB->setRank($match->getRelB()->getRank());
                    $matchrec->addMatchRelation($resultreqB);

                    $matchrec->getGroup()->getMatches()->add($matchrec);
                    $matchrec->getPlayground()->getMatches()->add($matchrec);
                    $this->em->persist($matchrec);
                }
                else {
                    /* @var $match MatchPlan */
                    if ($match->getTeamA() && $match->getTeamB()) {
                        // both home and away team must be defined
                        $matchrec = new Match();
                        $matchrec->setMatchno($match->getMatchno());
                        $matchrec->setDate($match->getDate());
                        $matchrec->setTime($match->getTime());
                        $matchrec->setGroup($match->getGroup());
                        $matchrec->setPlayground($match->getPlayground());

                        $resultreqA = new MatchRelation();
                        $resultreqA->setTeam($match->getTeamA());
                        $resultreqA->setAwayteam(MatchSupport::$HOME);
                        $resultreqA->setScorevalid(false);
                        $resultreqA->setScore(0);
                        $resultreqA->setPoints(0);
                        $matchrec->addMatchRelation($resultreqA);

                        $resultreqB = new MatchRelation();
                        $resultreqB->setTeam($match->getTeamB());
                        $resultreqB->setAwayteam(MatchSupport::$AWAY);
                        $resultreqB->setScorevalid(false);
                        $resultreqB->setScore(0);
                        $resultreqB->setPoints(0);
                        $matchrec->addMatchRelation($resultreqB);

                        $matchrec->getGroup()->getMatches()->add($matchrec);
                        $matchrec->getPlayground()->getMatches()->add($matchrec);
                        $this->em->persist($matchrec);
                    }
                }
            }
            $this->em->flush();
            $this->em->commit();
        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    /**
     * Return planned and unassigned matches for a specified venue and match date
     * @param Playground $playground reference to venue
     * @param String $matchDate match date (YYYYMMDD)
     * @return array
     */
    public function listMatchesByPlaygroundDate(Playground $playground, $matchDate) {
        $result = $this->getSchedule($playground->getSite()->getTournament());
        $matches = array();
        $playground->getPlaygroundAttributes()->forAll(function ($idx, PlaygroundAttribute $pattr) use ($matchDate, &$matches) {
            if ($pattr->getDate() == $matchDate) {
                $matches[$pattr->getId()] = array();
            }
            return true;
        });

        foreach ($result['advices'] as $advice) {
            foreach ($advice['alternatives'] as $alt) {
                /* @var $aitem PA */
                foreach ($alt as $aitem) {
                    if ($aitem->getPlayground()->getId() == $playground->getId() && Date::getDate($aitem->getSchedule()) == $matchDate) {
                        $matches[$aitem->getPA()->getId()][] = $advice['match'];
                    }
                }
            }
        }
        foreach ($result['matches'] as $match) {
            /* @var $match MatchPlan */
            if ($match->getPlayground()->getId() == $playground->getId() && $match->getDate() == $matchDate) {
                $matches[$match->getPlaygroundAttribute()->getId()][] = $match;
            }
        }
        return $matches;
    }

    /**
     * @param Tournament $tournament
     * @return array
     */
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

    /**
     * @param MatchSchedule $ms
     * @param MatchPlan $match
     */
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

    /**
     * @param QMatchSchedule $ms
     * @param QMatchPlan $match
     */
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
        $match->setAssigned(true);
        /* @var $pattr PlaygroundAttribute */
        $pattr = $plan->getPlaygroundAttribute();
        $match->setPlaygroundAttribute($pattr);
        $match->setPlayground($pattr->getPlayground());
        $match->setDate($pattr->getDate());
        if (isset($ts[$pattr->getId()])) {
            /* @var $pa PA */
            $pa = $ts[$pattr->getId()];
            $ml = $pa->getMatchlist();
            $ml[] = $match;
            $pa->setMatchlist($ml);
            $slotschedule = $match->getSchedule();
            $slotschedule->add(new DateInterval('PT' . $match->getCategory()->getMatchtime() . 'M'));
            $pa->setSchedule($slotschedule);
        }
    }

    /**
     * @param MatchPlan $match
     * @param $msid
     * @param $ts
     * @param $catcnt
     * @return array
     */
    private function prepareUnassignedMatch(MatchPlan $match, $msid, &$ts, &$catcnt) {
        $match->setTime('');
        $match->setPlayground(null);
        $match->setPlaygroundAttribute(null);
        $match->setDate('');
        $match->setFixed(false);
        $match->setAssigned(false);
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

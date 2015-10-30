<?php
/**
 * Created by PhpStorm.
 * User: mm
 * Date: 16/10/15
 * Time: 17.54
 */

namespace ICup\Bundle\PublicSiteBundle\Services\MatchPlanning;

use Doctrine\ORM\EntityManager;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchAlternative;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchSchedule;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchSchedulePlan;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchScheduleRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\PlaygroundAttribute;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\QMatchSchedule;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\QMatchScheduleRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Timeslot;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\MatchPlan;
use ICup\Bundle\PublicSiteBundle\Entity\QMatchPlan;
use ICup\Bundle\PublicSiteBundle\Services\Entity\PlanningOptions;
use ICup\Bundle\PublicSiteBundle\Services\Entity\PlanningResults;
use ICup\Bundle\PublicSiteBundle\Services\Entity\PlaygroundAttribute as PA;

class MatchUtil
{
    /* @var $em EntityManager */
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param Tournament $tournament
     * @param $matchList
     * @param PlanningOptions $options
     * @return PlanningResults
     */
    public function setupCriteria(Tournament $tournament, $matchList, PlanningOptions $options) {
        $result = new PlanningResults();
        /* @var $timeslot Timeslot */
        foreach ($tournament->getTimeslots()->toArray() as $timeslot) {
            /* @var $pattr PlaygroundAttribute */
            foreach ($timeslot->getPlaygroundattributes()->toArray() as $pattr) {
                if ($pattr->getFinals() == $options->isFinals()) {
                    $pa = new PA();
                    $pa->setPA($pattr);
                    $pa->setId($pattr->getId());
                    $pa->setPlayground($pattr->getPlayground());
                    $pa->setTimeslot($pattr->getTimeslot());
                    $pa->setSchedule($pattr->getStartSchedule());
                    $categories = array();
                    foreach ($pattr->getCategories() as $category) {
                        $categories[$category->getId()] = $category;
                    }
                    $pa->setCategories($categories);
                    $pa->setMatchlist(array());
                    $result->addTimeslot($pa);
                }
            }
        }

        if ($options->isFinals()) {
            // Order the match plan by classification ascending
            usort($matchList, function (QMatchPlan $m1, QMatchPlan $m2) {
                return min(1, max(-1, $m1->getClassification() - $m2->getClassification()));
            });
        }
        else {
            // Order the match plan by group ascending
            usort($matchList, function (MatchPlan $m1, MatchPlan $m2) {
                return min(1, max(-1, $m1->getGroup()->getId() - $m2->getGroup()->getId()));
            });
        }
        foreach ($matchList as $match) {
            $result->appendUnresolved($match);
        }

        return $result;
    }

    /**
     * @param Tournament $tournament
     * @param PlanningResults $results
     */
    public function savePlan(Tournament $tournament, PlanningResults $results) {
        /* @var $pa PA */
        foreach ($results->getTimeslots() as $pa) {
            /* @var $match MatchPlan */
            foreach ($pa->getMatchlist() as $match) {
                $ms = $this->makeMatchSchedule($tournament, $match);
                $mp = new MatchSchedulePlan();
                $mp->setPlaygroundAttribute($this->em->merge($pa->getPA()));
                $mp->setMatchstart($match->getTime());
                $mp->setFixed($match->isFixed());
                $ms->setPlan($mp);
                $this->em->persist($ms);
            }
        }

        /* @var $match MatchPlan */
        foreach ($results->getUnresolved() as $match) {
            $ms = $this->makeMatchSchedule($tournament, $match);
            $this->em->persist($ms);

            if (!($match instanceof QMatchPlan)) {
                /* @var $pa PA */
                foreach ($results->getTimeslots() as $pa) {
                    /* Both teams must be allowed to play now */
                    if ($results->getTeamCheck()->isCapacity($match, $pa->getSchedule(), $pa->getPlayground(), $pa->getTimeslot())) {
                        $matchAlternative = new MatchAlternative();
                        $matchAlternative->setMatchSchedule($ms);
                        $matchAlternative->setPlaygroundAttribute($this->em->merge($pa->getPA()));
                        $this->em->persist($matchAlternative);
                    }
                }
            }
        }

        $this->em->flush();
    }

    private function makeMatchSchedule(Tournament $tournament, MatchPlan $match) {
        // for QMatchPlan, group and team are undefined - use classification and litra instead
        if ($match instanceof QMatchPlan) {
            /* @var $match QMatchPlan */
            $ms = new QMatchSchedule();
            $ms->setTournament($tournament);
            $ms->setCategory($this->em->merge($match->getCategory()));
            $ms->setBranch('');
            $ms->setClassification($match->getClassification());
            $ms->setLitra($match->getLitra());
            $hr = new QMatchScheduleRelation();
            $hr->setBranch($match->getRelA()->getBranch());
            $hr->setClassification($match->getRelA()->getClassification());
            $hr->setLitra($match->getRelA()->getLitra());
            $hr->setRank($match->getRelA()->getRank());
            $hr->setAwayteam(false);
            $ms->addQMatchRelation($hr);
            $ar = new QMatchScheduleRelation();
            $ar->setBranch($match->getRelB()->getBranch());
            $ar->setClassification($match->getRelB()->getClassification());
            $ar->setLitra($match->getRelB()->getLitra());
            $ar->setRank($match->getRelB()->getRank());
            $ar->setAwayteam(true);
            $ms->addQMatchRelation($ar);
        }
        else {
            $ms = new MatchSchedule();
            $ms->setTournament($tournament);
            $ms->setGroup($this->em->merge($match->getGroup()));
            $hr = new MatchScheduleRelation();
            $hr->setTeam($this->em->merge($match->getTeamA()));
            $hr->setAwayteam(false);
            $ms->addMatchRelation($hr);
            $ar = new MatchScheduleRelation();
            $ar->setTeam($this->em->merge($match->getTeamB()));
            $ar->setAwayteam(true);
            $ms->addMatchRelation($ar);
        }
        return $ms;
    }
}
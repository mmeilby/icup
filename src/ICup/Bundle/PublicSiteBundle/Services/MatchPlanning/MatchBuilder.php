<?php
/**
 * Created by PhpStorm.
 * User: mm
 * Date: 16/10/15
 * Time: 17.54
 */

namespace ICup\Bundle\PublicSiteBundle\Services\MatchPlanning;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\MatchPlan;
use ICup\Bundle\PublicSiteBundle\Services\Entity\PlanningOptions;

class MatchBuilder
{
    /**
     * Build list of matches (preliminary rounds) for tournament
     * @param Tournament $tournament
     * @param PlanningOptions $options
     * @return array
     */
    public function populate(Tournament $tournament, PlanningOptions $options) {
        $matchPlanList = array();
        /* @var Category $category */
        foreach ($tournament->getCategories() as $category) {
            /* @var $group Group */
            foreach ($category->getGroupsClassified(Group::$PRE) as $group) {
                $matches = $this->populateGroup($group, $options);
                /* @var $match MatchPlan */
                foreach ($matches as $match) {
                    $match->setCategory($category);
                    $match->setGroup($group);
                    $matchPlanList[] = $match;
                }
            }
        }
        return $matchPlanList;
    }

    /**
     * @param Group $group
     * @param PlanningOptions $options
     * @return array
     */
    private function populateGroup(Group $group, PlanningOptions $options) {
        $matches = array();
        $teams = $group->getTeams();
        $check = array();
        /* @var $teamA Team */
        foreach ($teams as $teamA) {
            $idx = 0;
            /* @var $teamB Team */
            foreach ($teams as $teamB) {
                if (($teamA->getId() != $teamB->getId()) && !isset($check[$teamB->getId()])) {
                    $switch = $idx%2 == 0 || $options->isDoublematch();
                    $match = new MatchPlan();
                    $match->setTeamA($switch ? $teamA : $teamB);
                    $match->setTeamB($switch ? $teamB : $teamA);
                    $match->setFixed(false);
                    $matches[] = $match;
                    $idx++;
                }
            }
            if (!$options->isDoublematch()) {
                $check[$teamA->getId()] = $teamA;
            }
        }
        return $matches;
    }
}
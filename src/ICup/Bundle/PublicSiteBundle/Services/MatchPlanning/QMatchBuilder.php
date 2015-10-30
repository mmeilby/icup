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
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\QMatchPlan;
use ICup\Bundle\PublicSiteBundle\Services\Entity\QRelation;

class QMatchBuilder
{
    /**
     * Build list of matches (elimination rounds) for tournament
     * @param Tournament $tournament
     * @return array
     */
    public function populate(Tournament $tournament) {
        $matchPlanList = array();
        /* @var $category Category */
        foreach ($tournament->getCategories() as $category) {
            $matches = array();
            switch ($category->getStrategy()) {
                case 0: {
                    $matches = $this->populateAB($category);
                    break;
                }
                case 1: {
                    $matches = $this->populateType1($category);
                    break;
                }
                case 2: {
                    $matches = $this->populateType2($category);
                    break;
                }
                case 3: {
                    $matches = $this->populateType3($category);
                    break;
                }
            }
            /* @var $match QMatchPlan */
            foreach ($matches as $match) {
                $match->setCategory($category);
                $matchPlanList[] = $match;
            }
        }
        return $matchPlanList;
    }

    private function populateAB(Category $category) {
        $aclass = array();
        $bclass = array();
        $topteams = $category->getTopteams();
        $groups = $category->getGroupsClassified(Group::$PRE);
        $g = 1;
        /* @var $group Group */
        foreach ($groups as $group) {
            $teams = count($group->getTeams());
            for ($i = $teams; $i > 0; $i--) {
                if ($i > $topteams) {
                    $bclass[$g][$i-$topteams] = "0:".$g."r".$i;
                }
                else {
                    $aclass[$g][$i] = "0:".$g."r".$i;
                }
            }
            $g++;
        }
        $astrategy = $this->unfoldStrategy($aclass, 'A');
        $bstrategy = $this->unfoldStrategy($bclass, 'B');
        return $this->populateQMatches($category, array_merge($bstrategy, $astrategy));
    }

    private function unfoldStrategy($class, $branch = '') {
        $cnt = count($class, COUNT_RECURSIVE);
        if ($cnt == 0) {
            return array();
        }
        $list = array();
        for ($i=0; $i<$cnt; $i++) {
            foreach ($class as $group) {
                if (isset($group[$i])) {
                    $list[] = $group[$i];
                }
            }
        }
        $strategy = $this->strategies(count($list), $branch);
        foreach ($strategy as $key => $matchlist) {
            foreach ($matchlist as $i => $match) {
                foreach ($match as $j => $team) {
                    if (preg_match('/#(?<rank>\d+)/', $team, $args)) {
                        $strategy[$key][$i][$j] = $list[$args['rank']-1];
                    }
                }

            }
        }
        return $strategy;
    }

    private function strategies($teams, $branch) {
        if ($teams <= 2) {
            return array("10:1".$branch => array(array("#1", "#2")));
        }
        elseif ($teams == 3) {
            return array("10:1".$branch => array(array("#1", "8:1".$branch."r1")), "8:1".$branch => array(array("#2", "#3")));
        }
        $games = array(256, 128, 64, 32, 16, 8, 4, 2);
        $strategy = array();
        foreach ($games as $idx => $pyramidno) {
            if ($teams >= $pyramidno) {
                $rest = $teams - $pyramidno;
                $strategy = array(
                    "9:1".$branch => array(array("8:1".$branch."r2", "8:2".$branch."r2")),
                    "10:1".$branch => array(array("8:1".$branch."r1", "8:2".$branch."r1")));
                for ($j = 1; $j<=$pyramidno/2; $j++) {
                    if ($j > $pyramidno-$rest) {
                        $teamA = ($idx+1).":".($pyramidno-$j+1).$branch."r1";
                        $strategy[($idx+1).":".($pyramidno-$j+1).$branch] = array(array("#".$j, "#".(2*$pyramidno+1-$j)));
                    }
                    else {
                        $teamA = "#".$j;
                    }
                    if ($j-1 < $rest) {
                        $teamB = ($idx+1).":".$j.$branch."r1";
                        $strategy[($idx+1).":".$j.$branch] = array(array("#".($pyramidno-$j+1), "#".($pyramidno+$j)));
                    }
                    else {
                        $teamB = "#".($pyramidno-$j+1);
                    }
                    $strategy[($idx+2).":".$j.$branch] = array(array($teamA, $teamB));
                }
                $pmno = $pyramidno/2;
                for ($i = $idx+3; $i<9; $i++) {
                    for ($j = 1; $j<=$pmno/2; $j++) {
                        $strategy[$i.":".$j.$branch] = array(array(($i-1).":".$j.$branch."r1", ($i-1).":".($pmno-$j+1).$branch."r1"));
                    }
                    $pmno /= 2;
                }
                break;
            }
        }
        return $strategy;
    }

    private function populateType1(Category $category) {
        $groups = $category->getGroupsClassified(Group::$PRE);
        $strategy = $this->unfoldStrategyType1(count($groups));
        return $this->populateQMatches($category, $strategy);
    }

    private function unfoldStrategyType1($groups) {
        switch ($groups) {
            case 1: {
                $strategy = $this->unfoldStrategy(array(array("0:1r1", "0:1r2", "0:1r3", "0:1r4")));
                break;
            }
            case 2: {
                $strategy = $this->unfoldStrategy(array(array("0:1r1", "0:1r2", "0:1r3", "0:1r4"), array("0:2r1", "0:2r2", "0:2r3", "0:2r4")));
                break;
            }
            case 3: {
                $strategy = $this->unfoldStrategy(array(array("0:1r1", "0:1r2", "1:1r1"), array("0:2r1", "0:3r2", "1:1r2"), array("0:3r1", "0:2r2")));
                $strategy = array_merge(array("1:1" => array(array("0:1r3", "0:3r3"), array("0:3r3", "0:2r3"), array("0:2r3", "0:1r3"))), $strategy);
                break;
            }
            case 4: {
                $strategy = $this->unfoldStrategy(array(array("0:1r1", "0:1r2"), array("0:2r1", "0:2r2"), array("0:3r1", "0:3r2"), array("0:4r1", "0:4r2")));
                break;
            }
            default: {
                $strategy = array();
            }
        }
        return $strategy;
    }

    private function populateType2(Category $category) {
        $groups = $category->getGroupsClassified(Group::$PRE);
        $strategy = $this->unfoldStrategyType2(count($groups));
        return $this->populateQMatches($category, $strategy);
    }

    private function unfoldStrategyType2($groups) {
        switch ($groups) {
            case 1: {
                $strategy = array(
                    "9:1" => array(array("0:1r3", "0:1r4")),
                    "10:1" => array(array("0:1r1", "0:1r2")),
                );
                break;
            }
            case 2: {
                $strategy = $this->unfoldStrategy(array(array("0:1r1", "0:1r2"), array("0:2r1", "0:2r2")));
                break;
            }
            case 3: {
                $strategy = $this->unfoldStrategy(array(array("0:1r1", "1:1r1"), array("0:2r1"), array("0:3r1")));
                $strategy = array_merge(array("1:1" => array(array("0:1r2", "0:3r2"), array("0:3r2", "0:2r2"), array("0:2r2", "0:1r2"))), $strategy);
                break;
            }
            case 4: {
                $strategy = $this->unfoldStrategy(array(array("0:1r1"), array("0:2r1"), array("0:3r1"), array("0:4r1")));
                break;
            }
            default: {
                $strategy = array();
            }
        }
        return $strategy;
    }

    private function populateType3(Category $category) {
        $groups = $category->getGroupsClassified(Group::$PRE);
        $strategy = $this->unfoldStrategyType3(count($groups));
        return $this->populateQMatches($category, $strategy);
    }

    private function unfoldStrategyType3($groups) {
        switch ($groups) {
            case 2: {
                $strategy = array(
                    "9:1" => array(array("0:1r2", "0:2r2")),
                    "10:1" => array(array("0:1r1", "0:2r1")),
                );
                break;
            }
            default: {
                $strategy = array();
            }
        }
        return $strategy;
    }

    private function populateQMatches(Category $category, $strategy) {
        $matchList = array();
        uksort($strategy, function ($k1, $k2) {
            preg_match('/(?<group>\d+):(?<litra>\d+[AB]*)/', $k1, $v1);
            preg_match('/(?<group>\d+):(?<litra>\d+[AB]*)/', $k2, $v2);
            if ($v1["group"] == $v2["group"]) {
                return $v1["litra"] - $v2["litra"];
            }
            return $v1["group"] - $v2["group"];
        });
        foreach ($strategy as $key => $matches) {
            preg_match('/(?<classification>\d+):(?<litra>\d+[AB]*)/', $key, $group);
            foreach ($matches as $match) {
                if ($category->getTrophys() < 3 && $group['classification'] == Group::$BRONZE) {
                    continue;
                }
                $qmp = new QMatchPlan();
                $qmp->setClassification($group['classification']);
                $qmp->setLitra($group['litra']);
                preg_match('/(?<classification>\d+):(?<litra>\d+)(?<branch>[AB]*)r(?<rank>\d+)/', $match[0], $team);
                $qmp->setRelA(new QRelation($team['classification'], $team['litra'], $team['rank'], isset($team['branch']) ? $team['branch'] : ''));
                preg_match('/(?<classification>\d+):(?<litra>\d+)(?<branch>[AB]*)r(?<rank>\d+)/', $match[1], $team);
                $qmp->setRelB(new QRelation($team['classification'], $team['litra'], $team['rank'], isset($team['branch']) ? $team['branch'] : ''));
                $qmp->setFixed(false);
                $matchList[] = $qmp;
            }
        }
        return $matchList;
    }
}
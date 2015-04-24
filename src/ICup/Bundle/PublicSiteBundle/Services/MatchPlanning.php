<?php
namespace ICup\Bundle\PublicSiteBundle\Services;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchRelation;
use ICup\Bundle\PublicSiteBundle\Entity\TeamStat;
use ICup\Bundle\PublicSiteBundle\Services\Doctrine\BusinessLogic;
use ICup\Bundle\PublicSiteBundle\Entity\TeamInfo;
use ICup\Bundle\PublicSiteBundle\Entity\Match;
use ICup\Bundle\PublicSiteBundle\Entity\MatchPlan;
use Monolog\Logger;

class MatchPlanning
{
    /* @var $logic BusinessLogic */
    protected $logic;
    /* @var $logger Logger */
    protected $logger;

    private $normal_sort_order;
    
    public function __construct(BusinessLogic $logic, Logger $logger)
    {
        $this->logic = $logic;
        $this->logger = $logger;
        $this->normal_sort_order = array("ICup\Bundle\PublicSiteBundle\Services\Order\OrderByPoints", "reorder");
    }

    /**
     * Order teams in group by match results
     * @param Integer $groupid The group to sort
     * @return array A list of TeamStat objects ordered by match results and ordering
     */
    public function populateTournament($tournamentid, $doublematch = false) {
        $matchPlanList = array();
        $categories = $this->logic->listCategories($tournamentid);
        $categoryList = array();
        foreach ($categories as $category) {
            $categoryList[$category->getId()] = $category;
        }
        $groups = $this->listGroups($tournamentid);
        foreach ($groups as $group) {
            $matches = $this->populateGroup($group->getId(), $doublematch);
            foreach ($matches as $match) {
                $match->setCategory($categoryList[$group->getPid()]);
                $match->setGroup($group);
                $matchPlanList[] = $match;
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
     * @param Integer $groupid The group to sort
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
    
    /**
     * Order teams in group by match results
     * @param Integer $groupid The group to sort
     * @return array A list of TeamStat objects ordered by match results and ordering
     */
    public function sortGroup($groupid) {
        $teams = $this->logic->listTeamsByGroup($groupid);
        $teamResults = $this->logic->getTeamResultsByGroup($groupid);
        $teamMap = $this->buildTeamMap($teams);
        $this->traverseMatches($teamMap, $teamResults);
        return $this->sortList($teamMap, $teamResults);
    }
    
    /**
     * Generate group results
     * @param array $teams List of teams assigned to a group
     * @param array $teamResults List of match results for a group
     * @return array A list of TeamStat objects for each team
     */
    public function generateStat($teams, $teamResults) {
        $teamMap = $this->buildTeamMap($teams);
        $this->traverseMatches($teamMap, $teamResults);
        return array_values($teamMap);
    }

    private function buildTeamMap($teams) {
        $teamMap = array();
        foreach ($teams as $team) {
            $stat = new TeamStat();
            $stat->id = $team->id;
            $stat->club = $team->club;
            $stat->name = $team->name;
            $stat->country = $team->country;
            $stat->group = $team->group;
            $teamMap[$team->id] = $stat;
        }
        return $teamMap;
    }

    private function traverseMatches($teamMap, $teamResults) {
        $relationMap = array();
        foreach ($teamResults as $matchRelation) {
            if (array_key_exists($matchRelation->getCid(), $teamMap)) {
                $relationMap[$matchRelation->getPid()][$matchRelation->getAwayteam()?'A':'H'] = $matchRelation;
            }
        }
        foreach ($relationMap as $matchResults) {
            // We need two match results for a match to judge
            if (count($matchResults) != 2) {
                // This condition will occour for results from teams outside the (tie) group - ignore
                continue;
            }
            // Knowing that we got both match results - check that we have a registration for both
            if ($this->isScoreValid($matchResults['H'], $matchResults['A'])) {
                // Update team status for both teams in the match
                $this->updateTeamStat($teamMap, $matchResults['H'], $matchResults['A']);
                $this->updateTeamStat($teamMap, $matchResults['A'], $matchResults['H']);
            }
        }
    }
    private function updateTeamStat($teamMap, MatchRelation $relA, MatchRelation $relB) {
        $stat = $teamMap[$relA->getCid()];
        $stat->matches++;
        $stat->points += $relA->getPoints();
        $stat->score += $relA->getScore();
        $stat->goals += $relB->getScore();
        $diff = $relA->getScore() - $relB->getScore();
        $stat->diff += $diff;
        if ($relA->getPoints() > $relB->getPoints()) {
            $stat->won++;
        }
        if ($stat->maxscore < $relA->getScore()) {
            $stat->maxscore = $relA->getScore();
        }
        if ($stat->maxdiff < $diff) {
            $stat->maxdiff = $diff;
        }
    }
    
    private function isScoreValid(MatchRelation $relA, MatchRelation $relB) {
        return $relA->getScorevalid() && $relB->getScorevalid();
    }
    
    private function sortList($teamsList, $teamResults) {
        // Build a tie list of teams with equal score
        $tieList = array();
        foreach ($teamsList as $stat) {
            $tieList[$stat->points][$stat->id] = $stat;
        }
        // Iterate the tie list and find tie score for teams with equal score
        foreach ($tieList as $tieTeamList) {
            // inspect teams with equal score
            if (count($tieTeamList) > 1) {
                // make a copy of TeamStat objects (only copy the id though)
                $teamMap = $this->copyStat($tieTeamList);
                // traverse match results and update statitistics
                $this->traverseMatches($teamMap, $teamResults);
                // finally update the tie score for the original TeamStat object
                $this->updateTieScore($teamsList, $teamMap);
            }
        }
        // sort the teams based on points and tie score - secondary net score and finally score
        usort($teamsList, $this->normal_sort_order);
        return $teamsList;
    }

    private function copyStat($tieTeamList) {
        $teamMap = array();
        foreach ($tieTeamList as $tieStat) {
            // Make a fresh copy of status object
            $stat = new TeamStat();
            $stat->id = $tieStat->id;
            $teamMap[$stat->id] = $stat;
        }
        // teamMap is now a copy of the team order in tieTeamList
        return $teamMap;
    }
    
    private function updateTieScore($teamsList, $teamMap) {
/*        
        // Sort local tie group using normal sort order
        usort($teamMap, $this->normal_sort_order);
        // Now - first team is winner and should get the highest tie score
        $tiescore = count($teamMap);
        // Build a sub tie list of teams with equal score
        $tieList = array();
        foreach ($teamMap as $stat) {
            $tieList[$stat->points][$stat->id] = $stat;
        }
        // Iterate the tie list and update the tie score
        foreach ($tieList as $tieTeamList) {
            // update teams with equal score with same tie score
            foreach ($tieTeamList as $stat) {
                $oStat = $teamsList[$stat->id];
                $oStat->tiepoints = $tiescore;
            }
            $tiescore--;
        }
 * 
 */
        foreach ($teamMap as $stat) {
            $oStat = $teamsList[$stat->id];
            // Might be so that some teams score equal in the tie group - however this is ignored
            $oStat->tiepoints = $stat->points;
        }
    }
}

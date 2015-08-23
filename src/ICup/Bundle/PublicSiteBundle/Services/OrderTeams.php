<?php
namespace ICup\Bundle\PublicSiteBundle\Services;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchRelation;
use ICup\Bundle\PublicSiteBundle\Entity\TeamInfo;
use ICup\Bundle\PublicSiteBundle\Entity\TeamStat;
use ICup\Bundle\PublicSiteBundle\Services\Doctrine\BusinessLogic;
use ICup\Bundle\PublicSiteBundle\Services\Doctrine\MatchSupport;
use Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OrderTeams
{
    /* @var $container ContainerInterface */
    protected $container;
    /* @var $logic BusinessLogic */
    protected $logic;
    /* @var $match MatchSupport */
    protected $match;
    /* @var $logger Logger */
    protected $logger;

    private $order_by_points;
    private $order_by_goals;

    public function __construct(ContainerInterface $container, Logger $logger)
    {
        $this->container = $container;
        $this->logic = $container->get('logic');
        $this->match = $container->get('match');
        $this->logger = $logger;
        $this->order_by_points =
            function (TeamStat $team1, TeamStat $team2) {
                $p = $team1->getPoints() - $team2->getPoints();
                $t = $team1->getTiepoints() - $team2->getTiepoints();
                $d = $team1->getDiff() - $team2->getDiff();
                $s = $team1->getScore() - $team2->getScore();
                if ($p == 0 && $t == 0 && $d == 0 && $s == 0) {
                    return 0;
                } elseif ($p < 0 || ($p == 0 && $t < 0) || ($p == 0 && $t == 0 && $d < 0) || ($p == 0 && $t == 0 && $d == 0 && $s < 0)) {
                    return 1;
                } else {
                    return -1;
                }
            };
        $this->order_by_goals =
            function (TeamStat $team1, TeamStat $team2) {
                if ($team1->getMaxscore() == $team2->getMaxscore()) {
                    return 0;
                }
                return $team1->getMaxscore() < $team2->getMaxscore() ? 1 : -1;
            };
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
     * Order teams in finals group by match results
     * Teams are decided by result or if no results are present the rank requirement is returned
     * @param Integer $groupid The group to sort
     * @return array A list of TeamStat objects ordered by match results and ordering
     */
    public function sortGroupFinals($groupid) {
        $results = $this->match->listMatchesByGroup($groupid);
        $teamMap = array();
        foreach ($results as $match) {
            $this->addTeam($match['home'], $groupid, $teamMap);
            $this->addTeam($match['away'], $groupid, $teamMap);
        }
        $teamResults = $this->logic->getTeamResultsByGroup($groupid);
        $this->traverseMatches($teamMap, $teamResults);
        return $this->sortList($teamMap, $teamResults);
    }

    /**
     * Order teams in group by match results
     * @param Integer $groupid The group to sort
     * @return array A list of TeamStat objects ordered by match results and ordering or null if group is not completed
     */
    public function sortCompletedGroup($groupid) {
        $teams = $this->logic->listTeamsByGroup($groupid);
        $teamResults = $this->logic->getTeamResultsByGroup($groupid);
        $teamMap = $this->buildTeamMap($teams);
        $groupCompleted = $this->traverseMatches($teamMap, $teamResults);
        return $groupCompleted ? $this->sortList($teamMap, $teamResults) : null;
    }

    private function buildTeamMap($teams) {
        $teamMap = array();
        /* @var $team TeamInfo */
        foreach ($teams as $team) {
            $stat = new TeamStat();
            $stat->setId($team->getId());
            $stat->setClub($team->getClub());
            $stat->setName($team->getName());
            $stat->setCountry($team->getCountry());
            $stat->setGroup($team->getGroup());
            $teamMap[$team->getId()] = $stat;
        }
        return $teamMap;
    }

    private function addTeam($team, $groupid, &$teamMap) {
        if ($team['id'] > 0) {
            $tid = $team['id'];
            $name = $team['name'];
        }
        else {
            $tid = -$team['qid'];
            $name = $team['rank'];
        }
        if (!array_key_exists($tid, $teamMap)) {
            $stat = new TeamStat();
            $stat->setId($tid);
            $stat->setClub($name);
            $stat->setName($name);
            $stat->setCountry($team['country']);
            $stat->setGroup($groupid);
            $teamMap[$stat->getId()] = $stat;
        }
    }

    private function traverseMatches($teamMap, $teamResults) {
        $groupCompleted = true;
        $relationMap = array();
        /* @var $matchRelation MatchRelation */
        foreach ($teamResults as $matchRelation) {
            if (isset($teamMap[$matchRelation->getTeam()->getId()])) {
                $relationMap[$matchRelation->getMatch()->getId()][$matchRelation->getAwayteam()?'A':'H'] = $matchRelation;
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
            else {
                $groupCompleted = false;
            }
        }
        return $groupCompleted;
    }

    private function updateTeamStat($teamMap, MatchRelation $relA, MatchRelation $relB) {
        /* @var $stat TeamStat */
        $stat = $teamMap[$relA->getTeam()->getId()];
        $stat->setMatches($stat->getMatches()+1);
        $stat->setPoints($stat->getPoints()+$relA->getPoints());
        $stat->setScore($stat->getScore()+$relA->getScore());
        $stat->setGoals($stat->getGoals()+$relB->getScore());
        $diff = $relA->getScore() - $relB->getScore();
        $stat->setDiff($stat->getDiff()+$diff);
        if ($relA->getPoints() > $relB->getPoints()) {
            $stat->setWon($stat->getWon()+1);
        }
        if ($stat->getMaxscore() < $relA->getScore()) {
            $stat->setMaxscore($relA->getScore());
        }
        if ($stat->getMaxdiff() < $diff) {
            $stat->setMaxdiff($diff);
        }
    }
    
    private function isScoreValid(MatchRelation $relA, MatchRelation $relB) {
        return $relA->getScorevalid() && $relB->getScorevalid();
    }
    
    private function sortList($teamsList, $teamResults) {
        // Build a tie list of teams with equal score
        $tieList = array();
        /* @var $stat TeamStat */
        foreach ($teamsList as $stat) {
            $tieList[$stat->getPoints()][$stat->getId()] = $stat;
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
        usort($teamsList, $this->order_by_points);
        return $teamsList;
    }

    private function copyStat($tieTeamList) {
        $teamMap = array();
        /* @var $tieStat TeamStat */
        foreach ($tieTeamList as $tieStat) {
            // Make a fresh copy of status object
            $stat = new TeamStat();
            $stat->setId($tieStat->getId());
            $teamMap[$stat->getId()] = $stat;
        }
        // teamMap is now a copy of the team order in tieTeamList
        return $teamMap;
    }
    
    private function updateTieScore($teamsList, $teamMap) {
/*        
        // Sort local tie group using normal sort order
        usort($teamMap, $this->order_by_points);
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
        /* @var $stat TeamStat */
        foreach ($teamMap as $stat) {
            /* @var $oStat TeamStat */
            $oStat = $teamsList[$stat->getId()];
            // Might be so that some teams score equal in the tie group - however this is ignored
            $oStat->setTiepoints($stat->getPoints());
        }
    }
}

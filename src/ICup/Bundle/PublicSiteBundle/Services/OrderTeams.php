<?php
namespace ICup\Bundle\PublicSiteBundle\Services;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\TournamentOption;
use ICup\Bundle\PublicSiteBundle\Entity\TeamInfo;
use ICup\Bundle\PublicSiteBundle\Entity\TeamStat;
use ICup\Bundle\PublicSiteBundle\Services\Doctrine\BusinessLogic;
use ICup\Bundle\PublicSiteBundle\Services\Doctrine\Entity;
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
    /* @var $entity Entity */
    protected $entity;

    public function __construct(ContainerInterface $container, Logger $logger)
    {
        $this->container = $container;
        $this->logic = $container->get('logic');
        $this->match = $container->get('match');
        $this->entity = $container->get('entity');
        $this->logger = $logger;
    }

    /**
     * Order teams in group by match results
     * @param Integer $groupid The group to sort
     * @return array A list of TeamStat objects ordered by match results and ordering
     */
    public function sortGroup($groupid) {
        $tournament = $this->entity->getGroupById($groupid)->getCategory()->getTournament();
        $teams = $this->logic->listTeamsByGroup($groupid);
        $teamResults = $this->logic->getTeamResultsByGroup($groupid);
        $teamMap = $this->buildTeamMap($teams);
        $this->traverseMatches($teamMap, $teamResults);
        return $this->sortList($teamMap, $teamResults, $tournament);
    }

    /**
     * Order teams in finals group by match results
     * Teams are decided by result or if no results are present the rank requirement is returned
     * @param Integer $groupid The group to sort
     * @return array A list of TeamStat objects ordered by match results and ordering
     */
    public function sortGroupFinals($groupid) {
        $tournament = $this->entity->getGroupById($groupid)->getCategory()->getTournament();
        $results = $this->match->listMatchesByGroup($groupid);
        $teamMap = array();
        foreach ($results as $match) {
            $this->addTeam($match['home'], $groupid, $teamMap);
            $this->addTeam($match['away'], $groupid, $teamMap);
        }
        $teamResults = $this->logic->getTeamResultsByGroup($groupid);
        $this->traverseMatches($teamMap, $teamResults);
        return $this->sortList($teamMap, $teamResults, $tournament);
    }

    /**
     * Order teams in group by match results
     * @param Integer $groupid The group to sort
     * @return array A list of TeamStat objects ordered by match results and ordering or null if group is not completed
     */
    public function sortCompletedGroup($groupid) {
        $tournament = $this->entity->getGroupById($groupid)->getCategory()->getTournament();
        $teams = $this->logic->listTeamsByGroup($groupid);
        $teamResults = $this->logic->getTeamResultsByGroup($groupid);
        $teamMap = $this->buildTeamMap($teams);
        $groupCompleted = $this->traverseMatches($teamMap, $teamResults);
        return $groupCompleted ? $this->sortList($teamMap, $teamResults, $tournament) : null;
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
    
    private function sortList($teamsList, $teamResults, Tournament $tournament) {
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
        usort($teamsList, $this->sortingFunction($tournament));
        return $teamsList;
    }

    private function sortingFunction(Tournament $tournament) {
        $stack = array();
        foreach ($tournament->getOption()->getOrder() as $key) {
            if ($key == TournamentOption::MATCH_POINTS) {
                $stack[] = function (TeamStat $team1, TeamStat $team2) {
                    return $team2->getPoints() - $team1->getPoints();
                };
            }
            else if ($key == TournamentOption::TIE_SCORE_DIFF) {
                $stack[] = function (TeamStat $team1, TeamStat $team2) {
                    return $team2->getTiepoints() - $team1->getTiepoints();
                };
            }
            else if ($key == TournamentOption::MATCH_SCORE_DIFF) {
                $stack[] = function (TeamStat $team1, TeamStat $team2) {
                    return $team2->getDiff() - $team1->getDiff();
                };
            }
            else if ($key == TournamentOption::MATCH_SCORE) {
                $stack[] = function (TeamStat $team1, TeamStat $team2) {
                    return $team2->getScore() - $team1->getScore();
                };
            }
            else if ($key == TournamentOption::MAX_GOALS) {
                $stack[] = function (TeamStat $team1, TeamStat $team2) {
                    return $team2->getMaxscore() - $team1->getMaxscore();
                };
            }
        }
        return function (TeamStat $team1, TeamStat $team2) use ($stack) {
            foreach ($stack as $criteria) {
                $norm = min(1, max(-1, $criteria($team1, $team2)));
                if ($norm != 0) {
                    return $norm;
                }
            }
            return 0;
        };
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
        /* @var $stat TeamStat */
        foreach ($teamMap as $stat) {
            /* @var $oStat TeamStat */
            $oStat = $teamsList[$stat->getId()];
            // Might be so that some teams score equal in the tie group - so we must consider goal difference as well
            // We do that by scaling the points insaniously
            $oStat->setTiepoints($stat->getPoints()*1000+$stat->getDiff());
        }
    }
}

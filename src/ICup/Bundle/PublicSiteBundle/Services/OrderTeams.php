<?php
namespace ICup\Bundle\PublicSiteBundle\Services;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchRelation;
use ICup\Bundle\PublicSiteBundle\Entity\TeamStat;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class OrderTeams
{
    public function sortGroup(Controller $container, $group) {
        $em = $container->getDoctrine()->getManager();
        $qb = $em->createQuery("select t.id,t.name,t.division,c.country ".
                               "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\GroupOrder o, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team t, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club c ".
                               "where o.pid=:group and ".
                                     "o.cid=t.id and ".
                                     "t.pid=c.id ".
                               "order by o.id");
        $qb->setParameter('group', $group);
        $teams = $qb->getResult();

        $qbr = $em->createQuery("select r ".
                                "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchRelation r, ".
                                     "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match m ".
                                "where r.pid=m.id and m.pid=:group ".
                                "order by r.pid");
        $qbr->setParameter('group', $group);
        $teamResults = $qbr->getResult();

        $teamsList = $this->generateStat($teams, $teamResults, $group);
        return $this->sortTeams($teamsList);
    }

    public function generateStat($teams, $teamResults, $groupId = 0) {

        $teamMap = array();
        foreach ($teams as $team) {
            $stat = new TeamStat();
            $stat->id = $team['id'];
            $stat->name = $this->teamName($team['name'], $team['division']);
            $stat->country = $team['country'];
            if (key_exists('grp', $team)) {
                $stat->group = $team['grp'];
            }
            else {
                $stat->group = $groupId;
            }
            $teamMap[$team['id']] = $stat;
        }

        $rel = 0;
        $relA = null;
        foreach ($teamResults as $matchRelation) {
            if ($matchRelation->getPid() == $rel) {
                $relB = $matchRelation;
                $valid = $this->isScoreValid($relA, $relB);
                if ($valid) {
                    $stat = $teamMap[$relA->getCid()];
                    $stat->matches++;
                    $stat->points += $relA->getPoints();
                    $stat->score += $relA->getScore();
                    $stat->goals += $relB->getScore();
                    $diff = $relA->getScore() - $relB->getScore();
                    $stat->diff += $diff;
                    if ($relA->getPoints() > 1) {
                        $stat->won++;
                    }
                    if ($stat->maxscore < $relA->getScore()) {
                        $stat->maxscore = $relA->getScore();
                    }
                    if ($stat->maxdiff < $diff) {
                        $stat->maxdiff = $diff;
                    }

                    $stat = $teamMap[$relB->getCid()];
                    $stat->matches++;
                    $stat->points += $relB->getPoints();
                    $stat->score += $relB->getScore();
                    $stat->goals += $relA->getScore();
                    $diff = $relB->getScore() - $relA->getScore();
                    $stat->diff += $diff;
                    if ($relB->getPoints() > 1) {
                        $stat->won++;
                    }
                    if ($stat->maxscore < $relB->getScore()) {
                        $stat->maxscore = $relB->getScore();
                    }
                    if ($stat->maxdiff < $diff) {
                        $stat->maxdiff = $diff;
                    }
                }
            }
            else {
                $relA = $matchRelation;
                $rel = $matchRelation->getPid();
            }
        }

        $teamsList = array();
        foreach ($teamMap as $stat) {
            $teamsList[] = $stat;
        }

        return $teamsList;
    }

    public function sortTeams($teamsList) {
        $reorder = true;
        while ($reorder) {
            $reorder = false;
            for ($index = 0; $index < count($teamsList)-1; $index++) {
                if ($this->reorder($teamsList[$index], $teamsList[$index+1])) {
                    $tmp = $teamsList[$index+1];
                    $teamsList[$index+1] = $teamsList[$index];
                    $teamsList[$index] = $tmp;
                    $reorder = true;
                }
            }
        }
        return $teamsList;
    }
    
    public function sortTeamsByMostGoals($teamsList) {
        $reorder = true;
        while ($reorder) {
            $reorder = false;
            for ($index = 0; $index < count($teamsList)-1; $index++) {
                if ($this->reorderByMostGoals($teamsList[$index], $teamsList[$index+1])) {
                    $tmp = $teamsList[$index+1];
                    $teamsList[$index+1] = $teamsList[$index];
                    $teamsList[$index] = $tmp;
                    $reorder = true;
                }
            }
        }
        return $teamsList;
    }
    
    public function teamName($name, $division) {
        $teamName = $name;
        if ($division != '') {
            $teamName.= ' "'.$division.'"';
        }
        return $teamName;
    }
    
    public function isScoreValid(MatchRelation $relA, MatchRelation $relB) {
        return $relA->getScorevalid() && $relB->getScorevalid();
    }
    
    private function reorder(TeamStat $team1, TeamStat $team2) {
        $p = $team1->points - $team2->points;
        $d = $team1->diff - $team2->diff;
        $s = $team1->score - $team2->score;
        return $p < 0 || ($p==0 && $d < 0) || ($p==0 && $d==0 && $s < 0);
    }
    
    private function reorderByMostGoals(TeamStat $team1, TeamStat $team2) {
        $p = $team1->maxscore - $team2->maxscore;
        return $p < 0;
    }
}

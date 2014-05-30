<?php
namespace ICup\Bundle\PublicSiteBundle\Services\Order;

use ICup\Bundle\PublicSiteBundle\Entity\TeamStat;

class OrderByGoals
{
    static function reorder(TeamStat $team1, TeamStat $team2) {
        if ($team1->maxscore == $team2->maxscore) {
            return 0;
        }
        return $team1->maxscore < $team2->maxscore ? 1 : -1; 
    }
}

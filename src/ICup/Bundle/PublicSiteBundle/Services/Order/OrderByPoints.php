<?php
namespace ICup\Bundle\PublicSiteBundle\Services\Order;

use ICup\Bundle\PublicSiteBundle\Entity\TeamStat;

class OrderByPoints
{
    static function reorder(TeamStat $team1, TeamStat $team2) {
        $p = $team1->points - $team2->points;
        $t = $team1->tiepoints - $team2->tiepoints;
        $d = $team1->diff - $team2->diff;
        $s = $team1->score - $team2->score;
        if ($p==0 && $t==0 && $d==0 && $s==0) {
            return 0;
        }
        elseif ($p < 0 || ($p==0 && $t < 0) || ($p==0 && $t==0 && $d < 0) || ($p==0 && $t==0 && $d==0 && $s < 0)) {
            return 1;
        }
        else {
            return -1;
        }
    }
}

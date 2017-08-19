<?php
/**
 * Created by PhpStorm.
 * User: mm
 * Date: 25/07/2017
 * Time: 10.17
 */

namespace APIBundle\Entity\Wrapper\Doctrine;

use APIBundle\Entity\Wrapper\ObjectWrapper;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\QMatchRelation;

class MatchRelationWrapper extends ObjectWrapper
{
    public function getData($matchRelation) {
        if ($matchRelation instanceof MatchRelation) {
            /* @var $matchRelation MatchRelation */
            return array(
                "entity" => "MatchRelation",
                "relation" => $matchRelation->getAwayteam() ? "away" : "home",
                "team" => new TeamWrapper($matchRelation->getTeam()),
                "result" => $matchRelation->getScorevalid() ? array("score" => $matchRelation->getScore(), "points" => $matchRelation->getPoints(), "valid" => true) : array("score" => '', "points" => '', "valid" => false)
            );
        }
        else if ($matchRelation instanceof QMatchRelation) {
            /* @var $matchRelation QMatchRelation */
            return array(
                "entity" => "QMatchRelation",
                "relation" => $matchRelation->getAwayteam() ? "away" : "home",
                "qualified" => array(
                    "group" => new GroupWrapper($matchRelation->getGroup()),
                    "rank" => $matchRelation->getRank())
            );
        }
        return null;
    }
}
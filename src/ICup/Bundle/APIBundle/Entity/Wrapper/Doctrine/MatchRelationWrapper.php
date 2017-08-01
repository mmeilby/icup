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
    public function getData($siteRelation) {
        if ($siteRelation instanceof MatchRelation) {
            /* @var $siteRelation MatchRelation */
            return array(
                "entity" => "MatchRelation",
                "relation" => $siteRelation->getAwayteam() ? "away" : "home",
                "team" => new TeamWrapper($siteRelation->getTeam()),
                "result" => $siteRelation->getScorevalid() ? array("score" => $siteRelation->getScore(), "points" => $siteRelation->getPoints(), "valid" => true) : array("score" => '', "points" => '', "valid" => false)
            );
        }
        else if ($siteRelation instanceof QMatchRelation) {
            /* @var $siteRelation QMatchRelation */
            return array(
                "entity" => "QMatchRelation",
                "relation" => $siteRelation->getAwayteam() ? "away" : "home",
                "qualified" => array(
                    "group" => new GroupWrapper($siteRelation->getGroup()),
                    "rank" => $siteRelation->getRank())
            );
        }
        return null;
    }
}
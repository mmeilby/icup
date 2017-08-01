<?php
/**
 * Created by PhpStorm.
 * User: mm
 * Date: 25/07/2017
 * Time: 10.17
 */

namespace APIBundle\Entity\Wrapper\Doctrine;

use APIBundle\Entity\Wrapper\ObjectWrapper;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\QMatchRelation;

class MatchWrapper extends ObjectWrapper
{
    public function getData($site) {
        if ($site instanceof Match) {
            /* @var $site Match */
            if ($site->getKey() == null) {
                $site->setKey(strtoupper(uniqid()));
            }
            $matchtype = "Match";
            $qhome = array("id" => 0);
            $qaway = array("id" => 0);
            foreach ($site->getQMatchRelations() as $qmatchRelation) {
                /* @var $qmatchRelation QMatchRelation */
                if ($qmatchRelation->getAwayteam()) {
                    $qaway = new MatchRelationWrapper($qmatchRelation);
                }
                else {
                    $qhome = new MatchRelationWrapper($qmatchRelation);
                }
                $matchtype ="QualifyingMatch";
            }
            $home = array("id" => 0);
            $away = array("id" => 0);
            foreach ($site->getMatchRelations() as $matchRelation) {
                /* @var $matchRelation MatchRelation */
                if ($matchRelation->getAwayteam()) {
                    $away = new MatchRelationWrapper($matchRelation);
                }
                else {
                    $home = new MatchRelationWrapper($matchRelation);
                }

            }
            return array(
                "entity" => "Match",
                "key" => $site->getKey(),
                'matchno' => $site->getMatchno(),
                "matchtype" => $matchtype,
                'date' => Date::jsonDateSerialize($site->getDate()),
                'time' => Date::jsonTimeSerialize($site->getTime()),
                'category' => new CategoryWrapper($site->getGroup()->getCategory()),
                'group' => new GroupWrapper($site->getGroup()),
                'venue' => new PlaygroundWrapper($site->getPlayground()),
                'home' => array("qualifiedrelation" => $qhome, "matchrelation" => $home),
                'away' => array("qualifiedrelation" => $qaway, "matchrelation" => $away)
            );
        }
        return null;
    }
}
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
    public function getData($match) {
        if ($match instanceof Match) {
            /* @var $match Match */
            if ($match->getKey() == null) {
                $match->setKey(strtoupper(uniqid()));
            }
            $matchtype = "Match";
            $qhome = array("entity" => "Void");
            $qaway = array("entity" => "Void");
            foreach ($match->getQMatchRelations() as $qmatchRelation) {
                /* @var $qmatchRelation QMatchRelation */
                if ($qmatchRelation->getAwayteam()) {
                    $qaway = new MatchRelationWrapper($qmatchRelation);
                }
                else {
                    $qhome = new MatchRelationWrapper($qmatchRelation);
                }
                $matchtype ="QualifyingMatch";
            }
            $home = array("entity" => "Void");
            $away = array("entity" => "Void");
            foreach ($match->getMatchRelations() as $matchRelation) {
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
                "key" => $match->getKey(),
                'matchno' => $match->getMatchno(),
                "matchtype" => $matchtype,
                'date' => Date::jsonDateSerialize($match->getDate()),
                'time' => Date::jsonTimeSerialize($match->getTime()),
                'category' => new CategoryWrapper($match->getGroup()->getCategory()),
                'group' => new GroupWrapper($match->getGroup()),
                'venue' => new PlaygroundWrapper($match->getPlayground()),
                'home' => array("qualifiedrelation" => $qhome, "matchrelation" => $home),
                'away' => array("qualifiedrelation" => $qaway, "matchrelation" => $away)
            );
        }
        return null;
    }
}
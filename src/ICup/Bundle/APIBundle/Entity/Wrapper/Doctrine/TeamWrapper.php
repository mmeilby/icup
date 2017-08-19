<?php
/**
 * Created by PhpStorm.
 * User: mm
 * Date: 25/07/2017
 * Time: 10.17
 */

namespace APIBundle\Entity\Wrapper\Doctrine;

use APIBundle\Entity\Wrapper\ObjectWrapper;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team;

class TeamWrapper extends ObjectWrapper
{
    public function getData($team) {
        if ($team instanceof Team) {
            /* @var $team Team */
            if ($team->getKey() == null) {
                $team->setKey(strtoupper(uniqid()));
            }
            return array(
                "entity" => "Team",
                "key" => $team->getKey(),
                "name" => $team->getName(),
                "teamname" => $team->getTeamName(),
                "club" => new ClubWrapper($team->getClub()),
                "color" => $team->getColor(),
                "division" => $team->getDivision(),
                "vacant" => $team->isVacant(),
                "country_code" => $team->getClub()->getCountryCode(),
                "flag" => $team->getClub()->getCountry()->getFlag()
            );
        }
        return null;
    }
}
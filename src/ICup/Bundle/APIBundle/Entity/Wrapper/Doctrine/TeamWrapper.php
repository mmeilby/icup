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
    public function getData($site) {
        if ($site instanceof Team) {
            /* @var $site Team */
            if ($site->getKey() == null) {
                $site->setKey(strtoupper(uniqid()));
            }
            return array(
                "entity" => "Team",
                "key" => $site->getKey(),
                "name" => $site->getName(),
                "teamname" => $site->getTeamName(),
                "club" => new ClubWrapper($site->getClub()),
                "color" => $site->getColor(),
                "division" => $site->getDivision(),
                "vacant" => $site->isVacant(),
                "country_code" => $site->getClub()->getCountryCode(),
                "flag" => $site->getClub()->getCountry()->getFlag()
            );
        }
        return null;
    }
}
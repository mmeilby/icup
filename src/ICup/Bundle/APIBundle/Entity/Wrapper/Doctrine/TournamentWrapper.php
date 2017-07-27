<?php
/**
 * Created by PhpStorm.
 * User: mm
 * Date: 25/07/2017
 * Time: 10.17
 */

namespace APIBundle\Entity\Wrapper\Doctrine;

use APIBundle\Entity\Wrapper\ObjectWrapper;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;

class TournamentWrapper extends ObjectWrapper
{
    function getData($tournament) {
        if ($tournament instanceof Tournament) {
            /* @var $tournament Tournament */
            return array(
                "entity" => "Tournament",
                "host" => new HostWrapper($tournament->getHost()),
                "key" => $tournament->getKey(),
                "name" => $tournament->getName(),
                "edition" => $tournament->getEdition(),
                "description" => $tournament->getDescription(),
            );
        }
        return null;
    }
}
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
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Event;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use DateTime;
use DateInterval;

class TournamentWrapper extends ObjectWrapper
{
    function getData($tournament) {
        if ($tournament instanceof Tournament) {
            $start = new DateTime();
            $end = new DateTime();
            /* @var $tournament Tournament */
            foreach ($tournament->getEvents() as $event) {
                /* @var $event Event */
                if ($event->getEvent() == Event::$MATCH_START) {
                    $start = $event->getSchedule();
                }
                if ($event->getEvent() == Event::$MATCH_STOP) {
                    $end = $event->getSchedule();
                    $end = date_sub($end, new DateInterval("P1D"));
                }
            }
            return array(
                "entity" => "Tournament",
                "host" => new HostWrapper($tournament->getHost()),
                "key" => $tournament->getKey(),
                "name" => $tournament->getName(),
                "edition" => $tournament->getEdition(),
                "description" => $tournament->getDescription(),
                "matchcalendar" => array(
                    "start" => Date::jsonDateSerialize(Date::getDate($start)),
                    "end" => Date::jsonDateSerialize(Date::getDate($end))
                )
            );
        }
        return null;
    }
}
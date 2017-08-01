<?php
/**
 * Created by PhpStorm.
 * User: mm
 * Date: 25/07/2017
 * Time: 10.17
 */

namespace APIBundle\Entity\Wrapper\Doctrine;

use APIBundle\Entity\Wrapper\ObjectWrapper;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Timeslot;

class TimeslotWrapper extends ObjectWrapper
{
    public function getData($timeslot) {
        if ($timeslot instanceof Timeslot) {
            /* @var $site Timeslot */
            return array(
                "entity" => "Timeslot",
                "name" => $timeslot->getName()
            );
        }
        return null;
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: mm
 * Date: 25/07/2017
 * Time: 10.17
 */

namespace APIBundle\Entity\Wrapper\Doctrine;

use APIBundle\Entity\Wrapper\ObjectWrapper;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Playground;

class PlaygroundWrapper extends ObjectWrapper
{
    public function getData($venue) {
        if ($venue instanceof Playground) {
            /* @var $site Playground */
            if ($venue->getKey() == null) {
                $venue->setKey(strtoupper(uniqid()));
            }
            $location = explode(",", $venue->getLocation());
            if (count($location) > 1) {
                $latitude = $location[0];
                $longitude = $location[1];
            }
            else {
                $latitude = "";
                $longitude = "";
            }
            return array(
                "entity" => "Venue",
                "key" => $venue->getKey(),
                "no" => $venue->getNo(),
                "name" => $venue->getName(),
                "location" => array("latitude" => $latitude, "longitude" => $longitude),
                "site" => $venue->getSite()->getName()
            );
        }
        return null;
    }
}
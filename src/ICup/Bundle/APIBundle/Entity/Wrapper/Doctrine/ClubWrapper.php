<?php
/**
 * Created by PhpStorm.
 * User: mm
 * Date: 25/07/2017
 * Time: 10.17
 */

namespace APIBundle\Entity\Wrapper\Doctrine;

use APIBundle\Entity\Wrapper\ObjectWrapper;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club;

class ClubWrapper extends ObjectWrapper
{
    public function getData($club) {
        if ($club instanceof Club) {
            /* @var $club Club */
            if ($club->getKey() == null) {
                $club->setKey(strtoupper(uniqid()));
            }
            return array(
                "entity" => "Club",
                "key" => $club->getKey(),
                "name" => $club->getName(),
                "address" => $club->getAddress(),
                "city" => $club->getCity(),
                "country_code" => $club->getCountryCode(),
                "flag" => $club->getCountry()->getFlag()
            );
        }
        return null;
    }
}
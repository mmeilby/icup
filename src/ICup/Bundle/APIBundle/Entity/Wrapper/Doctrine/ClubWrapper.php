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
    public function getData($site) {
        if ($site instanceof Club) {
            /* @var $site Club */
            if ($site->getKey() == null) {
                $site->setKey(strtoupper(uniqid()));
            }
            return array(
                "entity" => "Club",
                "key" => $site->getKey(),
                "name" => $site->getName(),
                "address" => $site->getAddress(),
                "city" => $site->getCity(),
                "country_code" => $site->getCountryCode(),
                "flag" => $site->getCountry()->getFlag()
            );
        }
        return null;
    }
}
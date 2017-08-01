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
    public function getData($site) {
        if ($site instanceof Playground) {
            /* @var $site Playground */
            if ($site->getKey() == null) {
                $site->setKey(strtoupper(uniqid()));
            }
            return array(
                "entity" => "Venue",
                "key" => $site->getKey(),
                "no" => $site->getNo(),
                "name" => $site->getName(),
                "location" => $site->getLocation(),
                "site" => $site->getSite()->getName()
            );
        }
        return null;
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: mm
 * Date: 25/07/2017
 * Time: 10.17
 */

namespace APIBundle\Entity\Wrapper\Doctrine;

use APIBundle\Entity\Wrapper\ObjectWrapper;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Host;

class HostWrapper extends ObjectWrapper
{
    function getData($host) {
        if ($host instanceof Host) {
            /* @var $host Host */
            return array(
                "entity" => "Host",
                "name" => $host->getName(),
                "alias" => $host->getAlias(),
                "domain" => $host->getDomain(),
            );
        }
        return null;
    }
}
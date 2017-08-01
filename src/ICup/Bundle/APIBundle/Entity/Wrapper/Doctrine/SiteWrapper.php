<?php
/**
 * Created by PhpStorm.
 * User: mm
 * Date: 25/07/2017
 * Time: 10.17
 */

namespace APIBundle\Entity\Wrapper\Doctrine;

use APIBundle\Entity\Wrapper\ObjectWrapper;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Site;

class SiteWrapper extends ObjectWrapper
{
    public function getData($site) {
        if ($site instanceof Site) {
            /* @var $site Site */
            return array(
                "entity" => "Site",
                "name" => $site->getName(),
                "venues" => new PlaygroundWrapper($site->getPlaygrounds()->getValues())
            );
        }
        return null;
    }
}
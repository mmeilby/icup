<?php
/**
 * Created by PhpStorm.
 * User: mm
 * Date: 25/07/2017
 * Time: 10.17
 */

namespace APIBundle\Entity\Wrapper\Doctrine;

use APIBundle\Entity\Wrapper\ObjectWrapper;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;

class GroupWrapper extends ObjectWrapper
{
    function getData($group) {
        if ($group instanceof Group) {
            /* @var $group Group */
            if ($group->getKey() == null) {
                $group->setKey(strtoupper(uniqid()));
            }
            return array(
                "entity" => "Group",
                "category" => new CategoryWrapper($group->getCategory()),
                "key" => $group->getKey(),
                "name" => $group->getName(),
                "classification" => $group->getClassification(),
            );
        }
        return null;
    }
}
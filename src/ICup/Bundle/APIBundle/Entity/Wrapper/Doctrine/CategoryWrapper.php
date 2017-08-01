<?php
/**
 * Created by PhpStorm.
 * User: mm
 * Date: 25/07/2017
 * Time: 10.17
 */

namespace APIBundle\Entity\Wrapper\Doctrine;

use APIBundle\Entity\Wrapper\ObjectWrapper;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;

class CategoryWrapper extends ObjectWrapper
{
    public function getData($site) {
        if ($site instanceof Category) {
            /* @var $site Category */
            if ($site->getKey() == null) {
                $site->setKey(strtoupper(uniqid()));
            }
            return array(
                "entity" => "Category",
                "tournament" => new TournamentWrapper($site->getTournament()),
                "key" => $site->getKey(),
                "name" => $site->getName(),
                "gender" => $site->getGender(),
                "classification" => $site->getClassification(),
                "age" => $site->getAge(),
            );
        }
        return null;
    }
}
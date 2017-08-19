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
    public function getData($category) {
        if ($category instanceof Category) {
            /* @var $category Category */
            if ($category->getKey() == null) {
                $category->setKey(strtoupper(uniqid()));
            }
            return array(
                "entity" => "Category",
                "tournament" => new TournamentWrapper($category->getTournament()),
                "key" => $category->getKey(),
                "name" => $category->getName(),
                "gender" => $category->getGender(),
                "classification" => $category->getClassification(),
                "age" => $category->getAge(),
            );
        }
        return null;
    }
}
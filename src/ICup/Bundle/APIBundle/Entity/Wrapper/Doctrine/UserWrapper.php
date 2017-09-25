<?php
/**
 * Created by PhpStorm.
 * User: mm
 * Date: 25/07/2017
 * Time: 10.17
 */

namespace APIBundle\Entity\Wrapper\Doctrine;

use APIBundle\Entity\Wrapper\ObjectWrapper;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;

class UserWrapper extends ObjectWrapper
{
    function getData($user) {
        if ($user instanceof User) {
            /* @var $user User */
            return array(
                "entity" => "User",
                "name" => $user->getName(),
                "role" => $user->isAdmin() ? "admin" : ($user->isEditor() ? "editor" : "user")
            );
        }
        return null;
    }
}
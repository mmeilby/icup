<?php
/**
 * Created by PhpStorm.
 * User: mm
 * Date: 25/07/2017
 * Time: 10.17
 */

namespace APIBundle\Entity\Wrapper\Doctrine;

use APIBundle\Entity\Wrapper\ObjectWrapper;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Enrollment;

class EnrollmentWrapper extends ObjectWrapper
{
    public function getData($enrollment) {
        if ($enrollment instanceof Enrollment) {
            /* @var $enrollment Enrollment */
            return array(
                "entity" => "Enrollment",
                "date" => Date::jsonDateSerialize($enrollment->getDate()),
                "category" => new CategoryWrapper($enrollment->getCategory()),
                "team" => new TeamWrapper($enrollment->getTeam())
            );
        }
        return null;
    }
}
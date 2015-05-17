<?php
/**
 * Created by PhpStorm.
 * User: mm
 * Date: 12/05/15
 * Time: 18.09
 */

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use DateTime;

class Date {
    private static $db_date_format = "Ymd";
    private static $db_time_format = "Hi";

    public static function getDateTime($dbdate, $dbtime = "1200") {
        return DateTime::createFromFormat(Date::$db_date_format.'-'.Date::$db_time_format, $dbdate.'-'.$dbtime);
    }

    public static function getDate(DateTime $datetime) {
        return $datetime->format(Date::$db_date_format);
    }

    public static function getTime(DateTime $datetime) {
        return $datetime->format(Date::$db_time_format);
    }
}
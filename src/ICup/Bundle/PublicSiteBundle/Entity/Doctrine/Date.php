<?php
/**
 * Created by PhpStorm.
 * User: mm
 * Date: 12/05/15
 * Time: 18.09
 */

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use DateInterval;
use DateTime;

class Date {
    public static $db_date_format = "Ymd";
    public static $db_time_format = "Hi";

    public static function getDateTime($dbdate, $dbtime = "1200") {
        return DateTime::createFromFormat(Date::$db_date_format.'-'.Date::$db_time_format, $dbdate.'-'.$dbtime);
    }

    public static function getDate(DateTime $datetime) {
        return $datetime->format(Date::$db_date_format);
    }

    public static function getTime(DateTime $datetime) {
        return $datetime->format(Date::$db_time_format);
    }

    public static function addTime(DateTime $datetime, $ti) {
        $datetime->add(new DateInterval('PT'.$ti.'M'));
        return $datetime;
    }

    public static function jsonDateSerialize($dbdate) {
        return array(
            'raw' => $dbdate,
            'js' => $dbdate ? date_format(self::getDateTime($dbdate), "m/d/Y") : '',
            'ts' => $dbdate ? date_format(self::getDateTime($dbdate), "Y-m-d") : ''
        );
    }

    public static function jsonTimeSerialize($dbtime) {
        $dbdate = "20100101";
        return array(
            'raw' => $dbtime,
            'js' => $dbtime ? date_format(self::getDateTime($dbdate, $dbtime), "H:i") : '',
            'ts' => $dbtime ? date_format(self::getDateTime($dbdate, $dbtime), "H:i") : ''
        );
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: mm
 * Date: 25/07/2017
 * Time: 10.17
 */

namespace APIBundle\Entity\Wrapper;

use JsonSerializable;


class ObjectWrapper implements JsonSerializable, WrapperInterface
{
    private $isArray;
    private $objects;

    /**
     * TournamentWrapper constructor.
     * @param $objects mixed array or object to wrap for JSON serialization
     */
    public function __construct($objects) {
        $this->isArray = is_array($objects);
        $this->objects = $this->isArray ? $objects : array($objects);
    }

    /**
     * (PHP 5 &gt;= 5.4.0)<br/>
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     */
    public function jsonSerialize() {
        $results = array();
        foreach ($this->objects as $object) {
            $result = $this->getData($object);
            if ($result) {
                $results[] = $result;
            }
        }
        return $this->isArray ? $results : reset($results);
    }

    public function getData($object) {
        return null;
    }
}
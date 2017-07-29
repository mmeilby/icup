<?php
/**
 * Created by PhpStorm.
 * User: mm
 * Date: 16/07/2017
 * Time: 15.25
 */

namespace APIBundle\Entity;

use JsonSerializable;

class Error implements JsonSerializable
{
    /**
     * @var String $errorID
     * Error id string indicating the type
     * Ex. ObjectNotFound etc.
     */
    protected $errorID;

    /**
     * @var String $errorMessage
     * Readable error message
     * Ex. "There is no tournament with the id 729-110-2."
     */
    protected $errorMessage;

    /**
     * @return String
     */
    public function getErrorID() {
        return $this->errorID;
    }

    /**
     * @param String $errorID
     * @return Error
     */
    public function setErrorID($errorID) {
        $this->errorID = $errorID;
        return $this;
    }

    /**
     * @return String
     */
    public function getErrorMessage() {
        return $this->errorMessage;
    }

    /**
     * @param String $errorMessage
     * @return Error
     */
    public function setErrorMessage($errorMessage) {
        $this->errorMessage = $errorMessage;
        return $this;
    }

    public function __toString() {
        return $this->getErrorID().": ".$this->getErrorMessage();
    }

    /**
     * (PHP 5 &gt;= 5.4.0)<br/>
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     */
    function jsonSerialize() {
        return array(
            "entity" => "Error",
            "key" => $this->getErrorID(),
            "message" => $this->getErrorMessage()
        );
    }
}
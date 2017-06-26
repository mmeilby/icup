<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * Club entity
 * This is one of the three basic entities: Hosts, Users and Clubs
 * A club is a top level entity that can exist with several Hosts. Only one club can use a specific name in each country.
 *
 * @ORM\Table(name="enrollmentgendetails")
 * @ORM\Entity
 */
class EnrollmentGenDetail implements JsonSerializable
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var EnrollmentDetail $enrollment
     * Relation to EnrollmentDetail
     * @ORM\ManyToOne(targetEntity="EnrollmentDetail", inversedBy="details")
     * @ORM\JoinColumn(name="pid", referencedColumnName="id")
     */
    protected $enrollment;

    /**
     * @var string $key
     * Name describing the detail for the enrollment
     * @ORM\Column(name="key", type="string", length=50, nullable=false)
     */
    protected $key;

    /**
     * @var string $value
     * The detail value for the enrollment
     * @ORM\Column(name="value", type="string", length=50, nullable=true)
     */
    protected $value;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return EnrollmentDetail
     */
    public function getEnrollment() {
        return $this->enrollment;
    }

    /**
     * @param EnrollmentDetail $enrollment
     * @return EnrollmentGenDetail
     */
    public function setEnrollment($enrollment) {
        $this->enrollment = $enrollment;
        return $this;
    }

    /**
     * @return string
     */
    public function getKey() {
        return $this->key;
    }

    /**
     * @param string $key
     * @return EnrollmentGenDetail
     */
    public function setKey($key) {
        $this->key = $key;
        return $this;
    }

    /**
     * @return string
     */
    public function getValue() {
        return $this->value;
    }

    /**
     * @param string $value
     * @return EnrollmentGenDetail
     */
    public function setValue($value) {
        $this->value = $value;
        return $this;
    }

    public function __toString() {
        return $this->getKey().": ".$this->getValue();
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
            "id" => $this->id,
            "enrollment" => $this->enrollment->jsonSerialize(),
            "key" => $this->key, "value" => $this->value
        );
    }
}
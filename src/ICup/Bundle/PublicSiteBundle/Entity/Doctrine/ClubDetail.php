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
 * @ORM\Table(name="clubdetails")
 * @ORM\Entity
 */
class ClubDetail implements JsonSerializable
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
     * @var Club $club
     * Relation to Club
     * @ORM\ManyToOne(targetEntity="Club", inversedBy="details")
     * @ORM\JoinColumn(name="pid", referencedColumnName="id")
     */
    protected $club;

    /**
     * @var string $key
     * Name describing the detail for the club
     * @ORM\Column(name="detailkey", type="string", length=50, nullable=false)
     */
    protected $key;

    /**
     * @var string $value
     * The detail value for the club
     * @ORM\Column(name="detail", type="string", length=50, nullable=true)
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
     * @return Club
     */
    public function getClub() {
        return $this->club;
    }

    /**
     * @param Club $club
     * @return ClubDetail
     */
    public function setClub($club) {
        $this->club = $club;
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
     * @return ClubDetail
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
     * @return ClubDetail
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
            "objectType" => "ClubDetail",
            "id" => $this->id,
            "club" => $this->club->jsonSerialize(),
            "key" => $this->key, "value" => $this->value
        );
    }
}
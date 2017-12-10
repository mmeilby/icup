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
 * @ORM\Table(name="clubs", uniqueConstraints={@ORM\UniqueConstraint(name="ClubNameConstraint", columns={"name", "country"})})
 * @ORM\Entity
 */
class Club implements JsonSerializable
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
     * @var string $key
     *
     * @ORM\Column(name="externalkey", type="string", length=36, nullable=true)
     */
    protected $key;

    /**
     * @var string $name
     * Club name used in lists (and as template for team names)
     * @ORM\Column(name="name", type="string", length=50, nullable=false)
     */
    protected $name;

    /**
     * @var string $address
     * Club address
     * @ORM\Column(name="address", type="string", length=50, nullable=true)
     */
    protected $address;

    /**
     * @var string $city
     * Club address
     * @ORM\Column(name="city", type="string", length=50, nullable=true)
     */
    protected $city;

    /**
     * @var Country $country
     * Relation to Country - club residence - DNK for Denmark, DEU for Germany, FRA for France and so on...
     * @ORM\ManyToOne(targetEntity="Country")
     * @ORM\JoinColumn(name="country", referencedColumnName="country")
     */
    protected $country;

    /**
     * @var ArrayCollection $teams
     * Collection of teams associated with this club
     * @ORM\OneToMany(targetEntity="Team", mappedBy="club", cascade={"persist", "remove"})
     */
    protected $teams;

    /**
     * @var ArrayCollection $officials
     * Collection of users related to this club - managers, officials or simply members of the club
     * @ORM\OneToMany(targetEntity="ClubRelation", mappedBy="club", cascade={"persist", "remove"})
     **/
    protected $officials;

    /**
     * @var ArrayCollection $vouchers
     * Collection of vouchers associated with this club
     * @ORM\OneToMany(targetEntity="Voucher", mappedBy="club", cascade={"persist", "remove"})
     */
    protected $vouchers;

    /**
     * @var ArrayCollection $details
     * Collection of custom details associated with this club
     * @ORM\OneToMany(targetEntity="ClubDetail", mappedBy="club", cascade={"persist", "remove"})
     */
    protected $details;

    /**
     * Fixed name and country code for placeholder club for vacant teams
     */
    public static $VACANT_CLUB_NAME = "VACANT";
    public static $VACANT_CLUB_COUNTRYCODE = "[V]";

    /**
     * Club constructor.
     */
    public function __construct() {
        $this->teams = new ArrayCollection();
        $this->officials = new ArrayCollection();
        $this->vouchers = new ArrayCollection();
        $this->details = new ArrayCollection();
    }

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
     * @return string
     */
    public function getKey() {
        return $this->key;
    }

    /**
     * @param string $key
     * @return Club
     */
    public function setKey($key) {
        $this->key = $key;
        return $this;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Club
     */
    public function setName($name)
    {
        $this->name = $name;
    
        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getAddress() {
        return $this->address;
    }

    /**
     * @param string $address
     * @return Club
     */
    public function setAddress($address) {
        $this->address = $address;
        return $this;
    }

    /**
     * @return string
     */
    public function getCity() {
        return $this->city;
    }

    /**
     * @param string $city
     * @return Club
     */
    public function setCity($city) {
        $this->city = $city;
        return $this;
    }

    /**
     * Set country
     *
     * @param Country $country
     * @return Club
     */
    public function setCountry($country)
    {
        $this->country = $country;
    
        return $this;
    }

    /**
     * Get country record
     *
     * @return Country
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Get country code
     *
     * @return string 
     */
    public function getCountryCode()
    {
        return $this->country->getCountry();
    }

    /**
     * @return ArrayCollection
     */
    public function getTeams() {
        return $this->teams;
    }

    /**
     * @return ArrayCollection
     */
    public function getVouchers() {
        return $this->vouchers;
    }

    /**
     * @return ArrayCollection
     */
    public function getOfficials() {
        return $this->officials;
    }

    /**
     * @return ArrayCollection
     */
    public function getDetails() {
        return $this->details;
    }

    public function getClubdetails() {
        return $this->details->toArray();
    }

    public function setClubdetails($details) {
        if (is_array($details)) {
            foreach ($details as $key => $detail) {
                $clubdetails = $this->details->filter(function (ClubDetail $clubdetail) use ($key) {
                    return $clubdetail->getKey() == $key;
                });
                if ($clubdetails->isEmpty()) {
                    $clubdetail = new ClubDetail();
                    $clubdetail->setKey($key);
                    $clubdetail->setValue($detail);
                    $clubdetail->setClub($this);
                    $this->details->add($clubdetail);
                }
                else {
                    $clubdetail = $clubdetails->first();
                    $clubdetail->setValue($detail);
                }
            }
        }
        return $this;
    }

    /**
     * Test if this club is placeholder for vacant teams
     * @return bool true if club is placeholder
     */
    public function isVacant() {
        return $this->name == static::$VACANT_CLUB_NAME && $this->country->getCountry() == static::$VACANT_CLUB_COUNTRYCODE;
    }
    
    public function __toString() {
        return $this->getName()." (".$this->getCountryCode().")";
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
            "objectType" => "Club",
            "id" => $this->id,
            "name" => $this->name, "address" => $this->address, "city" => $this->city,
            "country_code" => $this->country->getCountry(), "flag" => $this->country->getFlag()
        );
    }
}
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
 * @ORM\Table(name="enrollmentdetails", uniqueConstraints={@ORM\UniqueConstraint(name="ClubConstraint", columns={"pid", "club"})})
 * @ORM\Entity
 */
class EnrollmentDetail implements JsonSerializable
{
    const STATUS_ENROLLED = 0;
    const STATUS_BANK_XFER = 1;
    const STATUS_CARD_PAID = 2;

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var Tournament $tournament
     * Relation to Tournament
     * @ORM\ManyToOne(targetEntity="Tournament", inversedBy="enrollments")
     * @ORM\JoinColumn(name="pid", referencedColumnName="id")
     */
    protected $tournament;

    /**
     * @var Club $club
     * Relation to Club
     * @ORM\ManyToOne(targetEntity="Club")
     * @ORM\JoinColumn(name="club", referencedColumnName="id")
     */
    protected $club;

    /**
     * @var string $name
     * Name of enrollment responsible
     * @ORM\Column(name="name", type="string", length=50, nullable=false)
     */
    protected $name;

    /**
     * @var string $email
     * E-mail for enrollment responsible
     * @ORM\Column(name="email", type="string", length=128, nullable=false)
     */
    protected $email;

    /**
     * @var string $mobile
     * Mobile for enrollment responsible
     * @ORM\Column(name="mobile", type="string", length=50, nullable=false)
     */
    protected $mobile;

    /**
     * @var integer $state
     * Current state of the enrollment
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    protected $state;

    /**
     * @var ArrayCollection $details
     * Collection of custom details associated with this enrollment
     * @ORM\OneToMany(targetEntity="EnrollmentGenDetail", mappedBy="enrollment", cascade={"persist", "remove"})
     */
    protected $details;

    /**
     * Group constructor.
     */
    public function __construct() {
        $this->details = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return Tournament
     */
    public function getTournament() {
        return $this->tournament;
    }

    /**
     * @param Tournament $tournament
     */
    public function setTournament($tournament) {
        $this->tournament = $tournament;
    }

    /**
     * @return Club
     */
    public function getClub() {
        return $this->club;
    }

    /**
     * @param Club $club
     */
    public function setClub($club) {
        $this->club = $club;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getEmail() {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email) {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getMobile() {
        return $this->mobile;
    }

    /**
     * @param string $mobile
     */
    public function setMobile($mobile) {
        $this->mobile = $mobile;
    }

    /**
     * @return int
     */
    public function getState() {
        return $this->state;
    }

    /**
     * @param int $state
     */
    public function setState($state) {
        $this->state = $state;
    }

    /**
     * @return ArrayCollection
     */
    public function getDetails() {
        return $this->details;
    }

    public function __toString() {
        return $this->getTournament()->getKey()." - ".$this->getClub()->getName()." - ".
            $this->getName()." <".$this->getEmail().">";
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
            "tournament" => $this->tournament->jsonSerialize(),
            "club" => $this->club->jsonSerialize(),
            "name" => $this->name,
            "email" => $this->email,
            "mobile" => $this->mobile,
            "state" => $this->state
        );
    }

}
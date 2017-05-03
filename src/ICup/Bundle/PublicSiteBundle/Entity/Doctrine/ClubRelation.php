<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\ORM\Mapping as ORM;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\ClubRelation
 *
 * @ORM\Table(name="clubrelations")
 * @ORM\Entity
 */
class ClubRelation
{
    public static $FOLLOWER = 1;
    public static $MEMBER = 2;
    public static $OFFICIAL = 3;
    public static $MANAGER = 4;

    /* Application for membership - user requesting for membership to the club */
    public static $APP = 1;
    /* Membership - user is granted membership to the club */
    public static $MEM = 2;
    /* Blocked membership - user has been revoked the membership to this club */
    public static $BLC = 3;

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
     * @ORM\ManyToOne(targetEntity="Club", inversedBy="officials")
     * @ORM\JoinColumn(name="club", referencedColumnName="id")
     */
    protected $club;

    /**
     * @var User $user
     * Relation to User
     * @ORM\ManyToOne(targetEntity="User", inversedBy="club_relations")
     * @ORM\JoinColumn(name="member", referencedColumnName="id")
     */
    protected $user;

    /**
     * @var integer $status
     * Relation status: 1: applicant, 2: member, 3: revoked member
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    protected $status;

    /**
     * @var integer $role
     * User role for this relation: 1: follower, 2: member, 3: official, 4: manager
     * @ORM\Column(name="role", type="integer", nullable=false)
     */
    protected $role;

    /**
     * @var string $application_date
     * Date of application - YYYYMMDD
     * @ORM\Column(name="application_date", type="string", length=8, nullable=false)
     */
    protected $application_date;

    /**
     * @var string $member_since
     * Date of membership start - YYYYMMDD
     * @ORM\Column(name="member_since", type="string", length=8, nullable=true)
     */
    protected $member_since;

    /**
     * @var string $last_change
     * Date of last change - YYYYMMDD
     * @ORM\Column(name="last_change", type="string", length=8, nullable=true)
     */
    protected $last_change;

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
     * @return ClubRelation
     */
    public function setClub($club) {
        $this->club = $club;
        return $this;
    }

    /**
     * @return User
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * @param User $user
     * @return ClubRelation
     */
    public function setUser($user) {
        $this->user = $user;
        return $this;
    }

    /**
     * @return int
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * @param int $status
     * @return ClubRelation
     */
    public function setStatus($status) {
        $this->status = $status;
        return $this;
    }

    /**
     * @return int
     */
    public function getRole() {
        return $this->role;
    }

    /**
     * @param int $role
     * @return ClubRelation
     */
    public function setRole($role) {
        $this->role = $role;
        return $this;
    }

    /**
     * @return string
     */
    public function getApplicationDate() {
        return $this->application_date;
    }

    /**
     * @param string $application_date
     * @return ClubRelation
     */
    public function setApplicationDate($application_date) {
        $this->application_date = $application_date;
        return $this;
    }

    /**
     * @return string
     */
    public function getMemberSince() {
        return $this->member_since;
    }

    /**
     * @param string $member_since
     * @return ClubRelation
     */
    public function setMemberSince($member_since) {
        $this->member_since = $member_since;
        return $this;
    }

    /**
     * @return string
     */
    public function getLastChange() {
        return $this->last_change;
    }

    /**
     * @param string $last_change
     * @return ClubRelation
     */
    public function setLastChange($last_change) {
        $this->last_change = $last_change;
        return $this;
    }
}
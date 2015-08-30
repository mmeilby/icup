<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\ORM\Mapping as ORM;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\SocialRelation
 *
 * @ORM\Table(name="socialrelations")
 * @ORM\Entity
 */
class SocialRelation
{
    public static $FOLLOWER = 1;
    public static $MEMBER = 2;
    public static $EDITOR = 3;
    public static $ADMIN = 4;
    public static $OWNER = 5;

    /* Application for membership - user requesting for membership of the group */
    public static $APP = 1;
    /* Membership - user is granted membership of the group */
    public static $MEM = 2;
    /* Blocked membership - user has been blacklisted for this group */
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
     * @var SocialGroup $group
     * Relation to SocialGroup
     * @ORM\ManyToOne(targetEntity="SocialGroup", inversedBy="social_relations")
     * @ORM\JoinColumn(name="social_group", referencedColumnName="id")
     */
    protected $group;

    /**
     * @var User $user
     * Relation to User
     * @ORM\ManyToOne(targetEntity="User", inversedBy="social_relations")
     * @ORM\JoinColumn(name="member", referencedColumnName="id")
     */
    protected $user;

    /**
     * @var integer $status
     * Relation status: 1: applicant, 2: member, 3: blacklisted member
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    protected $status;

    /**
     * @var integer $role
     * User role for this relation: 1: follower, 2: member, 3: editor, 4: admin, 5: owner
     * @ORM\Column(name="role", type="integer", nullable=false)
     */
    protected $role;

    /**
     * @var string $application_date
     * Date of application - DD/MM/YYYY
     * @ORM\Column(name="application_date", type="string", length=10, nullable=false)
     */
    protected $application_date;

    /**
     * @var string $member_since
     * Date of membership start - DD/MM/YYYY
     * @ORM\Column(name="member_since", type="string", length=10, nullable=true)
     */
    protected $member_since;

    /**
     * @var string $last_change
     * Date of last change - DD/MM/YYYY
     * @ORM\Column(name="last_change", type="string", length=10, nullable=true)
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
     * @return SocialGroup
     */
    public function getGroup() {
        return $this->group;
    }

    /**
     * @param SocialGroup $group
     * @return SocialRelation
     */
    public function setGroup($group) {
        $this->group = $group;
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
     * @return SocialRelation
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
     * @return SocialRelation
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
     * @return SocialRelation
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
     * @return SocialRelation
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
     * @return SocialRelation
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
     * @return SocialRelation
     */
    public function setLastChange($last_change) {
        $this->last_change = $last_change;
        return $this;
    }
}
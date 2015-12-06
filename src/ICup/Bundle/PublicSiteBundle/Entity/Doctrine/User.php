<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use DateTime;
use JsonSerializable;

/**
 * User entity
 * This is one of the three basic entities: Hosts, Users and Clubs
 * A user is a top level entity. Only one user can use a specific username in the system.
 *
 * The user entity is a special object used for login of users to the system.
 * Data in this object are used for authentication and administration of access rights
 *
 * However the user entity also relates to enrollments and hosts/clubs as a part of the data model
 * to bind the user to actions and relationships.
 *
 * @ORM\Table(name="users",uniqueConstraints={@ORM\UniqueConstraint(name="UserConstraint", columns={"username"})})
 * @ORM\Entity
 */
class User extends BaseUser implements JsonSerializable
{
    public static $CLUB = 1;
    public static $CLUB_ADMIN = 2;
    public static $EDITOR = 3;
    public static $EDITOR_ADMIN = 4;
    public static $ADMIN = 9;

    /* Authenticated user - just created and not yet verified */
    public static $AUTH = 1;
    /* Verified user - verified by email response however not connected to a club */
    public static $VER = 2;
    /* Prospector - verified user requesting a reference to a club - cid must be valid */
    public static $PRO = 3;
    /* Attached user - verified user - accepted to reference a club - cid must be valid */
    public static $ATT = 4;
    /* Inform - ignored user - accepted to reference a club - cid must be valid */
    public static $INF = 5;
    /* System user */
    public static $SYSTEM = 9;

    const ROLE_ADMIN = 'ROLE_ADMIN';
    const ROLE_EDITOR_ADMIN = 'ROLE_EDITOR_ADMIN';
    const ROLE_EDITOR = 'ROLE_EDITOR';
    const ROLE_CLUB_ADMIN = 'ROLE_CLUB_ADMIN';

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Host $host
     * Editor relation to a specific host (for system admins this is the host used recently)
     * @ORM\ManyToOne(targetEntity="Host", inversedBy="users")
     * @ORM\JoinColumn(name="pid", referencedColumnName="id")
     */
    protected $host;

    /**
     * @var Club $club_membership
     * Club user relation a specific club
     * @ORM\ManyToOne(targetEntity="Club", inversedBy="users")
     * @ORM\JoinColumn(name="cid", referencedColumnName="id")
     */
    protected $club_membership;

    /**
     * @var ArrayCollection $social_relations
     * User relation to social groups
     * @ORM\OneToMany(targetEntity="SocialRelation", mappedBy="user", cascade={"persist", "remove"})
     **/
    protected $social_relations;

    /**
     * @var string $name
     * Formal name used by the system
     * @ORM\Column(name="name", type="string", length=50, nullable=false)
     */
    protected $name;

    /**
     * @var integer $status
     * User status: 1: authenticating, 2: verified, 3: prospector, 4: attached, 5: inform, 9: system
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    protected $status;

    /**
     * @var integer $role
     * User role: 1: user, 2: club_admin, 3: editor, 4: tournament_admin, 9: admin
     * @ORM\Column(name="role", type="integer", nullable=false)
     */
    protected $role;

    /**
     * @var string $secret
     * Secret used for system routines like reset password
     * @ORM\Column(name="secret", type="string", length=50, nullable=true)
     */
    protected $secret;

    /**
     * @var integer $attempts
     * Number of failed login attempts since last successfull login
     * @ORM\Column(name="attempts", type="integer", nullable=false)
     */
    protected $attempts;

    /**
     * @var string
     *
     * @ORM\Column(name="facebook_id", type="string", nullable=true)
     */
    protected $facebookID;

    /**
     * @var string
     *
     * @ORM\Column(name="google_id", type="string", nullable=true)
     */
    protected $googleID;

    /**
     * @var ArrayCollection $enrollments
     * Collection of team enrollments authorized by this user
     * @ORM\OneToMany(targetEntity="Enrollment", mappedBy="user", cascade={"persist"})
     */
    protected $enrollments;

    /**
     * User constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->enrollments = new ArrayCollection();
        $this->social_relations = new ArrayCollection();
        $this->attempts = 0;
        $this->name = '';
        $this->role = static::$CLUB;
        $this->status = static::$AUTH;
    }

    /**
     * @return Host
     */
    public function getHost() {
        return $this->host;
    }

    /**
     * @param Host $host
     * @return User
     */
    public function setHost($host) {
        $this->host = $host;
        return $this;
    }

    /**
     * @return Club
     */
    public function getClub() {
        return $this->club_membership;
    }

    /**
     * @param Club $club
     * @return User
     */
    public function setClub($club) {
        $this->club_membership = $club;
        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getSocialRelations() {
        return $this->social_relations;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return User
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
     * Set role
     * User role: CLUB: user, CLUB_ADMIN: club_admin, EDITOR: editor, EDITOR_ADMIN: tournament_admin, ADMIN: admin
     * @param integer $role
     * @return User
     */
    public function setRole($role)
    {
        $role_map = array(
            User::$ADMIN => static::ROLE_ADMIN,
            User::$EDITOR_ADMIN => static::ROLE_EDITOR_ADMIN,
            User::$EDITOR => static::ROLE_EDITOR,
            User::$CLUB_ADMIN => static::ROLE_CLUB_ADMIN,
            User::$CLUB => BaseUser::ROLE_DEFAULT,
        );
        if (isset($role_map[$role])) {
            $roles = array_diff($this->getRoles(), array_values($role_map));
            $roles[] = $role_map[$role];
            $this->setRoles($roles);
        }
        $this->role = $role;
    
        return $this;
    }

    /**
     * Get role
     * User role: CLUB: user, CLUB_ADMIN: club_admin, EDITOR: editor, EDITOR_ADMIN: tournament_admin, ADMIN: admin
     * @return integer 
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Test for club role
     * @return boolean - true if the user has the club role 
     */
    public function isClub()
    {
        return $this->role === User::$CLUB || $this->role === User::$CLUB_ADMIN;
    }

    /**
     * Test for editor role
     * @return boolean - true if the user has the editor role 
     */
    public function isEditor()
    {
        return $this->role === User::$EDITOR || $this->role === User::$EDITOR_ADMIN;
    }

    /**
     * Test for admin role
     * @return boolean - true if the user has the admin role 
     */
    public function isAdmin()
    {
        return $this->role === User::$ADMIN;
    }
    
    /**
     * Set status
     * User status: AUTH: authenticating, VER: verified, PRO: prospector, ATT: attached, INF: inform, SYSTEM: system
     * @param integer $status
     * @return User
     */
    public function setStatus($status)
    {
        $this->status = $status;
    
        return $this;
    }

    /**
     * Get status
     * User status: AUTH: authenticating, VER: verified, PRO: prospector, ATT: attached, INF: inform, SYSTEM: system
     * @return integer 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Test for club relation
     * @return boolean - true if the user is attached or prospector to a club - cid refers to a valid club
     */
    public function isRelated()
    {
        return $this->status === User::$PRO || $this->status === User::$ATT || $this->status === User::$INF;
    }
    
    /**
     * Test for club relation to specific club
     * @return boolean - true if the user is attached or prospector to the club - cid refers to the specific club
     */
    public function isRelatedTo($clubid)
    {
        return $this->isRelated() && $this->getClub() && $this->getClub()->getId() == $clubid;
    }
    
    /**
     * Test for host relation to specific host
     * @return boolean - true if the user is editor for the host - pid refers to the specific host
     */
    public function isEditorFor($hostid)
    {
        return $this->isEditor() && $this->getHost() && $this->getHost()->getId() == $hostid;
    }

    /**
     * @return string
     */
    public function getFacebookID() {
        return $this->facebookID;
    }

    /**
     * @param string $facebookID
     */
    public function setFacebookID($facebookID) {
        $this->facebookID = $facebookID;
    }

    /**
     * @return string
     */
    public function getGoogleID() {
        return $this->googleID;
    }

    /**
     * @param string $googleID
     */
    public function setGoogleID($googleID) {
        $this->googleID = $googleID;
    }
    
    /**
     * @return int
     */
    public function getAttempts() {
        return $this->attempts;
    }

    /**
     * @param int $attempts
     */
    public function setAttempts($attempts) {
        $this->attempts = $attempts;
    }

    /**
     * @return string
     */
    public function getSecret() {
        return $this->secret;
    }

    /**
     * @param string $secret
     */
    public function setSecret($secret) {
        $this->secret = $secret;
    }

    public function loginFailed() {
        $this->attempts++;
    }

    public function loginSucceeded() {
        $this->attempts = 0;
        $this->lastLogin = new DateTime();
    }

    /**
     * @return ArrayCollection
     */
    public function getEnrollments() {
        return $this->enrollments;
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
            "id" => $this->getId(),
            "username" => $this->getUsername(),
            "name" => $this->getName(),
            "email" => $this->getEmail(),
            "club" => $this->getClub() ? $this->getClub()->getName() : "",
            "host" => $this->getHost() ? $this->getHost()->getName() : "",
            "role" => $this->getRole(),
            "status" => $this->getStatus(),
            "locked" => $this->isAccountNonLocked() ? 'N' : 'Y',
            "enabled" => $this->isEnabled() ? 'Y' : 'N'
        );
    }
}
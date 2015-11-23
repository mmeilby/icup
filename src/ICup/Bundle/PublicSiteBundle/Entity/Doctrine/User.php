<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

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
class User implements AdvancedUserInterface, JsonSerializable
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

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
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
     * @var string $email
     * E-mail address used for system messages like reset password
     * @ORM\Column(name="email", type="string", length=100, nullable=false)
     */
    protected $email;

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
     * @var string $username
     * Username used for login (could be the e-mail address)
     * @ORM\Column(name="username", type="string", length=50, nullable=false)
     */
    protected $username;

    /**
     * @var string $password
     * Password used for login (crypted)
     * @ORM\Column(name="password", type="string", length=128, nullable=false)
     */
    protected $password;

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
     * @var string $enabled
     * Set to Y if account is enabled
     * @ORM\Column(name="enabled", type="string", length=1, nullable=false)
     */
    protected $enabled;

    /**
     * @var DateTime $lastLogin
     * Timestamp of last successfull login
     * @ORM\Column(name="lastlogin", type="datetime", nullable=true)
     */
    protected $lastLogin;

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
        $this->enrollments = new ArrayCollection();
        $this->social_relations = new ArrayCollection();
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
     * Set email
     *
     * @param string $email
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = $email;
    
        return $this;
    }

    /**
     * Get email
     *
     * @return string 
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * INTERFACE IMPLEMENTATION
     * Returns the roles granted to the user.
     *
     * <code>
     * public function getRoles()
     * {
     *     return array('ROLE_USER');
     * }
     * </code>
     *
     * Alternatively, the roles might be stored on a ``roles`` property,
     * and populated in any number of different ways when the user object
     * is created.
     *
     * @return Role[] The user roles
     */
    public function getRoles()
    {
        switch ($this->role) {
            case User::$CLUB:
                $roles = 'ROLE_USER';
                break;
            case User::$CLUB_ADMIN:
                $roles = 'ROLE_CLUB_ADMIN';
                break;
            case User::$EDITOR:
                $roles = 'ROLE_EDITOR';
                break;
            case User::$EDITOR_ADMIN:
                $roles = 'ROLE_EDITOR_ADMIN';
                break;
            case User::$ADMIN:
                $roles = 'ROLE_ADMIN';
                break;
            default:
                $roles = '';
                break;
        }
        return array($roles);
    }

    /**
     * Sets the username used to authenticate the user.
     *
     * @param string $username
     * @return User
     */
    public function setUsername($username)
    {
        $this->username = $username;
    
        return $this;
    }

    /**
     * INTERFACE IMPLEMENTATION
     * Returns the username used to authenticate the user.
     *
     * @return string The username
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Sets the password used to authenticate the user.
     *
     * This should be the encoded password. On authentication, a plain-text
     * password will be salted, encoded, and then compared to this value.
     *
     * @param string $password
     * @return User
     */
    public function setPassword($password)
    {
        $this->password = $password;
    
        return $this;
    }

    /**
     * INTERFACE IMPLEMENTATION
     * Returns the password used to authenticate the user.
     *
     * This should be the encoded password. On authentication, a plain-text
     * password will be salted, encoded, and then compared to this value.
     *
     * @return string The password
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * INTERFACE IMPLEMENTATION
     * Returns the salt that was originally used to encode the password.
     *
     * This can return null if the password was not encoded using a salt.
     *
     * @return string The salt
     */
    public function getSalt()
    {
        return null;
    }

    /**
     * INTERFACE IMPLEMENTATION
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     *
     * @return void
     */
    public function eraseCredentials()
    {
    }

    /**
     * INTERFACE IMPLEMENTATION
     * Checks whether the user's account has expired.
     *
     * Internally, if this method returns false, the authentication system
     * will throw an AccountExpiredException and prevent login.
     *
     * @return bool true if the user's account is non expired, false otherwise
     *
     * @see AccountExpiredException
     */
    public function isAccountNonExpired() {
        return true;
    }

    /**
     * INTERFACE IMPLEMENTATION
     * Checks whether the user is locked.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a LockedException and prevent login.
     *
     * @return bool true if the user is not locked, false otherwise
     *
     * @see LockedException
     */
    public function isAccountNonLocked() {
        return $this->attempts < 5;
    }

    /**
     * INTERFACE IMPLEMENTATION
     * Checks whether the user's credentials (password) has expired.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a CredentialsExpiredException and prevent login.
     *
     * @return bool true if the user's credentials are non expired, false otherwise
     *
     * @see CredentialsExpiredException
     */
    public function isCredentialsNonExpired() {
        return true;
    }

    /**
     * INTERFACE IMPLEMENTATION
     * Checks whether the user is enabled.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a DisabledException and prevent login.
     *
     * @return bool true if the user is enabled, false otherwise
     *
     * @see DisabledException
     */
    public function isEnabled() {
        return $this->enabled != 'N';
    }

    /**
     * @param string $enabled
     */
    public function setEnabled($enabled) {
        $this->enabled = $enabled ? 'Y' : 'N';
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
     * @return DateTime
     */
    public function getLastLogin() {
        return $this->lastLogin;
    }

    /**
     * @param DateTime $lastLogin
     */
    public function setLastLogin($lastLogin) {
        $this->lastLogin = $lastLogin;
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
<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use DateTime;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User
 *
 * @ORM\Table(name="users",uniqueConstraints={@ORM\UniqueConstraint(name="IdxByUser", columns={"username"})})
 * @ORM\Entity
 */
class User implements AdvancedUserInterface
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
    private $id;

    /**
     * @var integer $pid
     * Relation to Host - pid=host.id
     * @ORM\Column(name="pid", type="integer", nullable=false)
     */
    private $pid;

    /**
     * @var integer $cid
     * Relation to Club - pid=club.id
     * @ORM\Column(name="cid", type="integer", nullable=false)
     */
    private $cid;

    /**
     * @var string $name
     *
     * @ORM\Column(name="name", type="string", length=50, nullable=false)
     */
    private $name;

    /**
     * @var string $email
     *
     * @ORM\Column(name="email", type="string", length=100, nullable=false)
     */
    private $email;

    /**
     * @var integer $status
     * User status: 1: authenticating, 2: verified, 3: prospector, 4: attached, 5: inform, 9: system
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    private $status;

    /**
     * @var integer $role
     * User role: 1: user, 2: club_admin, 3: editor, 4: tournament_admin, 9: admin
     * @ORM\Column(name="role", type="integer", nullable=false)
     */
    private $role;

    /**
     * @var string $username
     *
     * @ORM\Column(name="username", type="string", length=50, nullable=false)
     */
    private $username;

    /**
     * @var string $password
     *
     * @ORM\Column(name="password", type="string", length=128, nullable=false)
     */
    private $password;

    /**
     * @var string $secret
     *
     * @ORM\Column(name="secret", type="string", length=50, nullable=false)
     */
    private $secret;

    /**
     * @var integer $attempts
     * Number of failed login attempts since last successfull login
     * @ORM\Column(name="attempts", type="integer", nullable=false)
     */
    private $attempts;

    /**
     * @var string $enabled
     * Set to Y if account is enabled
     * @ORM\Column(name="enabled", type="string", length=1, nullable=false)
     */
    private $enabled;

    /**
     * @var DateTime $lastLogin
     *
     * @ORM\Column(name="lastlogin", type="datetime", nullable=true)
     */
    private $lastLogin;

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
     * Set parent id - related host
     *
     * @param integer $pid
     * @return User
     */
    public function setPid($pid)
    {
        $this->pid = $pid;
    
        return $this;
    }

    /**
     * Get parent id - related host
     *
     * @return integer 
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * Set child id - related club
     *
     * @param integer $cid
     * @return User
     */
    public function setCid($cid)
    {
        $this->cid = $cid;
    
        return $this;
    }

    /**
     * Get child id - related club
     *
     * @return integer 
     */
    public function getCid()
    {
        return $this->cid;
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
        return $this->isRelated() && $this->getCid() == $clubid;
    }
    
    /**
     * Test for host relation to specific host
     * @return boolean - true if the user is editor for the host - pid refers to the specific host
     */
    public function isEditorFor($hostid)
    {
        return $this->isEditor() && $this->getPid() == $hostid;
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

    public function dump() {
        return "User: id=".$this->getId().
                ", usr=".$this->getUsername().
                ", name=".$this->getName().
                ", email=".$this->getEmail().
                ", cid=".$this->getCid().
                ", pid=".$this->getPid().
                ", role=".$this->getRole().
                ", status=".$this->getStatus().
                ", locked=".($this->isAccountNonLocked() ? 'N' : 'Y').
                ". enabled=".($this->isEnabled() ? 'Y' : 'N');
    }
}
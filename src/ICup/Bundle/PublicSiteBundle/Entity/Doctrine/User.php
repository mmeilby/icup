<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use FOS\UserBundle\Entity\User as BaseUser;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
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
     * Editor relation to a specific host (for other users this is the host used recently)
     * @ORM\ManyToOne(targetEntity="Host", inversedBy="users")
     * @ORM\JoinColumn(name="host", referencedColumnName="id")
     */
    protected $host;

    /**
     * @var string $name
     * Formal name used by the system
     * @ORM\Column(name="name", type="string", length=50, nullable=true)
     */
    protected $name;

    /**
     * @var integer $attempts
     * Number of failed login attempts since last successfull login
     * @ORM\Column(name="attempts", type="integer", nullable=false)
     */
    protected $attempts;

    /**
     * @var string
     * Reference to facebook account connected for this user
     * @ORM\Column(name="facebook_id", type="string", nullable=true)
     */
    protected $facebookID;

    /**
     * @var string
     * Reference to Google account connected for this user
     * @ORM\Column(name="google_id", type="string", nullable=true)
     */
    protected $googleID;

    /**
     * @var string
     * Login realm for this user
     * @ORM\Column(name="realm", type="string", nullable=true)
     */
    protected $realm;

    /**
     * @var boolean
     * Flag for verified email address
     * @ORM\Column(name="emailVerified", type="boolean", nullable=true)
     */
    protected $emailVerified;

    /**
     * @var string
     * Token?
     * @ORM\Column(name="verificationToken", type="string", length=250, nullable=true)
     */
    protected $verificationToken;

    /**
     * @var ArrayCollection $enrollments
     * Collection of team enrollments authorized by this user
     * @ORM\OneToMany(targetEntity="Enrollment", mappedBy="user", cascade={"persist"})
     */
    protected $enrollments;

    /**
     * @var ArrayCollection $club_relations
     * User relation to club
     * @ORM\OneToMany(targetEntity="ClubRelation", mappedBy="user", cascade={"persist", "remove"})
     **/
    protected $club_relations;

    /**
     * @var ArrayCollection $social_relations
     * User relation to social groups
     * @ORM\OneToMany(targetEntity="SocialRelation", mappedBy="user", cascade={"persist", "remove"})
     **/
    protected $social_relations;

    /**
     * User constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->name = '';
        $this->attempts = 0;
        $this->enrollments = new ArrayCollection();
        $this->club_relations = new ArrayCollection();
        $this->social_relations = new ArrayCollection();
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
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
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
     * @return ArrayCollection
     */
    public function getClubRelations() {
        return $this->club_relations;
    }

    /**
     * @return ArrayCollection
     */
    public function getSocialRelations() {
        return $this->social_relations;
    }

    /**
     * @return ArrayCollection
     */
    public function getEnrollments() {
        return $this->enrollments;
    }

    /**
     * Test that user is official for the club passed
     * @param Club $club - the club to test membership of
     * @return bool - true if the user is official or manager for the club
     */
    public function isOfficialOf(Club $club) {
        $relations = $this->club_relations->filter(
            function (ClubRelation $rel) use ($club) {
                return $rel->getClub()->getId() == $club->getId() &&
                       $rel->getStatus() == ClubRelation::$MEM &&
                       array_search($rel->getRole(), array(ClubRelation::$MANAGER, ClubRelation::$OFFICIAL)) !== false;
            }
        );
        return !$relations->isEmpty();
    }

    /**
     * Test for club role
     * @return boolean - true if the user has the club role
     */
    public function isClubUser()
    {
        return $this->hasRole(static::ROLE_CLUB_ADMIN);
    }

    /**
     * Test for editor role
     * @return boolean - true if the user has the editor role
     */
    public function isEditor()
    {
        return $this->hasRole(static::ROLE_EDITOR) || $this->hasRole(static::ROLE_EDITOR_ADMIN);
    }

    /**
     * Test for editor admin role
     * @return boolean - true if the user has the editor admin role
     */
    public function isEditorAdmin()
    {
        return $this->hasRole(static::ROLE_EDITOR_ADMIN);
    }

    /**
     * Test for admin role
     * @return boolean - true if the user has the admin role
     */
    public function isAdmin()
    {
        return $this->hasRole(static::ROLE_ADMIN);
    }

    /**
     * (PHP 5 &gt;= 5.4.0)<br/>
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     */
    public function jsonSerialize() {
        return array(
            "objectType" => "User",
            "id" => $this->getId(),
            "username" => $this->getUsername(),
            "name" => $this->getName(),
            "email" => $this->getEmail(),
            "host" => $this->getHost() ? $this->getHost()->getName() : "",
            "roles" => implode(",", $this->getRoles()),
            "locked" => $this->isLocked() ? 'Y' : 'N',
            "enabled" => $this->isEnabled() ? 'Y' : 'N',
            "expired" => $this->isExpired() ? 'Y' : 'N',
        );
    }

    /**
     * Serializes the user.
     *
     * The serialized data have to contain the fields used during check for
     * changes and the id.
     *
     * @return string
     */
    public function serialize()
    {
        return serialize(array(
            parent::serialize(),
            $this->name,
            $this->attempts,
            $this->facebookID,
            $this->googleID,
        ));
    }

    /**
     * Unserializes the user.
     *
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);

        list(
            $parentdata,
            $this->name,
            $this->attempts,
            $this->facebookID,
            $this->googleID,
            ) = $data;
        parent::unserialize($parentdata);
    }

    public function __toString() {
        return $this->getUsername()." - email: ".$this->getEmail()." roles: ".implode(",", $this->getRoles());
    }
}

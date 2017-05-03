<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * SocialGroup entity
 * A social group is a top level entity that can exist with several Hosts. Only one group can use a specific name.
 *
 * @ORM\Table(name="socialgroups", uniqueConstraints={@ORM\UniqueConstraint(name="SocialGroupNameConstraint", columns={"name"})})
 * @ORM\Entity
 */
class SocialGroup
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
     * @var string $name
     * Group name used in lists
     * @ORM\Column(name="name", type="string", length=50, nullable=false)
     */
    protected $name;

    /**
     * @var string $country
     * Country code for group residence - DNK for Denmark, DEU for Germany, FRA for France and so on...
     * @ORM\Column(name="country", type="string", length=3, nullable=false)
     */
    protected $country;

    /**
     * @var boolean $private
     * Group membership flag - if set the group is not open for application for membership - membership available only by invitation
     * @ORM\Column(name="private", type="boolean", nullable=false)
     */
    protected $private;

    /**
     * @var boolean $restricted
     * Group membership flag - if set the group accepts only new members if the group admin accepts the application for membership
     * @ORM\Column(name="restricted", type="boolean", nullable=false)
     */
    protected $restricted;

    /**
     * @var boolean $hidden
     * Group visibility flag - if set the group is only visible for members
     * @ORM\Column(name="hidden", type="boolean", nullable=false)
     */
    protected $hidden;

    /**
     * @var ArrayCollection $teams
     * Social group relation to specific teams
     * @ORM\ManyToMany(targetEntity="Team", mappedBy="social_groups")
     */
    protected $teams;

    /**
     * @var ArrayCollection $social_relations
     * Collection of member relations to this group
     * @ORM\OneToMany(targetEntity="SocialRelation", mappedBy="group", cascade={"persist", "remove"})
     */
    protected $social_relations;

    /**
     * Club constructor.
     */
    public function __construct() {
        $this->teams = new ArrayCollection();
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
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param string $name
     * @return SocialGroup
     */
    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getCountry() {
        return $this->country;
    }

    /**
     * @param string $country
     * @return SocialGroup
     */
    public function setCountry($country) {
        $this->country = $country;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isPrivate() {
        return $this->private;
    }

    /**
     * @param boolean $private
     * @return SocialGroup
     */
    public function setPrivate($private) {
        $this->private = $private;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isRestricted() {
        return $this->restricted;
    }

    /**
     * @param boolean $restricted
     * @return SocialGroup
     */
    public function setRestricted($restricted) {
        $this->restricted = $restricted;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isHidden() {
        return $this->hidden;
    }

    /**
     * @param boolean $hidden
     * @return SocialGroup
     */
    public function setHidden($hidden) {
        $this->hidden = $hidden;
        return $this;
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
    public function getSocialRelations() {
        return $this->social_relations;
    }
}
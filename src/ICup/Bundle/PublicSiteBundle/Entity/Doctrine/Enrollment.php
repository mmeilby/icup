<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\ORM\Mapping as ORM;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Enrollment
 *
 * @ORM\Table(name="enrollments",uniqueConstraints={@ORM\UniqueConstraint(name="TeamConstraint", columns={"cid"})})
 * @ORM\Entity
 */
class Enrollment
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Category $category
     * Relation to Category
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="enrollments")
     * @ORM\JoinColumn(name="pid", referencedColumnName="id")
     */
    private $category;

    /**
     * @var Team $team
     * Relation to Team
     * @ORM\ManyToOne(targetEntity="Team", inversedBy="enrollments")
     * @ORM\JoinColumn(name="cid", referencedColumnName="id")
     */
    private $team;

    /**
     * @var User $user
     * Relation to User
     * @ORM\ManyToOne(targetEntity="User", inversedBy="enrollments")
     * @ORM\JoinColumn(name="uid", referencedColumnName="id")
     */
    private $user;

    /**
     * @var string $date
     * Enrollment date - DD/MM/YYYY
     * @ORM\Column(name="date", type="string", length=10, nullable=false)
     */
    private $date;

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
     * @return Category
     */
    public function getCategory() {
        return $this->category;
    }

    /**
     * @param Category $category
     * @return Enrollment
     */
    public function setCategory($category) {
        $this->category = $category;
        return $this;
    }

    /**
     * @return Team
     */
    public function getTeam() {
        return $this->team;
    }

    /**
     * @param Team $team
     * @return Enrollment
     */
    public function setTeam($team) {
        $this->team = $team;
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
     * @return Enrollment
     */
    public function setUser($user) {
        $this->user = $user;
        return $this;
    }

    /**
     * Set date
     *
     * @param string $date
     * @return Enrollment
     */
    public function setDate($date)
    {
        $this->date = $date;
    
        return $this;
    }

    /**
     * Get date
     *
     * @return string 
     */
    public function getDate()
    {
        return $this->date;
    }
}
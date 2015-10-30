<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\ORM\Mapping as ORM;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\QMatchSchedulePlan
 *
 * @ORM\Table(name="qmatchscheduleplans")
 * @ORM\Entity
 */
class QMatchSchedulePlan
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
     * @var Category $category
     * Relation to Category
     * @ORM\ManyToOne(targetEntity="Category")
     * @ORM\JoinColumn(name="category", referencedColumnName="id")
     */
    protected $category;

    /**
     * @var string $branch
     * The branch for this relation (A or B according to acheived end game)
     * @ORM\Column(name="branch", type="string", length=1, nullable=false)
     */
    protected $branch;

    /**
     * @var integer $classification
     * The classification for the qualifying group referenced by this relation
     * @ORM\Column(name="classification", type="integer", nullable=false)
     */
    protected $classification;

    /**
     * @var integer $litra
     * The litra for the qualifying group referenced by this relation
     * @ORM\Column(name="litra", type="integer", nullable=false)
     */
    protected $litra;

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
     * @return QMatchSchedulePlan
     */
    public function setCategory($category) {
        $this->category = $category;
        return $this;
    }

    /**
     * @return string
     */
    public function getBranch() {
        return $this->branch;
    }

    /**
     * @param string $branch
     * @return QMatchScheduleRelation
     */
    public function setBranch($branch) {
        $this->branch = $branch;
        return $this;
    }

    /**
     * @return int
     */
    public function getClassification() {
        return $this->classification;
    }

    /**
     * @param int $classification
     * @return QMatchScheduleRelation
     */
    public function setClassification($classification) {
        $this->classification = $classification;
        return $this;
    }

    /**
     * @return int
     */
    public function getLitra() {
        return $this->litra;
    }

    /**
     * @param int $litra
     * @return QMatchScheduleRelation
     */
    public function setLitra($litra) {
        $this->litra = $litra;
        return $this;
    }
}
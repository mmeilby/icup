<?php
namespace ICup\Bundle\PublicSiteBundle\Services\Entity;


use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;

class QRelation
{
    /**
     * @var string $id
     */
    private $branch;

    /**
     * @var integer $classification
     */
    private $classification;

    /**
     * @var integer $litra
     */
    private $litra;

    /**
     * @var integer $rank
     */
    private $rank;

    /**
     * @var Group $group
     */
    private $group;

    public function __construct($classification, $litra, $rank, $branch = '', $group = null) {
        $this->branch = $branch;
        $this->classification = $classification;
        $this->litra = $litra;
        $this->rank = $rank;
        $this->group = $group;
    }

    /**
     * @return string
     */
    public function getBranch() {
        return $this->branch;
    }

    /**
     * @param string $branch
     * @return QRelation
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
     * @return QRelation
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
     * @return QRelation
     */
    public function setLitra($litra) {
        $this->litra = $litra;
        return $this;
    }

    /**
     * @return int
     */
    public function getRank() {
        return $this->rank;
    }

    /**
     * @param int $rank
     * @return QRelation
     */
    public function setRank($rank) {
        $this->rank = $rank;
        return $this;
    }

    /**
     * @return Group
     */
    public function getGroup() {
        return $this->group;
    }

    public function __toString() {
        return $this->classification.":".($this->group?$this->group->getName():$this->litra).$this->branch."#".$this->rank;
    }
}
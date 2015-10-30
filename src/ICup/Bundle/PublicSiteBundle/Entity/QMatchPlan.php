<?php

namespace ICup\Bundle\PublicSiteBundle\Entity;

use ICup\Bundle\PublicSiteBundle\Services\Entity\QRelation;

class QMatchPlan extends MatchPlan
{
    /**
     * @var integer $classification
     * The classification of this match: 1-playoff, 2-1/128, 3-1/64, 4-1/32, 5-1/16, 6-1/8, 7-1/4, 8-1/2, 9-3/4, 10-final
     */
    private $classification;

    /**
     * @var integer $litra
     * Reference litra for this match
     */
    private $litra;

    /**
     * @var QRelation $relA
     * Relation for the qualifying team A
     */
    private $relA;

    /**
     * @var QRelation $relB
     * Relation for the qualifying team B
     */
    private $relB;

    /**
     * @return int
     */
    public function getClassification() {
        return $this->classification;
    }

    /**
     * @param int $classification
     * @return QMatchPlan
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
     * @return MatchPlan
     */
    public function setLitra($litra) {
        $this->litra = $litra;
        return $this;
    }

    /**
     * @return QRelation
     */
    public function getRelA() {
        return $this->relA;
    }

    /**
     * @param QRelation $relA
     * @return QMatchPlan
     */
    public function setRelA($relA) {
        $this->relA = $relA;
        return $this;
    }

    /**
     * @return QRelation
     */
    public function getRelB() {
        return $this->relB;
    }

    /**
     * @param QRelation $relB
     * @return QMatchPlan
     */
    public function setRelB($relB) {
        $this->relB = $relB;
        return $this;
    }
}
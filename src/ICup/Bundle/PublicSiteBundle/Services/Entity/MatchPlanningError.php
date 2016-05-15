<?php
namespace ICup\Bundle\PublicSiteBundle\Services\Entity;

use ICup\Bundle\PublicSiteBundle\Entity\MatchPlan;
use ICup\Bundle\PublicSiteBundle\Services\Entity\PlaygroundAttribute as PA;
use DateTime;

/**
 * Class MatchPlanningError
 * @package ICup\Bundle\PublicSiteBundle\Services\Entity
 */
class MatchPlanningError
{
    /**
     * @var $match MatchPlan
     */
    private $match;
    /**
     * @var $pa PA
     */
    private $pa;
    /**
     * @var $slotschedule DateTime
     */
    private $slotschedule;
    /**
     * @var
     */
    private $error;

    /**
     * MatchPlanningError constructor.
     * @param PA $pa
     * @param DateTime $slotschedule
     * @param $error
     */
    public function __construct(MatchPlan $match, PA $pa, DateTime $slotschedule, $error) {
        $this->match = $match;
        $this->pa = $pa;
        $this->slotschedule = $slotschedule;
        $this->error = $error;
    }

    /**
     * @return MatchPlan
     */
    public function getMatch() {
        return $this->match;
    }

    /**
     * @param MatchPlan $match
     * @return MatchPlanningError
     */
    public function setMatch($match) {
        $this->match = $match;
        return $this;
    }

    /**
     * @return PA
     */
    public function getPA() {
        return $this->pa;
    }

    /**
     * @param PA $pa
     * @return MatchPlanningError
     */
    public function setPA(PA $pa) {
        $this->pa = $pa;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getSlotschedule() {
        return $this->slotschedule;
    }

    /**
     * @param DateTime $slotschedule
     * @return MatchPlanningError
     */
    public function setSlotschedule(DateTime $slotschedule) {
        $this->slotschedule = $slotschedule;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getError() {
        return $this->error;
    }

    /**
     * @param mixed $error
     * @return MatchPlanningError
     */
    public function setError($error) {
        $this->error = $error;
        return $this;
    }
}
<?php

namespace ICup\Bundle\PublicSiteBundle\Services\Entity;

use DateTime;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Timeslot;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Playground;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\PlaygroundAttribute as PA;

class PlaygroundAttribute
{
    /**
     * @var integer $pa
     * Master for this attribute
     */
    private $pa;

    /**
     * @var array $categories
     * List of categories related to playground attribute
     */
    private $categories;

    /**
     * @var string $schedule
     * Current available time for this timeslot
     */
    private $schedule;

    /**
     * @var array $matchlist
     * List of matches allocated to this timeslot
     */
    private $matchlist;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->getPA()->getId();
    }

    /**
     * Set id
     *
     * @param PA $id
     * @return PlaygroundAttribute
     */
    public function setPA($pa)
    {
        $this->pa = $pa;

        return $this;
    }

    /**
     * Get id
     *
     * @return PA
     */
    public function getPA()
    {
        return $this->pa;
    }

    /**
     * Get parent id - related tournament
     *
     * @return Playground 
     */
    public function getPlayground()
    {
        return $this->getPA()->getPlayground();
    }

    /**
     * Get child id - related timeslot
     *
     * @return Timeslot
     */
    public function getTimeslot()
    {
        return $this->getPA()->getTimeslot();
    }

    /**
     * Get related categories
     *
     * @return array 
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * Set related categories
     *
     * @param array $categories
     * @return PlaygroundAttribute
     */
    public function setCategories($categories)
    {
        $this->categories = $categories;
        return $this;
    }
    
    /**
     * Set date
     *
     * @param DateTime $date
     * @return PlaygroundAttribute
     */
    public function setSchedule($schedule)
    {
        $this->schedule = $schedule;
    
        return $this;
    }

    /**
     * Get date
     *
     * @return DateTime
     */
    public function getSchedule()
    {
        return $this->schedule;
    }

    /**
     * @param $matchlist
     * @return PlaygroundAttribute
     */
    public function setMatchlist($matchlist) {
        $this->matchlist = $matchlist;

        return $this;
    }

    /**
     * @return array
     */
    public function getMatchlist() {
        return $this->matchlist;
    }

    /**
     * Get slot time left for this attribute
     *
     * @return integer 
     */
    public function getTimeleft()
    {
        $diff = $this->getPA()->getEndSchedule()->diff($this->getSchedule());
        return $diff->d*24*60 + $diff->h*60 + $diff->i;
    }

    /**
     * Get list of category names
     *
     * @return integer
     */
    public function getCategoryNames()
    {
        $names = array();
        foreach ($this->getCategories() as $category) {
            /* @var $category Category */
            $names[] = $category->getName();
        }
        return $names;
    }

    public function isCategoryAllowed(Category $category) {
        return count($this->categories) == 0 || isset($this->categories[$category->getId()]);
    }

    public function isClassificationAllowed($classification) {
        $cls = $this->getPA()->getClassification();
        return $cls == 0 || $cls == $classification;
    }
}
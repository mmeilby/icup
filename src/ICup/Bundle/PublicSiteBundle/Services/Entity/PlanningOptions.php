<?php

namespace ICup\Bundle\PublicSiteBundle\Services\Entity;

/**
 * PlanningOptions
 */
class PlanningOptions
{
    /**
     * @var boolean $doublematch
     *
     */
    private $doublematch;

    /**
     * @var boolean $preferpg
     *
     */
    private $preferpg;

    /**
     * @var boolean $finals
     *
     */
    private $finals;

    /**
     * @return boolean
     */
    public function isDoublematch()
    {
        return $this->doublematch;
    }

    /**
     * @param boolean $doublematch
     */
    public function setDoublematch($doublematch)
    {
        $this->doublematch = $doublematch;
    }

    /**
     * @return boolean
     */
    public function isPreferpg()
    {
        return $this->preferpg;
    }

    /**
     * @param boolean $preferpg
     */
    public function setPreferpg($preferpg)
    {
        $this->preferpg = $preferpg;
    }

    /**
     * @return boolean
     */
    public function isFinals()
    {
        return $this->finals;
    }

    /**
     * @param boolean $finals
     */
    public function setFinals($finals)
    {
        $this->finals = $finals;
    }

    public function getArray() {
        return array(
            "doublematch" => $this->doublematch,
            "preferpg" => $this->preferpg,
            "finals" => $this->finals,
        );
    }
}
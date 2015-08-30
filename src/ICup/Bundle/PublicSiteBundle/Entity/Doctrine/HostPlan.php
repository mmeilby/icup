<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\HostPlan
 *
 * @ORM\Table(name="hostplans")
 * @ORM\Entity
 */
class HostPlan
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
     * @var ArrayCollection $hosts
     * Collection of hosts signed up for this host plan
     * @ORM\OneToMany(targetEntity="Host", mappedBy="hostplan", cascade={"persist"})
     */
    protected $hosts;

    /**
     * @var boolean $ads
     * Allow ads on specific pages?
     * @ORM\Column(name="ads", type="boolean", nullable=false, options={"default":true})
     */
    protected $ads = true;

    /**
     * HostPlan constructor.
     */
    public function __construct() {
        $this->hosts = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return ArrayCollection
     */
    public function getHosts() {
        return $this->hosts;
    }

    /**
     * @return boolean
     */
    public function isAds() {
        return $this->ads;
    }

    /**
     * @param boolean $ads
     * @return HostPlan
     */
    public function setAds($ads) {
        $this->ads = $ads;
        return $this;
    }
}
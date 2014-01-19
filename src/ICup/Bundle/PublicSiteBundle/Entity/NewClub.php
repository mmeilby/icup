<?php

namespace ICup\Bundle\PublicSiteBundle\Entity;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\NewClub
 */
class NewClub
{
    /**
     * @var integer $id
     *
     */
    private $id;

    /**
     * @var string $name
     *
     */
    private $name;

    /**
     * @var string $name
     *
     */
    public $clubs;

    /**
     * @var string $country
     *
     */
    private $country;

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
     * Set name
     *
     * @param string $name
     * @return Club
     */
    public function setName($name)
    {
        $this->name = $name;
    
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
     * Set country
     *
     * @param string $country
     * @return Club
     */
    public function setCountry($country)
    {
        $this->country = $country;
    
        return $this;
    }

    /**
     * Get country
     *
     * @return string 
     */
    public function getCountry()
    {
        return $this->country;
    }
}
<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Country
 *
 * @ORM\Table(name="countries", uniqueConstraints={@ORM\UniqueConstraint(name="CountryConstraint", columns={"country"})})
 * @ORM\Entity
 */
class Country
{
    /**
     * @var string $country
     * Country code for residences - DNK for Denmark, DEU for Germany, FRA for France and so on...
     * @ORM\Column(name="country", type="string", length=3, nullable=false)
     * @ORM\Id
     */
    protected $country;

    /**
     * @var string $name
     * Country name (in some language - not used beyond this table)
     * @ORM\Column(name="name", type="string", length=50, nullable=false)
     */
    protected $name;

    /**
     * @var string $flag
     * File name for flag image
     * @ORM\Column(name="flag", type="string", length=50, nullable=false)
     */
    protected $flag;

    /**
     * @var boolean $active
     * Allow this country to be slected?
     * @ORM\Column(name="active", type="boolean", nullable=false, options={"default":true})
     */
    protected $active = true;

    /**
     * @return string
     */
    public function getCountry() {
        return $this->country;
    }

    /**
     * @param string $country
     * @return Country
     */
    public function setCountry($country) {
        $this->country = $country;
        return $this;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Country
     */
    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getFlag() {
        return $this->flag;
    }

    /**
     * @param string $flag
     * @return Country
     */
    public function setFlag($flag) {
        $this->flag = $flag;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isActive() {
        return $this->active;
    }

    /**
     * @param boolean $active
     * @return Country
     */
    public function setActive($active) {
        $this->active = $active;
        return $this;
    }
}
<?php

namespace ICup\Bundle\PublicSiteBundle\Entity;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club;

/**
 * Created by PhpStorm.
 * User: mm
 * Date: 17/05/2017
 * Time: 22.59
 */
class ClubForm
{
    protected $id;
    protected $name;
    protected $address;
    protected $city;
    protected $countrycode;

    /**
     * Club constructor.
     */
    public function __construct(Club $club) {
        $this->setId($club->getId());
        $this->setName($club->getName());
        $this->setAddress($club->getAddress());
        $this->setCity($club->getCity());
        if ($club->getCountry()) {
            $this->setCountrycode($club->getCountry()->getCountry());
        }
    }

    /**
     * @return mixed
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param mixed $id
     * @return ClubForm
     */
    public function setId($id) {
        $this->id = $id;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCountrycode() {
        return $this->countrycode;
    }

    /**
     * @param mixed $countrycode
     */
    public function setCountrycode($countrycode) {
        $this->countrycode = $countrycode;
    }

    /**
     * @return mixed
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param mixed $name
     * @return ClubForm
     */
    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getAddress() {
        return $this->address;
    }

    /**
     * @param mixed $address
     * @return ClubForm
     */
    public function setAddress($address) {
        $this->address = $address;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCity() {
        return $this->city;
    }

    /**
     * @param mixed $city
     * @return ClubForm
     */
    public function setCity($city) {
        $this->city = $city;
        return $this;
    }
}
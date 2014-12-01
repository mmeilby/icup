<?php

namespace ICup\Bundle\PublicSiteBundle\Entity;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Contact
 */
class Contact
{
    /**
     * @var string $name
     *
     */
    private $name;

    /**
     * @var string $club
     *
     */
    public $club;

    /**
     * @var string $phone
     *
     */
    private $phone;

    /**
     * @var string $email
     *
     */
    private $email;

    /**
     * @var string $msg
     *
     */
    private $msg;

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
     * Set name
     *
     * @param string $name
     * @return Club
     */
    public function setClub($name)
    {
        $this->club = $name;
    
        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getClub()
    {
        return $this->club;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Club
     */
    public function setPhone($name)
    {
        $this->phone = $name;
    
        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getPhone()
    {
        return $this->phone;
    }
    
    /**
     * Set name
     *
     * @param string $name
     * @return Club
     */
    public function setEmail($name)
    {
        $this->email = $name;
    
        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getEmail()
    {
        return $this->email;
    }
    
    /**
     * Set name
     *
     * @param string $name
     * @return Club
     */
    public function setMsg($name)
    {
        $this->msg = $name;
    
        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getMsg()
    {
        return $this->msg;
    }
    
    public function getArray() {
        return array(
            "name" => $this->name,
            "club" => $this->club,
            "phone" => $this->phone,
            "email" => $this->email,
            "msg" => $this->msg
        );
    }
}
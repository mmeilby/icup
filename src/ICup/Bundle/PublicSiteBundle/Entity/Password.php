<?php
namespace ICup\Bundle\PublicSiteBundle\Entity;

class Password {

    private $password;
    private $password2;

    /**
     * Set password
     *
     * @param string $password
     * @return Club
     */
    public function setPassword($password)
    {
        $this->password = $password;
    
        return $this;
    }

    /**
     * Get password
     *
     * @return string 
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set password2
     *
     * @param string $password2
     * @return Club
     */
    public function setPassword2($password2)
    {
        $this->password2 = $password2;
    
        return $this;
    }

    /**
     * Get password2
     *
     * @return string 
     */
    public function getPassword2()
    {
        return $this->password2;
    }

    public function isValid() {
        return $this->password == $this->password2;
    }
}
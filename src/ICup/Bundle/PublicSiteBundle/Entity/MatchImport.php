<?php

namespace ICup\Bundle\PublicSiteBundle\Entity;

class MatchImport
{
    /**
     * @var string $date
     * Match start date - DD/MM/YYYY
     */
    private $date;

    /**
     * @var String $import
     * Match details as a string in following format:
     *   7 09:15 C A AETNA MASCALUCIA (ITA) TVIS KFUM "A" (DNK)
     */
    private $import;

    /**
     * Set date
     *
     * @param string $date
     * @return MatchImport
     */
    public function setDate($date)
    {
        $this->date = $date;
    
        return $this;
    }

    /**
     * Get date
     *
     * @return string 
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set match no
     *
     * @param integer $import
     * @return MatchImport
     */
    public function setImport($import)
    {
        $this->import = $import;
    
        return $this;
    }

    /**
     * Get match no
     *
     * @return integer 
     */
    public function getImport()
    {
        return $this->import;
    }
}
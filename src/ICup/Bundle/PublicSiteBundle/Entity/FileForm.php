<?php

namespace ICup\Bundle\PublicSiteBundle\Entity;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\FileForm
 */
class FileForm
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
     * @var string $path
     *
     */
    private $path;

    /**
     * @var string $msg
     *
     */
    private $file;

    /**
     * Set name
     *
     * @param string $name
     * @return FileForm
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
     * @return FileForm
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
     * @return FileForm
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
     * Set path
     *
     * @param string $path
     * @return FileForm
     */
    public function setPath($path)
    {
        $this->path = $path;
    
        return $this;
    }

    /**
     * Get path
     *
     * @return string 
     */
    public function getPath()
    {
        return $this->path;
    }
    
    /**
     * Set file
     *
     * @param string $file
     * @return FileForm
     */
    public function setFile($file)
    {
        $this->file = $file;
    
        return $this;
    }

    /**
     * Get file
     *
     * @return string 
     */
    public function getFile()
    {
        return $this->file;
    }
}
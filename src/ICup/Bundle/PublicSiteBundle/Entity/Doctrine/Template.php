<?php
namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\ORM\Mapping as ORM;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Template
 *
 * @ORM\Table(name="templates",uniqueConstraints={@ORM\UniqueConstraint(name="IdxByName", columns={"name", "id"})})
 * @ORM\Entity
 */
class Template
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer $pid
     * Relation to Tournament - pid=tournament.id 
     * @ORM\Column(name="pid", type="integer", nullable=false)
     */
    private $pid;

    /**
     * @var string $name
     *
     * @ORM\Column(name="name", type="string", length=50, nullable=false)
     */
    private $name;

    /**
     * @var string $country
     *
     * @ORM\Column(name="source", type="string", length=1024, nullable=false)
     */
    private $source;

    /**
     * @var string $username
     *
     * @ORM\Column(name="last_modified", type="string", length=10, nullable=false)
     */
    private $last_modified;

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
     * Set parent id
     *
     * @param integer $pid
     * @return Site
     */
    public function setPid($pid)
    {
        $this->pid = $pid;
    
        return $this;
    }

    /**
     * Get parent id - related tournament
     *
     * @return integer 
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Template
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
     * Set source
     *
     * @param string $source
     * @return Template
     */
    public function setSource($source)
    {
        $this->source = $source;
    
        return $this;
    }

    /**
     * Get source
     *
     * @return string 
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Sets the last modification.
     *
     * @param string $last_modified
     * @return Template
     */
    public function setLastModified($last_modified)
    {
        $this->last_modified = $last_modified;
    
        return $this;
    }

    /**
     * Returns the last modification.
     *
     * @return string The last modification
     */
    public function getLastModified()
    {
        return $this->last_modified;
    }
}
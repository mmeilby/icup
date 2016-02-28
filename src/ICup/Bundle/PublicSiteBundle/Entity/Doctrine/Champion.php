<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\ORM\Mapping as ORM;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Champion
 *
 * @ORM\Table(name="champions")
 * @ORM\Entity
 */
class Champion
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
     * @var Category $category
     * Relation to Category
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="champions", cascade={"persist"})
     * @ORM\JoinColumn(name="category", referencedColumnName="id")
     */
    protected $category;

    /**
     * @var integer $champion
     * The champion rank for this category - 1=winner, 2=second place, ...
     * @ORM\Column(name="champion", type="integer", nullable=false)
     */
    protected $champion;

    /**
     * @var Group $group
     * The qualifying group
     * @ORM\ManyToOne(targetEntity="Group", cascade={"persist"})
     * @ORM\JoinColumn(name="cid", referencedColumnName="id")
     */
    protected $group;
    
    /**
     * @var integer $rank
     * The rank required by the team in qualifying group to achieve the championship - 1=first place, 2=second place, ...
     * @ORM\Column(name="rank", type="integer", nullable=false)
     */
    protected $rank;

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
     * @return Category
     */
    public function getCategory() {
        return $this->category;
    }

    /**
     * @param Category $category
     * @return Champion
     */
    public function setCategory($category) {
        $this->category = $category;
        return $this;
    }

    /**
     * @return int
     */
    public function getChampion() {
        return $this->champion;
    }

    /**
     * @param int $champion
     * @return Champion
     */
    public function setChampion($champion) {
        $this->champion = $champion;
        return $this;
    }

    /**
     * @return Group
     */
    public function getGroup() {
        return $this->group;
    }

    /**
     * @param Group $group
     * @return Champion
     */
    public function setGroup($group) {
        $this->group = $group;
        return $this;
    }

    /**
     * @return int
     */
    public function getRank() {
        return $this->rank;
    }

    /**
     * @param int $rank
     * @return Champion
     */
    public function setRank($rank) {
        $this->rank = $rank;
        return $this;
    }
}
<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\HostKey
 *
 * @ORM\Table(name="hostkeys", uniqueConstraints={@ORM\UniqueConstraint(name="KeyConstraint", columns={"apikey"})})
 * @ORM\Entity
 */
class HostKey implements JsonSerializable
{
    const KEYSTATUS_TYPE_VALID = 'V';
    const KEYSTATUS_TYPE_REVOKED = 'R';

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var Host $host
     * Relation to Host
     * @ORM\ManyToOne(targetEntity="Host", inversedBy="keys")
     * @ORM\JoinColumn(name="pid", referencedColumnName="id")
     */
    protected $host;

    /**
     * @var string $apikey
     * APIkey used in API method calls
     * @ORM\Column(name="apikey", type="string", length=50, nullable=false, unique=true)
     */
    protected $apikey;

    /**
     * @var string $status
     * APIkey status - valid or revoked
     * @ORM\Column(name="status", type="string", length=2, nullable=false)
     */
    protected $status;

    /**
     * @var string $date
     * APIkey created date or revoked date
     * @ORM\Column(name="date", type="string", length=10, nullable=false)
     */
    protected $date;

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
     * @return Host
     */
    public function getHost() {
        return $this->host;
    }

    /**
     * @param Host $host
     * @return HostKey
     */
    public function setHost($host) {
        $this->host = $host;
        return $this;
    }

    /**
     * @return string
     */
    public function getApikey() {
        return $this->apikey;
    }

    /**
     * @param string $apikey
     * @return HostKey
     */
    public function setApikey($apikey) {
        $this->apikey = $apikey;
        return $this;
    }

    /**
     * @return string
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * @param string $status
     * @return HostKey
     */
    public function setStatus($status) {
        $this->status = $status;
        return $this;
    }

    /**
     * @return string
     */
    public function getDate() {
        return $this->date;
    }

    /**
     * @param string $date
     * @return HostKey
     */
    public function setDate($date) {
        $this->date = $date;
        return $this;
    }

    public function __toString() {
        return $this->getHost()->getName().": ".$this->getApikey();
    }

    /**
     * (PHP 5 &gt;= 5.4.0)<br/>
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     */
    function jsonSerialize() {
        return array(
            "objectType" => "HostKey",
            "id" => $this->id, "host" => $this->host->jsonSerialize(), "apikey" => $this->apikey,
            "status" => $this->status, "date" => Date::jsonDateSerialize($this->date)
        );
    }
}
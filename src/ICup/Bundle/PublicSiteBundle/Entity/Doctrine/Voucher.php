<?php

namespace ICup\Bundle\PublicSiteBundle\Entity\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Voucher
 *
 * @ORM\Table(name="vouchers")
 * @ORM\Entity
 */
class Voucher implements JsonSerializable
{
    const VOUCHER_TYPE_ENROLLMENT = 'E';
    const VOUCHER_TYPE_CARD_PAYMENT = 'C';
    const VOUCHER_TYPE_BANK_PAYMENT = 'B';
    const VOUCHER_TYPE_REFUND = 'R';
    const VOUCHER_TYPE_DISCOUNT = 'D';

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var Club $club
     * Relation to Club
     * @ORM\ManyToOne(targetEntity="Club", inversedBy="vouchers")
     * @ORM\JoinColumn(name="pid", referencedColumnName="id")
     */
    protected $club;

    /**
     * @var string $voucherid
     * Voucher reference id
     * @ORM\Column(name="voucherid", type="string", length=50, nullable=false)
     */
    protected $voucherid;

    /**
     * @var string $date
     * Voucher date
     * @ORM\Column(name="date", type="string", length=10, nullable=false)
     */
    protected $date;

    /**
     * @var string $amount
     * Voucher amount (in smallest currency - øre, cent, ...)
     * @ORM\Column(name="amount", type="integer", nullable=false)
     */
    protected $amount;

    /**
     * @var string $currency
     * Currency identification
     * @ORM\Column(name="currency", type="string", length=3, nullable=false)
     */
    protected $currency;

    /**
     * @var string $vtype
     * Voucher type
     * @ORM\Column(name="vtype", type="string", length=1, nullable=false)
     */
    protected $vtype;

    /**
     * Group constructor.
     */
    public function __construct() {
    }

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
     * @return Club
     */
    public function getClub() {
        return $this->club;
    }

    /**
     * @param Club $club
     */
    public function setClub($club) {
        $this->club = $club;
    }

    /**
     * @return string
     */
    public function getVoucherid() {
        return $this->voucherid;
    }

    /**
     * @param string $voucherid
     * @return Voucher
     */
    public function setVoucherid($voucherid) {
        $this->voucherid = $voucherid;
        return $this;
    }

    /**
     * @return string
     */
    public function getAmount() {
        return $this->amount;
    }

    /**
     * @param string $amount
     * @return Voucher
     */
    public function setAmount($amount) {
        $this->amount = $amount;
        return $this;
    }

    /**
     * @return string
     */
    public function getCurrency() {
        return $this->currency;
    }

    /**
     * @param string $currency
     * @return Voucher
     */
    public function setCurrency($currency) {
        $this->currency = $currency;
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
     * @return Voucher
     */
    public function setDate($date) {
        $this->date = $date;
        return $this;
    }

    /**
     * @return string
     */
    public function getVtype() {
        return $this->vtype;
    }

    /**
     * @param string $vtype
     * @return Voucher
     */
    public function setVtype($vtype) {
        $this->vtype = $vtype;
        return $this;
    }

    public function __toString() {
        return $this->isVacant() ? $this->getTeamName() : $this->getTeamName()." (".$this->getClub()->getCountryCode().")";
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
            "objectType" => "Voucher",
            "id" => $this->id, "name" => $this->name, "teamname" => $this->getTeamName(),
            "color" => $this->color, "division" => $this->division, "vacant" => $this->isVacant(),
            "country_code" => $this->getClub()->getCountryCode(),
            "flag" => $this->getClub()->getCountry()->getFlag()
        );
    }
}
<?php

namespace ICup\Bundle\PublicSiteBundle\Entity;

class EnrollmentTeamCheckoutForm
{
    /**
     * @var String $token
     */
    protected $token;

    /**
     * @var \DateTime $tx_timestamp
     */
    protected $tx_timestamp;

    /**
     * @var array $enrolled
     * { teams, price }
     */
    protected $enrolled;

    /**
     * @var array $club
     * { name, country }
     */
    protected $club;

    /**
     * @var array $manager
     * { name, email, mobile }
     */
    protected $manager;

    /**
     * @return String
     */
    public function getToken() {
        return $this->token;
    }

    /**
     * @param String $token
     */
    public function setToken($token) {
        $this->token = $token;
    }

    /**
     * @return String
     */
    public function getTxTimestamp() {
        return $this->tx_timestamp;
    }

    /**
     * @param String $tx_timestamp
     */
    public function setTxTimestamp($tx_timestamp) {
        $this->tx_timestamp = $tx_timestamp;
    }

    /**
     * @return array
     */
    public function getEnrolled() {
        return $this->enrolled;
    }

    /**
     * @param array $enrolled
     */
    public function setEnrolled($enrolled) {
        $this->enrolled = $enrolled;
    }

    /**
     * @return array
     */
    public function getClub() {
        return $this->club;
    }

    /**
     * @param array $club
     */
    public function setClub($club) {
        $this->club = $club;
    }

    /**
     * @return array
     */
    public function getManager() {
        return $this->manager;
    }

    /**
     * @param array $manager
     */
    public function setManager($manager) {
        $this->manager = $manager;
    }
}
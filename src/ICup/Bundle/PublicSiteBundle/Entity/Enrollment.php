<?php

namespace ICup\Bundle\PublicSiteBundle\Entity;

/**
 * ICup\Bundle\PublicSiteBundle\Entity\Enrollment
 */
class Enrollment
{
    public $club;
    public $address;
    public $city;
    public $country;
    public $phone;
    public $fax;
    public $skype;
    public $email;
    public $website;
    public $membership;
    public $affiliation;
    public $championship;
    public $bestresult;

    public $manager;
    public $m_address;
    public $m_city;
    public $m_country;
    public $m_phone;
    public $m_fax;
    public $m_skype;
    public $m_mobile;
    public $m_email;

    public $t_ntmu18;
    public $t_ntfu18;
    public $t_mo18;
    public $t_fo18;
    public $t_mu18;
    public $t_fu18;
    public $t_mu16;
    public $t_fu16;
    public $t_mu14;
    public $t_fu14;
    public $t_mu12;
    public $t_fu12;
    public $t_total;
        
    public $a_teramo_wb;
    public $a_teramo_wob;
    public $a_teramo_tent;
    public $a_guilianova_tent;
    public $a_restaurant;
    public $a_teramo_hotel;
    public $a_guilianova_hotel;
    public $a_other;
    public $a_none;
    public $a_total;
    public $a_transport;

    public $arrival_date;
    public $arrival_time;
    public $departure_date;
    public $departure_time;

    public $bus;
    public $arrival_date_roma_f;
    public $arrival_date_roma_c;
    public $arrival_date_pescara;
    public $arrival_date_ancona;
    public $departure_date_roma_f;
    public $departure_date_roma_c;
    public $departure_date_pescara;
    public $departure_date_ancona;
        
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
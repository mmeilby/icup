<?php

namespace ICup\Bundle\PublicSiteBundle\Entity;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;


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

    public $arrival_date;
    public $arrival_time;
    public $departure_date;
    public $departure_time;

    public $b_arrival_airport;
    public $b_arrival_date;
    public $b_arrival_time;
    public $b_departure_airport;
    public $b_departure_date;
    public $b_departure_time;

    public function getArray() {
        return array(
            // Club - step1
            "club" => $this->club,
            "address" => $this->address,
            "city" => $this->city,
            "country" => $this->country,
            "phone" => $this->phone,
            "fax" => $this->fax,
            "skype" => $this->skype,
            "email" => $this->email,
            "website" => $this->website,
            "membership" => $this->membership,
            "affiliation" => $this->affiliation,
            "championship" => $this->championship,
            "bestresult" => $this->bestresult,
            // Manager - step2
            "manager" => $this->manager,
            "m_address" => $this->m_address,
            "m_city" => $this->m_city,
            "m_country" => $this->m_country,
            "m_phone" => $this->m_phone,
            "m_fax" => $this->m_fax,
            "m_skype" => $this->m_skype,
            "m_mobile" => $this->m_mobile,
            "m_email" => $this->m_email,
            // Teams - step3
            "male_uniteam" => $this->t_ntmu18,
            "female_uniteam" => $this->t_ntfu18,
            "male_o18" => $this->t_mo18,
            "female_o18" => $this->t_fo18,
            "male_u18" => $this->t_mu18,
            "female_u18" => $this->t_fu18,
            "male_u16" => $this->t_mu16,
            "female_u16" => $this->t_fu16,
            "male_u14" => $this->t_mu14,
            "female_u14" => $this->t_fu14,
            "male_u12" => $this->t_mu12,
            "female_u12" => $this->t_fu12,
            "team_total" => $this->t_total,
            // Lodging - step4
            "lodging_teramo_with_beds" => $this->a_teramo_wb,
            "lodging_teramo_without_beds" => $this->a_teramo_wob,
            "lodging_teramo_tents" => $this->a_teramo_tent,
            "lodging_seaside_tents" => $this->a_guilianova_tent,
            "lodging_with_meals" => $this->a_restaurant,
            "lodging_teramo_hotel" => $this->a_teramo_hotel,
            "lodging_seaside_hotel" => $this->a_guilianova_hotel,
            "lodging_other_requests" => $this->a_other,
            "lodging_independant_teams" => $this->a_none,
            "lodging_total_people" => $this->a_total,
            // Transport - step5
            "arrival_date" => $this->arrival_date,
            "arrival_time" => $this->arrival_time,
            "departure_date" => $this->departure_date,
            "departure_time" => $this->departure_time,
            "arrival_airport" => $this->b_arrival_airport,
            "b_arrival_date" => $this->b_arrival_date,
            "b_arrival_time" => $this->b_arrival_time,
            "departure_airport" => $this->b_departure_airport,
            "b_departure_date" => $this->b_departure_date,
            "b_departure_time" => $this->b_departure_time,
        );
    }

    public function checkForm(Form $form, ValidatorInterface $validator, TranslatorInterface $translator) {
        if ($form->isValid()) {
            if ($this->club == null || trim($this->club) == '') {
                $form->get('club')->addError(new FormError($translator->trans('FORM.ENROLLMENT.NOCLUB', array(), 'club')));
            }
            if ($this->address == null || trim($this->address) == '') {
                $form->get('address')->addError(new FormError($translator->trans('FORM.ENROLLMENT.NOADDRESS', array(), 'club')));
            }
            if ($this->city == null || trim($this->city) == '') {
                $form->get('city')->addError(new FormError($translator->trans('FORM.ENROLLMENT.NOCITY', array(), 'club')));
            }
            if ($this->country == null || trim($this->country) == '') {
                $form->get('country')->addError(new FormError($translator->trans('FORM.ENROLLMENT.NOCOUNTRY', array(), 'club')));
            }
            if ($this->email == null || trim($this->email) == '') {
                $form->get('email')->addError(new FormError($translator->trans('FORM.ENROLLMENT.NOEMAIL', array(), 'club')));
            }
            else {
                $emailConstraint = new Email();
                $emailConstraint->checkHost = true;
                $emailConstraint->message = $translator->trans('FORM.ENROLLMENT.INVALIDEMAIL', array(), 'club');
                /* @var $violations ConstraintViolationListInterface */
                $violations = $validator->validate($this->email, $emailConstraint);
                foreach ($violations as $violation) {
                    $form->get('email')->addError(new FormError($violation->getMessage()));
                }
            }
        }
        return $form->isValid();
    }

    public function checkForm2(Form $form, ValidatorInterface $validator, TranslatorInterface $translator) {
        if ($form->isValid()) {
            if ($this->manager == null || trim($this->manager) == '') {
                $form->get('manager')->addError(new FormError($translator->trans('FORM.ENROLLMENT.NOMANAGER', array(), 'club')));
            }
            if ($this->m_mobile == null || trim($this->m_mobile) == '') {
                $form->get('m_mobile')->addError(new FormError($translator->trans('FORM.ENROLLMENT.NOMOBILE', array(), 'club')));
            }
            if ($this->m_email == null || trim($this->m_email) == '') {
                $form->get('m_email')->addError(new FormError($translator->trans('FORM.ENROLLMENT.NOEMAIL', array(), 'club')));
            }
            else {
                $emailConstraint = new Email();
                $emailConstraint->checkHost = true;
                $emailConstraint->message = $translator->trans('FORM.ENROLLMENT.INVALIDEMAIL', array(), 'club');
                /* @var $violations ConstraintViolationListInterface */
                $violations = $validator->validate($this->m_email, $emailConstraint);
                foreach ($violations as $violation) {
                    $form->get('m_email')->addError(new FormError($violation->getMessage()));
                }
            }
        }
        return $form->isValid();
    }

    public function checkForm3(Form $form, ValidatorInterface $validator, TranslatorInterface $translator) {
        if ($form->isValid()) {
            $this->t_total =
                $this->t_ntmu18 + $this->t_ntfu18 +
                $this->t_mo18 + $this->t_fo18 +
                $this->t_mu18 + $this->t_fu18 +
                $this->t_mu16 + $this->t_fu16 +
                $this->t_mu14 + $this->t_fu14 +
                $this->t_mu12 + $this->t_fu12;
            if ($this->t_total == null || trim($this->t_total) == '') {
                $form->addError(new FormError($translator->trans('FORM.ENROLLMENT.NOCATEGORIES', array(), 'club')));
            }
        }
        return $form->isValid();
    }

    public function checkForm4(Form $form, ValidatorInterface $validator, TranslatorInterface $translator) {
        if ($form->isValid()) {
            $this->a_total =
                $this->a_teramo_wb + $this->a_teramo_wob + $this->a_teramo_tent +
                $this->a_guilianova_tent + $this->a_restaurant + $this->a_teramo_hotel +
                $this->a_guilianova_hotel + $this->a_none;
            if ($this->a_total == null || trim($this->a_total) == '') {
                $form->addError(new FormError($translator->trans('FORM.ENROLLMENT.NOACCOMODATION', array(), 'club')));
            }
        }
        return $form->isValid();
    }

    public function checkForm5(Form $form, ValidatorInterface $validator, TranslatorInterface $translator) {
        if ($form->isValid()) {
            if ($this->arrival_date == null || trim($this->arrival_date) == '') {
                $form->get('arrival_date')->addError(new FormError($translator->trans('FORM.ENROLLMENT.NOARRIVALDATE', array(), 'club')));
            }
            if ($this->arrival_time == null || trim($this->arrival_time) == '') {
//                $form->get('arrival_time')->addError(new FormError($translator->trans('FORM.ENROLLMENT.NOARRIVALTIME', array(), 'club')));
            }
            if ($this->departure_date == null || trim($this->departure_date) == '') {
                $form->get('departure_date')->addError(new FormError($translator->trans('FORM.ENROLLMENT.NODEPARTUREDATE', array(), 'club')));
            }
            if ($this->departure_time == null || trim($this->departure_time) == '') {
//                $form->get('departure_time')->addError(new FormError($translator->trans('FORM.ENROLLMENT.NODEPARTURETIME', array(), 'club')));
            }
        }
        return $form->isValid();
    }
}
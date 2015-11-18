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
            "bestresult" => $this->bestresult
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
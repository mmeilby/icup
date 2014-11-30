<?php

namespace ICup\Bundle\PublicSiteBundle\Controller\General;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class EnrollmentController extends Controller
{
    /**
     * @Route("/enrollment", name="_enrollment")
     * @Template("ICupPublicSiteBundle:General:enrollment.html.twig")
     */
    public function showEnrollment()
    {
//        $form = $this->makeEnrollmentForm(new Contact());
//        $request = $this->getRequest();
//        $form->handleRequest($request);

//        return array('form' => $form->createView(), 'tournaments' => $tournamentList, 'statuslist' => $statusList);
        return array();
    }
    
    private function makeEnrollmentForm(Contact $contact) {
        $formDef = $this->createFormBuilder($contact);
        $formDef->add('club', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('address', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('city', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('country', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('phone', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('fax', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('skype', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('email', 'text', array('label' => 'FORM.FRONTPAGE.EMAIL', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('website', 'text', array('label' => 'FORM.FRONTPAGE.NAME', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('membership', 'text', array('label' => 'FORM.FRONTPAGE.PHONE', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('affiliation', 'text', array('label' => 'FORM.FRONTPAGE.EMAIL', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('championship', 'text', array('label' => 'FORM.FRONTPAGE.EMAIL', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('bestresult', 'text', array('label' => 'FORM.FRONTPAGE.EMAIL', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));

        $formDef->add('manager', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('m_address', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('m_city', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('m_country', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('m_phone', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('m_fax', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('m_skype', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('m_mobile', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('m_email', 'text', array('label' => 'FORM.FRONTPAGE.EMAIL', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));

        $formDef->add('t_ntmu18', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('t_ntfu18', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('t_mo18', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('t_fo18', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('t_mu18', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('t_fu18', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('t_mu16', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('t_fu16', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('t_mu14', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('t_fu14', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('t_mu12', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('t_fu12', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('t_total', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        
        $formDef->add('a_teramo_wb', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('a_teramo_wob', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('a_teramo_tent', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('a_guilianova_tent', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('a_restaurant', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('a_teramo_hotel', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('a_guilianova_hotel', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('a_other', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('a_none', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('a_total', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('a_transport', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));

        $formDef->add('arrival_date', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('arrival_time', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('departure_date', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('departure_time', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));

        $formDef->add('bus', 'check', array('label' => 'FORM.FRONTPAGE.MSG', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('arrival_date_roma_f', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('arrival_date_roma_c', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('arrival_date_pescara', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('arrival_date_ancona', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('departure_date_roma_f', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('departure_date_roma_c', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('departure_date_pescara', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('departure_date_ancona', 'text', array('label' => 'FORM.FRONTPAGE.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));

        $formDef->add('msg', 'textarea', array('label' => 'FORM.FRONTPAGE.MSG', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('send', 'submit', array('label' => 'FORM.FRONTPAGE.SUBMIT',
                                                'translation_domain' => 'club',
                                                'icon' => 'fa fa-envelope'));
        return $formDef->getForm();
    }
}

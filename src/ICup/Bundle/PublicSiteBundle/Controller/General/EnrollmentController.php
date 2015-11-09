<?php

namespace ICup\Bundle\PublicSiteBundle\Controller\General;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ICup\Bundle\PublicSiteBundle\Entity\Enrollment;
use Symfony\Component\HttpFoundation\Request;
use Swift_Message;

class EnrollmentController extends Controller
{
    /**
     * @Route("/enrollment/step1", name="_enrollment_step1")
     * @Template("ICupPublicSiteBundle:General:enrollment/enrollment_step1.html.twig")
     */
    public function showEnrollment(Request $request)
    {
        $enrollform = $request->getSession()->get('enrollform', new Enrollment());
        $form = $this->makeEnrollmentFormStep1($enrollform);
        $form->handleRequest($request);
        if ($enrollform->checkForm($form, $this->get('validator'), $this->get('translator'))) {
            $request->getSession()->set('enrollform', $enrollform);
            return $this->redirectToRoute('_enrollment_step2');
        }
        return array('form' => $form->createView());
    }

    /**
     * @Route("/enrollment/step2", name="_enrollment_step2")
     * @Template("ICupPublicSiteBundle:General:enrollment/enrollment_step2.html.twig")
     */
    public function showEnrollmentStep2(Request $request)
    {
        $enrollform = $request->getSession()->get('enrollform', new Enrollment());
        $form = $this->makeEnrollmentFormStep2($enrollform);
        $form->handleRequest($request);
        if ($form->get('back')->isClicked()) {
            return $this->redirectToRoute('_enrollment_step1');
        }
        if ($enrollform->checkForm2($form, $this->get('validator'), $this->get('translator'))) {
            $request->getSession()->set('enrollform', $enrollform);
            return $this->redirectToRoute('_enrollment_step3');
        }
        return array('form' => $form->createView());
    }
    
    /**
     * @Route("/enrollment/step3", name="_enrollment_step3")
     * @Template("ICupPublicSiteBundle:General:enrollment/enrollment_step3.html.twig")
     */
    public function showEnrollmentStep3(Request $request)
    {
        $enrollform = $request->getSession()->get('enrollform', new Enrollment());
        $form = $this->makeEnrollmentFormStep3($enrollform);
        $form->handleRequest($request);
        if ($form->get('back')->isClicked()) {
            return $this->redirectToRoute('_enrollment_step2');
        }
        if ($enrollform->checkForm3($form, $this->get('validator'), $this->get('translator'))) {
            $request->getSession()->set('enrollform', $enrollform);
            return $this->redirectToRoute('_enrollment_step4');
        }
        return array('form' => $form->createView());
    }
    
    /**
     * @Route("/enrollment/step4", name="_enrollment_step4")
     * @Template("ICupPublicSiteBundle:General:enrollment/enrollment_step4.html.twig")
     */
    public function showEnrollmentStep4(Request $request)
    {
        $enrollform = $request->getSession()->get('enrollform', new Enrollment());
        $form = $this->makeEnrollmentFormStep4($enrollform);
        $form->handleRequest($request);
        if ($form->get('back')->isClicked()) {
            return $this->redirectToRoute('_enrollment_step3');
        }
        if ($enrollform->checkForm4($form, $this->get('validator'), $this->get('translator'))) {
            $request->getSession()->set('enrollform', $enrollform);
            return $this->redirectToRoute('_enrollment_step5');
        }
        return array('form' => $form->createView());
    }
    
    /**
     * @Route("/enrollment/step5", name="_enrollment_step5")
     * @Template("ICupPublicSiteBundle:General:enrollment/enrollment_step5.html.twig")
     */
    public function showEnrollmentStep5(Request $request)
    {
        $enrollform = $request->getSession()->get('enrollform', new Enrollment());
        $form = $this->makeEnrollmentFormStep5($enrollform);
        $form->handleRequest($request);
        if ($form->get('back')->isClicked()) {
            return $this->redirectToRoute('_enrollment_step4');
        }
        if ($enrollform->checkForm5($form, $this->get('validator'), $this->get('translator'))) {
            $request->getSession()->set('enrollform', $enrollform);
            $this->sendMail($enrollform);
            return $this->redirectToRoute('_enrollment_done');
        }
        return array('form' => $form->createView());
    }

    /**
     * @Route("/enrollment/done", name="_enrollment_done")
     * @Template("ICupPublicSiteBundle:General:enrollment/enrollment_done.html.twig")
     */
    public function showEnrollmentDone(Request $request)
    {
        $enrollform = $request->getSession()->get('enrollform', new Enrollment());
        return array('form' => $enrollform);
    }

    private function sendMail(Enrollment $enrollform) {
        $from = array($this->container->getParameter('mailer_user') => "icup.dk support");
        $admins = $this->get('logic')->listAdminUsers();
        if (count($admins) < 1) {
            $recv = $from;
        }
        else {
            $recv = array();
            foreach ($admins as $admin) {
                if ($admin->getEmail() != '' && $admin->getName() != '') {
                    $recv[$admin->getEmail()] = $admin->getName();
                }
            }
        }
        $mailbody = $this->renderView('ICupPublicSiteBundle:Email:enrollmail.html.twig', array('form' => $enrollform));
        $message = Swift_Message::newInstance()
            ->setSubject($this->get('translator')->trans('FORM.ENROLLMENT.MAILTITLE', array(), 'club'))
            ->setFrom($from)
            ->setTo($recv)
            ->setBody($mailbody, 'text/html');
        $this->get('mailer')->send($message);
    }

    private function makeEnrollmentFormStep1(Enrollment $enrollment) {
        $formDef = $this->createFormBuilder($enrollment);
        $formDef->add('club', 'text', array('label' => 'FORM.ENROLLMENT.CLUB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('address', 'text', array('label' => 'FORM.ENROLLMENT.ADDRESS', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('city', 'text', array('label' => 'FORM.ENROLLMENT.CITY', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('country', 'text', array('label' => 'FORM.ENROLLMENT.COUNTRY', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('phone', 'text', array('label' => 'FORM.ENROLLMENT.PHONE', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('fax', 'text', array('label' => 'FORM.ENROLLMENT.FAX', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('skype', 'text', array('label' => 'FORM.ENROLLMENT.SKYPE', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('email', 'text', array('label' => 'FORM.ENROLLMENT.EMAIL', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('website', 'text', array('label' => 'FORM.ENROLLMENT.WEB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('membership', 'text', array('label' => 'FORM.ENROLLMENT.MEMBERSHIP', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('affiliation', 'text', array('label' => 'FORM.ENROLLMENT.AFFILIATION', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('championship', 'text', array('label' => 'FORM.ENROLLMENT.CHAMPIONSHIP', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('bestresult', 'text', array('label' => 'FORM.ENROLLMENT.BESTRESULT', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('send', 'submit', array('label' => 'FORM.ENROLLMENT.SUBMIT',
                                                'translation_domain' => 'club',
                                                'icon' => 'fa fa-ticket'));
        return $formDef->getForm();
    }

    private function makeEnrollmentFormStep2(Enrollment $enrollment) {
        $formDef = $this->createFormBuilder($enrollment);
        $formDef->add('manager', 'text', array('label' => 'FORM.ENROLLMENT.MANAGER', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('m_address', 'text', array('label' => 'FORM.ENROLLMENT.M_ADDRESS', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('m_city', 'text', array('label' => 'FORM.ENROLLMENT.M_CITY', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('m_country', 'text', array('label' => 'FORM.ENROLLMENT.M_COUNTRY', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('m_phone', 'text', array('label' => 'FORM.ENROLLMENT.M_PHONE', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('m_fax', 'text', array('label' => 'FORM.ENROLLMENT.M_FAX', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('m_skype', 'text', array('label' => 'FORM.ENROLLMENT.M_SKYPE', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('m_mobile', 'text', array('label' => 'FORM.ENROLLMENT.M_MOBILE', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('m_email', 'text', array('label' => 'FORM.ENROLLMENT.M_EMAIL', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('send', 'submit', array('label' => 'FORM.ENROLLMENT.SUBMIT',
                                                'translation_domain' => 'club',
                                                'icon' => 'fa fa-ticket'));
        $formDef->add('back', 'submit', array('label' => 'FORM.ENROLLMENT.BACK',
            'translation_domain' => 'club',
            'buttontype' => 'btn btn-default',
            'icon' => 'fa fa-arrow-left'));
        return $formDef->getForm();
    }

    private function makeEnrollmentFormStep3(Enrollment $enrollment) {
        $formDef = $this->createFormBuilder($enrollment);
        $formDef->add('t_ntmu18', 'text', array('label' => 'FORM.ENROLLMENT.NTMU18', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('t_ntfu18', 'text', array('label' => 'FORM.ENROLLMENT.NTFU18', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('t_mo18', 'text', array('label' => 'FORM.ENROLLMENT.MO18', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('t_fo18', 'text', array('label' => 'FORM.ENROLLMENT.FO18', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('t_mu18', 'text', array('label' => 'FORM.ENROLLMENT.MU18', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('t_fu18', 'text', array('label' => 'FORM.ENROLLMENT.FU18', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('t_mu16', 'text', array('label' => 'FORM.ENROLLMENT.MU16', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('t_fu16', 'text', array('label' => 'FORM.ENROLLMENT.FU16', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('t_mu14', 'text', array('label' => 'FORM.ENROLLMENT.MU14', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('t_fu14', 'text', array('label' => 'FORM.ENROLLMENT.FU14', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('t_mu12', 'text', array('label' => 'FORM.ENROLLMENT.MU12', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('t_fu12', 'text', array('label' => 'FORM.ENROLLMENT.FU12', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('send', 'submit', array('label' => 'FORM.ENROLLMENT.SUBMIT',
                                                'translation_domain' => 'club',
                                                'icon' => 'fa fa-ticket'));
        $formDef->add('back', 'submit', array('label' => 'FORM.ENROLLMENT.BACK',
            'translation_domain' => 'club',
            'buttontype' => 'btn btn-default',
            'icon' => 'fa fa-arrow-left'));
        return $formDef->getForm();
    }
        
    private function makeEnrollmentFormStep4(Enrollment $enrollment) {
        $formDef = $this->createFormBuilder($enrollment);
        $formDef->add('a_teramo_wob', 'text', array('label' => 'FORM.ENROLLMENT.A_TERAMO_WOB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('a_teramo_wb', 'text', array('label' => 'FORM.ENROLLMENT.A_TERAMO_WB', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('a_teramo_tent', 'text', array('label' => 'FORM.ENROLLMENT.A_TERAMO_TENT', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('a_teramo_hotel', 'text', array('label' => 'FORM.ENROLLMENT.A_TERAMO_HOTEL', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('a_guilianova_tent', 'text', array('label' => 'FORM.ENROLLMENT.A_GUILIANOVA_TENT', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('a_guilianova_hotel', 'text', array('label' => 'FORM.ENROLLMENT.A_GUILIANOVA_HOTEL', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('a_restaurant', 'text', array('label' => 'FORM.ENROLLMENT.A_RESTAURANT', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('a_other', 'text', array('label' => 'FORM.ENROLLMENT.A_OTHER', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('a_none', 'text', array('label' => 'FORM.ENROLLMENT.A_NONE', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('send', 'submit', array('label' => 'FORM.ENROLLMENT.SUBMIT',
                                                'translation_domain' => 'club',
                                                'icon' => 'fa fa-ticket'));
        $formDef->add('back', 'submit', array('label' => 'FORM.ENROLLMENT.BACK',
            'translation_domain' => 'club',
            'buttontype' => 'btn btn-default',
            'icon' => 'fa fa-arrow-left'));
        return $formDef->getForm();
    }

    private function makeEnrollmentFormStep5(Enrollment $enrollment) {
        $formDef = $this->createFormBuilder($enrollment);
        $formDef->add('arrival_date', 'text', array('label' => 'FORM.ENROLLMENT.ARRIVAL_DATE', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('arrival_time', 'text', array('label' => 'FORM.ENROLLMENT.ARRIVAL_TIME', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('departure_date', 'text', array('label' => 'FORM.ENROLLMENT.DEPARTURE_DATE', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('departure_time', 'text', array('label' => 'FORM.ENROLLMENT.DEPARTURE_TIME', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));

        $formDef->add('b_arrival_airport', 'text', array('label' => 'FORM.ENROLLMENT.B_ARRIVAL_AIRPORT', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('b_arrival_date', 'text', array('label' => 'FORM.ENROLLMENT.B_ARRIVAL_DATE', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('b_arrival_time', 'text', array('label' => 'FORM.ENROLLMENT.B_ARRIVAL_TIME', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('b_departure_airport', 'text', array('label' => 'FORM.ENROLLMENT.B_DEPARTURE_AIRPORT', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('b_departure_date', 'text', array('label' => 'FORM.ENROLLMENT.B_DEPARTURE_DATE', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('b_departure_time', 'text', array('label' => 'FORM.ENROLLMENT.B_DEPARTURE_TIME', 'phonestyle' => true, 'required' => false, 'disabled' => false, 'translation_domain' => 'club'));
        $formDef->add('send', 'submit', array('label' => 'FORM.ENROLLMENT.SEND',
                                                'translation_domain' => 'club',
                                                'icon' => 'fa fa-envelope'));
        $formDef->add('back', 'submit', array('label' => 'FORM.ENROLLMENT.BACK',
            'translation_domain' => 'club',
            'buttontype' => 'btn btn-default',
            'icon' => 'fa fa-arrow-left'));
        return $formDef->getForm();
    }
}

<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\User;

use ICup\Bundle\PublicSiteBundle\Services\Util;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Request;

class LoginController extends Controller
{
    /**
     * Login users and administrators
     * @Route("/login", name="_admin_login")
     * @Method("GET")
     */
    public function loginAction(Request $request)
    {
        if ($this->getUser() != null) {
            // Controller is called by authenticated user - switch to "MyPage"
            return $this->redirect($this->generateUrl('_user_my_page'));
        }

        /* @var $utilService Util */
        $utilService = $this->get('util');

        $session = $request->getSession();
        if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
        } else {
            $error = $session->remove(SecurityContext::AUTHENTICATION_ERROR);
        }
        
        $twig = 'ICupPublicSiteBundle:Edit:login.html.twig';
        $requestedPath = $session->get('_security.secured_area.target_path', '');
        $startpos = strripos($requestedPath, $request->getBaseUrl());
        $basePath = substr($requestedPath, $startpos);
        if ($basePath === $this->generateUrl('_club_enroll_check')) {
            $twig = 'ICupPublicSiteBundle:User:ausr_login.html.twig';
        }

        $form = $this->makeLoginForm($request);
        $form->handleRequest($request);

        $tournamentKey = $utilService->getTournamentKey();
        if ($tournamentKey != '_') {
            $tournament = $this->get('logic')->getTournamentByKey($tournamentKey);
        }
        else {
            $tournament = null;
        }
        return $this->render($twig, array(
            'form'          => $form->createView(),
            'tournament'    => $tournament,
            'error'         => $error
        ));
    }

    private function makeLoginForm(Request $request) {
        $formDef = $this->createFormBuilder(array('username' => $request->getSession()->get(SecurityContext::LAST_USERNAME)));
        $formDef->setAction($this->generateUrl('_security_check'));
        $formDef->add('username', 'text', array('label' => 'FORM.LOGIN.USERNAME',
                                                'translation_domain' => 'club',
                                                'required' => false,
                                                'help' => 'FORM.LOGIN.HELP.USERNAME',
                                                'icon' => 'fa fa-user'));
        $formDef->add('password', 'password', array('label' => 'FORM.LOGIN.PASSWORD', 
                                                    'translation_domain' => 'club',
                                                    'required' => false,
                                                    'help' => 'FORM.LOGIN.HELP.PASSWORD',
                                                    'icon' => 'fa fa-key'));
        $formDef->add('login', 'submit', array('label' => 'FORM.LOGIN.LOGIN',
                                               'translation_domain' => 'club',
                                               'icon' => 'fa fa-sign-in'));
        return $formDef->getForm();
    }
    
    /**
     * @Route("/login_check", name="_security_check")
     */
    public function securityCheckAction()
    {
        // The security layer will intercept this request
        return $this->redirect($this->generateUrl('_admin_login'));
    }

    /**
     * @Route("/logout", name="_admin_logout")
     */
    public function logoutAction()
    {
        // The security layer will intercept this request
    }
}

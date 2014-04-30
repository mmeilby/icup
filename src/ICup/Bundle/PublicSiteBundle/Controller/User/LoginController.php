<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\User;

use ICup\Bundle\PublicSiteBundle\Services\Util;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\SecurityContext;

class LoginController extends Controller
{
    /**
     * Login users and administrators
     * @Route("/login", name="_admin_login")
     * @Method("GET")
     */
    public function loginAction()
    {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $utilService->setupController();

        $request = $this->getRequest();
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

        $form = $this->makeLoginForm();
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

    private function makeLoginForm() {
        $request = $this->getRequest();
        $formDef = $this->createFormBuilder(array('username' => $request->getSession()->get(SecurityContext::LAST_USERNAME)));
        $formDef->setAction($this->generateUrl('_security_check'));
        $formDef->add('username', 'text', array('label' => 'FORM.LOGIN.USERNAME', 'translation_domain' => 'admin', 'required' => false));
        $formDef->add('password', 'password', array('label' => 'FORM.LOGIN.PASSWORD', 'translation_domain' => 'admin', 'required' => false));
        $formDef->add('login', 'submit', array('label' => 'FORM.LOGIN.LOGIN', 'translation_domain' => 'admin'));
        return $formDef->getForm();
    }
    
    /**
     * @Route("/login_check", name="_security_check")
     */
    public function securityCheckAction()
    {
        // The security layer will intercept this request
    }

    /**
     * @Route("/logout", name="_admin_logout")
     */
    public function logoutAction()
    {
        // The security layer will intercept this request
    }
}

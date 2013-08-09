<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Edit;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\SecurityContext;

class EditLoginController extends Controller
{
    /**
     * @Route("/login", name="_admin_login")
     * @Template("ICupPublicSiteBundle:Edit:login.html.twig")
     */
    public function loginAction()
    {
        $this->get('util')->setupController($this);
        $tournamentId = $this->get('util')->getTournament($this);
        $request = $this->getRequest();
        if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
        } else {
            $error = $request->getSession()->get(SecurityContext::AUTHENTICATION_ERROR);
        }

        $em = $this->getDoctrine()->getManager();

        $tournament = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')
                            ->find($tournamentId);

/*
        $formDef = $this->createFormBuilder(array('_username' => $request->getSession()->get(SecurityContext::LAST_USERNAME)));
        $formDef->setAction($this->generateUrl('_security_check'));
//        $formDef->add('id', 'hidden', array('mapped' => false));
        $formDef->add('_username', 'text', array('label' => 'USERNAME', 'required' => false));
        $formDef->add('_password', 'password', array('label' => 'PASSWORD', 'required' => false));
        $formDef->add('login', 'submit', array('label' => 'LOGIN'));
        $form = $formDef->getForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            return $this->redirect($this->generateUrl('_edit_host_list'));
        }
        return array(
            'form' => $form->createView(),
            'tournament'    => $tournament,
            'error'         => $error
        );

 */        
        return array(
            'last_username' => $request->getSession()->get(SecurityContext::LAST_USERNAME),
            'tournament'    => $tournament,
            'error'         => $error,
        );
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

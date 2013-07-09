<?php
namespace ICup\Bundle\PublicSiteBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\SecurityContext;

class EditLoginController extends Controller
{
    /**
     * @Route("/edit/login", name="_admin_login")
     * @Template("ICupPublicSiteBundle:Default:login.html.twig")
     */
    public function loginAction()
    {
        DefaultController::switchLanguage($this);
        $request = $this->getRequest();
        if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
        } else {
            $error = $request->getSession()->get(SecurityContext::AUTHENTICATION_ERROR);
        }

        $tournamentId = DefaultController::getTournament($this);
        $em = $this->getDoctrine()->getManager();

        $tournament = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')
                            ->find($tournamentId);
        
        return array(
            'last_username' => $request->getSession()->get(SecurityContext::LAST_USERNAME),
            'tournament'    => $tournament,
            'error'         => $error,
        );
    }

    /**
     * @Route("/edit/login_check", name="_security_check")
     */
    public function securityCheckAction()
    {
        // The security layer will intercept this request
    }

    /**
     * @Route("/edit/logout", name="_admin_logout")
     */
    public function logoutAction()
    {
        // The security layer will intercept this request
    }
}

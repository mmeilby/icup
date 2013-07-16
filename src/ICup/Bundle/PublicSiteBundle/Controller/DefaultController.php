<?php

namespace ICup\Bundle\PublicSiteBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

class DefaultController extends Controller
{
    /**
     * @Route("/switch/{locale}", name="_switch")
     * @Template()
     */
    public function switchAction($locale)
    {
        $request = $this->getRequest();
        $session = $request->getSession();
        $session->set('locale', $locale);
        $referer = $request->headers->get('referer');
        return new RedirectResponse($referer);
    }
    
    /**
     * @Route("/tournament", name="_showtournament")
     * @Template()
     */
    public function listAction()
    {
        return $this->redirect($this->generateUrl('_tournament_categories', array('tournament' => '_')));
    }

}

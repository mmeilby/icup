<?php

namespace ICup\Bundle\PublicSiteBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

class DefaultController extends Controller
{
    /**
     * @Route("/switch/{locale}", name="_switch")
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
     * @Route("/", name="_icup")
     */
    public function rootAction()
    {
        $tmnt = $this->get('util')->getTournamentKey($this);
        if ($tmnt != '_') {
            return $this->redirect($this->generateUrl('_tournament_overview', array('tournament' => $tmnt)));
        }
        else {
            return $this->redirect($this->generateUrl('_tournament_select'));
        }
    }
}

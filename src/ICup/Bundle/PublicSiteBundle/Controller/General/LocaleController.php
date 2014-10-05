<?php

namespace ICup\Bundle\PublicSiteBundle\Controller\General;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

class LocaleController extends Controller
{
    /**
     * @Route("/switch/{locale}", name="_switch")
     */
    public function switchAction($locale)
    {
        $request = $this->getRequest();
        $session = $request->getSession();
        $session->set('_locale', $locale);
        $referer = $request->headers->get('referer');
        return new RedirectResponse($referer);
    }
}

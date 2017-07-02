<?php

namespace ICup\Bundle\PublicSiteBundle\Controller\General;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ContactController extends Controller
{
    /**
     * @Route("/contact", name="_contact")
     */
    public function showAction(Request $request)
    {
        $domain = $this->get('util')->parseHostDomain($request);
        if (trim($domain) != "") {
            $template = "ICupPublicSiteBundle:General:contact.".$domain.".html.twig";
        }
        else {
            $template = "ICupPublicSiteBundle:General:contact.html.twig";
        }

        return $this->render($template, array());
    }
}

<?php

namespace ICup\Bundle\PublicSiteBundle\Controller\General;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ContactController extends Controller
{
    /**
     * @Route("/contact", name="_contact")
     * @Template("ICupPublicSiteBundle:General:contact.html.twig")
     */
    public function showAction()
    {
        return array();
    }
}

<?php

namespace ICup\Bundle\PublicSiteBundle\Controller\General;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ContentPageController extends Controller
{
    /**
     * @Route("/information", name="_information")
     * @Template("ICupPublicSiteBundle:General:information.html.twig")
     */
    public function showInformation()
    {
        return array();
    }
    
    /**
     * @Route("/enrollment", name="_enrollment")
     * @Template("ICupPublicSiteBundle:General:enrollment.html.twig")
     */
    public function showEnrollment()
    {
        return array();
    }
    
}

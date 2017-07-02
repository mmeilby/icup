<?php

namespace ICup\Bundle\PublicSiteBundle\Controller\General;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ContentPageController extends Controller
{
    /**
     * @Route("/information", name="_information")
     */
    public function showInformation(Request $request)
    {
        $domain = $this->get('util')->parseHostDomain($request);
        if (trim($domain) != "") {
            $template = "ICupPublicSiteBundle:General:information.".$domain.".html.twig";
        }
        else {
            $template = "ICupPublicSiteBundle:General:information.html.twig";
        }
        return $this->render($template, array());
    }
    
    /**
     * @Route("/enrollment", name="_enrollment")
     * @Template("ICupPublicSiteBundle:General:enrollment.html.twig")
     */
    public function showEnrollment(Request $request)
    {
        $domain = $this->get('util')->parseHostDomain($request);
        if (trim($domain) != "") {
            $template = "ICupPublicSiteBundle:General:enrollment.".$domain.".html.twig";
        }
        else {
            $template = "ICupPublicSiteBundle:General:enrollment.html.twig";
        }
        /* @var $manager \Doctrine\ODM\PHPCR\DocumentManager */
        $manager = $this->get('doctrine_phpcr')->getManager('default');
        $parent = $manager->find(null, '/cms/media/enrollment');
        if ($parent) {
            $files = $manager->getChildren($parent);
            return $this->render($template, array('files' => $files));
        }
        else {
            return $this->render($template, array('files' => array()));
        }
    }

    /**
     * @Route("/cookies", name="_cookies")
     * @Template("ICupPublicSiteBundle:General:cookieinfo.html.twig")
     */
    public function showCookieInformation()
    {
        return array();
    }
}

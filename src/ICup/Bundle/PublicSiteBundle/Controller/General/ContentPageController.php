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
        /* @var $manager \Doctrine\ODM\PHPCR\DocumentManager */
        $manager = $this->get('doctrine_phpcr')->getManager('default');
        $parent = $manager->find(null, '/cms/media/enrollment');
        if ($parent) {
            $files = $manager->getChildren($parent);
            return array('files' => $files);
        }
        else {
            return array('files' => array());
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

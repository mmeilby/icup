<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Edit;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class EditGroupController extends Controller
{
    /**
     * Add or update the group information
     * @Route("/edit/tmnt/{tournament}/new/group/{categoryId}", name="_newgroup")
     * @Secure(roles="ROLE_ADMIN")
     * @Method("GET")
     * @Template("ICupPublicSiteBundle:Edit:editgroup.html.twig")
     */
    public function newAction($tournament, $categoryId) {
        $this->get('util')->setupController($this, $tournament);
        $tournamentId = $this->get('util')->getTournament($this);
        $em = $this->getDoctrine()->getManager();

        $tournament = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')
                            ->find($tournamentId);
        
        $category = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category')
                            ->find($categoryId);

        $form = $this->makeform();
        return array('form' => $form->createView(), 'tournament' => $tournament, 'category' => $category);
    }

    /**
     * Add or update the group information
     * @Route("/edit/tmnt/{tournament}/new/group/{categoryId}", name="_newgrouppost")
     * @Secure(roles="ROLE_ADMIN")
     * @Method("POST")
     * @Template("ICupPublicSiteBundle:Edit:editgroup.html.twig")
     */
    public function newPostAction($tournament, $categoryId) {
        $this->get('util')->setupController($this, $tournament);
        $tournamentId = $this->get('util')->getTournament($this);
        $em = $this->getDoctrine()->getManager();

        $tournament = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')
                            ->find($tournamentId);
        
        $category = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category')
                            ->find($categoryId);

        $form = $this->makeform();
        $request = $this->getRequest();
        $form->bind($request);
        if ($form->isValid()) {
            $formData = $form->getData();
            $formData->setPid($categoryId);
            $em->persist($formData);
            $em->flush();
        }
        return array('form' => $form->createView(), 'tournament' => $tournament, 'category' => $category);
    }
    
    private function makeform() {
        $classifications = array( 0 => 'Kvalifikation', 1 => 'Playoff', 6 => '1/8 finale', 7 => '1/4 finale', 8 => 'Semifinale', 9 => '3/4 placering', 10 => 'Finale');
        $formData = new Group();
        $formDef = $this->createFormBuilder($formData);
        $formDef->add('name', 'text', array('label' => 'Navn', 'required' => false));
        $formDef->add('playingtime', 'text', array('label' => 'Spilletid (min.)', 'required' => false));
        $formDef->add('classification', 'choice', array('label' => 'Klassifikation', 'required' => false, 'choices' => $classifications, 'empty_value' => 'Vælg...'));
        return $formDef->getForm();
    }
}

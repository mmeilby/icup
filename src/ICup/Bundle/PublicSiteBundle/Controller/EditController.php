<?php
namespace ICup\Bundle\PublicSiteBundle\Controller;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class EditController extends Controller
{
     /**
     * Add or update the category information
     * @Route("/edit", name="_editmaster")
     * @Secure(roles="ROLE_ADMIN")
     * @Method("GET")
     * @Template("ICupPublicSiteBundle:Default:editmaster.html.twig")
     */
    public function editAction() {
        $this->get('util')->setupController($this, $tournament);
        $tournamentId = $this->get('util')->getTournament($this);
        $em = $this->getDoctrine()->getManager();

        $tournament = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')
                            ->find($tournamentId);
        
        $categories = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category')
                            ->findBy(array('pid' => $tournament->getId()));

        return array('tournament' => $tournament, 'categories' => $categories);
    }

    /**
     * Main menu for edit the category structure
     * @Route("/edit/menu/{categoryId}", name="_editmenu")
     * @Secure(roles="ROLE_ADMIN")
     * @Method("GET")
     * @Template("ICupPublicSiteBundle:Default:editmenu.html.twig")
     */
    public function menuAction($categoryId) {
        $this->get('util')->setupController($this, $tournament);
        $tournamentId = $this->get('util')->getTournament($this);
        $em = $this->getDoctrine()->getManager();

        $tournament = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')
                            ->find($tournamentId);
        
        $category = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category')
                            ->find($categoryId);

        $groups = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group')
                            ->findBy(array('pid' => $category->getId()));

        return array('tournament' => $tournament, 'category' => $category, 'groups' => $groups);
    }

    /**
     * Add or update the category information
     * @Route("/edit/new/category", name="_editcategory")
     * @Secure(roles="ROLE_ADMIN")
     * @Method("GET")
     * @Template("ICupPublicSiteBundle:Default:editcategory.html.twig")
     */
    public function newAction() {
        $this->get('util')->setupController($this, $tournament);
        $tournamentId = $this->get('util')->getTournament($this);
        $em = $this->getDoctrine()->getManager();

        $tournament = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')
                            ->find($tournamentId);
        
        $form = $this->makeform();
        return array('form' => $form->createView(), 'tournament' => $tournament);
    }

    /**
     * Add or update the category information
     * @Route("/edit/new/category", name="_newcategorypost")
     * @Secure(roles="ROLE_ADMIN")
     * @Method("POST")
     * @Template("ICupPublicSiteBundle:Default:editcategory.html.twig")
     */
    public function newPostAction() {
        $this->get('util')->setupController($this, $tournament);
        $tournamentId = $this->get('util')->getTournament($this);
        $em = $this->getDoctrine()->getManager();

        $tournament = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')
                            ->find($tournamentId);
        
        $form = $this->makeform();
        $request = $this->getRequest();
        $form->bind($request);
        if ($form->isValid()) {
            $formData = $form->getData();
            $formData->setPid($tournament->getId());
            $em->persist($formData);
            $em->flush();
        }
        return array('form' => $form->createView(), 'tournament' => $tournament);
    }
    
    private function makeform() {
        $gender = array( 'M' => 'Mænd', 'F' => 'Kvinder' );
        $classifications = array( 'U12' => 'U12', 'U14' => 'U14', 'U16' => 'U16', 'U18' => 'U18', 'U21' => 'U21', 'U30' => 'U30');
        $formData = new Category();
        $formDef = $this->createFormBuilder($formData);
        $formDef->add('name', 'text', array('label' => 'Navn', 'required' => false));
        $formDef->add('gender', 'choice', array('label' => 'Køn', 'required' => false, 'choices' => $gender, 'empty_value' => 'Vælg...'));
        $formDef->add('classification', 'choice', array('label' => 'Klassifikation', 'required' => false, 'choices' => $classifications, 'empty_value' => 'Vælg...'));
        return $formDef->getForm();
    }
}

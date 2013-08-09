<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Edit;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * List the categories and groups available
 */
class EditCategoryController extends Controller
{
    /**
     * List the items available for a tournament
     * @Route("/edit/category/list/{tournamentid}", name="_edit_category_list")
     * @Method("GET")
     * @Template("ICupPublicSiteBundle:Edit:listcategories.html.twig")
     */
    public function listAction($tournamentid) {
        $this->get('util')->setupController($this);
        $em = $this->getDoctrine()->getManager();

        $tournament = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')->find($tournamentid);
        if ($tournament == null) {
            $error = "FORM.ERROR.BADTOURNAMENT";
        }
        
        $categories = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category')
                            ->findBy(array('pid' => $tournamentid));
        
        $qb = $em->createQuery("select g ".
                               "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group g, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category c ".
                               "where c.pid=:tournament and g.pid=c.id ".
                               "order by g.name asc");
        $qb->setParameter('tournament', $tournamentid);
        $groups = $qb->getResult();

        $groupList = array();
        foreach ($groups as $group) {
            $groupList[$group->getPid()][] = $group;
        }
        
        return array('tournament' => $tournament, 'groups' => $groupList, 'categories' => $categories, 'error' => isset($error) ? $error : null);
    }

    /**
     * Add new category
     * @Route("/edit/category/add/{tournamentid}", name="_edit_category_add")
     * @Template("ICupPublicSiteBundle:Edit:editcategory.html.twig")
     */
    public function addAction($tournamentid) {
        $this->get('util')->setupController($this);
        $em = $this->getDoctrine()->getManager();
        
        $tournament = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')->find($tournamentid);
        if ($tournament == null) {
            $error = "FORM.ERROR.BADTOURNAMENT";
        }
        
        $category = new Category();
        $category->setPid($tournament != null ? $tournament->getId() : 0);
        $form = $this->makeCategoryForm($category, 'add');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->generateUrl('_edit_category_list', array('tournamentid' => $category->getPid())));
        }
        if ($form->isValid()) {
            $em->persist($category);
            $em->flush();
            return $this->redirect($this->generateUrl('_edit_category_list', array('tournamentid' => $category->getPid())));
        }
        return array('form' => $form->createView(), 'action' => 'add', 'category' => $category, 'error' => isset($error) ? $error : null);
    }
    
    /**
     * Change information of an existing category
     * @Route("/edit/category/chg/{categoryid}", name="_edit_category_chg")
     * @Template("ICupPublicSiteBundle:Edit:editcategory.html.twig")
     */
    public function chgAction($categoryid) {
        $this->get('util')->setupController($this);
        $em = $this->getDoctrine()->getManager();
        
        $category = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category')->find($categoryid);
        if ($category == null) {
            $error = "FORM.ERROR.BADCATEGORY";
        }
     
        $form = $this->makeCategoryForm($category, 'chg');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->generateUrl('_edit_category_list', array('tournamentid' => $category->getPid())));
        }
        if (!isset($error) && $form->isValid()) {
            $em->persist($category);
            $em->flush();
            return $this->redirect($this->generateUrl('_edit_category_list', array('tournamentid' => $category->getPid())));
        }
        return array('form' => $form->createView(), 'action' => 'chg', 'category' => $category, 'error' => isset($error) ? $error : null);
    }
    
    /**
     * Remove category from the register - including all related groups and match results
     * @Route("/edit/category/del/{categoryid}", name="_edit_category_del")
     * @Template("ICupPublicSiteBundle:Edit:editcategory.html.twig")
     */
    public function delAction($categoryid) {
        $this->get('util')->setupController($this);
        $em = $this->getDoctrine()->getManager();
        
        $category = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category')->find($categoryid);
        if ($category == null) {
            $error = "FORM.ERROR.BADCATEGORY";
        }
                
        $form = $this->makeCategoryForm($category, 'del');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->generateUrl('_edit_category_list', array('tournamentid' => $category->getPid())));
        }
        if (!isset($error) && $form->isValid()) {
            $em->remove($category);
            $em->flush();
            return $this->redirect($this->generateUrl('_edit_category_list', array('tournamentid' => $category->getPid())));
        }
        return array('form' => $form->createView(), 'action' => 'del', 'category' => $category, 'error' => isset($error) ? $error : null);
    }
    
    private function makeCategoryForm($category, $action) {
        $gender = array( 'M' => 'MALE', 'F' => 'FEMALE' );
        $classifications = array();
        foreach (array('U12','U14','U16','U18','U21','U30','U30/U21') as $id) {
            $classifications[$id] = $id;
        }
        $formDef = $this->createFormBuilder($category);
        $formDef->add('name', 'text', array('label' => 'FORM.CATEGORY.NAME', 'required' => false, 'disabled' => $action == 'del'));
        $formDef->add('gender', 'choice', array('label' => 'FORM.CATEGORY.GENDER', 'required' => false, 'choices' => $gender, 'empty_value' => 'FORM.CATEGORY.DEFAULT', 'disabled' => $action == 'del'));
        $formDef->add('classification', 'choice', array('label' => 'FORM.CATEGORY.CLASSIFICATION', 'required' => false, 'choices' => $classifications, 'empty_value' => 'FORM.CATEGORY.DEFAULT', 'disabled' => $action == 'del'));
        $formDef->add('cancel', 'submit', array('label' => 'FORM.CATEGORY.CANCEL.'.strtoupper($action)));
        $formDef->add('save', 'submit', array('label' => 'FORM.CATEGORY.SUBMIT.'.strtoupper($action)));
        return $formDef->getForm();
    }
    
    /**
     * Add new playground to a site
     * @Route("/edit/group/add/{categoryid}", name="_edit_group_add")
     * @Template("ICupPublicSiteBundle:Edit:editgroup.html.twig")
     */
    public function addGroupAction($categoryid) {
        $this->get('util')->setupController($this);
        $em = $this->getDoctrine()->getManager();
        
        $category = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category')->find($categoryid);
        if ($category == null) {
            $error = "FORM.ERROR.BADCATEGORY";
        }
                
        $group = new Group();
        $group->setPid($category != null ? $category->getId() : 0);
        $form = $this->makeGroupForm($group, 'add');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->generateUrl('_edit_category_list', array('tournamentid' => $category->getPid())));
        }
        if (!isset($error) && $form->isValid()) {
            $em->persist($group);
            $em->flush();
            return $this->redirect($this->generateUrl('_edit_category_list', array('tournamentid' => $category->getPid())));
        }
        return array('form' => $form->createView(), 'action' => 'add', 'group' => $group, 'error' => isset($error) ? $error : null);
    }
    
    /**
     * Change information of an existing playground
     * @Route("/edit/group/chg/{groupid}", name="_edit_group_chg")
     * @Template("ICupPublicSiteBundle:Edit:editgroup.html.twig")
     */
    public function chgGroupAction($groupid) {
        $this->get('util')->setupController($this);
        $em = $this->getDoctrine()->getManager();
        
        $group = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group')->find($groupid);
        if ($group == null) {
            $error = "FORM.ERROR.BADGROUP";
        }
        else {
            $category = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category')->find($group->getPid());
            if ($category == null) {
                $error = "FORM.ERROR.BADCATEGORY";
            }
        }

        $form = $this->makeGroupForm($group, 'chg');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->generateUrl('_edit_category_list', array('tournamentid' => $category->getPid())));
        }
        if (!isset($error) && $form->isValid()) {
            $em->persist($group);
            $em->flush();
            return $this->redirect($this->generateUrl('_edit_category_list', array('tournamentid' => $category->getPid())));
        }
        return array('form' => $form->createView(), 'action' => 'chg', 'group' => $group, 'error' => isset($error) ? $error : null);
    }
    
    /**
     * Remove playground from the register - all match results and playground information is lost
     * @Route("/edit/group/del/{groupid}", name="_edit_group_del")
     * @Template("ICupPublicSiteBundle:Edit:editgroup.html.twig")
     */
    public function delGroupAction($groupid) {
        $this->get('util')->setupController($this);
        $em = $this->getDoctrine()->getManager();
        
        $group = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group')->find($groupid);
        if ($group == null) {
            $error = "FORM.ERROR.BADGROUP";
        }
        else {
            $category = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category')->find($group->getPid());
            if ($category == null) {
                $error = "FORM.ERROR.BADCATEGORY";
            }
        }
        
        $form = $this->makeGroupForm($group, 'del');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->generateUrl('_edit_category_list', array('tournamentid' => $category->getPid())));
        }
        if (!isset($error) && $form->isValid()) {
            $em->remove($group);
            $em->flush();
            return $this->redirect($this->generateUrl('_edit_category_list', array('tournamentid' => $category->getPid())));
        }
        return array('form' => $form->createView(), 'action' => 'del', 'group' => $group, 'error' => isset($error) ? $error : null);
    }
    
    private function makeGroupForm($playground, $action) {
        $classifications = array();
        foreach (array(0,1,6,7,8,9,10) as $id) {
            $classifications[$id] = 'GROUPCLASS.'.$id;
        }
        $formDef = $this->createFormBuilder($playground);
        $formDef->add('name', 'text', array('label' => 'FORM.GROUP.NAME', 'required' => false, 'disabled' => $action == 'del'));
        $formDef->add('playingtime', 'text', array('label' => 'FORM.GROUP.TIME', 'required' => false, 'disabled' => $action == 'del'));
        $formDef->add('classification', 'choice', array('label' => 'FORM.GROUP.CLASSIFICATION', 'required' => false, 'choices' => $classifications, 'empty_value' => 'FORM.GROUP.DEFAULT', 'disabled' => $action == 'del'));
        $formDef->add('cancel', 'submit', array('label' => 'FORM.GROUP.CANCEL.'.strtoupper($action)));
        $formDef->add('save', 'submit', array('label' => 'FORM.GROUP.SUBMIT.'.strtoupper($action)));
        return $formDef->getForm();
    }
}

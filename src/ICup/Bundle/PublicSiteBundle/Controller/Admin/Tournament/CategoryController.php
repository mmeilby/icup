<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * List the categories and groups available
 */
class CategoryController extends Controller
{
    /**
     * Add new category
     * @Route("/edit/category/add/{tournamentid}", name="_edit_category_add")
     * @Template("ICupPublicSiteBundle:Host:editcategory.html.twig")
     */
    public function addAction($tournamentid) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        
        $returnUrl = $utilService->getReferer();

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $tournament = $this->get('entity')->getTournamentById($tournamentid);
        $utilService->validateEditorAdminUser($user, $tournament->getPid());

        $category = new Category();
        $category->setPid($tournament->getId());
        $form = $this->makeCategoryForm($category, 'add');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($this->checkForm($form, $category)) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($category);
            $em->flush();
            return $this->redirect($returnUrl);
        }
        return array('form' => $form->createView(), 'action' => 'add', 'category' => $category, 'error' => null);
    }
    
    /**
     * Change information of an existing category
     * @Route("/edit/category/chg/{categoryid}", name="_edit_category_chg")
     * @Template("ICupPublicSiteBundle:Host:editcategory.html.twig")
     */
    public function chgAction($categoryid) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        
        $returnUrl = $utilService->getReferer();

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $category = $this->get('entity')->getCategoryById($categoryid);
        $tournament = $this->get('entity')->getTournamentById($category->getPid());
        $utilService->validateEditorAdminUser($user, $tournament->getPid());

        $form = $this->makeCategoryForm($category, 'chg');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($this->checkForm($form, $category)) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($category);
            $em->flush();
            return $this->redirect($returnUrl);
        }
        return array('form' => $form->createView(), 'action' => 'chg', 'category' => $category, 'error' => null);
    }
    
    /**
     * Remove category from the register - including all related groups and match results
     * @Route("/edit/category/del/{categoryid}", name="_edit_category_del")
     * @Template("ICupPublicSiteBundle:Host:editcategory.html.twig")
     */
    public function delAction($categoryid) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        
        $returnUrl = $utilService->getReferer();

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $category = $this->get('entity')->getCategoryById($categoryid);
        $tournament = $this->get('entity')->getTournamentById($category->getPid());
        $utilService->validateEditorAdminUser($user, $tournament->getPid());

        $form = $this->makeCategoryForm($category, 'del');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($form->isValid()) {
            if ($this->get('logic')->listGroupsByCategory($category->getId()) != null) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.CATEGORY.GROUPSEXIST', array(), 'admin')));
            }
            elseif ($this->get('logic')->listEnrolledByCategory($category->getId()) != null) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.CATEGORY.ENROLLEDEXIST', array(), 'admin')));
            }
            else {
                $em = $this->getDoctrine()->getManager();
                $em->remove($category);
                $em->flush();
                return $this->redirect($returnUrl);
            }
        }
        return array('form' => $form->createView(), 'action' => 'del', 'category' => $category, 'error' => null);
    }
    
    private function makeCategoryForm($category, $action) {
        $gender = array( 'M' => 'FORM.CATEGORY.SEX.MALE', 'F' => 'FORM.CATEGORY.SEX.FEMALE' );
        $classifications = array();
        foreach (array('U12','U14','U16','U18','U21','U30','U30/U21') as $id) {
            $classifications[$id] = 'FORM.CATEGORY.CLASS.'.$id;
        }
        $formDef = $this->createFormBuilder($category);
        $formDef->add('name', 'text', array('label' => 'FORM.CATEGORY.NAME', 'required' => false, 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('gender', 'choice', array('label' => 'FORM.CATEGORY.GENDER', 'required' => false, 'choices' => $gender, 'empty_value' => 'FORM.CATEGORY.DEFAULT', 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('classification', 'choice', array('label' => 'FORM.CATEGORY.CLASSIFICATION', 'required' => false, 'choices' => $classifications, 'empty_value' => 'FORM.CATEGORY.DEFAULT', 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('cancel', 'submit', array('label' => 'FORM.CATEGORY.CANCEL.'.strtoupper($action), 'translation_domain' => 'admin'));
        $formDef->add('save', 'submit', array('label' => 'FORM.CATEGORY.SUBMIT.'.strtoupper($action), 'translation_domain' => 'admin'));
        return $formDef->getForm();
    }
    
    private function checkForm($form, Category $category) {
        if ($form->isValid()) {
            if ($category->getName() == null || trim($category->getName()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.CATEGORY.NONAME', array(), 'admin')));
                return false;
            }
            if ($category->getGender() == null) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.CATEGORY.NOGENDER', array(), 'admin')));
                return false;
            }
            if ($category->getClassification() == null) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.CATEGORY.NOCLASSIFICATION', array(), 'admin')));
                return false;
            }
            return true;
        }
        return false;
    }
}
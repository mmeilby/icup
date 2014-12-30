<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;

/**
 * List the categories and groups available
 */
class GroupController extends Controller
{
    /**
     * Add new group to a category
     * @Route("/edit/group/add/{categoryid}", name="_edit_group_add")
     * @Template("ICupPublicSiteBundle:Host:editgroup.html.twig")
     */
    public function addGroupAction($categoryid, Request $request) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        
        $returnUrl = $utilService->getReferer();

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $category = $this->get('entity')->getCategoryById($categoryid);
        $tournament = $this->get('entity')->getTournamentById($category->getPid());
        $utilService->validateEditorAdminUser($user, $tournament->getPid());

        $group = new Group();
        $group->setPid($category->getId());
        $form = $this->makeGroupForm($group, 'add');
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($this->checkForm($form, $group)) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($group);
            $em->flush();
            return $this->redirect($returnUrl);
        }
        return array('form' => $form->createView(), 'action' => 'add', 'group' => $group, 'error' => null);
    }
    
    /**
     * Change information of an existing group
     * @Route("/edit/group/chg/{groupid}", name="_edit_group_chg")
     * @Template("ICupPublicSiteBundle:Host:editgroup.html.twig")
     */
    public function chgGroupAction($groupid, Request $request) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        
        $returnUrl = $utilService->getReferer();

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $group = $this->get('entity')->getGroupById($groupid);
        $category = $this->get('entity')->getCategoryById($group->getPid());
        $tournament = $this->get('entity')->getTournamentById($category->getPid());
        $utilService->validateEditorAdminUser($user, $tournament->getPid());

        $form = $this->makeGroupForm($group, 'chg');
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($this->checkForm($form, $group)) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($group);
            $em->flush();
            return $this->redirect($returnUrl);
        }
        return array('form' => $form->createView(), 'action' => 'chg', 'group' => $group, 'error' => null);
    }
    
    /**
     * Remove group from the register
     * @Route("/edit/group/del/{groupid}", name="_edit_group_del")
     * @Template("ICupPublicSiteBundle:Host:editgroup.html.twig")
     */
    public function delGroupAction($groupid, Request $request) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        
        $returnUrl = $utilService->getReferer();

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $group = $this->get('entity')->getGroupById($groupid);
        $category = $this->get('entity')->getCategoryById($group->getPid());
        $tournament = $this->get('entity')->getTournamentById($category->getPid());
        $utilService->validateEditorAdminUser($user, $tournament->getPid());

        $form = $this->makeGroupForm($group, 'del');
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($form->isValid()) {
            if ($this->get('logic')->listGroupOrders($group->getId()) != null) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.GROUP.ORDEREXIST', array(), 'admin')));
            }
            elseif (count($this->get('match')->listMatchesByGroup($group->getId())) > 0) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.GROUP.MATCHESEXIST', array(), 'admin')));
            }
            else {
                $em = $this->getDoctrine()->getManager();
                $em->remove($group);
                $em->flush();
                return $this->redirect($returnUrl);
            }
        }
        return array('form' => $form->createView(), 'action' => 'del', 'group' => $group, 'error' => null);
    }
    
    private function makeGroupForm($group, $action) {
        $classifications = array();
        foreach (array(0,1,6,7,8,9,10) as $id) {
            $classifications[$id] = 'FORM.GROUP.CLASS.'.$id;
        }
        $formDef = $this->createFormBuilder($group);
        $formDef->add('name', 'text', array('label' => 'FORM.GROUP.NAME', 'required' => false, 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('playingtime', 'text', array('label' => 'FORM.GROUP.TIME', 'required' => false, 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('classification', 'choice', array('label' => 'FORM.GROUP.CLASSIFICATION', 'required' => false, 'choices' => $classifications, 'empty_value' => 'FORM.GROUP.DEFAULT', 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('cancel', 'submit', array('label' => 'FORM.GROUP.CANCEL.'.strtoupper($action),
                                                'translation_domain' => 'admin',
                                                'buttontype' => 'btn btn-default',
                                                'icon' => 'fa fa-times'));
        $formDef->add('save', 'submit', array('label' => 'FORM.GROUP.SUBMIT.'.strtoupper($action),
                                                'translation_domain' => 'admin',
                                                'icon' => 'fa fa-check'));
        return $formDef->getForm();
    }
    
    private function checkForm($form, Group $group) {
        if ($form->isValid()) {
            if ($group->getName() == null || trim($group->getName()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.GROUP.NONAME', array(), 'admin')));
                return false;
            }
            if ($group->getClassification() === null) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.GROUP.NOCLASSIFICATION', array(), 'admin')));
                return false;
            }
            return true;
        }
        return false;
    }
}

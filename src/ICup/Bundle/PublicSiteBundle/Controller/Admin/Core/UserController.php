<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Core;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Entity\Password;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;

class UserController extends Controller
{
    /**
     * Add new club attached user
     * @Route("/admin/add/club/{clubid}", name="_edit_user_add")
     * @Template("ICupPublicSiteBundle:Edit:edituser.html.twig")
     */
    public function addAction($clubid) {
        
        $returnUrl = $this->get('util')->getReferer();

        $club = $this->get('entity')->getClubById($clubid);

        $user = new User();
        // User should be attached to the club when created this way
        $user->setStatus(User::$ATT);
        $user->setRole(User::$CLUB);
        $form = $this->makeUserForm($user, 'add');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($form->isValid()) {
            if ($this->get('logic')->isUserKnown($user->getUsername())) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.USER.NAMEEXIST', array(), 'admin')));
            }
            else {
                $user->setCid($clubid);
                $user->setPid(0);
                $this->get('util')->generatePassword($user);
                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();
                return $this->redirect($returnUrl);
            }
        }
        return array('form' => $form->createView(), 'action' => 'add', 'club' => $club, 'user' => $user);
    }
    
    /**
     * Add new host attached user
     * @Route("/edit/user/add/host/{hostid}", name="_edit_user_add_host")
     * @Template("ICupPublicSiteBundle:Edit:edituser.html.twig")
     */
    public function addHostAction($hostid) {
        
        $returnUrl = $this->get('util')->getReferer();

        /* @var $thisuser User */
        $thisuser = $this->get('util')->getCurrentUser();
        $host = $this->get('entity')->getHostById($hostid);
        $this->get('util')->validateEditorAdminUser($thisuser, $hostid);

        $user = new User();
        $user->setStatus(User::$SYSTEM);
        $user->setRole(User::$EDITOR);
        $form = $this->makeUserForm($user, 'add');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($form->isValid()) {
            if ($this->get('logic')->isUserKnown($user->getUsername())) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.USER.NAMEEXIST', array(), 'admin')));
            }
            else {
                $user->setCid(0);
                $user->setPid($hostid);
                $this->get('util')->generatePassword($user);
                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();
                return $this->redirect($returnUrl);
            }
        }
        return array('form' => $form->createView(), 'action' => 'add', 'host' => $host->getId(), 'user' => $user);
    }
    
    /**
     * Add new system user
     * @Route("/admin/user/add/system", name="_edit_user_add_system")
     * @Template("ICupPublicSiteBundle:Edit:edituser.html.twig")
     */
    public function addSystemAction() {
        
        $returnUrl = $this->get('util')->getReferer();

        $user = new User();
        $user->setStatus(User::$SYSTEM);
        $user->setRole(User::$ADMIN);
        $form = $this->makeUserForm($user, 'add');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($this->checkForm($form, $user)) {
            if ($this->get('logic')->isUserKnown($user->getUsername())) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.USER.NAMEEXIST', array(), 'admin')));
            }
            else {
                $user->setCid(0);
                $user->setPid(0);
                $this->get('util')->generatePassword($user);
                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();
                return $this->redirect($returnUrl);
            }
        }
        return array('form' => $form->createView(), 'action' => 'add', 'user' => $user);
    }
    
   /**
     * Change user information
     * @Route("/edit/chg/{userid}", name="_edit_user_chg")
     * @Template("ICupPublicSiteBundle:Edit:edituser.html.twig")
     */
    public function chgAction($userid) {
        
        $returnUrl = $this->get('util')->getReferer();

        /* @var $user User */
        $user = $this->get('entity')->getUserById($userid);
        $previousUserRole = $user->getRole();

        /* @var $thisuser User */
        $thisuser = $this->get('util')->getCurrentUser();
        if ($user->isEditor()) {
            $hostid = $user->getPid();
        }
        else {
            // If the user to be changed is not an editor - then make it impossible for editor admin to change it
            $hostid = -1;
        }
        $this->get('util')->validateEditorAdminUser($thisuser, $hostid);

        $form = $this->makeUserForm($user, 'chg');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($this->checkForm($form, $user)) {
            $otherUser = $this->get('logic')->getUserByName($user->getUsername());
            if ($otherUser != null && $otherUser->getId() != $user->getId()) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.USER.CANTCHANGENAME', array(), 'admin')));
            }
            else {
                $this->validateUserRole($user->getRole(), $previousUserRole);
                $em = $this->getDoctrine()->getManager();
                $em->flush();
                return $this->redirect($returnUrl);
            }
        }
        if ($user->isClub() && $user->isRelated()) {
            $club = $this->get('entity')->getClubById($user->getCid());
        }
        else {
            $club = null;
        }
        return array('form' => $form->createView(), 'action' => 'chg', 'host' => $user->getPid(), 'club' => $club, 'user' => $user, 'error' => null);
    }
    
   /**
     * Delete user information
     * @Route("/edit/del/{userid}", name="_edit_user_del")
     * @Template("ICupPublicSiteBundle:Edit:edituser.html.twig")
     */
    public function delAction($userid) {
        
        $returnUrl = $this->get('util')->getReferer();

        /* @var $user User */
        $user = $this->get('entity')->getUserById($userid);
                
        /* @var $thisuser User */
        $thisuser = $this->get('util')->getCurrentUser();
        if ($user->isEditor()) {
            $hostid = $user->getPid();
        }
        else {
            // If the user to be removed is not an editor - then make it impossible for editor admin to change it
            $hostid = -1;
        }
        $this->get('util')->validateEditorAdminUser($thisuser, $hostid);
        // Check for "self destruction" - current user is not allowed to remove own profile 
        if ($thisuser->getId() == $user->getId()) {
            throw new ValidationException("CANNOTDELETESELF", "Attempt to remove current user: user=".$thisuser->getId());
        }
        $form = $this->makeUserForm($user, 'del');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($form->isValid()) {
            $enrolls = $this->get('logic')->listEnrolledByUser($user->getId());
            foreach ($enrolls as $enroll) {
                $enroll->setUid(0);
            }
            $em = $this->getDoctrine()->getManager();
            $em->remove($user);
            $em->flush();
            return $this->redirect($returnUrl);
        }
        if ($user->isClub() && $user->isRelated()) {
            $club = $this->get('entity')->getClubById($user->getCid());
        }
        else {
            $club = null;
        }
        return array('form' => $form->createView(), 'action' => 'del', 'host' => $user->getPid(), 'club' => $club, 'user' => $user, 'error' => null);
    }
    
    private function makeUserForm(User $user, $action) {
        $roleMap = array(
            array(User::$CLUB, User::$CLUB_ADMIN),
            array(User::$EDITOR, User::$EDITOR_ADMIN),
        );
        $found = false;
        foreach ($roleMap as $roleCategory) {
            $roles = array();
            foreach ($roleCategory as $role) {
                $roles[$role] = 'FORM.USER.CHOICE.ROLE.'.$role;
                if ($user->getRole() === $role) {
                    $found = true;
                }
            }
            if ($found) {
                break;
            }
        }
        $status = array();
        foreach (array(User::$AUTH, User::$VER, User::$PRO, User::$ATT) as $stat) {
            $status[$stat] = 'FORM.USER.CHOICE.STATUS.'.$stat;
        }
        $formDef = $this->createFormBuilder($user);
        $formDef->add('name', 'text', array('label' => 'FORM.USER.NAME', 'required' => false, 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('email', 'text', array('label' => 'FORM.USER.EMAIL', 'required' => false, 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('username', 'text', array('label' => 'FORM.USER.USERNAME', 'required' => false, 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        if (!$user->isAdmin()) {
            /* @var $thisuser User */
            $thisuser = $this->get('util')->getCurrentUser();
            $formDef->add('role', 'choice', array('label' => 'FORM.USER.ROLE', 'required' => false, 'choices' => $roles, 'empty_value' => 'FORM.USER.DEFAULT',
                                                  'disabled' => $action == 'del' || ($action == 'chg' && $thisuser->getId() == $user->getId()),
                                                  'translation_domain' => 'admin'));
        }
        if ($user->isClub()) {
            $formDef->add('status', 'choice', array('label' => 'FORM.USER.STATUS', 'required' => false, 'choices' => $status, 'empty_value' => 'FORM.USER.DEFAULT', 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        }
        $formDef->add('cancel', 'submit', array('label' => 'FORM.USER.CANCEL.'.strtoupper($action), 'translation_domain' => 'admin'));
        $formDef->add('save', 'submit', array('label' => 'FORM.USER.SUBMIT.'.strtoupper($action), 'translation_domain' => 'admin'));
        return $formDef->getForm();
    }
    
    private function checkForm($form, User $user) {
        if ($form->isValid()) {
            if ($user->getName() == null || trim($user->getName()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.USER.NONAME', array(), 'admin')));
                return false;
            }
            if ($user->getUsername() == null || trim($user->getUsername()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.USER.NOUSERNAME', array(), 'admin')));
                return false;
            }
            if ($user->getRole() == null) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.USER.NOROLE', array(), 'admin')));
                return false;
            }
            if ($user->getStatus() == null) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.USER.NOSTATUS', array(), 'admin')));
                return false;
            }
            return true;
        }
        return false;
    }
    
    private function validateUserRole($userRole, $previousUserRole) {
        $roleMap = array(
            User::$CLUB => 1,
            User::$CLUB_ADMIN => 1,
            User::$EDITOR => 2,
            User::$EDITOR_ADMIN => 2,
            User::$ADMIN => 3
        );
        if ($roleMap[$userRole] != $roleMap[$previousUserRole]) {
            /* @var $thisuser User */
            $thisuser = $this->get('util')->getCurrentUser();
            throw new ValidationException("INVALIDROLECHANGE", "Attempt to upgrade user role: user=".$thisuser->getId());
        }
    }
    
   /**
     * Change password
     * @Route("/edit/chg/pass/{userid}", name="_edit_user_chg_pass")
     * @Template("ICupPublicSiteBundle:Edit:edituser.html.twig")
     */
    public function passAction($userid) {
        
        $returnUrl = $this->get('util')->getReferer();
        
        /* @var $user User */
        $user = $this->get('entity')->getUserById($userid);
        
        /* @var $thisuser User */
        $thisuser = $this->get('util')->getCurrentUser();
        if ($user->isEditor()) {
            $hostid = $user->getPid();
        }
        else {
            // If the user to change is not an editor - then make it impossible for editor admin to change it
            $hostid = -1;
        }
        $this->get('util')->validateEditorAdminUser($thisuser, $hostid);
        
        $pwd = new Password();
        $formDef = $this->createFormBuilder($pwd);
        $formDef->add('password', 'password', array('label' => 'FORM.USER.PASSWORD', 'required' => false, 'translation_domain' => 'admin'));
        $formDef->add('password2', 'password', array('label' => 'FORM.USER.PASSWORD2', 'required' => false, 'translation_domain' => 'admin'));
        $formDef->add('cancel', 'submit', array('label' => 'FORM.USER.CANCEL.CHG', 'translation_domain' => 'admin'));
        $formDef->add('save', 'submit', array('label' => 'FORM.USER.SUBMIT.CHG', 'translation_domain' => 'admin'));
        $form = $formDef->getForm();
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($form->isValid()) {
            $this->get('util')->generatePassword($user, $pwd->getPassword());
            $em = $this->getDoctrine()->getManager();
            $em->flush();
            return $this->redirect($returnUrl);
        }
        if ($user->isClub() && $user->isRelated()) {
            $club = $this->get('entity')->getClubById($user->getCid());
        }
        else {
            $club = null;
        }
        return array('form' => $form->createView(), 'action' => 'chg', 'host' => $user->getPid(), 'club' => $club, 'user' => $user, 'error' => null);
    }
}
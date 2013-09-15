<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Edit;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Entity\Password;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;

class EditUserController extends Controller
{
    /**
     * List the users related to a club
     * @Route("/edit/user/list/club/{clubid}", name="_edit_user_list")
     * @Method("GET")
     * @Template("ICupPublicSiteBundle:Edit:listusers.html.twig")
     */
    public function listUsersAction($clubid)
    {
        $this->get('util')->setupController($this);
        $em = $this->getDoctrine()->getManager();

        $club = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')->find($clubid);
        if ($club == null) {
            return $this->render('ICupPublicSiteBundle:Errors:badclub.html.twig');
        }
        
        $users = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User')
                ->findBy(array('cid' => $clubid));

        return array('club' => $club, 'users' => $users);
    }
    
    /**
     * Add new club attached user
     * @Route("/edit/user/add/club/{clubid}", name="_edit_user_add")
     * @Template("ICupPublicSiteBundle:Edit:edituser.html.twig")
     */
    public function addAction($clubid) {
        $this->get('util')->setupController($this);
        $em = $this->getDoctrine()->getManager();

        $club = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')->find($clubid);
        if ($club == null) {
            return $this->render('ICupPublicSiteBundle:Errors:badclub.html.twig');
        }

        $user = new User();
        $user->setStatus(User::$AUTH);
        $user->setRole(User::$CLUB);
        $form = $this->makeUserForm($user, 'add');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->generateUrl('_edit_user_list', array('clubid' => $clubid)));
        }
        if ($form->isValid()) {
            $usr = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User')
                    ->findOneBy(array('name' => $user->getUsername()));
            if ($usr != null) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.USER.NAMEEXIST', array(), 'admin')));
            }
            else {
                $user->setCid($clubid);
                $user->setPid(0);
                $this->generatePassword($user);
                $em->persist($user);
                $em->flush();
                return $this->redirect($this->generateUrl('_edit_user_list', array('clubid' => $clubid)));
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
        $this->get('util')->setupController($this);
        $em = $this->getDoctrine()->getManager();

        $host = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Host')->find($hostid);
        if ($host == null) {
            return $this->render('ICupPublicSiteBundle:Errors:badhost.html.twig');
        }

        $user = new User();
        $user->setStatus(User::$SYSTEM);
        $user->setRole(User::$EDITOR);
        $form = $this->makeUserForm($user, 'add');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->generateUrl('_edit_host_list'));
        }
        if ($form->isValid()) {
            $usr = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User')
                    ->findOneBy(array('name' => $user->getUsername()));
            if ($usr != null) {
                $form->addError(new FormError('ERROR.NAMEEXIST'));
            }
            else {
                $user->setCid(0);
                $user->setPid($hostid);
                $this->generatePassword($user);
                $em->persist($user);
                $em->flush();
                return $this->redirect($this->generateUrl('_edit_host_list'));
            }
        }
        return array('form' => $form->createView(), 'action' => 'add', 'host' => $hostid, 'user' => $user);
    }
    
    /**
     * Add new system user
     * @Route("/edit/user/add/system", name="_edit_user_add_system")
     * @Template("ICupPublicSiteBundle:Edit:edituser.html.twig")
     */
    public function addSystemAction() {
        $this->get('util')->setupController($this);
        $em = $this->getDoctrine()->getManager();

        $user = new User();
        $user->setStatus(User::$SYSTEM);
        $user->setRole(User::$ADMIN);
        $form = $this->makeUserForm($user, 'add');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->generateUrl('_edit_host_list'));
        }
        if ($form->isValid()) {
            $usr = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User')
                    ->findOneBy(array('name' => $user->getUsername()));
            if ($usr != null) {
                $form->addError(new FormError('ERROR.NAMEEXIST'));
            }
            else {
                $user->setCid(0);
                $user->setPid(0);
                $this->generatePassword($user);
                $em->persist($user);
                $em->flush();
                return $this->redirect($this->generateUrl('_edit_host_list'));
            }
        }
        return array('form' => $form->createView(), 'action' => 'add', 'user' => $user);
    }
    
   /**
     * Change user information
     * @Route("/edit/user/chg/{userid}", name="_edit_user_chg")
     * @Template("ICupPublicSiteBundle:Edit:edituser.html.twig")
     */
    public function chgAction($userid) {
        $this->get('util')->setupController($this);
        $em = $this->getDoctrine()->getManager();

        /* @var $user User */
        $user = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User')->find($userid);
        if ($user == null) {
             return $this->render('ICupPublicSiteBundle:Errors:baduser.html.twig');
        }

        $form = $this->makeUserForm($user, 'chg');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->generateUrl('_edit_user_list', array('clubid' => $user->getCid())));
        }
        if ($form->isValid()) {
            $otherUser = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User')
                    ->findOneBy(array('name' => $user->getUsername()));
            if ($otherUser != null && $otherUser->getId() != $user->getId()) {
                $form->addError(new FormError('ERROR.CANTCHANGENAME'));
            }
            else {
                $em->flush();
                return $this->redirect($this->generateUrl('_edit_user_list', array('clubid' => $user->getCid())));
            }
        }
        $club = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')->find($user->getCid());
        return array('form' => $form->createView(), 'action' => 'chg', 'host' => $user->getPid(), 'club' => $club, 'user' => $user, 'error' => isset($error) ? $error : null);
    }
    
   /**
     * Delete user information
     * @Route("/edit/user/del/{userid}", name="_edit_user_del")
     * @Template("ICupPublicSiteBundle:Edit:edituser.html.twig")
     */
    public function delAction($userid) {
        $this->get('util')->setupController($this);
        $em = $this->getDoctrine()->getManager();

        /* @var $user User */
        $user = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User')->find($userid);
        if ($user == null) {
             return $this->render('ICupPublicSiteBundle:Errors:baduser.html.twig');
        }
        
        $form = $this->makeUserForm($user, 'del');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->generateUrl('_edit_user_list', array('clubid' => $user->getCid())));
        }
        if ($form->isValid()) {
            $em->remove($user);
            $em->flush();
            return $this->redirect($this->generateUrl('_edit_user_list', array('clubid' => $user->getCid())));
        }
        $club = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')->find($user->getCid());
        return array('form' => $form->createView(), 'action' => 'del', 'host' => $user->getPid(), 'club' => $club, 'user' => $user, 'error' => isset($error) ? $error : null);
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
                if ($user->getRole() === $role) $found = true;
            }
            if ($found) break;
        }
        $status = array();
        foreach (array(User::$AUTH,User::$VER,User::$PRO,User::$ATT) as $stat) {
            $status[$stat] = 'FORM.USER.CHOICE.STATUS.'.$stat;
        }
        $formDef = $this->createFormBuilder($user);
        $formDef->add('name', 'text', array('label' => 'FORM.USER.NAME', 'required' => false, 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('email', 'text', array('label' => 'FORM.USER.EMAIL', 'required' => false, 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('username', 'text', array('label' => 'FORM.USER.USERNAME', 'required' => false, 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        if ($user->getRole() != User::$ADMIN) {
            $formDef->add('role', 'choice', array('label' => 'FORM.USER.ROLE', 'required' => false, 'choices' => $roles, 'empty_value' => 'FORM.USER.DEFAULT', 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        }
        if ($user->getRole() != User::$SYSTEM) {
            $formDef->add('status', 'choice', array('label' => 'FORM.USER.STATUS', 'required' => false, 'choices' => $status, 'empty_value' => 'FORM.USER.DEFAULT', 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        }
//        $formDef->add('password', 'text', array('label' => 'FORM.USER.PASSWORD', 'required' => false, 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('cancel', 'submit', array('label' => 'FORM.USER.CANCEL.'.strtoupper($action), 'translation_domain' => 'admin'));
        $formDef->add('save', 'submit', array('label' => 'FORM.USER.SUBMIT.'.strtoupper($action), 'translation_domain' => 'admin'));
        return $formDef->getForm();
    }
    
   /**
     * Change password
     * @Route("/edit/user/chg/pass/{userid}", name="_edit_user_chg_pass")
     * @Template("ICupPublicSiteBundle:Edit:edituser.html.twig")
     */
    public function passAction($userid) {
        $this->get('util')->setupController($this);
        $em = $this->getDoctrine()->getManager();

        /* @var $user User */
        $user = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User')->find($userid);
        if ($user == null) {
             return $this->render('ICupPublicSiteBundle:Errors:baduser.html.twig');
        }
        
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
            return $this->redirect($this->generateUrl('_edit_user_list', array('clubid' => $user->getCid())));
        }
        if ($form->isValid()) {
            $this->generatePassword($user, $pwd->getPassword());
            $em->flush();
            return $this->redirect($this->generateUrl('_edit_user_list', array('clubid' => $user->getCid())));
        }
        $club = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')->find($user->getCid());
        return array('form' => $form->createView(), 'action' => 'chg', 'host' => $user->getPid(), 'club' => $club, 'user' => $user, 'error' => isset($error) ? $error : null);
    }
    
    private function generatePassword(User $user, $secret = null) {
        if ($secret == null) {
            $secret = uniqid();
        }
        $factory = $this->get('security.encoder_factory');
        $encoder = $factory->getEncoder($user);
        $password = $encoder->encodePassword($secret, $user->getSalt());
        $user->setPassword($password);
        $this->get('logger')->addNotice($user->getName() . ": " . $secret . " -> " . $password);
    }
}

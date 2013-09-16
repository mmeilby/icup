<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Club;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\Yaml\Exception\RuntimeException;

/**
 * List the categories and groups available
 */
class ClubEditController extends Controller
{
    /**
     * List the clubs available
     * @Route("/club/list", name="_club_list")
     * @Method("GET")
     * @Template("ICupPublicSiteBundle:Club:listclub.html.twig")
     */
    public function listClubsAction()
    {
        $this->get('util')->setupController($this);
        $em = $this->getDoctrine()->getManager();

        /* @var $user User */
        $user = $this->getUser();
        if ($user == null) {
            throw new RuntimeException("This controller is not available for anonymous users");
        }

        if (!is_a($user, 'ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User')) {
            // Controller is called by default admin - switch to select club view
            return $this->redirect($this->generateUrl('_edit_club_list'));
        }
        if (!$user->isClub()) {
            // Admins and editors should select a club from clublist
            return $this->redirect($this->generateUrl('_edit_club_list'));
        }
        if (!$user->isRelated()) {
            // Non related users get a different view
            return array('currentuser' => $user);
        }
        
        /* @var $club Club */
        $club = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')->find($user->getCid());
        if ($club == null) {
            // User was related to a missing club
            return $this->render('ICupPublicSiteBundle:Errors:badclub.html.twig');
        }

        $users = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User')
                        ->findBy(array('cid' => $club->getId()), array('name' => 'asc'));

        return array('club' => $club, 'users' => $users, 'currentuser' => $user);
    }
    
    /**
     * List the clubs available
     * @Route("/club/disc/{userid}", name="_club_user_disconnect")
     * @Method("GET")
     * @Template("ICupPublicSiteBundle:Club:listclub.html.twig")
     */
    public function disconnectAction($userid)
    {
        $this->get('util')->setupController($this);
        $em = $this->getDoctrine()->getManager();

        /* @var $thisuser User */
        $thisuser = $this->getUser();
        if ($thisuser == null) {
            throw new RuntimeException("This controller is not available for anonymous users");
        }
        // User must have CLUB_ADMIN role to change user relation
        if (!$this->get('security.context')->isGranted('ROLE_CLUB_ADMIN')) {
             return $this->render('ICupPublicSiteBundle:Errors:notclubadmin.html.twig');
        }

        /* @var $user User */
        $user = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User')->find($userid);
        if ($user == null) {
             return $this->render('ICupPublicSiteBundle:Errors:baduser.html.twig');
        }
        if (!$user->isClub() || !$user->isRelated()) {
            // The user to be disconnected has no relation?
            return $this->render('ICupPublicSiteBundle:Errors:baduser.html.twig');
        }

        // If controller is not called by default admin - then validate the rights to change user relation
        if (is_a($thisuser, 'ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User')) {
            if ($thisuser->isClub() && $thisuser->getCid() != $user->getCid()) {
                // Even though this is a club admin - the admin does not administer the user's related club
                return $this->render('ICupPublicSiteBundle:Errors:notclubadmin.html.twig');
            }
        }
        
        // Disconnect user from club - make user a verified user with no relation
        $user->setCid(0);
        $user->setRole(User::$CLUB);
        $user->setStatus(User::$VER);
        $em->flush();
        
        return $this->redirect($this->generateUrl('_club_list'));
    }

    /**
     * List the clubs available
     * @Route("/club/connect/{clubid}/{userid}", name="_club_user_connect")
     * @Method("GET")
     * @Template("ICupPublicSiteBundle:Club:listclub.html.twig")
     */
    public function connectAction($clubid, $userid)
    {
        $this->get('util')->setupController($this);
        $em = $this->getDoctrine()->getManager();

        /* @var $thisuser User */
        $thisuser = $this->getUser();
        if ($thisuser == null) {
            throw new RuntimeException("This controller is not available for anonymous users");
        }
        // User must have CLUB_ADMIN role to change user relation
        if (!$this->get('security.context')->isGranted('ROLE_CLUB_ADMIN')) {
             return $this->render('ICupPublicSiteBundle:Errors:notclubadmin.html.twig');
        }

        /* @var $club Club */
        $club = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')->find($clubid);
        if ($club == null) {
            // User was related to a missing club
            return $this->render('ICupPublicSiteBundle:Errors:badclub.html.twig');
        }
        
        // If controller is not called by default admin then validate the rights to change user relation
        if (is_a($thisuser, 'ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User')) {
            if ($thisuser->isClub() && $thisuser->getCid() != $club->getId()) {
                // Even though this is a club admin - the admin does not administer this club
                return $this->render('ICupPublicSiteBundle:Errors:notclubadmin.html.twig');
            }
        }
        
        /* @var $user User */
        $user = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User')->find($userid);
        if ($user == null) {
             return $this->render('ICupPublicSiteBundle:Errors:baduser.html.twig');
        }
        if (!$user->isClub() || $user->getStatus() != User::$PRO || $user->getCid() != $club->getId()) {
            // The user to be connected is not a prospect?
            return $this->render('ICupPublicSiteBundle:Errors:baduser.html.twig');
        }

        // Connect user to the club - make user an attached user
        $user->setStatus(User::$ATT);
        $em->flush();
        
        return $this->redirect($this->generateUrl('_club_list'));
    }

    /**
     * List the clubs available
     * @Route("/club/chgrole/{userid}", name="_club_user_chg_role")
     * @Method("GET")
     * @Template("ICupPublicSiteBundle:Club:listclub.html.twig")
     */
    public function chgRoleAction($userid)
    {
        $this->get('util')->setupController($this);
        $em = $this->getDoctrine()->getManager();

        /* @var $thisuser User */
        $thisuser = $this->getUser();
        if ($thisuser == null) {
            throw new RuntimeException("This controller is not available for anonymous users");
        }
        // User must have CLUB_ADMIN role to change user relation
        if (!$this->get('security.context')->isGranted('ROLE_CLUB_ADMIN')) {
             return $this->render('ICupPublicSiteBundle:Errors:notclubadmin.html.twig');
        }

        /* @var $user User */
        $user = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User')->find($userid);
        if ($user == null) {
             return $this->render('ICupPublicSiteBundle:Errors:baduser.html.twig');
        }
        if (!$user->isClub() || !$user->isRelated()) {
            // The user to change role has no relation?
            return $this->render('ICupPublicSiteBundle:Errors:baduser.html.twig');
        }

        // If controller is not called by default admin then validate the rights to change user relation
        if (is_a($thisuser, 'ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User')) {
            if ($thisuser->isClub() && $thisuser->getCid() != $user->getCid()) {
                // Even though this is a club admin - the admin does not administer the user's related club
                return $this->render('ICupPublicSiteBundle:Errors:notclubadmin.html.twig');
            }
        }
        
        if ($user->getRole() == User::$CLUB) {
            $user->setRole(User::$CLUB_ADMIN);
        }
        else {
            $user->setRole(User::$CLUB);
        }
        $em->flush();
        
        return $this->redirect($this->generateUrl('_club_list'));
    }

    /**
     * Add new club
     * @Route("/club/add", name="_club_add")
     * @Template("ICupPublicSiteBundle:Club:clubenroll.html.twig")
     */
    public function addAction() {
        $this->get('util')->setupController($this);
        $em = $this->getDoctrine()->getManager();

        /* @var $user User */
        $user = $this->getUser();
        if ($user == null) {
            throw new RuntimeException("This controller is not available for anonymous users");
        }

        if ($user->isClub()) {
            if ($user->isRelated()) {
                /* @var $newclub Club */
                $newclub = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')->find($user->getPid());
                if ($newclub != null) {
                    // Controller is called by user assigned to a club - switch to club edit view
                    return $this->redirect($this->generateUrl('_club_chg_withid', array('clubid' => $newclub->getId())));
                }
                else {
                    // User was related to missing club - update status to non related
                    $user->setPid(0);
                    $user->setStatus(User::$VER);
                    $em->flush();
                }
            }
        }
        else {
            // Controller is called by editor or admin user - switch to club list view
            return $this->redirect($this->generateUrl('_edit_club_list'));
        }
        
        $club = new Club();
        $form = $this->makeClubForm($club, 'add');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->generateUrl('_club_enroll_list'));
        }
        if ($this->checkForm($form, $club, 'add')) {
            $em->persist($club);
            $em->flush();
            $user->setPid($club->getId());
            $user->setRole(User::$CLUB_ADMIN);
            $user->setStatus(User::$ATT);
            $em->flush();
            return $this->render('ICupPublicSiteBundle:Club:clubwellcome.html.twig', array('club' => $club));
        }
        return array('form' => $form->createView(), 'action' => 'add', 'club' => $club);
    }
    
   /**
     * Change club information
     * @Route("/club/chg", name="_club_chg")
     * @Template("ICupPublicSiteBundle:Club:clubenroll.html.twig")
     */
    public function chgClubAction() {
        $this->get('util')->setupController($this);
        $em = $this->getDoctrine()->getManager();

        /* @var $user User */
        $user = $this->getUser();
        if ($user == null) {
            throw new RuntimeException("This controller is not available for anonymous users");
        }

        if (!$user->isClub()) {
            // Admins and editors should select a club from clublist
            return $this->redirect($this->generateUrl('_edit_club_list'));
        }
            
        /* @var $club Club */
        $club = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')->find($user->getPid());
        if ($club == null) {
            // Controller is called by user assigned to a club - switch to club edit view
            return $this->redirect($this->generateUrl('_club_add'));
        }

        if (!$this->get('security.context')->isGranted('ROLE_CLUB_ADMIN')) {
             return $this->render('ICupPublicSiteBundle:Errors:notclubadmin.html.twig');
        }

        $form = $this->makeClubForm($club, 'chg');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->generateUrl('_club_enroll_list'));
        }
        if ($form->isValid()) {
            $otherClub = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')->findOneBy(array('name' => $club->getName()));
            if ($otherClub != null && $otherClub->getId() != $club->getId()) {
                $form->addError(new FormError('ERROR.CANTCHANGENAME'));
            }
            else {
                $em->flush();
                return $this->redirect($this->generateUrl('_club_enroll_list'));
            }
        }
        return array('form' => $form->createView(), 'action' => 'chg', 'club' => $club);
    }
    
   /**
     * Delete club information
     * @Route("/club/del", name="_club_del")
     * @Template("ICupPublicSiteBundle:Club:clubenroll.html.twig")
     */
    public function delClubAction() {
        $this->get('util')->setupController($this);
        $em = $this->getDoctrine()->getManager();

        /* @var $user User */
        $user = $this->getUser();
        if ($user == null) {
            throw new RuntimeException("This controller is not available for anonymous users");
        }

        if (!$user->isClub()) {
            // Admins and editors should select a club from clublist
            return $this->redirect($this->generateUrl('_edit_club_list'));
        }
            
        /* @var $club Club */
        $club = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')->find($user->getPid());
        if ($club == null) {
            // Controller is called by user assigned to a club - switch to club edit view
            return $this->redirect($this->generateUrl('_club_add'));
        }

        if (!$this->get('security.context')->isGranted('ROLE_CLUB_ADMIN')) {
             return $this->render('ICupPublicSiteBundle:Errors:notclubadmin.html.twig');
        }

        $form = $this->makeClubForm($club, 'del');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->generateUrl('_club_enroll_list'));
        }
        if ($form->isValid()) {
            $em->remove($club);
            $user->setPid(0);
            $em->flush();
            return $this->redirect($this->generateUrl('_club_enroll_list'));
        }
        return array('form' => $form->createView(), 'action' => 'chg', 'club' => $club);
    }
    
    private function makeClubForm($club, $action) {
        $countries = array();
        foreach (array_keys($this->get('util')->getCountries()) as $ccode) {
            $country = $this->get('translator')->trans($ccode, array(), 'lang');
            $countries[$ccode] = $country;
        }
        asort(&$countries);
        $formDef = $this->createFormBuilder($club);
        $formDef->add('name', 'text', array('label' => 'FORM.CLUB.NAME', 'required' => false, 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('country', 'choice', array('label' => 'FORM.CLUB.COUNTRY', 'required' => false, 'choices' => $countries, 'empty_value' => 'FORM.CLUB.DEFAULT', 'disabled' => $action == 'del', 'translation_domain' => 'lang'));
        $formDef->add('cancel', 'submit', array('label' => 'FORM.CLUB.CANCEL.'.strtoupper($action), 'translation_domain' => 'admin'));
        $formDef->add('save', 'submit', array('label' => 'FORM.CLUB.SUBMIT.'.strtoupper($action), 'translation_domain' => 'admin'));
        return $formDef->getForm();
    }
    
    private function checkForm($form, $club, $action) {
        if ($form->isValid()) {
            if ($club->getName() == null || trim($club->getName()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.CLUB.NONAME', array(), 'admin')));
                return false;
            }
            if ($club->getCountry() == null) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.CLUB.NOCOUNTRY', array(), 'admin')));
                return false;
            }
            $em = $this->getDoctrine()->getManager();
            $clubs = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')
                        ->findBy(array('name' => $club->getName(), 'country' => $club->getCountry()));
            if ($clubs != null && count($clubs) > 0 && $clubs[0]->getId() != $club->getId()) {
                if ($action == 'add') {
                    $form->addError(new FormError($this->get('translator')->trans('FORM.CLUB.NAMEEXIST', array(), 'admin')));
                }
                else {
                    $form->addError(new FormError($this->get('translator')->trans('FORM.CLUB.CANTCHANGENAME', array(), 'admin')));
                }
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * Enrolls a club in a tournament
     * @Route("/club/newuser/{clubid}", name="_club_enroll_new_user")
     * @Template("ICupPublicSiteBundle:Club:newuser.html.twig")
     */
    public function addUserAction($clubid) {
        $this->get('util')->setupController($this);
        $em = $this->getDoctrine()->getManager();

        /* @var $user User */
        $user = $this->getUser();
        if ($user != null) {
            /* @var $club Club */
            $club = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')->find($user->getPid());
            if ($club != null) {
                // Controller is called by non editor or admin - switch to club edit view
                return $this->redirect($this->generateUrl('_club_chg', array('clubid' => $club->getId())));
            }
        }
        
        $user = new User();
        $form = $this->makeUserForm($user, 'add');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->generateUrl('_icup'));
        }
        if ($form->isValid()) {
            if ($em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User')->findOneBy(array('username' => $user->getUsername())) != null) {
                $form->addError(new FormError('User name in use'));
            }
            else {
                $factory = $this->get('security.encoder_factory');
                $encoder = $factory->getEncoder($user);
                $password = $encoder->encodePassword($user->getPassword(), $user->getSalt());
                echo $password;
                $user->setPassword($password);
                $user->setRoles(array('ROLE_USER'));
                $em->persist($user);
                $em->flush();
                return $this->render('ICupPublicSiteBundle:Club:clubwellcome.html.twig');
            }
        }
        return array('form' => $form->createView(), 'action' => 'add', 'club' => $club);
    }
    
    private function makeUserForm($user, $action) {
        $formDef = $this->createFormBuilder($user);
        $formDef->add('name', 'text', array('label' => 'FORM.USER.NAME', 'required' => false, 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('username', 'text', array('label' => 'FORM.USER.USERNAME', 'required' => false, 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('password', 'password', array('label' => 'FORM.USER.PASSWORD', 'required' => false, 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('cancel', 'submit', array('label' => 'FORM.USER.CANCEL.'.strtoupper($action), 'translation_domain' => 'admin'));
        $formDef->add('save', 'submit', array('label' => 'FORM.USER.SUBMIT.'.strtoupper($action), 'translation_domain' => 'admin'));
        return $formDef->getForm();
    }
    
}

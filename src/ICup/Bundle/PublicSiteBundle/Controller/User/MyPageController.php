<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\User;

use ICup\Bundle\PublicSiteBundle\Services\Util;
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
class MyPageController extends Controller
{
    /**
     * Show myICup page for authenticated users
     * @Route("/user/mypage", name="_user_my_page")
     * @Method("GET")
     * @Template("ICupPublicSiteBundle:User:mypage.html.twig")
     */
    public function myPageAction()
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
            return $this->render('ICupPublicSiteBundle:User:mypage_def_admin.html.twig');
        }
        if ($user->isAdmin()) {
            // Admins should get a different view
            return $this->render('ICupPublicSiteBundle:User:mypage_admin.html.twig', array('currentuser' => $user));
        }
        if ($user->isEditor()) {
            /* @var $host Host */
            $host = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Host')->find($user->getPid());
            if ($host == null) {
                // User was related to a missing host
                return $this->render('ICupPublicSiteBundle:Errors:badhost.html.twig');
            }
            $users = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User')
                            ->findBy(array('pid' => $host->getId()), array('name' => 'asc'));

            // Editors should get a different view
            return $this->render('ICupPublicSiteBundle:User:mypage_editor.html.twig',
                    array('host' => $host, 'users' => $users, 'currentuser' => $user));
        }
        if (!$user->isRelated()) {
            // Non related users get a different view
            return $this->render('ICupPublicSiteBundle:User:mypage_nonrel.html.twig', array('currentuser' => $user));
        }
        
        /* @var $club Club */
        $club = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')->find($user->getCid());
        if ($club == null) {
            // User was related to a missing club
            return $this->render('ICupPublicSiteBundle:Errors:badclub.html.twig');
        }

        $users = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User')
                        ->findBy(array('cid' => $club->getId()), array('status' => 'asc', 'role' => 'desc', 'name' => 'asc'));

        return array('club' => $club, 'users' => $users, 'currentuser' => $user);
    }
    
    /**
     * Disconnect club relation for user
     * @Route("/club/disc/{userid}", name="_club_user_disconnect")
     * @Method("GET")
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
//        $user->setCid(0);
        $user->setRole(User::$CLUB);
        $user->setStatus(User::$PRO);
        $em->flush();
        
        return $this->redirect($this->generateUrl('_user_my_page'));
    }

    /**
     * Connect user to club
     * @Route("/club/connect/{clubid}/{userid}", name="_club_user_connect")
     * @Method("GET")
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
        
        return $this->redirect($this->generateUrl('_user_my_page'));
    }

    /**
     * List the clubs available
     * @Route("/club/chgrole/{userid}", name="_club_user_chg_role")
     * @Method("GET")
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
        
        return $this->redirect($this->generateUrl('_user_my_page'));
    }

   /**
     * Change password for logged in user
     * @Route("/user/chg/pass", name="_user_chg_pass")
     * @Template("ICupPublicSiteBundle:User:chg_pass.html.twig")
     */
    public function passAction() {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $utilService->setupController($this);
        $em = $this->getDoctrine()->getManager();

        /* @var $user User */
        $user = $this->getUser();
        if ($user == null) {
            throw new RuntimeException("This controller is not available for anonymous users");
        }

        if (!is_a($user, 'ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User')) {
            // Controller is called by default admin - prepare a new database user
            $admin = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User')->findBy(array('username' => $user->getUsername()));
            if ($admin == null) {
                $admin = new User();
                $admin->setName($user->getUsername());
                $admin->setUsername($user->getUsername());
                $admin->setRole(User::$ADMIN);
                $admin->setStatus(User::$SYSTEM);
                $admin->setEmail('');
                $admin->setPid(0);
                $admin->setCid(0);
                $utilService->generatePassword($this, $admin, $user->getUsername());
                $em->persist($admin);
                $em->flush();
                $user = $admin;
            }
            else {
                $user = $admin[0];
            }
        }
        
        $pwd = new \ICup\Bundle\PublicSiteBundle\Entity\Password();
        $formDef = $this->createFormBuilder($pwd);
        $formDef->add('password', 'password', array('label' => 'FORM.NEWPASS.PASSWORD', 'required' => false, 'translation_domain' => 'club'));
        $formDef->add('password2', 'password', array('label' => 'FORM.NEWPASS.PASSWORD2', 'required' => false, 'translation_domain' => 'club'));
        $formDef->add('cancel', 'submit', array('label' => 'FORM.NEWPASS.CANCEL', 'translation_domain' => 'club'));
        $formDef->add('save', 'submit', array('label' => 'FORM.NEWPASS.SUBMIT', 'translation_domain' => 'club'));
        $form = $formDef->getForm();
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->generateUrl('_user_my_page'));
        }
        if ($form->isValid()) {
            $utilService->generatePassword($this, $user, $pwd->getPassword());
            $em->flush();
            return $this->redirect($this->generateUrl('_user_my_page'));
        }
        return array('form' => $form->createView(), 'user' => $user);
    }
    
// TODO: FROM THIS POINT CHECK CODE
    
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
        asort($countries);
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

    
// TODO: VALIDATE THIS CODE    
    
    
    /**
     * Login club users and club administrators
     * @Route("/user/enroll", name="_ausr_enroll")
     * @Method("GET")
     * @Template("ICupPublicSiteBundle:User:ausr_enroll.html.twig")
     */
    public function enrollAction()
    {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $utilService->setupController($this);

        /* @var $user User */
        $user = $this->getUser();
        if ($user != null) {
            // Controller is called by authenticated user - switch to "MyPage"
            return $this->redirect($this->generateUrl('_user_my_page'));
        }

        $tournament = $utilService->getTournament($this);
        return array('tournament' => $tournament);
    }


    /**
     * Add new club user - part 2
     * @Route("/new/club", name="_ausr_new_club_step2")
     * @Template("ICupPublicSiteBundle:User:ausr_new_club.html.twig")
     */
    public function newClubAction()
    {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $utilService->setupController($this);
        $em = $this->getDoctrine()->getManager();

        /* @var $user User */
        $user = $this->getUser();
        if ($user == null) {
            return $this->redirect($this->generateUrl('_ausr_new_club'));
        }

        if (!$user->isClub() || $user->isRelated()) {
            // Controller is called by authenticated user directly - not a new user - switch to "MyPage"
            return $this->redirect($this->generateUrl('_user_my_page'));
        }

        $tournament = $utilService->getTournament($this);

        $country = $this->getRequest()->get('country');
        if ($country == null) {
            $map = array('en'=>'GBR', 'da'=>'DNK', 'it'=>'ITA', 'fr'=>'FRA', 'de'=>'DEU', 'es'=>'ESP', 'po'=>'POL');
            $country = $map[$this->getRequest()->getLocale()];
        }

        $club = new \ICup\Bundle\PublicSiteBundle\Entity\NewClub();
        // If country is a part of the request parameters - use it
        $club->setCountry($country);
        $form = $this->makeClubFormAlt($club, 'sel');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->generateUrl('_user_my_page'));
        }
        if ($this->checkFormAlt($form, $club, 'sel')) {
            $newClub = new Club();
            $newClub->setName($club->getName());
            $newClub->setCountry($club->getCountry());
            $em->persist($newClub);
            $em->flush();

            $user->setStatus(User::$AUTH);
            $user->setRole(User::$CLUB);
            $user->setCid($club->getId());
            $em->flush();
                
            return $this->redirect($this->generateUrl('_ausr_enroll'));
        }
        return array('form' => $form->createView(), 'action' => 'add', 'user' => $user, 'tournament' => $tournament);
    }

    /**
     * List the clubs available
     * @Route("/rest/list/clubs", name="_rest_list_clubs")
     */
    public function restListClubsAction()
    {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $utilService->setupController($this);
        $request = $this->getRequest();
        $pattern = $request->get('pattern', '%');
        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQuery("select c ".
                               "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club c ".
                               "where c.name like :pattern ".
                               "order by c.name");
        $qb->setParameter('pattern', $pattern);
        $clubs = $qb->getResult();
        $result = array();
        foreach ($clubs as $club) {
            $country = $this->get('translator')->trans($club->getCountry(), array(), 'lang');
            $result[] = array('id' => $club->getId(), 'name' => $club->getname(), 'country' => $country);
        }
        return new Response(json_encode($result));
    }
    
    
    private function makeClubFormAlt($club, $action) {
        $countries = array();
        foreach (array_keys($this->get('util')->getCountries()) as $ccode) {
            $country = $this->get('translator')->trans($ccode, array(), 'lang');
            $countries[$ccode] = $country;
        }
        asort($countries);
        $formDef = $this->createFormBuilder($club);
        $formDef->add('name', 'text', array('label' => 'FORM.CLUB.NAME', 'required' => false, 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        if ($action === 'add') {
            $formDef->add('country', 'choice', array('label' => 'FORM.CLUB.COUNTRY', 'required' => false, 'choices' => $countries, 'empty_value' => 'FORM.CLUB.DEFAULT', 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        }
        else {
            $formDef->add('clubs', 'choice', array('label' => 'FORM.CLUB.NAME', 'required' => false, 'choices' => array(), 'empty_value' => 'FORM.CLUB.DEFAULT', 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        }
        $formDef->add('cancel', 'submit', array('label' => 'FORM.CLUB.CANCEL.'.strtoupper($action), 'translation_domain' => 'admin'));
        $formDef->add('save', 'submit', array('label' => 'FORM.CLUB.SUBMIT.'.strtoupper($action), 'translation_domain' => 'admin'));
        return $formDef->getForm();
    }
    
    private function checkFormAlt($form, $club, $action) {
        if ($form->isValid()) {
            if ($club->getName() == null || trim($club->getName()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.CLUB.NONAME', array(), 'admin')));
                return false;
            }
            if ($club->getCountry() == null) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.CLUB.NOCOUNTRY', array(), 'admin')));
                return false;
            }
            return true;
        }
        return false;
    }
    
}

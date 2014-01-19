<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\User;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Entity\NewClub;
use ICup\Bundle\PublicSiteBundle\Exceptions\RedirectException;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\Yaml\Exception\RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Club administrator core functions
 */
class ClubAdminController extends Controller
{
    /**
     * Remove club relation from user identified by userid
     * Current user must be club administrator assigned to the same club as the user to be disconnected
     * @Route("/club/disc/{userid}", name="_club_user_disconnect")
     * @Method("GET")
     */
    public function disconnectAction($userid)
    {
        $this->get('util')->setupController($this);
        $em = $this->getDoctrine()->getManager();

        try {
            /* @var $user User */
            $user = $this->getUserById($em, $userid);
            // Validate current user - is it a club administrator?
            $this->validateCurrentUser($user->getCid());
            // Disconnect user from club - make user a verified user with no relation
            // However cid should not be cleared in order to restore the connection if in error
            $user->setRole(User::$CLUB);
            $user->setStatus(User::$PRO);
            $em->flush();
            // Redirect to my page
            return $this->redirect($this->generateUrl('_user_my_page_users'));
        } catch (ValidationException $vexc) {
            return $this->render('ICupPublicSiteBundle:Errors:' . $vexc->getMessage(), array('redirect' => '_user_my_page'));
        } 
    }

    /**
     * Promote user identified by userid as attached user to club identified by clubid
     * Current user must be club administrator assigned to the club
     * The promoted user must be related to the club as a prospect
     * This function can not promote non related users
     * @Route("/club/connect/{clubid}/{userid}", name="_club_user_connect")
     * @Method("GET")
     */
    public function connectAction($clubid, $userid)
    {
        $this->get('util')->setupController($this);
        $em = $this->getDoctrine()->getManager();

        try {
            /* @var $user User */
            $user = $this->getUserById($em, $userid);
            // Validate user - must be a club user prospect
            if (!$user->isRelatedTo($clubid)) {
                // User is not related to the club
                throw new ValidationException("notclubadmin.html.twig");
            }
            // Validate current user - is it a club administrator?
            $this->validateCurrentUser($clubid);
            // Connect user to the club - make user an attached user
            $user->setStatus(User::$ATT);
            $em->flush();
            // Redirect to my page
            return $this->redirect($this->generateUrl('_user_my_page_users'));
        } catch (ValidationException $vexc) {
            return $this->render('ICupPublicSiteBundle:Errors:' . $vexc->getMessage(), array('redirect' => '_user_my_page'));
        } 
    }

    /**
     * Switch user role for user identified by userid
     * Club users will be promoted to club administrators
     * Club administrators will be demoted to club users
     * Current user must be club administrator assigned to the same club as the user to switch role
     * @Route("/club/chgrole/{userid}", name="_club_user_chg_role")
     * @Method("GET")
     */
    public function chgRoleAction($userid)
    {
        $this->get('util')->setupController($this);
        $em = $this->getDoctrine()->getManager();

        try {
            /* @var $user User */
            $user = $this->getUserById($em, $userid);
            // Validate current user - is it a club administrator?
            $this->validateCurrentUser($user->getCid());
            // Switch user role
            $user->setRole($user->getRole() === User::$CLUB ? User::$CLUB_ADMIN : User::$CLUB);
            $em->flush();
            // Redirect to my page
            return $this->redirect($this->generateUrl('_user_my_page_users'));
        } catch (ValidationException $vexc) {
            return $this->render('ICupPublicSiteBundle:Errors:' . $vexc->getMessage(), array('redirect' => '_user_my_page'));
        } 
    }

    /**
     * Submit request for current user to be an attached user to club identified by clubid
     * Current user must be a non related plain user
     * This function can not promote related prospect users
     * NOTE: this action will be requested from javascript and can not be parameterized the traditional Symfony way
     * @Route("/club/request", name="_club_user_request")
     * @Method("GET")
     */
    public function requestAction()
    {
        $this->get('util')->setupController($this);
        $em = $this->getDoctrine()->getManager();

        $clubid = $this->getRequest()->get('clubid', '');
        try {
            /* @var $user User */
            $user = $this->getCurrentUser();
            // Validate user - must be an unrelated user
            if ($user->isRelated()) {
                // User is related to the club
                throw new ValidationException("cannotberelated.html.twig");
            }
            // Validate club id
            $club = $this->getClubById($clubid);
            // Connect user to the club - make user a prospected user
            $user->setStatus(User::$PRO);
            $user->setCid($club->getId());
            $em->flush();
            // Redirect to my page
            return $this->redirect($this->generateUrl('_user_my_page'));
        } catch (ValidationException $vexc) {
            return $this->render('ICupPublicSiteBundle:Errors:' . $vexc->getMessage(), array('redirect' => '_user_my_page'));
        } 
    }

    /**
     * Add new club for user not related to any club
     * Current user must be a non related plain user
     * @Route("/club/new", name="_club_new")
     * @Template("ICupPublicSiteBundle:User:ausr_new_club.html.twig")
     */
    public function newClubAction()
    {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $utilService->setupController($this);

        try {
            /* @var $user User */
            $user = $this->getCurrentUser();
            // Validate current user - is it a club user/administrator?
            $this->validateClubUser($user);
            // Validate user - must be a non related club user
            if ($user->isRelated()) {
                // Controller is called by user assigned to a club - switch to my page
                return $this->redirect($this->generateUrl('_user_my_page'));
            }
            // Get tournament if defined
            $tournament = $utilService->getTournament($this);
            // Prepare default data for form
            $clubFormData = $this->getClubDefaults();
            $form = $this->makeClubForm($clubFormData, 'sel');
            $request = $this->getRequest();
            $form->handleRequest($request);
            if ($form->get('cancel')->isClicked()) {
                return $this->redirect($this->generateUrl('_user_my_page'));
            }
            if ($this->checkForm($form, $clubFormData, 'sel')) {
                $this->updateOrCreateClub($user, $clubFormData);
                return $this->redirect($this->generateUrl('_user_my_page'));
            }
            return array('form' => $form->createView(), 'action' => 'add', 'user' => $user, 'tournament' => $tournament);
        } catch (RedirectException $rexc) {
            return $rexc->getResponse();
        } catch (ValidationException $vexc) {
            $this->get('logger')->addError("User CID/PID is invalid: " . $user->dump());
            return $this->render('ICupPublicSiteBundle:Errors:' . $vexc->getMessage(), array('redirect' => '_user_my_page'));
        }
    }

    /**
     * List the clubs available for a country matching the pattern given
     * Arguments:
     *   country: countrycode
     *   pattern: stringpattern with % for wildcard
     * @Route("/rest/list/clubs", name="_rest_list_clubs")
     */
    public function restListClubsAction()
    {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $utilService->setupController($this);
        $request = $this->getRequest();
        $pattern = $request->get('pattern', '%');
        $countryCode = $request->get('country', '');
        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQuery("select c ".
                               "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club c ".
                               "where c.name like :pattern and c.country=:country ".
                               "order by c.name");
        $qb->setParameter('pattern', $pattern);
        $qb->setParameter('country', $countryCode);
        $clubs = $qb->getResult();
        $result = array();
        foreach ($clubs as $club) {
            $country = $this->get('translator')->trans($club->getCountry(), array(), 'lang');
            $result[] = array('id' => $club->getId(), 'name' => $club->getname(), 'country' => $country);
        }
        return new Response(json_encode($result));
    }
    
    private function getClubDefaults() {
        // Prepare current language selection for preset of country
        $country = $this->getRequest()->get('country');
        if ($country == null) {
            $map = array('en'=>'GBR', 'da'=>'DNK', 'it'=>'ITA', 'fr'=>'FRA', 'de'=>'DEU', 'es'=>'ESP', 'po'=>'POL');
            $country = $map[$this->getRequest()->getLocale()];
        }

        $clubFormData = new NewClub();
        // If country is a part of the request parameters - use it
        $clubFormData->setCountry($country);
        return $clubFormData;
    }

    private function updateOrCreateClub(User $user, NewClub $clubFormData) {
        $em = $this->getDoctrine()->getManager();
        $club = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')
                   ->findOneBy(array('name' => $clubFormData->getName(), 'country' => $clubFormData->getCountry()));
        if ($club != null) {
            $user->setStatus(User::$PRO);
            $user->setRole(User::$CLUB);
        }
        else {
            $club = new Club();
            $club->setName($clubFormData->getName());
            $club->setCountry($clubFormData->getCountry());
            $em->persist($club);
            $em->flush();
            $user->setStatus(User::$ATT);
            $user->setRole(User::$CLUB_ADMIN);
        }

        $user->setCid($club->getId());
        $em->flush();
        return $club;
    }
    
    private function makeClubForm($club, $action) {
        $countries = array();
        foreach (array_keys($this->get('util')->getCountries()) as $ccode) {
            $country = $this->get('translator')->trans($ccode, array(), 'lang');
            $countries[$ccode] = $country;
        }
        asort($countries);
        $formDef = $this->createFormBuilder($club);
        $formDef->add('country', 'choice', array('label' => 'FORM.CLUB.COUNTRY', 'required' => false, 'choices' => $countries, 'empty_value' => 'FORM.CLUB.DEFAULT', 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('name', 'text', array('label' => 'FORM.CLUB.NAME', 'required' => false, 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
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
            return true;
        }
        return false;
    }

    private function validateClubUser(User $user) {
        if (!$user->isClub()) {
            // Controller is called by editor or admin user - switch to my page
            $rexp = new RedirectException();
            $rexp->setResponse($this->redirect($this->generateUrl('_user_my_page')));
            throw $rexp;
        }
    }
    
    private function validateCurrentUser($clubid) {
        /* @var $thisuser User */
        $thisuser = $this->getCurrentUser();
        // User must have CLUB_ADMIN role to change user properties
        if (!$this->get('security.context')->isGranted('ROLE_CLUB_ADMIN')) {
            throw new ValidationException("notclubadmin.html.twig");
        }
        // If controller is not called by default admin then validate the user
        if (is_a($thisuser, 'ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User')) {
            // If user is a club administrator then validate relation to the club
            if ($thisuser->isClub() && !$thisuser->isRelatedTo($clubid)) {
                // Even though this is a club admin - the admin does not administer this club
                throw new ValidationException("notclubadmin.html.twig");
            }
        }
        return $thisuser;
    }

    private function getCurrentUser() {
        /* @var $thisuser User */
        $thisuser = $this->getUser();
        if ($thisuser == null) {
            throw new RuntimeException("This controller is not available for anonymous users");
        }
        return $thisuser;
    }
    
    private function getUserById($em, $userid) {
        /* @var $user User */
        $user = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User')->find($userid);
        if ($user == null) {
            throw new ValidationException("baduser.html.twig");
        }
        if (!$user->isClub() || !$user->isRelated()) {
            // The user to be disconnected has no relation?
            throw new ValidationException("baduser.html.twig");
        }
        return $user;
    }

    private function getClubById($clubid) {
        $em = $this->getDoctrine()->getManager();
        /* @var $club Club */
        $club = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')->find($clubid);
        if ($club == null) {
            // User was related to a missing club
            throw new ValidationException("badclub.html.twig");
        }
        return $club;
    }
}

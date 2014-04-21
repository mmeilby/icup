<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Club;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
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
use Symfony\Component\HttpFoundation\Response;
use ICup\Bundle\PublicSiteBundle\Services\Util;

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
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $utilService->setupController();
        $em = $this->getDoctrine()->getManager();

        try {
            /* @var $user User */
            $user = $this->getClubUserById($userid);
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
            return $this->render('ICupPublicSiteBundle:Errors:' . $vexc->getMessage(), array('redirect' => $this->generateUrl('_user_my_page')));
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
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $utilService->setupController();
        $em = $this->getDoctrine()->getManager();

        try {
            /* @var $user User */
            $user = $this->getClubUserById($userid);
            // Validate user - must be a club user prospect
            if (!$user->isRelatedTo($clubid)) {
                // User is not related to the club
                throw new ValidationException("NOTCLUBADMIN", "userid=".$user->getId().", clubid=".$clubid);
            }
            // Validate current user - is it a club administrator?
            $this->validateCurrentUser($clubid);
            // Connect user to the club - make user an attached user
            $user->setStatus(User::$ATT);
            $em->flush();
            // Redirect to my page
            return $this->redirect($this->generateUrl('_user_my_page_users'));
        } catch (ValidationException $vexc) {
            return $this->render('ICupPublicSiteBundle:Errors:' . $vexc->getMessage(), array('redirect' => $this->generateUrl('_user_my_page')));
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
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $utilService->setupController();
        $em = $this->getDoctrine()->getManager();

        try {
            /* @var $user User */
            $user = $this->getClubUserById($userid);
            // Validate current user - is it a club administrator?
            $this->validateCurrentUser($user->getCid());
            // Switch user role
            $user->setRole($user->getRole() === User::$CLUB ? User::$CLUB_ADMIN : User::$CLUB);
            $em->flush();
            // Redirect to my page
            return $this->redirect($this->generateUrl('_user_my_page_users'));
        } catch (ValidationException $vexc) {
            return $this->render('ICupPublicSiteBundle:Errors:' . $vexc->getMessage(), array('redirect' => $this->generateUrl('_user_my_page')));
        } 
    }

    /**
     * Submit request for current user to be an attached user to club identified by clubid
     * Current user must be a non related plain user
     * This function can not promote related prospect users
     * NOTE: this action will be requested from javascript and can not be parameterized the traditional Symfony way
     * @Route("/user/request", name="_club_user_request")
     * @Method("GET")
     */
    public function requestAction()
    {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $utilService->setupController();
        $em = $this->getDoctrine()->getManager();

        $clubid = $this->getRequest()->get('clubid', '');
        try {
            /* @var $user User */
            $user = $utilService->getCurrentUser();
            // Validate user - must be an unrelated user
            if ($user->isRelated()) {
                // User is related to the club
                throw new ValidationException("CANNOTBERELATED", "userid=".$user->getId());
            }
            // Validate club id
            $club = $this->get('entity')->getClubById($clubid);
            // Connect user to the club - make user a prospected user
            $user->setStatus(User::$PRO);
            $user->setCid($club->getId());
            $em->flush();
            // Redirect to my page
            return $this->redirect($this->generateUrl('_user_my_page'));
        } catch (ValidationException $vexc) {
            return $this->render('ICupPublicSiteBundle:Errors:' . $vexc->getMessage(), array('redirect' => $this->generateUrl('_user_my_page')));
        } 
    }

    /**
     * Cancel submitted request for club relation from current user
     * Current user must be prospect user for any club
     * User will be reset to a verified user with no relation
     * @Route("/user/refuse", name="_club_user_refuse")
     * @Method("GET")
     */
    public function refuseAction()
    {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $utilService->setupController();
        $em = $this->getDoctrine()->getManager();

        try {
            /* @var $user User */
            $user = $utilService->getCurrentUser();
            // Validate user - must be a prospect user
            if (!$user->isRelated()) {
                // User is not related to any club
                throw new ValidationException("NEEDTOBERELATED", "userid=".$user->getId());
            }
            // Disconnect user from club - make user a verified user with no relation
            // However cid should not be cleared in order to restore the connection if in error
            $user->setRole(User::$CLUB);
            $user->setStatus(User::$VER);
            $em->flush();
            // Redirect to my page
            return $this->redirect($this->generateUrl('_user_my_page'));
        } catch (ValidationException $vexc) {
            return $this->render('ICupPublicSiteBundle:Errors:' . $vexc->getMessage(), array('redirect' => $this->generateUrl('_user_my_page')));
        } 
    }

    /**
     * Add new club for user not related to any club
     * Current user must be a non related plain user
     * @Route("/user/new", name="_club_new")
     * @Template("ICupPublicSiteBundle:User:ausr_new_club.html.twig")
     */
    public function newClubAction()
    {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $utilService->setupController();

        try {
            /* @var $user User */
            $user = $utilService->getCurrentUser();
            // Validate current user - is it a club user/administrator?
            $utilService->validateClubUser($user);
            // Validate user - must be a non related club user
            if ($user->isRelated()) {
                // Controller is called by user assigned to a club - switch to my page
                return $this->redirect($this->generateUrl('_user_my_page'));
            }
            // Get tournament if defined
            $tournament = $utilService->getTournament();
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
            return $this->render('ICupPublicSiteBundle:Errors:' . $vexc->getMessage(), array('redirect' => $this->generateUrl('_user_my_page')));
        }
    }

    /**
     * Add new club for for selected tournament
     * - Editor version only -
     * @Route("/host/club/new/{tournamentid}", name="_host_club_new")
     * @Template("ICupPublicSiteBundle:Host:new_club.html.twig")
     */
    public function hostNewClubAction($tournamentid)
    {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $utilService->setupController();

        try {
            /* @var $user User */
            $user = $utilService->getCurrentUser();
            /* @var $tournament Tournament */
            $tournament = $this->get('entity')->getTournamentById($tournamentid);
            // Check that user is editor
            $utilService->validateEditorAdminUser($user, $tournament->getPid());

            // Prepare default data for form
            $clubFormData = $this->getClubDefaults();
            $form = $this->makeClubForm($clubFormData, 'sel');
            $request = $this->getRequest();
            $form->handleRequest($request);
            if ($form->get('cancel')->isClicked()) {
                return $this->redirect($this->generateUrl('_host_list_clubs', array('tournamentid' => $tournamentid)));
            }
            if ($this->checkForm($form, $clubFormData, 'sel')) {
                $club = $this->enrollClub($tournament, $user, $clubFormData);
                return $this->redirect($this->generateUrl('_club_enroll_list_admin', array('tournament' => $tournamentid, 'club' => $club->getId())));
            }
            return array('form' => $form->createView(), 'action' => 'add', 'user' => $user, 'tournament' => $tournament);
        } catch (RedirectException $rexc) {
            return $rexc->getResponse();
        } catch (ValidationException $vexc) {
            return $this->render('ICupPublicSiteBundle:Errors:' . $vexc->getMessage(), array('redirect' => $this->generateUrl('_host_list_clubs', array('tournamentid' => $tournamentid))));
        }
    }

    /**
     * Select club from list of matched club names rather than adding a new club
     * Current user must be an editor
     * NOTE: this action will be requested from javascript and can not be parameterized the traditional Symfony way
     * @Route("/host/select/club", name="_host_select_club")
     * @Method("GET")
     */
    public function selectClubAction()
    {
        $clubid = $this->getRequest()->get('clubid', '');
        $tournamentid = $this->getRequest()->get('tournamentid', '');
        return $this->redirect($this->generateUrl('_club_enroll_list_admin', array('tournament' => $tournamentid, 'club' => $clubid)));
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
        $utilService->setupController();
        $request = $this->getRequest();
        $pattern = $request->get('pattern', '%');
        $countryCode = $request->get('country', '');
        $clubs = $this->get('logic')->listClubsByPattern($pattern, $countryCode);
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
        $club = $this->get('logic')->getClubByName($clubFormData->getName(), $clubFormData->getCountry());
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
    
    private function enrollClub(Tournament $tournament, User $user, NewClub $clubFormData) {
        $em = $this->getDoctrine()->getManager();
        $club = $this->get('logic')->getClubByName($clubFormData->getName(), $clubFormData->getCountry());
        if ($club == null) {
            $club = new Club();
            $club->setName($clubFormData->getName());
            $club->setCountry($clubFormData->getCountry());
            $em->persist($club);
            $em->flush();
        }
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
    
    private function getClubUserById($userid) {
        /* @var $user User */
        $user = $this->get('entity')->getUserById($userid);
        if (!$user->isClub() || !$user->isRelated()) {
            // The user has no relation?
            throw new ValidationException("NEEDTOBERELATED", "userid=".$user->getId().", role=".$user->getRole());
        }
        return $user;
    }
    
    
    private function validateCurrentUser($clubid) {
        /* @var $thisuser User */
        $thisuser = $this->get('util')->getCurrentUser();
        // User must have CLUB_ADMIN role to change user properties
        if (!$this->get('security.context')->isGranted('ROLE_CLUB_ADMIN')) {
            throw new ValidationException("NEEDTOBERELATED", $this->get('entity')->isLocalAdmin($thisuser) ?
                    "Local admin" : "userid=".$thisuser->getId().", role=".$thisuser->getRole());
        }
        // If controller is not called by default admin then validate the user
        if (!$this->get('entity')->isLocalAdmin($thisuser)) {
            // If user is a club administrator then validate relation to the club
            if ($thisuser->isClub() && !$thisuser->isRelatedTo($clubid)) {
                // Even though this is a club admin - the admin does not administer this club
                throw new ValidationException("NOTCLUBADMIN", "userid=".$thisuser->getId().", role=".$thisuser->getRole());
            }
        }
        return $thisuser;
    }
}

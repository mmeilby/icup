<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Club;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Entity\NewClub;
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
     * Add new club for user not related to any club
     * Current user must be a non related plain user
     * @Route("/user/club/new", name="_club_new")
     * @Template("ICupPublicSiteBundle:User:ausr_new_club.html.twig")
     */
    public function newClubAction()
    {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        if (!$user->isClub()) {
            // The user is not a club user...
            throw new ValidationException("NOTCLUBUSER", "userid=".$user->getId().", role=".$user->getRole());
        }
        // Validate user - must be a non related club user
        if ($user->isRelated()) {
            // Controller is called by user assigned to a club - switch to my page
            return $this->redirect($this->generateUrl('_user_my_page'));
        }
        // Prepare default data for form
        $clubFormData = $this->getClubDefaults();
        $form = $this->makeClubForm($clubFormData, 'sel');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->generateUrl('_user_my_page'));
        }
        if ($this->checkForm($form, $clubFormData)) {
            $club = $this->get('logic')->getClubByName($clubFormData->getName(), $clubFormData->getCountry());
            if ($club != null) {
                $user->setStatus(User::$PRO);
                $user->setRole(User::$CLUB);
            }
            else {
                $club = new Club();
                $club->setName($clubFormData->getName());
                $club->setCountry($clubFormData->getCountry());
                $em = $this->getDoctrine()->getManager();
                $em->persist($club);
                $em->flush();
                $user->setStatus(User::$ATT);
                $user->setRole(User::$CLUB_ADMIN);
            }
            $user->setCid($club->getId());
            $em->flush();
            return $this->redirect($this->generateUrl('_user_my_page'));
        }
        // Get tournament if defined
        $tournamentKey = $utilService->getTournamentKey();
        if ($tournamentKey != '_') {
            $tournament = $this->get('logic')->getTournamentByKey($tournamentKey);
        }
        else {
            $tournament = null;
        }
        return array('form' => $form->createView(), 'action' => 'add', 'user' => $user, 'tournament' => $tournament);
    }

    /**
     * Add new club for for selected tournament
     * - Editor version only -
     * @Route("/edit/club/new/{tournamentid}", name="_host_club_new")
     * @Template("ICupPublicSiteBundle:Host:new_club.html.twig")
     */
    public function hostNewClubAction($tournamentid)
    {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        

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
        if ($this->checkForm($form, $clubFormData)) {
            $club = $this->get('logic')->getClubByName($clubFormData->getName(), $clubFormData->getCountry());
            if ($club == null) {
                $club = new Club();
                $club->setName($clubFormData->getName());
                $club->setCountry($clubFormData->getCountry());
                $em = $this->getDoctrine()->getManager();
                $em->persist($club);
                $em->flush();
            }
            return $this->redirect($this->generateUrl('_club_enroll_list_admin', array('tournament' => $tournamentid, 'club' => $club->getId())));
        }
        return array('form' => $form->createView(), 'action' => 'add', 'user' => $user, 'tournament' => $tournament);
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

    private function makeClubForm($club, $action) {
        $countries = array();
        foreach ($this->get('util')->getCountries() as $ccode) {
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
    
    private function checkForm($form, $club) {
        if ($form->isValid()) {
            if ($club->getName() == null || trim($club->getName()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.CLUB.NONAME', array(), 'admin')));
            }
            if ($club->getCountry() == null) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.CLUB.NOCOUNTRY', array(), 'admin')));
            }
        }
        return $form->isValid();
    }
}

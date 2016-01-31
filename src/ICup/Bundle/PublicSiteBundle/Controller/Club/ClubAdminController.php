<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Club;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Entity\NewClub;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use ICup\Bundle\PublicSiteBundle\Services\Util;
use Symfony\Component\HttpFoundation\Request;

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
    public function newClubAction(Request $request)
    {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        /* @var $user User */
        $user = $utilService->getCurrentUser();
        if (!$user->isClubUser()) {
            // The user is not a club user...
            throw new ValidationException("NOTCLUBUSER", "user=".$user->__toString());
        }
        // Validate user - must be a non related club user
        if ($user->isRelated()) {
            // Controller is called by user assigned to a club - switch to my page
            return $this->redirect($this->generateUrl('_user_my_page'));
        }
        // Prepare default data for form
        $clubFormData = $this->getClubDefaults();
        $form = $this->makeClubForm($clubFormData);
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->generateUrl('_user_my_page'));
        }
        if ($this->checkForm($form, $clubFormData)) {
            $club = $this->get('logic')->getClubByName($clubFormData->getName(), $clubFormData->getCountry());
            if ($club != null) {
//                $user->setStatus(User::$PRO);
//                $user->setRole(User::$CLUB);
            }
            else {
                $club = new Club();
                $club->setName($clubFormData->getName());
                $club->setCountry($clubFormData->getCountry());
                $em = $this->getDoctrine()->getManager();
                $em->persist($club);
                $em->flush();
//                $user->setStatus(User::$ATT);
                $user->addRole(User::ROLE_CLUB_ADMIN);
//                $user->setRole(User::$CLUB_ADMIN);
            }
            $user->setClub($club);
            $em = $this->getDoctrine()->getManager();
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
        return array('form' => $form->createView(), 'currentuser' => $user, 'tournament' => $tournament);
    }

    /**
     * Add new club for for selected tournament
     * - Editor version only -
     * @Route("/edit/club/new/{tournamentid}", name="_host_club_new")
     * @Template("ICupPublicSiteBundle:Host:new_club.html.twig")
     */
    public function hostNewClubAction($tournamentid, Request $request)
    {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        /* @var $user User */
        $user = $utilService->getCurrentUser();
        /* @var $tournament Tournament */
        $tournament = $this->get('entity')->getTournamentById($tournamentid);
        $host = $tournament->getHost();
        $utilService->validateEditorAdminUser($user, $host);

        // Prepare default data for form
        $clubFormData = $this->getClubDefaults($request);
        $form = $this->makeClubForm($clubFormData);
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

    private function getClubDefaults(Request $request) {
        // Prepare current language selection for preset of country
        $country = $request->get('country');
        if ($country == null) {
            $country = $this->get('util')->getCountryByLocale($request->getLocale());
        }

        $clubFormData = new NewClub();
        // If country is a part of the request parameters - use it
        $clubFormData->setCountry($country);
        return $clubFormData;
    }

    private function makeClubForm($club) {
        $countries = array();
        foreach ($this->get('util')->getCountries() as $ccode) {
            $country = $this->get('translator')->trans($ccode, array(), 'lang');
            $countries[$ccode] = $country;
        }
        asort($countries);
        $formDef = $this->createFormBuilder($club);
        $formDef->add('country', 'choice', array('label' => 'FORM.NEWCLUB.COUNTRY',
                                                'required' => false,
                                                'choices' => $countries,
                                                'empty_value' => 'FORM.NEWCLUB.DEFAULT',
                                                'disabled' => false,
                                                'translation_domain' => 'club',
                                                'icon' => 'fa fa-globe'));
        $formDef->add('name', 'text', array('label' => 'FORM.NEWCLUB.NAME',
                                                'required' => false,
                                                'disabled' => false,
                                                'translation_domain' => 'club',
                                                'help' => 'FORM.NEWCLUB.HELP.NAME',
                                                'icon' => 'fa fa-home'));
        $formDef->add('cancel', 'submit', array('label' => 'FORM.NEWCLUB.CANCEL',
                                                'translation_domain' => 'club',
                                                'buttontype' => 'btn btn-default',
                                                'icon' => 'fa fa-times'));
        $formDef->add('save', 'submit', array('label' => 'FORM.NEWCLUB.SUBMIT',
                                                'translation_domain' => 'club',
                                                'icon' => 'fa fa-check'));
        return $formDef->getForm();
    }
    
    private function checkForm($form, $club) {
        if ($form->isValid()) {
            if ($club->getName() == null || trim($club->getName()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.NEWCLUB.NONAME', array(), 'club')));
            }
            if ($club->getCountry() == null) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.NEWCLUB.NOCOUNTRY', array(), 'club')));
            }
        }
        return $form->isValid();
    }
}

<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\User;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Entity\NewClub;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use ICup\Bundle\PublicSiteBundle\Services\Util;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Select club to follow for anonymous users
 */
class SelectClubController extends Controller
{
    public static $ENV_CLUB_LIST = '_club_list';
    
    /**
     * Display list of clubs the anonymous user has selected from the environment store
     * Current user can be a registered user
     * @Route("/env/club/list", name="_club_select")
     * @Template("ICupPublicSiteBundle:User:select_club.html.twig")
     */
    public function listClubAction(Request $request)
    {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        /* @var $session Session */
        $session = $request->getSession();
        if ($session->has(SelectClubController::$ENV_CLUB_LIST)) {
            $clubs = $session->get(SelectClubController::$ENV_CLUB_LIST);
        }
        else {
            $clubs = $this->getClubList($request);
        }
        // Prepare default data for form
        $clubFormData = $this->getClubDefaults($request);
        $form = $this->makeClubForm($clubFormData);
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            // Reset club list to last known list
            $this->getClubList($request);
            return $this->redirect($this->generateUrl('_icup'));
        }
        elseif ($form->isValid()) {
            $redirectResponse = $this->redirect($this->generateUrl('_icup'));
            $this->setClubList($redirectResponse, $clubs);
            return $redirectResponse;
        }
        else {
            // Get tournament if defined
            $tournamentKey = $utilService->getTournamentKey();
            if ($tournamentKey != '_') {
                $tournament = $this->get('logic')->getTournamentByKey($tournamentKey);
            }
            else {
                $tournament = null;
            }
            return array('form' => $form->createView(),
                         'clubs' => $clubs,
                         'tournament' => $tournament);
        }
    }

    /**
     * Display list of clubs the anonymous user has selected from the environment store
     * Current user can be a registered user
     * @Route("/env/club/request/{clubid}", name="_club_select_request", options={"expose"=true})
     * @Template("ICupPublicSiteBundle:User:select_club.html.twig")
     */
    public function addClubAction($clubid, Request $request)
    {
        /* @var $session Session */
        $session = $request->getSession();
        $club = $this->get('entity')->getClubById($clubid);
        $clubs = $session->get(SelectClubController::$ENV_CLUB_LIST, array());
        $clubs[$club->getId()] = array('club' => $club, 'selected' => true);
        $session->set(SelectClubController::$ENV_CLUB_LIST, $clubs);
        return $this->redirect($this->generateUrl('_club_select'));;
    }

    /**
     * Display list of clubs the anonymous user has selected from the environment store
     * Current user can be a registered user
     * @Route("/env/club/select/{clubid}", name="_club_select_toggle", options={"expose"=true})
     * @Template("ICupPublicSiteBundle:User:select_club.html.twig")
     */
    public function selectClubAction($clubid, Request $request)
    {
        /* @var $session Session */
        $session = $request->getSession();
        $clubs = $session->get(SelectClubController::$ENV_CLUB_LIST, array());
        if (array_key_exists($clubid, $clubs)) {
            $clubs[$clubid]['selected'] = !$clubs[$clubid]['selected'];
            $session->set(SelectClubController::$ENV_CLUB_LIST, $clubs);
        }
        return $this->redirect($this->generateUrl('_club_select'));;
    }

    private function setClubList(Response $response, $clubs) {
        $club_list = array();
        foreach ($clubs as $club_rec) {
            if ($club_rec['selected']) {
                $club = $club_rec['club'];
                $club_list[] = $club->getName().'|'.$club->getCountry();
            }
        }
        $response->headers->setCookie(
           new Cookie(SelectClubController::$ENV_CLUB_LIST,
                      implode(':', $club_list),
                      time() + 60*60*24*365
           )
        );
    }
    
    public function getClubList(Request $request) {
        $clubs = array();
        $club_list = $request->cookies->get(SelectClubController::$ENV_CLUB_LIST, '');
        foreach (explode(':', $club_list) as $club_ident) {
            $club_ident_array = explode('|', $club_ident);
            $name = $club_ident_array[0];
            if (count($club_ident_array) > 1) {
                $countryCode = $club_ident_array[1];
            }
            else {
                $countryCode = 'EUR';
            }
            $club = $this->get('logic')->getClubByName($name, $countryCode);
            if ($club) {
                $clubs[$club->getId()] = array('club' => $club, 'selected' => true);
            }
        }
        /* @var $session Session */
        $session = $request->getSession();
        $session->set(SelectClubController::$ENV_CLUB_LIST, $clubs);
        return $clubs;
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
        $formDef->add('country', 'choice', array('label' => 'FORM.SELECTCLUB.COUNTRY',
                                                'required' => false,
                                                'choices' => $countries,
                                                'empty_value' => 'FORM.SELECTCLUB.DEFAULT',
                                                'disabled' => false,
                                                'translation_domain' => 'club',
                                                'icon' => 'fa fa-globe'));
        $formDef->add('name', 'text', array('label' => 'FORM.SELECTCLUB.NAME',
                                                'required' => false,
                                                'disabled' => false,
                                                'translation_domain' => 'club',
                                                'help' => 'FORM.SELECTCLUB.HELP.NAME',
                                                'icon' => 'fa fa-home'));
        $formDef->add('cancel', 'submit', array('label' => 'FORM.SELECTCLUB.CANCEL',
                                                'translation_domain' => 'club',
                                                'buttontype' => 'btn btn-default',
                                                'icon' => 'fa fa-times'));
        $formDef->add('save', 'submit', array('label' => 'FORM.SELECTCLUB.SUBMIT',
                                                'translation_domain' => 'club',
                                                'icon' => 'fa fa-check'));
        return $formDef->getForm();
    }
}

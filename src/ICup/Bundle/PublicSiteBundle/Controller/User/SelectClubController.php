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

/**
 * Club administrator core functions
 */
class SelectClubController extends Controller
{
    /**
     * Add new club for user not related to any club
     * Current user must be a non related plain user
     * @Route("/new/club/select", name="_club_select")
     * @Template("ICupPublicSiteBundle:User:ausr_new_club.html.twig")
     */
    public function selectClubAction(Request $request)
    {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        /* @var $user User */
        $user = $utilService->getCurrentUser();
        // Prepare default data for form
        $clubFormData = $this->getClubDefaults($request);
        $form = $this->makeClubForm($clubFormData);
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->generateUrl('_user_my_page'));
        }
        elseif ($this->checkForm($form, $clubFormData)) {
            $club = $this->get('logic')->getClubByName($clubFormData->getName(), $clubFormData->getCountry());
            if ($club != null) {
            }
            else {
            }
            $user->setCid($club->getId());
            $em->flush();
            return $this->redirect($this->generateUrl('_user_my_page'));
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
            return array('form' => $form->createView(), 'currentuser' => $user, 'tournament' => $tournament);
        }
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

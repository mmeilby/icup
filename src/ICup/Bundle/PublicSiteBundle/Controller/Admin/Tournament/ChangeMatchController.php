<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Services\Doctrine\TournamentSupport;
use ICup\Bundle\PublicSiteBundle\Services\Util;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use ICup\Bundle\PublicSiteBundle\Entity\ResultForm;
use Symfony\Component\HttpFoundation\Request;
use DateTime;

/**
 * List the categories and groups available
 */
class ChangeMatchController extends Controller
{
    /**
     * Change an existing match by number
     * @Route("/edit/match/no", name="_edit_match_by_no")
     * @Template("ICupPublicSiteBundle:Host:changematch.html.twig")
     */
    public function reportAction(Request $request) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $returnUrl = $utilService->getReferer();

        $resultForm = new ResultForm();
        $form = $this->makeResultForm($resultForm);
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($this->checkForm($form, $form->getData())) {
            /* @var $match Match */
            $match = $this->get('match')->getMatchByNo($resultForm->getTournament(), $resultForm->getMatchno());
            if ($match) {
                return $this->redirect($this->generateUrl("_edit_match_chg", array("matchid" => $match->getId())));
            }
            $form->addError(new FormError($this->get('translator')->trans('FORM.CHANGEMATCH.INVALIDMATCHNO', array(), 'admin')));
        }
        return array('form' => $form->createView());
    }
    
    private function makeResultForm(ResultForm $resultForm) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $tournamentKey = $this->get('util')->getTournamentKey();
        if ($tournamentKey != '_') {
            $tournament = $this->get('logic')->getTournamentByKey($tournamentKey);;
            if ($tournament != null) {
                $resultForm->setTournament($tournament->getId());
            }
        }

        if ($user->isEditor()) {
            $tournaments = $user->getHost()->getTournaments();
        }
        else {
            $tournaments = $this->get('logic')->listAvailableTournaments();
        }
        $tournamentList = array();
        foreach ($tournaments as $tmnt) {
            $tournamentList[$tmnt->getId()] = $tmnt->getName();
        }

        $formDef = $this->createFormBuilder($resultForm);
        $formDef->add('tournament', 'choice', array('label' => 'FORM.CHANGEMATCH.TOURNAMENT',
                                                    'choices' => $tournamentList,
                                                    'empty_value' => false,
                                                    'required' => false,
                                                    'icon' => 'fa fa-lg fa-university',
                                                    'translation_domain' => 'admin'));
        $formDef->add('matchno', 'text', array('label' => 'FORM.CHANGEMATCH.MATCHNO',
                                               'required' => false,
                                               'help' => 'FORM.CHANGEMATCH.HELP.MATCHNO',
                                               'icon' => 'fa fa-lg fa-calendar',
                                               'translation_domain' => 'admin'));
        $formDef->add('cancel', 'submit', array('label' => 'FORM.CHANGEMATCH.CANCEL',
                                                'translation_domain' => 'admin',
                                                'buttontype' => 'btn btn-default',
                                                'icon' => 'fa fa-times'));
        $formDef->add('save', 'submit', array('label' => 'FORM.CHANGEMATCH.SUBMIT',
                                                'translation_domain' => 'admin',
                                                'icon' => 'fa fa-check'));
        return $formDef->getForm();
    }
    
    private function checkForm($form, ResultForm $resultForm) {
        if (!$form->isValid()) {
            return false;
        }
        /*
         * Check for blank fields
         */
        if ($resultForm->getMatchno() == null || trim($resultForm->getMatchno()) == '') {
            $form->addError(new FormError($this->get('translator')->trans('FORM.CHANGEMATCH.NONO', array(), 'admin')));
            return false;
        }
        /*
         * Check for valid contents
         */
        /* @var $match Match */
        $match = $this->get('match')->getMatchByNo($resultForm->getTournament(), $resultForm->getMatchno());
        if ($match == null) {
            $form->addError(new FormError($this->get('translator')->trans('FORM.CHANGEMATCH.INVALIDMATCHNO', array(), 'admin')));
        }
        else if ($this->get('match')->isMatchResultValid($match->getId())) {
            $form->addError(new FormError($this->get('translator')->trans('FORM.CHANGEMATCH.CANTCHANGE', array(), 'admin')));
        }
        return $form->isValid();
    }
}

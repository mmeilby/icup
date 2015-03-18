<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match;
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
class ResultReportingController extends Controller
{
    /**
     * Report result of an existing match
     * @Route("/report", name="_report_result")
     * @Template("ICupPublicSiteBundle:Host:reportresult.html.twig")
     */
    public function reportAction(Request $request) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $returnUrl = $this->get('router')->generate('_icup');

        $resultForm = new ResultForm();
        $tournamentKey = $utilService->getTournamentKey();
        if ($tournamentKey != '_') {
            $tournament = $this->get('logic')->getTournamentByKey($tournamentKey);;
            if ($tournament != null) {
                $resultForm->setTournament($tournament->getId());
            }
        }
        $resultForm->setEvent(ResultForm::$EVENT_MATCH_PLAYED);
        $form = $this->makeResultForm($resultForm);
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($this->checkForm($form, $resultForm)) {
            $this->chgMatch($resultForm);
            return $this->redirect($returnUrl);
        }
        return array('form' => $form->createView());
    }
    
    private function chgMatch(ResultForm $resultForm) {
        $em = $this->getDoctrine()->getManager();
        $match = $this->get('match')->getMatchByNo($resultForm->getTournament(), $resultForm->getMatchno());
        $homeRel = $this->get('match')->getMatchRelationByMatch($match->getId(), false);
        $awayRel = $this->get('match')->getMatchRelationByMatch($match->getId(), true);
        switch ($resultForm->getEvent()) {
            case ResultForm::$EVENT_MATCH_PLAYED:
                $homeRel->setScorevalid(true);
                $homeRel->setScore($resultForm->getScoreA());
                $awayRel->setScorevalid(true);
                $awayRel->setScore($resultForm->getScoreB());
                $this->get('match')->updatePoints($homeRel, $awayRel);
                break;
            case ResultForm::$EVENT_HOME_DISQ:
                $this->get('match')->disqualify($awayRel, $homeRel);
                break;
            case ResultForm::$EVENT_AWAY_DISQ:
                $this->get('match')->disqualify($homeRel, $awayRel);
                break;
            case ResultForm::$EVENT_NOT_PLAYED:
                $homeRel->setScorevalid(true);
                $homeRel->setPoints(0);
                $homeRel->setScore(0);
                $awayRel->setScorevalid(true);
                $awayRel->setPoints(0);
                $awayRel->setScore(0);
                break;
        }
        $em->flush();
    }

    private function makeResultForm(ResultForm $resultForm) {
        $tournaments = $this->get('logic')->listAvailableTournaments();
        $tournamentList = array();
        $today = new DateTime();
        foreach ($tournaments as $tournament) {
            $stat = $this->get('tmnt')->getTournamentStatus($tournament->getId(), $today);
            if ($stat == TournamentSupport::$TMNT_GOING) {
                $tournamentList[$tournament->getId()] = $tournament->getName();
            }
        }

        $eventList = array(
            ResultForm::$EVENT_MATCH_PLAYED => 'MP',
            ResultForm::$EVENT_HOME_DISQ => 'HD',
            ResultForm::$EVENT_AWAY_DISQ => 'AD',
            ResultForm::$EVENT_NOT_PLAYED => 'NP'
        );
        foreach ($eventList as $event => $id) {
            $eventList[$event] = 
                $this->get('translator')
                     ->trans('FORM.RESULTREPORT.EVENTTYPE.'.$id, array(), 'tournament');
        }
        
        $formDef = $this->createFormBuilder($resultForm);
        $formDef->add('tournament', 'choice', array('label' => 'FORM.RESULTREPORT.TOURNAMENT',
                                                    'choices' => $tournamentList,
                                                    'empty_value' => false,
                                                    'required' => false,
                                                    'icon' => 'fa fa-lg fa-university',
                                                    'translation_domain' => 'tournament'));
        $formDef->add('matchno', 'text', array('label' => 'FORM.RESULTREPORT.MATCHNO',
                                               'required' => false,
                                               'help' => 'FORM.RESULTREPORT.HELP.MATCHNO',
                                               'icon' => 'fa fa-lg fa-calendar',
                                               'translation_domain' => 'tournament'));
        $formDef->add('scoreA', 'text', array('label' => 'FORM.RESULTREPORT.HOME',
                                              'required' => false,
                                               'help' => 'FORM.RESULTREPORT.HELP.HOME',
                                              'icon' => 'fa fa-lg fa-home',
                                              'translation_domain' => 'tournament'));
        $formDef->add('scoreB', 'text', array('label' => 'FORM.RESULTREPORT.AWAY',
                                              'required' => false,
                                               'help' => 'FORM.RESULTREPORT.HELP.AWAY',
                                              'icon' => 'fa fa-lg fa-picture-o',
                                              'translation_domain' => 'tournament'));
        $formDef->add('event', 'choice', array('label' => 'FORM.RESULTREPORT.EVENT',
                                               'choices' => $eventList,
                                               'empty_value' => false,
                                               'required' => false,
                                               'icon' => 'fa fa-lg fa-bolt',
                                               'translation_domain' => 'tournament'));
        $formDef->add('cancel', 'submit', array('label' => 'FORM.RESULTREPORT.CANCEL',
                                                'translation_domain' => 'tournament',
                                                'buttontype' => 'btn btn-default',
                                                'icon' => 'fa fa-times'));
        $formDef->add('save', 'submit', array('label' => 'FORM.RESULTREPORT.SUBMIT',
                                                'translation_domain' => 'tournament',
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
            $form->addError(new FormError($this->get('translator')->trans('FORM.RESULTREPORT.NONO', array(), 'tournament')));
        }
        if ($resultForm->getEvent() == ResultForm::$EVENT_MATCH_PLAYED) {
            if ($resultForm->getScoreA() == null || trim($resultForm->getScoreA()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.RESULTREPORT.NOHOMESCORE', array(), 'tournament')));
            }
            if ($resultForm->getScoreB() == null || trim($resultForm->getScoreB()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.RESULTREPORT.NOAWAYSCORE', array(), 'tournament')));
            }
        }
        if (!$form->isValid()) {
            return false;
        }
        /*
         * Check for valid contents
         */
        if ($resultForm->getEvent() == ResultForm::$EVENT_MATCH_PLAYED) {
            if ($resultForm->getScoreA() > 100 || $resultForm->getScoreA() < 0) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.RESULTREPORT.INVALIDHOMESCORE', array(), 'tournament')));
            }
            if ($resultForm->getScoreB() > 100 || $resultForm->getScoreB() < 0) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.RESULTREPORT.INVALIDAWAYSCORE', array(), 'tournament')));
            }
        }
        /* @var $match Match */
        $match = $this->get('match')->getMatchByNo($resultForm->getTournament(), $resultForm->getMatchno());
        if ($match == null) {
            $form->addError(new FormError($this->get('translator')->trans('FORM.RESULTREPORT.INVALIDMATCHNO', array(), 'tournament')));
        }
        else if (!$this->get('match')->isMatchResultValid($match->getId())) {
            $form->addError(new FormError($this->get('translator')->trans('FORM.RESULTREPORT.MATCHNOTREADY', array(), 'tournament')));
        }
        else if ($this->isScoreValid($match)) {
            $form->addError(new FormError($this->get('translator')->trans('FORM.RESULTREPORT.CANTCHANGE', array(), 'tournament')));
        }
        else if ($resultForm->getEvent() == ResultForm::$EVENT_MATCH_PLAYED) {
            $today = new DateTime();
            $matchdate = DateTime::createFromFormat(
                                        $this->container->getParameter('db_date_format').
                                        '-'.
                                        $this->container->getParameter('db_time_format'),
                                        $match->getDate().'-'.$match->getTime());
            if ($matchdate > $today) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.RESULTREPORT.TOOEARLY', array(), 'tournament')));
            }
        }
        return $form->isValid();
    }
    
    private function isScoreValid($match) {
        $homeRel = $this->get('match')->getMatchRelationByMatch($match->getId(), false);
        $awayRel = $this->get('match')->getMatchRelationByMatch($match->getId(), true);
        return $homeRel != null && $awayRel != null && $homeRel->getScorevalid() && $awayRel->getScorevalid();
    }
}

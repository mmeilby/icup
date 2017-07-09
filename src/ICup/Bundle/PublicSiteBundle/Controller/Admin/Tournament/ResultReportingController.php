<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Services\Doctrine\TournamentSupport;
use ICup\Bundle\PublicSiteBundle\Services\Util;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Form;
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
     * @param Request $request
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function reportAction(Request $request) {
        $returnUrl = $this->get('router')->generate('_icup');

        $resultForm = new ResultForm();
        $form = $this->makeResultForm($request, $resultForm);
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($this->checkForm($form, $resultForm)) {
            /* @var $match Match */
            $match = $this->chgMatch($resultForm);
            $matchhomedetails = $this->get('match')->getMatchRelationDetails($match->getId(), false);
            $matchhomedetails['name'] = $this->get('logic')->getTeamName($matchhomedetails['team'], $matchhomedetails['division']);
            $matchawaydetails = $this->get('match')->getMatchRelationDetails($match->getId(), true);
            $matchawaydetails['name'] = $this->get('logic')->getTeamName($matchawaydetails['team'], $matchawaydetails['division']);
            $flashdata = array(
                'match' => $match,
                'schedule' => Date::getDateTime($match->getDate(), $match->getTime()),
                'playground' => $this->get('entity')->getPlaygroundById($match->getPlayground()),
                'home' => $matchhomedetails,
                'away' => $matchawaydetails
            );
            $request->getSession()->getFlashBag()->add('matchupdated', $flashdata);
            $form = $this->makeResultForm($request, new ResultForm());
        }
        return array('form' => $form->createView());
    }
    
    private function chgMatch(ResultForm $resultForm) {
        $em = $this->getDoctrine()->getManager();
        $tournament = $this->get('entity')->getTournamentById($resultForm->getTournament());
        /* @var $match Match */
        $match = $this->get('match')->getMatchByNo($resultForm->getTournament(), $resultForm->getMatchno());
        /* @var $homeRel MatchRelation */
        $homeRel = $this->get('match')->getMatchRelationByMatch($match->getId(), false);
        /* @var $awayRel MatchRelation */
        $awayRel = $this->get('match')->getMatchRelationByMatch($match->getId(), true);
        switch ($resultForm->getEvent()) {
            case ResultForm::$EVENT_MATCH_PLAYED:
                $homeRel->setScore($resultForm->getScoreA());
                $awayRel->setScore($resultForm->getScoreB());
                $this->get('match')->updatePoints($tournament, $homeRel, $awayRel);
                break;
            case ResultForm::$EVENT_HOME_DISQ:
                $this->get('match')->disqualify($tournament, $awayRel, $homeRel);
                break;
            case ResultForm::$EVENT_AWAY_DISQ:
                $this->get('match')->disqualify($tournament, $homeRel, $awayRel);
                break;
            case ResultForm::$EVENT_NOT_PLAYED:
                $homeRel->setPoints(0);
                $homeRel->setScore(0);
                $awayRel->setPoints(0);
                $awayRel->setScore(0);
                break;
        }
        $homeRel->setScorevalid(true);
        $awayRel->setScorevalid(true);
        $em->flush();
        return $match;
    }

    private function makeResultForm(Request $request, ResultForm $resultForm) {
        $tournamentKey = $this->get('util')->getTournamentKey();
        if ($tournamentKey != '_') {
            /* @var $tournament Tournament */
            $tournament = $this->get('logic')->getTournamentByKey($tournamentKey);
            if ($tournament != null) {
                $resultForm->setTournament($tournament->getId());
            }
        }
        $resultForm->setEvent(ResultForm::$EVENT_MATCH_PLAYED);

        $domain = $this->get('util')->parseHostDomain($request);
        $tournaments = $this->get('logic')->listAvailableTournaments();
        $tournamentList = array();
        $today = new DateTime();
        foreach ($tournaments as $tmnt) {
            /* @var $tmnt Tournament */
            $stat = $this->get('tmnt')->getTournamentStatus($tmnt->getId(), $today);
            if ($stat == TournamentSupport::$TMNT_GOING && $tmnt->getHost()->getAlias() == $domain) {
                $tournamentList[$tmnt->getId()] = $tmnt->getName();
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
    
    private function checkForm(Form $form, ResultForm $resultForm) {
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
        else if ($match->getMatchRelations()->count() != 2) {
            $form->addError(new FormError($this->get('translator')->trans('FORM.RESULTREPORT.MATCHNOTREADY', array(), 'tournament')));
        }
        else if ($this->get('match')->isMatchResultValid($match->getId())) {
            $form->addError(new FormError($this->get('translator')->trans('FORM.RESULTREPORT.CANTCHANGE', array(), 'tournament')));
        }
        else {
            $today = new DateTime();
            $matchdate = Date::getDateTime($match->getDate(), $match->getTime());
            if ($matchdate > $today) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.RESULTREPORT.TOOEARLY', array(), 'tournament')));
            }
        }
        return $form->isValid();
    }
}

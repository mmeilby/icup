<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use ICup\Bundle\PublicSiteBundle\Entity\Match as MatchForm;
use Symfony\Component\HttpFoundation\Request;
use DateTime;

/**
 * List the categories and groups available
 */
class MatchRelationController extends Controller
{
    /**
     * Change information of an existing match
     * @Route("/edit/matchrel/chg/{matchid}", name="_edit_matchrel_chg")
     * @Template("ICupPublicSiteBundle:Host:editmatchrelation.html.twig")
     */
    public function chgAction($matchid, Request $request) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $returnUrl = $utilService->getReferer();

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        /* @var $match Match */
        $match = $this->get('entity')->getMatchById($matchid);
        $group = $this->get('entity')->getGroupById($match->getPid());
        $category = $this->get('entity')->getCategoryById($group->getPid());
        $tournament = $this->get('entity')->getTournamentById($category->getPid());
        $utilService->validateEditorAdminUser($user, $tournament->getPid());

        $matchForm = $this->copyMatchForm($match);
        $form = $this->makeMatchForm($matchForm, 'chg');
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($this->checkForm($form, $matchForm)) {
            $this->chgMatch($matchForm, $match);
            return $this->redirect($returnUrl);
        }
        $playground = $this->get('entity')->getPlaygroundById($match->getPlayground());
        return array('form' => $form->createView(),
                     'category' => $category,
                     'match' => $match,
                     'playground' => $playground,
                     'action' => 'chg',
                     'schedule' => Date::getDateTime($match->getDate(), $match->getTime()));
    }
    
    /**
     * Remove match from the register - including all related match results
     * @Route("/edit/matchrel/del/{matchid}", name="_edit_matchrel_del")
     * @Template("ICupPublicSiteBundle:Host:editmatchrelation.html.twig")
     */
    public function delAction($matchid, Request $request) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $returnUrl = $utilService->getReferer();

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $match = $this->get('entity')->getMatchById($matchid);
        $group = $this->get('entity')->getGroupById($match->getPid());
        $category = $this->get('entity')->getCategoryById($group->getPid());
        $tournament = $this->get('entity')->getTournamentById($category->getPid());
        $utilService->validateEditorAdminUser($user, $tournament->getPid());

        $matchForm = $this->copyMatchForm($match);
        $form = $this->makeMatchForm($matchForm, 'del');
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($form->isValid()) {
            $this->delMatch($match);
            return $this->redirect($returnUrl);
        }
        $playground = $this->get('entity')->getPlaygroundById($match->getPlayground());
        return array('form' => $form->createView(),
                     'category' => $category,
                     'match' => $match,
                     'playground' => $playground,
                     'action' => 'del',
                     'schedule' => Date::getDateTime($match->getDate(), $match->getTime()));
    }

    /**
     * Update information of an existing match with qualifying relations
     * @Route("/edit/matchrel/upd/{matchid}", name="_edit_matchrel_upd", options={"expose"=true})
     * @Template("ICupPublicSiteBundle:Host:editmatchrelation.html.twig")
     */
    public function matchfix($matchid, Request $request) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $returnUrl = $utilService->getReferer();

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $match = $this->get('entity')->getMatchById($matchid);
        $group = $this->get('entity')->getGroupById($match->getPid());
        $category = $this->get('entity')->getCategoryById($group->getPid());
        $tournament = $this->get('entity')->getTournamentById($category->getPid());
        $utilService->validateEditorAdminUser($user, $tournament->getPid());
        
        $matchForm = $this->copyMatchForm($match);
        $form = $this->makeUpdMatchForm($matchForm, $match);
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($this->checkForm($form, $matchForm)) {
            $this->chgMatch($matchForm, $match);
            return $this->redirect($returnUrl);
        }
        $playground = $this->get('entity')->getPlaygroundById($match->getPlayground());
        return array('form' => $form->createView(),
                     'category' => $category,
                     'match' => $match,
                     'playground' => $playground,
                     'action' => 'chg',
                     'schedule' => Date::getDateTime($match->getDate(), $match->getTime()));
    }

    private function chgMatch(MatchForm $matchForm, Match &$match) {
        $em = $this->getDoctrine()->getManager();
        $homeRel = $this->get('match')->getMatchRelationByMatch($match->getId(), false);
        if ($homeRel == null) {
            $homeRel = new MatchRelation();
            $homeRel->setPid($matchForm->getId());
            $homeRel->setAwayteam(false);
            $homeRel->setScorevalid(false);
            $homeRel->setPoints(0);
            $homeRel->setScore(0);
            $em->persist($homeRel);
        }
        $homeRel->setCid($matchForm->getTeamA());
        $awayRel = $this->get('match')->getMatchRelationByMatch($match->getId(), true);
        if ($awayRel == null) {
            $awayRel = new MatchRelation();
            $awayRel->setPid($matchForm->getId());
            $awayRel->setAwayteam(true);
            $awayRel->setScorevalid(false);
            $awayRel->setPoints(0);
            $awayRel->setScore(0);
            $em->persist($awayRel);
        }
        $awayRel->setCid($matchForm->getTeamB());
        $em->flush();
    }

    private function delMatch(Match $match) {
        $em = $this->getDoctrine()->getManager();
        $homeRel = $this->get('match')->getMatchRelationByMatch($match->getId(), false);
        if ($homeRel != null) {
            $em->remove($homeRel);
        }
        $awayRel = $this->get('match')->getMatchRelationByMatch($match->getId(), true);
        if ($awayRel != null) {
            $em->remove($awayRel);
        }
        $em->flush();
    }
    
    private function copyMatchForm(Match $match) {
        $matchForm = new MatchForm();
        $matchForm->setId($match->getId());
        $matchForm->setPid($match->getPid());
        $matchForm->setMatchno($match->getMatchno());
        $matchdate = Date::getDateTime($match->getDate(), $match->getTime());
        $dateformat = $this->get('translator')->trans('FORMAT.DATE');
        $matchForm->setDate(date_format($matchdate, $dateformat));
        $timeformat = $this->get('translator')->trans('FORMAT.TIME');
        $matchForm->setTime(date_format($matchdate, $timeformat));
        $matchForm->setPlayground($match->getPlayground());
        $matchForm->setTeamA($this->get('match')->getMatchHomeTeam($match->getId()));
        $matchForm->setTeamB($this->get('match')->getMatchAwayTeam($match->getId()));
        return $matchForm;
    }

    private function makeMatchForm(MatchForm $matchForm, $action) {
        $teams = $this->get('logic')->listTeamsByGroup($matchForm->getPid());
        $teamnames = array();
        foreach ($teams as $team) {
            $teamnames[$team->id] = $team->name;
        }

        $show = $action != 'del';
        $extshow = $show && !$this->get('match')->isMatchResultValid($matchForm->getId());
        
        $formDef = $this->createFormBuilder($matchForm);
        $formDef->add('teamA', 'choice', array('label' => 'FORM.MATCH.HOME',
            'choices' => $teamnames, 'empty_value' => 'FORM.MATCH.DEFAULT',
            'required' => false, 'disabled' => !$extshow, 'translation_domain' => 'admin'));
        $formDef->add('teamB', 'choice', array('label' => 'FORM.MATCH.AWAY',
            'choices' => $teamnames, 'empty_value' => 'FORM.MATCH.DEFAULT',
            'required' => false, 'disabled' => !$extshow, 'translation_domain' => 'admin'));
        $formDef->add('cancel', 'submit', array('label' => 'FORM.MATCH.CANCEL.'.strtoupper($action),
                                                'translation_domain' => 'admin',
                                                'buttontype' => 'btn btn-default',
                                                'icon' => 'fa fa-times'));
        $formDef->add('save', 'submit', array('label' => 'FORM.MATCH.SUBMIT.'.strtoupper($action),
                                                'translation_domain' => 'admin',
                                                'icon' => 'fa fa-check'));
        return $formDef->getForm();
    }
    
    private function checkForm($form, MatchForm $matchForm) {
        if (!$form->isValid()) {
            return false;
        }
        if ($matchForm->getTeamA() == null) {
            $form->addError(new FormError($this->get('translator')->trans('FORM.MATCH.NOHOMETEAM', array(), 'admin')));
        }
        if ($matchForm->getTeamB() == null) {
            $form->addError(new FormError($this->get('translator')->trans('FORM.MATCH.NOAWAYTEAM', array(), 'admin')));
        }
        elseif ($matchForm->getTeamA() == $matchForm->getTeamB()) {
            $form->addError(new FormError($this->get('translator')->trans('FORM.MATCH.SAMETEAM', array(), 'admin')));
        }
        return $form->isValid();
    }
    
    private function makeUpdMatchForm(MatchForm $matchForm, Match $match) {
        $qmh = $this->get('match')->getQMatchRelationByMatch($match->getId(), false);
        $teamListA = $this->get('orderTeams')->sortGroup($qmh->getCid());
        $teamnamesA = array();
        foreach ($teamListA as $team) {
            $teamnamesA[$team->id] = $team->name;
        }
        $teamA = $teamListA[$qmh->getRank()-1];
        $matchForm->setTeamA($teamA->id);

        $qma = $this->get('match')->getQMatchRelationByMatch($match->getId(), true);
        $teamListB = $this->get('orderTeams')->sortGroup($qma->getCid());
        $teamnamesB = array();
        foreach ($teamListB as $team) {
            $teamnamesB[$team->id] = $team->name;
        }
        $teamB = $teamListB[$qma->getRank()-1];
        $matchForm->setTeamB($teamB->id);

        $show = true;
        $extshow = $show && !$this->get('match')->isMatchResultValid($matchForm->getId());
        
        $formDef = $this->createFormBuilder($matchForm);
        $formDef->add('teamA', 'choice', array('label' => 'FORM.MATCH.HOME',
            'choices' => $teamnamesA, 'empty_value' => 'FORM.MATCH.DEFAULT',
            'required' => false, 'disabled' => !$extshow, 'translation_domain' => 'admin'));
        $formDef->add('teamB', 'choice', array('label' => 'FORM.MATCH.AWAY',
            'choices' => $teamnamesB, 'empty_value' => 'FORM.MATCH.DEFAULT',
            'required' => false, 'disabled' => !$extshow, 'translation_domain' => 'admin'));
        $formDef->add('cancel', 'submit', array('label' => 'FORM.MATCH.CANCEL.CHG',
                                                'translation_domain' => 'admin',
                                                'buttontype' => 'btn btn-default',
                                                'icon' => 'fa fa-times'));
        $formDef->add('save', 'submit', array('label' => 'FORM.MATCH.SUBMIT.CHG',
                                                'translation_domain' => 'admin',
                                                'icon' => 'fa fa-check'));
        return $formDef->getForm();
    }
}

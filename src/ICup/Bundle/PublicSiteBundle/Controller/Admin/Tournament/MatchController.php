<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use ICup\Bundle\PublicSiteBundle\Entity\Match as MatchForm;

/**
 * List the categories and groups available
 */
class MatchController extends Controller
{
    /**
     * Add new match
     * @Route("/edit/match/add/{groupid}", name="_edit_match_add")
     * @Template("ICupPublicSiteBundle:Host:editmatch.html.twig")
     */
    public function addAction($groupid) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $returnUrl = $utilService->getReferer();

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $group = $this->get('entity')->getGroupById($groupid);
        $category = $this->get('entity')->getCategoryById($group->getPid());
        $tournament = $this->get('entity')->getTournamentById($category->getPid());
        $utilService->validateEditorAdminUser($user, $tournament->getPid());

        $matchForm = new MatchForm();
        $matchForm->setPid($group->getId());
        $form = $this->makeMatchForm($matchForm, $tournament->getId(), 'add');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($this->checkForm($form, $matchForm)) {
            $otherMatch = $this->get('logic')->getMatchByNo($tournament->getId(), $matchForm->getMatchno());
            if ($otherMatch != null) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.MATCH.NOEXISTS', array(), 'admin')));
            }
            else {
                $this->addMatch($matchForm);
                return $this->redirect($returnUrl);
            }
        }
        return array('form' => $form->createView(), 'action' => 'add', 'category' => $category);
    }
    
    /**
     * Change information of an existing match
     * @Route("/edit/match/chg/{matchid}", name="_edit_match_chg")
     * @Template("ICupPublicSiteBundle:Host:editmatch.html.twig")
     */
    public function chgAction($matchid) {
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
        $form = $this->makeMatchForm($matchForm, $tournament->getId(), 'chg');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($this->checkForm($form, $matchForm)) {
            $otherMatch = $this->get('logic')->getMatchByNo($tournament->getId(), $matchForm->getMatchno());
            if ($otherMatch != null && $otherMatch->getId() != $matchForm->getId()) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.MATCH.CANTCHANGENO', array(), 'admin')));
            }
            else {
                $this->chgMatch($matchForm, $match);
                return $this->redirect($returnUrl);
            }
        }
        return array('form' => $form->createView(), 'action' => 'chg', 'category' => $category);
    }
    
    /**
     * Remove match from the register - including all related match results
     * @Route("/edit/match/del/{matchid}", name="_edit_match_del")
     * @Template("ICupPublicSiteBundle:Host:editmatch.html.twig")
     */
    public function delAction($matchid) {
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
        $form = $this->makeMatchForm($matchForm, $tournament->getId(), 'del');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($form->isValid()) {
            $this->delMatch($match);
            return $this->redirect($returnUrl);
        }
        return array('form' => $form->createView(), 'action' => 'del', 'category' => $category);
    }

    private function addMatch(MatchForm $matchForm) {
        $match = new Match();
        $match->setPid($matchForm->getPid());
        $this->updateMatch($matchForm, $match);
        $em = $this->getDoctrine()->getManager();
        $em->persist($match);
        $em->flush();
        
        $homeRel = new MatchRelation();
        $homeRel->setPid($match->getId());
        $homeRel->setAwayteam(false);
        $homeRel->setScorevalid(false);
        $homeRel->setCid($matchForm->getTeamA());
        $homeRel->setPoints(0);
        $homeRel->setScore(0);
        $em->persist($homeRel);

        $awayRel = new MatchRelation();
        $awayRel->setPid($match->getId());
        $awayRel->setAwayteam(true);
        $awayRel->setScorevalid(false);
        $awayRel->setCid($matchForm->getTeamB());
        $awayRel->setPoints(0);
        $awayRel->setScore(0);
        $em->persist($awayRel);
        $em->flush();
    }
    
    private function chgMatch(MatchForm $matchForm, Match &$match) {
        $this->updateMatch($matchForm, $match);
        $homeRel = $this->get('tmnt')->getMatchRelationByMatch($match->getId(), false);
        $homeRel->setCid($matchForm->getTeamA());
        $awayRel = $this->get('tmnt')->getMatchRelationByMatch($match->getId(), true);
        $awayRel->setCid($matchForm->getTeamB());
        $em = $this->getDoctrine()->getManager();
        $em->flush();
    }

    private function delMatch(Match $match) {
        $em = $this->getDoctrine()->getManager();
        $homeRel = $this->get('tmnt')->getMatchRelationByMatch($match->getId(), false);
        if ($homeRel != null) {
            $em->remove($homeRel);
        }
        $awayRel = $this->get('tmnt')->getMatchRelationByMatch($match->getId(), true);
        if ($awayRel != null) {
            $em->remove($awayRel);
        }
        $em->remove($match);
        $em->flush();
    }
    
    private function updateMatch(MatchForm $matchForm, Match &$match) {
        $match->setMatchno($matchForm->getMatchno());
        $dateformat = $this->get('translator')->trans('FORMAT.DATE');
        $matchdate = date_create_from_format($dateformat, $matchForm->getDate());
        $match->setDate(date_format($matchdate, "d/m/Y"));
        $timeformat = $this->get('translator')->trans('FORMAT.TIME');
        $matchtime = date_create_from_format($timeformat, $matchForm->getTime());
        $match->setTime(date_format($matchtime, "G.i"));
        $match->setPlayground($matchForm->getPlayground());
    }
    
    private function copyMatchForm(Match $match) {
        $matchForm = new MatchForm();
        $matchForm->setId($match->getId());
        $matchForm->setPid($match->getPId());
        $matchForm->setMatchno($match->getMatchno());
        $dateformat = $this->get('translator')->trans('FORMAT.DATE');
        $matchdate = date_create_from_format("d/m/Y", $match->getDate());
        $matchForm->setDate(date_format($matchdate, $dateformat));
        $timeformat = $this->get('translator')->trans('FORMAT.TIME');
        $matchtime = date_create_from_format("G.i", $match->getTime());
        $matchForm->setTime(date_format($matchtime, $timeformat));
        $matchForm->setPlayground($match->getPlayground());
        $matchForm->setTeamA($this->get('tmnt')->getMatchHomeTeam($match->getId()));
        $matchForm->setTeamB($this->get('tmnt')->getMatchAwayTeam($match->getId()));
        return $matchForm;
    }

    private function makeMatchForm(MatchForm $matchForm, $tournamentid, $action) {
        $playgrounds = $this->get('logic')->listPlaygroundsByTournament($tournamentid);
        $playgroundnames = array();
        foreach ($playgrounds as $playground) {
            $playgroundnames[$playground->getId()] = $playground->getName();
        }

        $teams = $this->get('logic')->listTeamsByGroup($matchForm->getPid());
        $teamnames = array();
        foreach ($teams as $team) {
            $teamnames[$team->id] = $team->name;
        }

        $show = $action != 'del';
        $extshow = $show && !$this->get('tmnt')->isMatchResultValid($matchForm->getId());
        
        $formDef = $this->createFormBuilder($matchForm);
        $formDef->add('matchno', 'text', array('label' => 'FORM.MATCH.NO',
            'required' => false, 'disabled' => !$show, 'translation_domain' => 'admin'));
        $formDef->add('date', 'text', array('label' => 'FORM.MATCH.DATE',
            'required' => false, 'disabled' => !$show, 'translation_domain' => 'admin'));
        $formDef->add('time', 'text', array('label' => 'FORM.MATCH.TIME',
            'required' => false, 'disabled' => !$show, 'translation_domain' => 'admin'));
        $formDef->add('playground', 'choice', array('label' => 'FORM.MATCH.PLAYGROUND',
            'choices' => $playgroundnames, 'empty_value' => 'FORM.MATCH.DEFAULT',
            'required' => false, 'disabled' => !$show, 'translation_domain' => 'admin'));
        $formDef->add('teamA', 'choice', array('label' => 'FORM.MATCH.HOME',
            'choices' => $teamnames, 'empty_value' => 'FORM.MATCH.DEFAULT',
            'required' => false, 'disabled' => !$extshow, 'translation_domain' => 'admin'));
        $formDef->add('teamB', 'choice', array('label' => 'FORM.MATCH.AWAY',
            'choices' => $teamnames, 'empty_value' => 'FORM.MATCH.DEFAULT',
            'required' => false, 'disabled' => !$extshow, 'translation_domain' => 'admin'));
        $formDef->add('cancel', 'submit', array('label' => 'FORM.MATCH.CANCEL.'.strtoupper($action), 'translation_domain' => 'admin'));
        $formDef->add('save', 'submit', array('label' => 'FORM.MATCH.SUBMIT.'.strtoupper($action), 'translation_domain' => 'admin'));
        return $formDef->getForm();
    }
    
    private function checkForm($form, MatchForm $matchForm) {
        if (!$form->isValid()) {
            return false;
        }
        if ($matchForm->getMatchno() == null || trim($matchForm->getMatchno()) == '') {
            $form->addError(new FormError($this->get('translator')->trans('FORM.MATCH.NONO', array(), 'admin')));
        }
        if ($matchForm->getDate() == null || trim($matchForm->getDate()) == '') {
            $form->addError(new FormError($this->get('translator')->trans('FORM.MATCH.NODATE', array(), 'admin')));
        }
        else {
            date_create_from_format($this->get('translator')->trans('FORMAT.DATE'), $matchForm->getDate());
            $date_errors = date_get_last_errors();
            if ($date_errors['error_count'] > 0) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.MATCH.BADDATE', array(), 'admin')));
            }
        }
        if ($matchForm->getTime() == null || trim($matchForm->getTime()) == '') {
            $form->addError(new FormError($this->get('translator')->trans('FORM.MATCH.NOTIME', array(), 'admin')));
        }
        else {
            date_create_from_format($this->get('translator')->trans('FORMAT.TIME'), $matchForm->getTime());
            $date_errors = date_get_last_errors();
            if ($date_errors['error_count'] > 0) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.MATCH.BADTIME', array(), 'admin')));
            }
        }
        if ($matchForm->getPlayground() == null) {
            $form->addError(new FormError($this->get('translator')->trans('FORM.MATCH.NOPLAYGROUND', array(), 'admin')));
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
}

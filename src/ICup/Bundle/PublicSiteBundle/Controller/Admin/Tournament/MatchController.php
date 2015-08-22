<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\QMatchRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Services\Doctrine\MatchSupport;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use ICup\Bundle\PublicSiteBundle\Entity\Match as MatchForm;
use Symfony\Component\HttpFoundation\Request;

/**
 * List the categories and groups available
 */
class MatchController extends Controller
{
    /**
     * Add new match
     * @Route("/edit/match/add/{groupid}", name="_edit_match_add", options={"expose"=true})
     * @Template("ICupPublicSiteBundle:Host:editmatch.html.twig")
     */
    public function addAction($groupid, Request $request) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $returnUrl = $utilService->getReferer();

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $group = $this->get('entity')->getGroupById($groupid);
        /* @var $category Category */
        $category = $group->getCategory();
        /* @var $tournament Tournament */
        $tournament = $category->getTournament();
        $host = $tournament->getHost();
        $utilService->validateEditorAdminUser($user, $host->getId());

        $matchForm = new MatchForm();
        $matchForm->setGroup($group);
        $form = $this->makeMatchForm($matchForm, $tournament->getId(), 'add');
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($this->checkForm($form, $matchForm)) {
            $otherMatch = $this->get('match')->getMatchByNo($tournament->getId(), $matchForm->getMatchno());
            if ($otherMatch != null) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.MATCH.NOEXISTS', array(), 'admin')));
            }
            else {
                $this->addMatch($matchForm);
                return $this->redirect($returnUrl);
            }
        }
        return array(
            'form' => $form->createView(),
            'action' => 'add',
            'tournament' => $tournament,
            'category' => $category,
            'group' => $group);
    }
    
    /**
     * Change information of an existing match
     * @Route("/edit/match/chg/{matchid}", name="_edit_match_chg", options={"expose"=true})
     * @Template("ICupPublicSiteBundle:Host:editmatch.html.twig")
     */
    public function chgAction($matchid, Request $request) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $returnUrl = $utilService->getReferer();

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        /* @var $match Match */
        $match = $this->get('entity')->getMatchById($matchid);
        /* @var $group Group */
        $group = $match->getGroup();
        /* @var $category Category */
        $category = $group->getCategory();
        /* @var $tournament Tournament */
        $tournament = $category->getTournament();
        $host = $tournament->getHost();
        $utilService->validateEditorAdminUser($user, $host->getId());

        $matchForm = $this->copyMatchForm($match);
        $form = $this->makeMatchForm($matchForm, $tournament->getId(), 'chg');
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($this->checkForm($form, $matchForm)) {
            $otherMatch = $this->get('match')->getMatchByNo($tournament->getId(), $matchForm->getMatchno());
            if ($otherMatch != null && $otherMatch->getId() != $matchForm->getId()) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.MATCH.CANTCHANGENO', array(), 'admin')));
            }
            else {
                $this->chgMatch($matchForm, $match);
                return $this->redirect($returnUrl);
            }
        }
        return array(
            'form' => $form->createView(),
            'action' => 'chg',
            'tournament' => $tournament,
            'category' => $category,
            'group' => $group,
            'match' => $match,
            'teamA' => $this->getDetails($match, MatchSupport::$HOME),
            'teamB' => $this->getDetails($match, MatchSupport::$AWAY));
    }
    
    /**
     * Remove match from the register - including all related match results
     * @Route("/edit/match/del/{matchid}", name="_edit_match_del", options={"expose"=true})
     * @Template("ICupPublicSiteBundle:Host:editmatch.html.twig")
     */
    public function delAction($matchid, Request $request) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $returnUrl = $utilService->getReferer();

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        /* @var $match Match */
        $match = $this->get('entity')->getMatchById($matchid);
        /* @var $group Group */
        $group = $match->getGroup();
        /* @var $category Category */
        $category = $group->getCategory();
        /* @var $tournament Tournament */
        $tournament = $category->getTournament();
        $host = $tournament->getHost();
        $utilService->validateEditorAdminUser($user, $host->getId());

        $matchForm = $this->copyMatchForm($match);
        $form = $this->makeMatchForm($matchForm, $tournament->getId(), 'del');
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($form->isValid()) {
            $this->delMatch($match);
            return $this->redirect($returnUrl);
        }
        return array(
            'form' => $form->createView(),
            'action' => 'del',
            'tournament' => $tournament,
            'category' => $category,
            'group' => $group,
            'match' => $match,
            'teamA' => $this->getDetails($match, MatchSupport::$HOME),
            'teamB' => $this->getDetails($match, MatchSupport::$AWAY));
    }

    private function addMatch(MatchForm $matchForm) {
        $match = new Match();
        $match->setGroup($matchForm->getGroup());
        $this->updateMatch($matchForm, $match);
        $em = $this->getDoctrine()->getManager();
        $em->persist($match);
        $em->flush();
    }
    
    private function chgMatch(MatchForm $matchForm, Match &$match) {
        $this->updateMatch($matchForm, $match);
        $em = $this->getDoctrine()->getManager();
        $em->flush();
    }

    private function delMatch(Match $match) {
        $em = $this->getDoctrine()->getManager();
        $em->remove($match);
        $em->flush();
    }
    
    private function updateMatch(MatchForm $matchForm, Match &$match) {
        $match->setMatchno($matchForm->getMatchno());
        $dateformat = $this->get('translator')->trans('FORMAT.DATE');
        $matchdate = date_create_from_format($dateformat, $matchForm->getDate());
        $match->setDate(Date::getDate($matchdate));
        $timeformat = $this->get('translator')->trans('FORMAT.TIME');
        $matchtime = date_create_from_format($timeformat, $matchForm->getTime());
        $match->setTime(Date::getTime($matchtime));
        $match->setPlayground($matchForm->getPlayground());
    }
    
    private function copyMatchForm(Match $match) {
        $matchForm = new MatchForm();
        $matchForm->setId($match->getId());
        $matchForm->setGroup($match->getGroup());
        $matchForm->setMatchno($match->getMatchno());
        $matchdate = Date::getDateTime($match->getDate(), $match->getTime());
        $dateformat = $this->get('translator')->trans('FORMAT.DATE');
        $matchForm->setDate(date_format($matchdate, $dateformat));
        $timeformat = $this->get('translator')->trans('FORMAT.TIME');
        $matchForm->setTime(date_format($matchdate, $timeformat));
        $matchForm->setPlayground($match->getPlayground());
        return $matchForm;
    }

    private function makeMatchForm(MatchForm $matchForm, $tournamentid, $action) {
        $playgrounds = $this->get('logic')->listPlaygroundsByTournament($tournamentid);
        $playgroundnames = array();
        foreach ($playgrounds as $playground) {
            $playgroundnames[$playground->getId()] = $playground->getName();
        }

        $show = $action != 'del';
        
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
        if ($matchForm->getMatchno() == null || trim($matchForm->getMatchno()) == '') {
            $form->addError(new FormError($this->get('translator')->trans('FORM.MATCH.NONO', array(), 'admin')));
        }
        if ($matchForm->getDate() == null || trim($matchForm->getDate()) == '') {
            $form->addError(new FormError($this->get('translator')->trans('FORM.MATCH.NODATE', array(), 'admin')));
        }
        else {
            $date = date_create_from_format($this->get('translator')->trans('FORMAT.DATE'), $matchForm->getDate());
            if ($date === false) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.MATCH.BADDATE', array(), 'admin')));
            }
        }
        if ($matchForm->getTime() == null || trim($matchForm->getTime()) == '') {
            $form->addError(new FormError($this->get('translator')->trans('FORM.MATCH.NOTIME', array(), 'admin')));
        }
        else {
            $time = date_create_from_format($this->get('translator')->trans('FORMAT.TIME'), $matchForm->getTime());
            if ($time === false) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.MATCH.BADTIME', array(), 'admin')));
            }
        }
        if ($matchForm->getPlayground() == null) {
            $form->addError(new FormError($this->get('translator')->trans('FORM.MATCH.NOPLAYGROUND', array(), 'admin')));
        }
        return $form->isValid();
    }

    private function getDetails(Match $match, $away) {
        $detail = array();
        $details = $this->get('match')->getMatchRelationDetails($match->getId(), $away);
        if ($details) {
            $detail['id'] = $details['id'];
            $detail['name'] = $this->get('logic')->getTeamName($details['team'], $details['division']);
            $detail['country'] = $details['country'];
        }
        /* @var $qrel QMatchRelation */
        $qrel = $this->get('match')->getQMatchRelationByMatch($match->getId(), $away);
        if ($qrel) {
            $group = $this->get('entity')->getGroupById($qrel->getCid());
            if ($group->getClassification() > 0) {
                $groupname = $this->get('translator')->trans('GROUPCLASS.'.$group->getClassification(), array(), 'tournament');
            }
            else {
                $groupname = $this->get('translator')->trans('GROUP', array(), 'tournament');
            }
            $rankTxt = $this->get('translator')->transChoice('RANK', $qrel->getRank(),
                    array('%rank%' => $qrel->getRank(),
                          '%group%' => strtolower($groupname).' '.$group->getName()), 'tournament');
            if (!array_key_exists('id', $detail)) {
                $detail['id'] = -1;
            }
            $detail['rank'] = $rankTxt;
            $detail['rgrp'] = $qrel->getCid();
        }
        return count($detail) > 0 ? $detail : null;
    }
}

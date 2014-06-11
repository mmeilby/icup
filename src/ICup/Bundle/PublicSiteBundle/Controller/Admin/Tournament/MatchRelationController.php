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
class MatchRelationController extends Controller
{
    /**
     * Change information of an existing match
     * @Route("/edit/matchrel/chg/{matchid}", name="_edit_matchrel_chg")
     * @Template("ICupPublicSiteBundle:Host:editmatchrelation.html.twig")
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
        $form = $this->makeMatchForm($matchForm, 'chg');
        $request = $this->getRequest();
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
                     'action' => 'chg');
    }
    
    /**
     * Remove match from the register - including all related match results
     * @Route("/edit/matchrel/del/{matchid}", name="_edit_matchrel_del")
     * @Template("ICupPublicSiteBundle:Host:editmatchrelation.html.twig")
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
        $form = $this->makeMatchForm($matchForm, 'del');
        $request = $this->getRequest();
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
                     'action' => 'del');
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
        $matchForm->setPid($match->getPId());
        $matchForm->setMatchno($match->getMatchno());
        $dateformat = $this->get('translator')->trans('FORMAT.DATE');
        $matchdate = date_create_from_format($this->container->getParameter('db_date_format'), $match->getDate());
        $matchForm->setDate(date_format($matchdate, $dateformat));
        $timeformat = $this->get('translator')->trans('FORMAT.TIME');
        $matchtime = date_create_from_format($this->container->getParameter('db_time_format'), $match->getTime());
        $matchForm->setTime(date_format($matchtime, $timeformat));
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
        $formDef->add('cancel', 'submit', array('label' => 'FORM.MATCH.CANCEL.'.strtoupper($action), 'translation_domain' => 'admin'));
        $formDef->add('save', 'submit', array('label' => 'FORM.MATCH.SUBMIT.'.strtoupper($action), 'translation_domain' => 'admin'));
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
}

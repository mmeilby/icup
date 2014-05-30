<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\QMatchRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use ICup\Bundle\PublicSiteBundle\Entity\QMatch as MatchForm;

/**
 * List the categories and groups available
 */
class QMatchRelationController extends Controller
{
    /**
     * Change information of an existing match
     * @Route("/edit/qmatchrel/chg/{matchid}", name="_edit_qmatchrel_chg")
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
        $form = $this->makeMatchForm($matchForm, $category->getId(), 'chg');
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
     * @Route("/edit/qmatchrel/del/{matchid}", name="_edit_qmatchrel_del")
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
        $form = $this->makeMatchForm($matchForm, $category->getId(), 'del');
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

    private function chgMatch(MatchForm $matchForm, Match $match) {
        $em = $this->getDoctrine()->getManager();
        $homeRel = $this->get('match')->getQMatchRelationByMatch($match->getId(), false);
        if ($homeRel == null) {
            $homeRel = new QMatchRelation();
            $homeRel->setPid($matchForm->getId());
            $homeRel->setAwayteam(false);
            $em->persist($homeRel);
        }
        $homeRel->setCid($matchForm->getGroupA());
        $homeRel->setRank($matchForm->getRankA());
        $awayRel = $this->get('match')->getQMatchRelationByMatch($match->getId(), true);
        if ($awayRel == null) {
            $awayRel = new QMatchRelation();
            $awayRel->setPid($matchForm->getId());
            $awayRel->setAwayteam(true);
            $awayRel->setCid($matchForm->getGroupB());
            $awayRel->setRank($matchForm->getRankB());
            $em->persist($awayRel);
        }
        $awayRel->setCid($matchForm->getGroupB());
        $awayRel->setRank($matchForm->getRankB());
        $em->flush();
    }

    private function delMatch(Match $match) {
        $em = $this->getDoctrine()->getManager();
        $qhomeRel = $this->get('match')->getQMatchRelationByMatch($match->getId(), false);
        if ($qhomeRel != null) {
            $em->remove($qhomeRel);
        }
        $qawayRel = $this->get('match')->getQMatchRelationByMatch($match->getId(), true);
        if ($qawayRel != null) {
            $em->remove($qawayRel);
        }
        $em->flush();
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
        $homeRel = $this->get('match')->getQMatchRelationByMatch($match->getId(), false);
        if ($homeRel != null) {
            $matchForm->setGroupA($homeRel->getCid());
            $matchForm->setRankA($homeRel->getRank());
        }
        $awayRel = $this->get('match')->getQMatchRelationByMatch($match->getId(), true);
        if ($awayRel != null) {
            $matchForm->setGroupB($awayRel->getCid());
            $matchForm->setRankB($awayRel->getRank());
        }
        return $matchForm;
    }

    private function makeMatchForm(MatchForm $matchForm, $categoryid, $action) {
        $groups = $this->get('logic')->listGroupsByCategory($categoryid);
        $groupnames = array();
        foreach ($groups as $group) {
            $groupnames[$group->getId()] = $group->getName();
        }

        $show = $action != 'del';
        
        $formDef = $this->createFormBuilder($matchForm);
        $formDef->add('groupA', 'choice', array('label' => 'FORM.MATCH.QHOME.GROUP',
            'choices' => $groupnames, 'empty_value' => 'FORM.MATCH.DEFAULT',
            'required' => false, 'disabled' => !$show, 'translation_domain' => 'admin'));
        $formDef->add('rankA', 'text', array('label' => 'FORM.MATCH.QHOME.RANK',
            'required' => false, 'disabled' => !$show, 'translation_domain' => 'admin'));
        $formDef->add('groupB', 'choice', array('label' => 'FORM.MATCH.QAWAY.GROUP',
            'choices' => $groupnames, 'empty_value' => 'FORM.MATCH.DEFAULT',
            'required' => false, 'disabled' => !$show, 'translation_domain' => 'admin'));
        $formDef->add('rankB', 'text', array('label' => 'FORM.MATCH.QAWAY.RANK',
            'required' => false, 'disabled' => !$show, 'translation_domain' => 'admin'));

        $formDef->add('cancel', 'submit', array('label' => 'FORM.MATCH.CANCEL.'.strtoupper($action), 'translation_domain' => 'admin'));
        $formDef->add('save', 'submit', array('label' => 'FORM.MATCH.SUBMIT.'.strtoupper($action), 'translation_domain' => 'admin'));
        return $formDef->getForm();
    }
    
    private function checkForm($form, MatchForm $matchForm) {
        if (!$form->isValid()) {
            return false;
        }
        if ($matchForm->getGroupA() == null) {
            $form->addError(new FormError($this->get('translator')->trans('FORM.MATCH.NOHOMEGROUP', array(), 'admin')));
        }
        if ($matchForm->getGroupB() == null) {
            $form->addError(new FormError($this->get('translator')->trans('FORM.MATCH.NOAWAYGROUP', array(), 'admin')));
        }
        if ($matchForm->getRankA() == null || trim($matchForm->getRankA()) == '') {
            $form->addError(new FormError($this->get('translator')->trans('FORM.MATCH.NOHOMERANK', array(), 'admin')));
        }
        if ($matchForm->getRankB() == null || trim($matchForm->getRankB()) == '') {
            $form->addError(new FormError($this->get('translator')->trans('FORM.MATCH.NOAWAYRANK', array(), 'admin')));
        }
        elseif ($matchForm->getGroupA() == $matchForm->getGroupB() &&
                $matchForm->getRankA() == $matchForm->getRankB()) {
            $form->addError(new FormError($this->get('translator')->trans('FORM.MATCH.SAMEQ', array(), 'admin')));
        }
        return $form->isValid();
    }
}

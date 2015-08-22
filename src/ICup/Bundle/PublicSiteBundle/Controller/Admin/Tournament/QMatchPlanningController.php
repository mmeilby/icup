<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\QMatchRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use ICup\Bundle\PublicSiteBundle\Entity\QMatch as MatchForm;
use Symfony\Component\HttpFoundation\Request;
use DateTime;

/**
 * List the categories and groups available
 */
class QMatchPlanningController extends Controller
{
    /**
     * Change information of an existing match
     * @Route("/edit/qmatchplan/{categoryid}", name="_edit_qmatchplan")
     * @Template("ICupPublicSiteBundle:Edit:planfinals.html.twig")
     */
    public function chgAction($categoryid, Request $request) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $returnUrl = $utilService->getReferer();

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        /* @var $category Category */
        $category = $this->get('entity')->getCategoryById($categoryid);
        /* @var $tournament Tournament */
        $tournament = $category->getTournament();
        $host = $tournament->getHost();
        $utilService->validateEditorAdminUser($user, $host->getId());

        $groupList = array();
        $groups = $this->get('logic')->listGroups($category->getId());
        /* @var $group Group */
        foreach ($groups as $group) {
            $teams = count($this->get('logic')->listTeamsByGroup($group->getId()));
            if ($teams > 0) {
                $groupList[] = array('group' => $group, 'count' => $teams);
            }
        }
        $matchForm = array('strategy' => 'option1');
//        $matchForm = $this->copyMatchForm($match);
        $formDef = $this->createFormBuilder();
//        $form = $formDef->getForm();
        $form = $this->makeMatchForm($matchForm, $category->getId(), count($groupList));
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
//        if ($this->checkForm($form, $matchForm)) {
//            $this->chgMatch($matchForm, $match);
//            return $this->redirect($returnUrl);
//        }
//        $playground = $this->get('entity')->getPlaygroundById($match->getPlayground());
        return array(
            'form' => $form->createView(),
            'host' => $host,
            'tournament' => $tournament,
            'category' => $category,
            'groupList' => $groupList);
    }
    
    /**
     * Remove match from the register - including all related match results
     * @Route("/edit/qmatchrel/del/{matchid}", name="_edit_qmatchrel_del")
     * @Template("ICupPublicSiteBundle:Host:editmatchrelation.html.twig")
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
        $form = $this->makeMatchForm($matchForm, $category->getId(), 'del');
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
                     'schedule' => $match->getSchedule()
        );
    }

    private function chgMatch(MatchForm $matchForm, Match $match) {
        $em = $this->getDoctrine()->getManager();
        $homeRel = $this->get('match')->getQMatchRelationByMatch($match->getId(), false);
        if ($homeRel == null) {
            $homeRel = new QMatchRelation();
            $homeRel->setAwayteam(false);
            $match->addMatchRelation($homeRel);
            $em->persist($homeRel);
        }
        $homeRel->setCid($matchForm->getGroupA());
        $homeRel->setRank($matchForm->getRankA());
        $awayRel = $this->get('match')->getQMatchRelationByMatch($match->getId(), true);
        if ($awayRel == null) {
            $awayRel = new QMatchRelation();
            $awayRel->setAwayteam(true);
            $match->addMatchRelation($awayRel);
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
        $matchForm->setPid($match->getGroup()->getId());
        $matchForm->setMatchno($match->getMatchno());
        $matchdate = Date::getDateTime($match->getDate(), $match->getTime());
        $dateformat = $this->get('translator')->trans('FORMAT.DATE');
        $matchForm->setDate(date_format($matchdate, $dateformat));
        $timeformat = $this->get('translator')->trans('FORMAT.TIME');
        $matchForm->setTime(date_format($matchdate, $timeformat));
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

    private function makeMatchForm($matchForm, $categoryid, $noofgroups) {
        $groups = $this->get('logic')->listGroupsByCategory($categoryid);
        $groupnames = array();
        foreach ($groups as $group) {
            $groupnames[$group->getId()] = $group->getName();
        }

        $show = true;
        
        $formDef = $this->createFormBuilder($matchForm);
        switch ($noofgroups) {
            case 1: {
                $formDef->add('strategy', 'choice', array(
                    'label' => 'FORM.QMATCHPLANNING.GROUP.ONE',
                    'placeholder' => false,
                    'choices' => array('option1' => 'FORM.QMATCHPLANNING.RADIO.GROUP1.OPTION1', 'option2' => 'FORM.QMATCHPLANNING.RADIO.GROUP1.OPTION2'),
                    'required' => false,
                    'disabled' => !$show,
                    'translation_domain' => 'admin',
                    'expanded' => true,
                    'multiple' => false));
                break;
            }
            case 2: {
                $formDef->add('strategy', 'choice', array(
                    'label' => 'FORM.QMATCHPLANNING.GROUP.TWO',
                    'placeholder' => false,
                    'choices' => array('option1' => 'FORM.QMATCHPLANNING.RADIO.GROUP2.OPTION1', 'option2' => 'FORM.QMATCHPLANNING.RADIO.GROUP2.OPTION2', 'option3' => 'FORM.QMATCHPLANNING.RADIO.GROUP2.OPTION3'),
                    'required' => false,
                    'disabled' => !$show,
                    'translation_domain' => 'admin',
                    'expanded' => true,
                    'multiple' => false));
                break;
            }
            case 3: {
                $formDef->add('strategy', 'choice', array(
                    'label' => 'FORM.QMATCHPLANNING.GROUP.THREE',
                    'placeholder' => false,
                    'choices' => array('option1' => 'FORM.QMATCHPLANNING.RADIO.GROUP3.OPTION1', 'option2' => 'FORM.QMATCHPLANNING.RADIO.GROUP3.OPTION2'),
                    'required' => false,
                    'disabled' => !$show,
                    'translation_domain' => 'admin',
                    'expanded' => true,
                    'multiple' => false));
                break;
            }
            case 4: {
                $formDef->add('strategy', 'choice', array(
                    'label' => 'FORM.QMATCHPLANNING.GROUP.FOUR',
                    'placeholder' => false,
                    'choices' => array('option1' => 'FORM.QMATCHPLANNING.RADIO.GROUP4.OPTION1', 'option2' => 'FORM.QMATCHPLANNING.RADIO.GROUP4.OPTION2'),
                    'required' => false,
                    'disabled' => !$show,
                    'translation_domain' => 'admin',
                    'expanded' => true,
                    'multiple' => false));
                break;
            }
        }

        $formDef->add('cancel', 'submit', array('label' => 'FORM.MATCH.CANCEL.CHG',
                                                'translation_domain' => 'admin',
                                                'buttontype' => 'btn btn-default',
                                                'icon' => 'fa fa-times'));
        $formDef->add('save', 'submit', array('label' => 'FORM.MATCH.SUBMIT.CHG',
                                                'translation_domain' => 'admin',
                                                'icon' => 'fa fa-check'));
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

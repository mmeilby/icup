<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ICup\Bundle\PublicSiteBundle\Services\Util;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;

/**
 * List the tournaments available
 */
class TournamentController extends Controller
{
    /**
     * Add new tournament to a host
     * @Route("/edit/tournament/add/{hostid}", name="_edit_tournament_add")
     * @Template("ICupPublicSiteBundle:Edit:edittournament.html.twig")
     */
    public function addTournamentAction($hostid, Request $request) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        
        $returnUrl = $utilService->getReferer();

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $host = $this->get('entity')->getHostById($hostid);
        $utilService->validateEditorAdminUser($user, $hostid);

        $tournament = new Tournament();
        $tournament->setPid($host->getId());
        $form = $this->makeTournamentForm($tournament, 'add');
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($this->checkForm($form, $tournament)) {
            if ($this->get('logic')->getTournamentByKey($tournament->getKey())) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.TOURNAMENT.KEYEXIST', array(), 'admin')));
            }
            else {
                $em = $this->getDoctrine()->getManager();
                $em->persist($tournament);
                $em->flush();
                return $this->redirect($returnUrl);
            }
        }
        return array('form' => $form->createView(), 'action' => 'add', 'tournament' => $tournament);
    }
    
    /**
     * Change information of an existing tournament
     * @Route("/edit/tournament/chg/{tournamentid}", name="_edit_tournament_chg")
     * @Template("ICupPublicSiteBundle:Edit:edittournament.html.twig")
     */
    public function chgTournamentAction($tournamentid, Request $request) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        
        $returnUrl = $utilService->getReferer();

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $tournament = $this->get('entity')->getTournamentById($tournamentid);
        $host = $this->get('entity')->getHostById($tournament->getPid());
        $utilService->validateEditorAdminUser($user, $host->getId());

        $form = $this->makeTournamentForm($tournament, 'chg');
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($this->checkForm($form, $tournament)) {
            $otherTmnt = $this->get('logic')->getTournamentByKey($tournament->getKey());
            if ($otherTmnt != null && $otherTmnt->getId() != $tournament->getId()) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.TOURNAMENT.CANTCHANGEKEY', array(), 'admin')));
            }
            else {
                $em = $this->getDoctrine()->getManager();
                $em->flush();
                return $this->redirect($returnUrl);
            }
        }
        return array('form' => $form->createView(), 'action' => 'chg', 'tournament' => $tournament);
    }
    
    /**
     * Remove tournament from the register - all match results and tournament information is lost
     * @Route("/edit/tournament/del/{tournamentid}", name="_edit_tournament_del")
     * @Template("ICupPublicSiteBundle:Edit:edittournament.html.twig")
     */
    public function delTournamentAction($tournamentid, Request $request) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        
        $returnUrl = $utilService->getReferer();

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $tournament = $this->get('entity')->getTournamentById($tournamentid);
        $host = $this->get('entity')->getHostById($tournament->getPid());
        $utilService->validateEditorAdminUser($user, $host->getId());

        $form = $this->makeTournamentForm($tournament, 'del');
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($form->isValid()) {
            if ($this->get('logic')->listSites($tournament->getId()) != null) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.TOURNAMENT.SITESEXIST', array(), 'admin')));
            }
            if ($this->get('logic')->listCategories($tournament->getId()) != null) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.TOURNAMENT.CATEGORIESEXIST', array(), 'admin')));
            }
            if ($this->get('logic')->listTimeslots($tournament->getId()) != null) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.TOURNAMENT.TIMESLOTSEXIST', array(), 'admin')));
            }
            if (count($this->get('tmnt')->listEventsByTournament($tournament->getId())) > 0) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.TOURNAMENT.EVENTSEXIST', array(), 'admin')));
            }
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $em->remove($tournament);
                $em->flush();
                return $this->redirect($returnUrl);
            }
        }
        return array('form' => $form->createView(), 'action' => 'del', 'tournament' => $tournament);
    }
    
    private function makeTournamentForm($tournament, $action) {
        $formDef = $this->createFormBuilder($tournament);
        $formDef->add('name', 'text', array('label' => 'FORM.TOURNAMENT.NAME', 'required' => false, 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('key', 'text', array('label' => 'FORM.TOURNAMENT.KEY', 'required' => false, 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('edition', 'text', array('label' => 'FORM.TOURNAMENT.EDITION', 'required' => false, 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('description', 'text', array('label' => 'FORM.TOURNAMENT.DESC', 'required' => false, 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('cancel', 'submit', array('label' => 'FORM.TOURNAMENT.CANCEL.'.strtoupper($action),
                                                'translation_domain' => 'admin',
                                                'buttontype' => 'btn btn-default',
                                                'icon' => 'fa fa-times'));
        $formDef->add('save', 'submit', array('label' => 'FORM.TOURNAMENT.SUBMIT.'.strtoupper($action),
                                                'translation_domain' => 'admin',
                                                'icon' => 'fa fa-check'));
        return $formDef->getForm();
    }
    
    private function checkForm($form, Tournament $tournament) {
        if ($form->isValid()) {
            if ($tournament->getName() == null || trim($tournament->getName()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.TOURNAMENT.NONAME', array(), 'admin')));
                return false;
            }
            if ($tournament->getKey() == null || trim($tournament->getKey()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.TOURNAMENT.NOKEY', array(), 'admin')));
                return false;
            }
            if ($tournament->getEdition() == null || trim($tournament->getEdition()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.TOURNAMENT.NOEDITION', array(), 'admin')));
                return false;
            }
            return true;
        }
        return false;
    }
}

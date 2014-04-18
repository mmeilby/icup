<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ICup\Bundle\PublicSiteBundle\Services\Util;

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
    public function addTournamentAction($hostid) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $utilService->setupController();
        $em = $this->getDoctrine()->getManager();

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $host = $this->get('entity')->getHostById($hostid);
        $utilService->validateEditorAdminUser($user, $hostid);

        $tournament = new Tournament();
        $tournament->setPid($host->getId());
        $form = $this->makeTournamentForm($tournament, 'add');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->getReturnPath($user));
        }
        if ($form->isValid()) {
            $em->persist($tournament);
            $em->flush();
            return $this->redirect($this->getReturnPath($user));
        }
        return array('form' => $form->createView(), 'action' => 'add', 'tournament' => $tournament, 'error' => null);
    }
    
    /**
     * Change information of an existing tournament
     * @Route("/edit/tournament/chg/{tournamentid}", name="_edit_tournament_chg")
     * @Template("ICupPublicSiteBundle:Edit:edittournament.html.twig")
     */
    public function chgTournamentAction($tournamentid) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $utilService->setupController();
        $em = $this->getDoctrine()->getManager();

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $tournament = $this->get('entity')->getTournamentById($tournamentid);
        $host = $this->get('entity')->getHostById($tournament->getPid());
        $utilService->validateEditorAdminUser($user, $host->getId());

        $form = $this->makeTournamentForm($tournament, 'chg');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->getReturnPath($user));
        }
        if ($form->isValid()) {
            $em->persist($tournament);
            $em->flush();
            return $this->redirect($this->getReturnPath($user));
        }
        return array('form' => $form->createView(), 'action' => 'chg', 'tournament' => $tournament, 'error' => null);
    }
    
    /**
     * Remove tournament from the register - all match results and tournament information is lost
     * @Route("/edit/tournament/del/{tournamentid}", name="_edit_tournament_del")
     * @Template("ICupPublicSiteBundle:Edit:edittournament.html.twig")
     */
    public function delTournamentAction($tournamentid) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $utilService->setupController();
        $em = $this->getDoctrine()->getManager();

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $tournament = $this->get('entity')->getTournamentById($tournamentid);
        $host = $this->get('entity')->getHostById($tournament->getPid());
        $utilService->validateEditorAdminUser($user, $host->getId());

        $form = $this->makeTournamentForm($tournament, 'del');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->getReturnPath($user));
        }
        if ($form->isValid()) {
            $em->remove($tournament);
            $em->flush();
            return $this->redirect($this->getReturnPath($user));
        }
        return array('form' => $form->createView(), 'action' => 'del', 'tournament' => $tournament, 'error' => null);
    }
    
    private function makeTournamentForm($tournament, $action) {
        $formDef = $this->createFormBuilder($tournament);
        $formDef->add('name', 'text', array('label' => 'FORM.TOURNAMENT.NAME', 'required' => false, 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('key', 'text', array('label' => 'FORM.TOURNAMENT.KEY', 'required' => false, 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('edition', 'text', array('label' => 'FORM.TOURNAMENT.EDITION', 'required' => false, 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('description', 'text', array('label' => 'FORM.TOURNAMENT.DESC', 'required' => false, 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('cancel', 'submit', array('label' => 'FORM.TOURNAMENT.CANCEL.'.strtoupper($action), 'translation_domain' => 'admin'));
        $formDef->add('save', 'submit', array('label' => 'FORM.TOURNAMENT.SUBMIT.'.strtoupper($action), 'translation_domain' => 'admin'));
        return $formDef->getForm();
    }
    
    private function getReturnPath(User $user) {
        if ($this->get('util')->isAdminUser($user)) {
            return $this->generateUrl('_edit_host_list');
        }
        else {
            return $this->generateUrl('_host_list_tournaments');
        }
    }
}

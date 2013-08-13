<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Edit;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Host;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * List the tournaments available
 */
class EditHostController extends Controller
{
    /**
     * List the tournaments available
     * @Route("/edit/host/list", name="_edit_host_list")
     * @Method("GET")
     * @Template("ICupPublicSiteBundle:Edit:listhosts.html.twig")
     */
    public function listAction() {
        $this->get('util')->setupController($this);
        $em = $this->getDoctrine()->getManager();

        $hosts = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Host')
                            ->findAll();
        
        $tournaments = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')
                            ->findAll();

        $hostList = array();
        foreach ($tournaments as $tournament) {
            $hostList[$tournament->getPid()][] = $tournament;
        }
        
        return array('tournaments' => $hostList, 'hosts' => $hosts);
    }

    /**
     * Add new host
     * @Route("/edit/host/add", name="_edit_host_add")
     * @Template("ICupPublicSiteBundle:Edit:edithost.html.twig")
     */
    public function addAction() {
        $this->get('util')->setupController($this);
        
        $host = new Host();
        $form = $this->makeHostForm($host, 'add');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->generateUrl('_edit_host_list'));
        }
        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($host);
            $em->flush();
            return $this->redirect($this->generateUrl('_edit_host_list'));
        }
        return array('form' => $form->createView(), 'action' => 'add', 'host' => $host, 'error' => null);
    }
    
    /**
     * Change information of an existing host
     * @Route("/edit/host/chg/{hostid}", name="_edit_host_chg")
     * @Template("ICupPublicSiteBundle:Edit:edithost.html.twig")
     */
    public function chgAction($hostid) {
        $this->get('util')->setupController($this);
        $em = $this->getDoctrine()->getManager();
        
        $host = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Host')->find($hostid);
        if ($host == null) {
            $error = "FORM.ERROR.BADHOST";
        }
     
        $form = $this->makeHostForm($host, 'chg');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->generateUrl('_edit_host_list'));
        }
        if (!isset($error) && $form->isValid()) {
            $em->persist($host);
            $em->flush();
            return $this->redirect($this->generateUrl('_edit_host_list'));
        }
        return array('form' => $form->createView(), 'action' => 'chg', 'host' => $host, 'error' => isset($error) ? $error : null);
    }
    
    /**
     * Remove host from the register - including all related tournaments and match results
     * @Route("/edit/host/del/{hostid}", name="_edit_host_del")
     * @Template("ICupPublicSiteBundle:Edit:edithost.html.twig")
     */
    public function delAction($hostid) {
        $this->get('util')->setupController($this);
        $em = $this->getDoctrine()->getManager();
        
        $host = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Host')->find($hostid);
        if ($host == null) {
            $error = "FORM.ERROR.BADHOST";
        }
                
        $form = $this->makeHostForm($host, 'del');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->generateUrl('_edit_host_list'));
        }
        if (!isset($error) && $form->isValid()) {
            $em->remove($host);
            $em->flush();
            return $this->redirect($this->generateUrl('_edit_host_list'));
        }
        return array('form' => $form->createView(), 'action' => 'del', 'host' => $host, 'error' => isset($error) ? $error : null);
    }
    
    private function makeHostForm($host, $action) {
        $formDef = $this->createFormBuilder($host);
        $formDef->add('name', 'text', array('label' => 'FORM.HOST.NAME', 'required' => false, 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('cancel', 'submit', array('label' => 'FORM.HOST.CANCEL.'.strtoupper($action), 'translation_domain' => 'admin'));
        $formDef->add('save', 'submit', array('label' => 'FORM.HOST.SUBMIT.'.strtoupper($action), 'translation_domain' => 'admin'));
        return $formDef->getForm();
    }
    
    /**
     * Add new tournament to a host
     * @Route("/edit/tournament/add/{hostid}", name="_edit_tournament_add")
     * @Template("ICupPublicSiteBundle:Edit:edittournament.html.twig")
     */
    public function addTournamentAction($hostid) {
        $this->get('util')->setupController($this);
        $em = $this->getDoctrine()->getManager();
        
        $host = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Host')->find($hostid);
        if ($host == null) {
            $error = "FORM.ERROR.BADHOST";
        }
                
        $tournament = new Tournament();
        $tournament->setPid($host != null ? $host->getId() : 0);
        $form = $this->makeTournamentForm($tournament, 'add');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->generateUrl('_edit_host_list'));
        }
        if (!isset($error) && $form->isValid()) {
            $em->persist($tournament);
            $em->flush();
            return $this->redirect($this->generateUrl('_edit_host_list'));
        }
        return array('form' => $form->createView(), 'action' => 'add', 'tournament' => $tournament, 'error' => isset($error) ? $error : null);
    }
    
    /**
     * Change information of an existing tournament
     * @Route("/edit/tournament/chg/{tournamentid}", name="_edit_tournament_chg")
     * @Template("ICupPublicSiteBundle:Edit:edittournament.html.twig")
     */
    public function chgTournamentAction($tournamentid) {
        $this->get('util')->setupController($this);
        $em = $this->getDoctrine()->getManager();
        
        $tournament = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')->find($tournamentid);
        if ($tournament == null) {
            $error = "FORM.ERROR.BADTOURNAMENT";
        }
        
        $form = $this->makeTournamentForm($tournament, 'chg');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->generateUrl('_edit_host_list'));
        }
        if (!isset($error) && $form->isValid()) {
            $em->persist($tournament);
            $em->flush();
            return $this->redirect($this->generateUrl('_edit_host_list'));
        }
        return array('form' => $form->createView(), 'action' => 'chg', 'tournament' => $tournament, 'error' => isset($error) ? $error : null);
    }
    
    /**
     * Remove tournament from the register - all match results and tournament information is lost
     * @Route("/edit/tournament/del/{tournamentid}", name="_edit_tournament_del")
     * @Template("ICupPublicSiteBundle:Edit:edittournament.html.twig")
     */
    public function delTournamentAction($tournamentid) {
        $this->get('util')->setupController($this);
        $em = $this->getDoctrine()->getManager();
        
        $tournament = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')->find($tournamentid);
        if ($tournament == null) {
            $error = "FORM.ERROR.BADTOURNAMENT";
        }
        
        $form = $this->makeTournamentForm($tournament, 'del');
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->generateUrl('_edit_host_list'));
        }
        if (!isset($error) && $form->isValid()) {
            $em->remove($tournament);
            $em->flush();
            return $this->redirect($this->generateUrl('_edit_host_list'));
        }
        return array('form' => $form->createView(), 'action' => 'del', 'tournament' => $tournament, 'error' => isset($error) ? $error : null);
    }
    
    private function makeTournamentForm($tournament, $action) {
        $formDef = $this->createFormBuilder($tournament);
        $formDef->add('name', 'text', array('label' => 'FORM.TOURNAMENT.NAME', 'required' => false, 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('key', 'text', array('label' => 'FORM.TOURNAMENT.KEY', 'required' => false, 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('edition', 'text', array('label' => 'FORM.TOURNAMENT.EDITION', 'required' => false, 'disabled' => $action == 'del', 'translation_domain' => 'admin'));
        $formDef->add('cancel', 'submit', array('label' => 'FORM.TOURNAMENT.CANCEL.'.strtoupper($action), 'translation_domain' => 'admin'));
        $formDef->add('save', 'submit', array('label' => 'FORM.TOURNAMENT.SUBMIT.'.strtoupper($action), 'translation_domain' => 'admin'));
        return $formDef->getForm();
    }
}

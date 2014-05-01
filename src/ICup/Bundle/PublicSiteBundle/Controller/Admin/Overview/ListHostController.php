<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Overview;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ICup\Bundle\PublicSiteBundle\Services\Util;

/**
 * List the tournaments available
 */
class ListHostController extends Controller
{
    /**
     * List the hosts and tournaments available
     * - ADMIN ONLY function -
     * @Route("/edit/host/list", name="_edit_host_list")
     * @Method("GET")
     * @Template("ICupPublicSiteBundle:Edit:listhosts.html.twig")
     */
    public function listAction() {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        

        // If user is not admin redirect to editor view
        if (!$this->get('security.context')->isGranted('ROLE_ADMIN')) {
            return $this->redirect($this->generateUrl('_host_list_tournaments'));
        }

        $hosts = $this->get('entity')->getHostRepo()->findAll();
        $tournaments = $this->get('entity')->getTournamentRepo()->findAll();
        $hostList = array();
        foreach ($tournaments as $tournament) {
            $hostList[$tournament->getPid()][] = $tournament;
        }
        return array('tournaments' => $hostList, 'hosts' => $hosts);
    }
    
    /**
     * List the tournaments available for current editor
     * @Route("/edit/host/list/tournaments", name="_host_list_tournaments")
     * @Method("GET")
     * @Template("ICupPublicSiteBundle:Host:listtournaments.html.twig")
     */
    public function listActionAsEditor() {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        // Validate current user - is it an editor?
        $utilService->validateEditorUser($user);
        // Get the host from current user
        $hostid = $user->getPid();
        $host = $this->get('entity')->getHostById($hostid);
        // Find list of tournaments for this host
        $tournaments = $this->get('logic')->listTournaments($hostid);
        return array('tournaments' => $tournaments, 'host' => $host);
    }
}

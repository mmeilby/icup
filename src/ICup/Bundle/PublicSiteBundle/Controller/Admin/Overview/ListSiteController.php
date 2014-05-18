<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Overview;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ICup\Bundle\PublicSiteBundle\Services\Util;

/**
 * List the sites and playgrounds available for a tournament
 */
class ListSiteController extends Controller
{
    /**
     * List the items available for a tournament
     * @Route("/edit/site/list/{tournamentid}", name="_edit_site_list")
     * @Method("GET")
     * @Template("ICupPublicSiteBundle:Edit:listsites.html.twig")
     */
    public function listAction($tournamentid) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $tournament = $this->get('entity')->getTournamentById($tournamentid);
        $utilService->validateEditorAdminUser($user, $tournament->getPid());

        $host = $this->get('entity')->getHostById($tournament->getPid());
        $sites = $this->get('logic')->listSites($tournamentid);
        $playgrounds = $this->get('logic')->listPlaygroundsByTournament($tournamentid);

        $siteList = array();
        foreach ($playgrounds as $playground) {
            $siteList[$playground->getPid()][] = $playground;
        }

        return array('host' => $host, 'tournament' => $tournament, 'playgrounds' => $siteList, 'sites' => $sites);
    }
}

<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Overview;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Playground;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
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
        /* @var $tournament Tournament */
        $tournament = $this->get('entity')->getTournamentById($tournamentid);
        $host = $tournament->getHost();
        $utilService->validateEditorAdminUser($user, $host);
        $sites = $tournament->getSites();
        $siteList = array();
        foreach ($tournament->getPlaygrounds() as $playground) {
            /* @var $playground Playground */
            $siteList[$playground->getSite()->getId()][] = $playground;
        }

        return array('host' => $host, 'tournament' => $tournament, 'playgrounds' => $siteList, 'sites' => $sites);
    }
}

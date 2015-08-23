<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Overview;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ICup\Bundle\PublicSiteBundle\Services\Util;

/**
 * List the timeslots available for a tournament
 */
class ListTimeslotController extends Controller
{
    /**
     * List the items available for a tournament
     * @Route("/edit/timeslot/list/{tournamentid}", name="_edit_timeslot_list")
     * @Method("GET")
     * @Template("ICupPublicSiteBundle:Edit:listtimeslots.html.twig")
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
        $timeslots = $tournament->getTimeslots();

        return array('host' => $host, 'tournament' => $tournament, 'timeslots' => $timeslots);
    }
}

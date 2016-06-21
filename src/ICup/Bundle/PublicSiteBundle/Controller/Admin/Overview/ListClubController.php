<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Overview;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Services\Util;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ListClubController extends Controller
{
    /**
     * List all clubs available
     * @Route("/admin/club/list", name="_edit_club_list")
     * @Method("GET")
     * @Template("ICupPublicSiteBundle:Edit:listclubs.html.twig")
     */
    public function listClubsAction()
    {
        $clubs = $this->get('logic')->listClubs();
        $teamList = array();
        $countries = $this->get('util')->getCountries();
        /* @var $club Club */
        foreach ($clubs as $club) {
            $country = $club->getCountryCode();
            if (array_search($country, $countries)) {
                $teams = $club->getTeams();
                $teamList[$country][$club->getId()] = array('club' => $club, 'teams' => $teams);
            }
        }
        return array('teams' => $teamList);
    }

    /**
     * List the clubs enrolled for a tournament
     * @Route("/edit/club/list/{tournamentid}", name="_host_list_clubs")
     * @Method("GET")
     * @Template("ICupPublicSiteBundle:Host:listclubs.html.twig")
     * @param $tournamentid
     * @return array
     */
    public function listClubsActionEditor($tournamentid) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        /* @var $user User */
        $user = $utilService->getCurrentUser();
        /* @var $tournament Tournament */
        $tournament = $this->get('entity')->getTournamentById($tournamentid);
        $host = $tournament->getHost();
        $utilService->validateEditorAdminUser($user, $host);
        return array('host' => $host, 'tournament' => $tournament);
    }
}

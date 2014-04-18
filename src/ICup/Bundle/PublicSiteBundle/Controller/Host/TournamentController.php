<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Host;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;

class TournamentController extends Controller
{
    /**
     * List the clubs enrolled for a tournament
     * @Route("/edit/list/clubs/{tournamentid}", name="_host_list_clubs")
     * @Method("GET")
     * @Template("ICupPublicSiteBundle:Host:listclubs.html.twig")
     */
    public function listClubsAction($tournamentid) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $utilService->setupController();

        try {
            /* @var $user User */
            $user = $utilService->getCurrentUser();
            $tournament = $this->get('entity')->getTournamentById($tournamentid);
            if (!$utilService->isAdminUser($user)) {
                // Validate current user - is it an editor?
                $utilService->validateEditorUser($user, $tournament->getPid());
            }
            $host = $this->get('entity')->getHostById($tournament->getPid());
            $clubs = $this->get('logic')->listEnrolled($tournament->getId());
            $teamcount = 0;
            $teamList = array();
            foreach ($clubs as $clb) {
                $club = $clb['club'];
                $country = $club->getCountry();
                $teamList[$country][$club->getId()] = $clb;
                $teamcount++;
            }

            $teamcount /= 2;
            $teamColumns = array();
            $ccount = 0;
            $column = 0;
            foreach ($teamList as $country => $clubs) {
                $teamColumns[$column][] = array($country => $clubs);
                $ccount += count($clubs);
                if ($ccount > $teamcount && $column < 1) {
                    $column++;
                    $ccount = 0;
                }
            }
            return array('host' => $host, 'tournament' => $tournament, 'teams' => $teamColumns);
        } catch (ValidationException $vexc) {
            return $this->render('ICupPublicSiteBundle:Errors:' . $vexc->getMessage(), array('redirect' => $this->generateUrl('_user_my_page')));
        } 
    }
}

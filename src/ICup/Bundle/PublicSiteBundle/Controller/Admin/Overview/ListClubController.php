<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Overview;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
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
        foreach ($clubs as $club) {
            $country = $club->getCountry();
            $teamList[$country][$club->getId()] = $club;
        }

        $teamcount = count($teamList, COUNT_RECURSIVE)/2;
        $teamColumns = array();
        $ccount = 0;
        $column = 0;
        foreach ($teamList as $country => $clubs) {
            $teamColumns[$column][] = array($country => $clubs);
            $ccount += count($clubs) + 1;
            if ($ccount > $teamcount && $column < 1) {
                $column++;
                $ccount = 0;
            }
        }
        return array('teams' => $teamColumns);
    }
    
    /**
     * List the clubs available for a country matching the pattern given
     * Arguments:
     *   country: countrycode
     *   pattern: stringpattern with % for wildcard
     * @Route("/rest/club/list", name="_rest_list_clubs")
     */
    public function restListClubsAction()
    {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        
        // Validate that user is logged in...
        $utilService->getCurrentUser();
        $request = $this->getRequest();
        $pattern = $request->get('pattern', '%');
        $countryCode = $request->get('country', '');
        $clubs = $this->get('logic')->listClubsByPattern($pattern, $countryCode);
        $result = array();
        foreach ($clubs as $club) {
            $country = $this->get('translator')->trans($club->getCountry(), array(), 'lang');
            $result[] = array('id' => $club->getId(), 'name' => $club->getname(), 'country' => $country);
            if (count($result) > 3) break;
        }
        return new Response(json_encode($result));
    }
    
    /**
     * List the clubs enrolled for a tournament
     * @Route("/edit/club/list/{tournamentid}", name="_host_list_clubs")
     * @Method("GET")
     * @Template("ICupPublicSiteBundle:Host:listclubs.html.twig")
     */
    public function listClubsActionEditor($tournamentid) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $tournament = $this->get('entity')->getTournamentById($tournamentid);
        $utilService->validateEditorAdminUser($user, $tournament->getPid());
        
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
    }
}

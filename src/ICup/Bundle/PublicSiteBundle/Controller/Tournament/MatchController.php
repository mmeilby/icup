<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use ICup\Bundle\PublicSiteBundle\Controller\User\SelectClubController;
use DateTime;

class MatchController extends Controller
{
    /**
     * List the latest matches for a tournament
     * @Route("/tmnt/m/list/{tournament}", name="_show_matches")
     * @Template("ICupPublicSiteBundle:Tournament:matchlist.html.twig")
     */
    public function listMatchesAction($tournament, Request $request) {
        $this->get('util')->setTournamentKey($tournament);
        $tournament = $this->get('util')->getTournament();
        if ($tournament == null) {
            return $this->redirect($this->generateUrl('_tournament_select'));
        }

        $club_list = $this->getClubList($request);
        $today = new DateTime();
//        $today = new DateTime('2014-07-06 20:15:00');

        if (count($club_list) > 0) {
            $matchList = $this->get('match')->listMatchesByTournament($tournament->getId(), $club_list);
        }
        else {
            $matchList = $this->get('match')->listMatchesLimitedWithTournament($tournament->getId(), $today, 10, 6);
        }
        $matches = array();
        foreach ($matchList as $match) {
            $matches[date_format($match['schedule'], "Y/m/d")][] = $match;
        }
        
        $shortMatchList = $matchList;
        $shortMatches = array();
        foreach ($shortMatchList as $match) {
            $shortMatches[date_format($match['schedule'], "Y/m/d")][] = $match;
        }
        
        return array('tournament' => $tournament,
                     'matchlist' => $matches,
                     'shortmatchlist' => $shortMatches);
    }
    
    public function getClubList(Request $request) {
        $clubs = array();
        $club_list = $request->cookies->get(SelectClubController::$ENV_CLUB_LIST, '');
        foreach (explode(':', $club_list) as $club_ident) {
            $club_ident_array = explode('|', $club_ident);
            $name = $club_ident_array[0];
            if (count($club_ident_array) > 1) {
                $countryCode = $club_ident_array[1];
            }
            else {
                $countryCode = 'EUR';
            }
            $club = $this->get('logic')->getClubByName($name, $countryCode);
            if ($club) {
                $clubs[] = $club->getId();
            }
        }
        return $clubs;
    }
    
}

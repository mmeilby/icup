<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Tournament;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use DateTime;

class MatchController extends Controller
{
    /**
     * List the latest matches for a tournament
     * @Route("/tmnt/m/list/{tournament}", name="_show_matches")
     * @Template("ICupPublicSiteBundle:Tournament:matchlist.html.twig")
     */
    public function listMatchesAction($tournament) {
        $this->get('util')->setTournamentKey($tournament);
        $tournament = $this->get('util')->getTournament();
        if ($tournament == null) {
            return $this->redirect($this->generateUrl('_tournament_select'));
        }

        $today = new DateTime();
        $club_list = $this->get('util')->getClubList();
        if (count($club_list) > 0) {
            $matchList = $this->get('match')->listMatchesByTournament($tournament->getId(), array_keys($club_list));
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
}

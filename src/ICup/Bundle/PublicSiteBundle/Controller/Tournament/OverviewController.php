<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Tournament;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ICup\Bundle\PublicSiteBundle\Controller\User\SelectClubController;
use Symfony\Component\HttpFoundation\Request;
use DateTime;

class OverviewController extends Controller
{
    /**
     * @Route("/tmnt/vw/{tournament}", name="_tournament_overview")
     * @Template("ICupPublicSiteBundle:Tournament:overview.html.twig")
     */
    public function overviewAction(Request $request, $tournament)
    {
        $this->get('util')->setTournamentKey($tournament);
        $tournament = $this->get('util')->getTournament();
        if ($tournament == null) {
            return $this->redirect($this->generateUrl('_tournament_select'));
        }
        
        $club_list = $this->get('util')->getClubList();
        $today = new DateTime();

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
        
        $newsStream = array(
/*            
            array(
                'date' => time(),
                'text' => 'TEKNOELETTRONICA TERAMO disqualified due to use of players without license.',
                'path' => $this->generateUrl('_tournament_overview', array('tournament' => $tournament->getKey()))
            ),
            array(
                'date' => time(),
                'text' => 'Dimitri Populos, SPE STROVOLOU, male U18 received red card for improper act to game officials.',
                'path' => $this->generateUrl('_tournament_overview', array('tournament' => $tournament->getKey()))
            )
*/
        );
        $teaserList = array(
            array(
                'titletext' => 'FORM.TEASER.TOURNAMENT.GROUPS.TITLE',
                'text' => 'FORM.TEASER.TOURNAMENT.GROUPS.DESC',
                'path' => $this->generateUrl('_tournament_categories', array('tournament' => $tournament->getKey()))
            ),
            array(
                'titletext' => 'FORM.TEASER.TOURNAMENT.PLAYGROUNDS.TITLE',
                'text' => 'FORM.TEASER.TOURNAMENT.PLAYGROUNDS.DESC',
                'path' => $this->generateUrl('_tournament_playgrounds', array('tournament' => $tournament->getKey()))
            ),
            array(
                'titletext' => 'FORM.TEASER.TOURNAMENT.TEAMS.TITLE',
                'text' => 'FORM.TEASER.TOURNAMENT.TEAMS.DESC',
                'path' => $this->generateUrl('_tournament_clubs', array('tournament' => $tournament->getKey()))
            ),
            array(
                'titletext' => 'FORM.TEASER.TOURNAMENT.WINNERS.TITLE',
                'text' => 'FORM.TEASER.TOURNAMENT.WINNERS.DESC',
                'path' => $this->generateUrl('_tournament_winners', array('tournament' => $tournament->getKey()))
            ),
            array(
                'titletext' => 'FORM.TEASER.TOURNAMENT.STATISTICS.TITLE',
                'text' => 'FORM.TEASER.TOURNAMENT.STATISTICS.DESC',
                'path' => $this->generateUrl('_tournament_statistics', array('tournament' => $tournament->getKey()))
            )
        );

        return array('tournament' => $tournament,
                     'newsstream' => $newsStream,
                     'matchlist' => $shortMatches,
                     'teaserlist' => $teaserList);
    }
}

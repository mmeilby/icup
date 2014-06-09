<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Tournament;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class OverviewController extends Controller
{
    /**
     * @Route("/tmnt/vw/{tournament}", name="_tournament_overview")
     * @Template("ICupPublicSiteBundle:Tournament:overview.html.twig")
     */
    public function overviewAction($tournament)
    {
        $this->get('util')->setTournamentKey($tournament);
        $tournament = $this->get('util')->getTournament();
        if ($tournament == null) {
            return $this->redirect($this->generateUrl('_tournament_select'));
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
                     'teaserlist' => $teaserList);
    }
}

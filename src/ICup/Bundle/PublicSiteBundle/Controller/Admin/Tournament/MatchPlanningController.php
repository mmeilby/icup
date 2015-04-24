<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Tournament;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ICup\Bundle\PublicSiteBundle\Entity\MatchPlan;
use ICup\Bundle\PublicSiteBundle\Entity\Match;
use DateTime;

class MatchPlanningController extends Controller
{
    /**
     * List the latest matches for a tournament
     * @Route("/edit/m/plan/{tournamentid}", name="_plan_matches")
     * @Template("ICupPublicSiteBundle:Tournament:matchlist.html.twig")
     */
    public function listMatchesAction($tournamentid) {
        $tournament = $this->get('entity')->getTournamentById($tournamentid);

        $club_list = $this->get('util')->getClubList();
        $today = new DateTime();
//        $today = new DateTime('2014-07-06 20:15:00');

        $matchList = $this->get('planning')->populateTournament($tournamentid);
/*        
        if (count($club_list) > 0) {
            $matchList = $this->get('match')->listMatchesByTournament($tournament->getId(), $club_list);
        }
        else {
            $matchList = $this->get('match')->listMatchesLimitedWithTournament($tournament->getId(), $today, 10, 6);
        }
 * 
 */
        $matchno = 1;
        $matches = array();
        foreach ($matchList as $matchplan) {
            $match = array(
                'id' => 0,
                'matchno' => $matchno++,
                'schedule' => new DateTime('2014-07-06 20:15:00'),
/*                DateTime::createFromFormat(
                        $this->container->getParameter('db_date_format').
                        '-'.
                        $this->container->getParameter('db_time_format'),
                        $homeMatch['date'].'-'.$homeMatch['time']), */
                'playground' => array('no' => '1',
                                      'name' => 'Playground',
                                      'id' => 0),
                'category' => array('name' => $matchplan->getCategory()->getName(),
                                    'id' => $matchplan->getCategory()->getId()),
                'group' => array('name' => $matchplan->getGroup()->getName(),
                                 'id' => $matchplan->getGroup()->getId()),
                'home' => array('rid' => 0,
                                'clubid' => 0,
                                'id' => $matchplan->getTeamA()->id,
                                'name' => $matchplan->getTeamA()->name,
                                'country' => $matchplan->getTeamA()->country,
                                'score' => '',
                                'points' => '',
                                'rank' => ''),
                'away' => array('rid' => 0,
                                'clubid' => 0,
                                'id' => $matchplan->getTeamB()->id,
                                'name' => $matchplan->getTeamB()->name,
                                'country' => $matchplan->getTeamB()->country,
                                'score' => '',
                                'points' => '',
                                'rank' => '')
            );
            $matches[date_format($match['schedule'], "Y/m/d")][] = $match;
        }
        
        return array('tournament' => $tournament,
                     'matchlist' => $matches,
                     'shortmatchlist' => $matches);
    }
}

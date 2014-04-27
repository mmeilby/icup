<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Tournament;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class StatisticsController extends Controller
{
    /**
     * @Route("/tmnt/{tournament}/stt", name="_tournament_statistics")
     * @Template("ICupPublicSiteBundle:Tournament:statistics.html.twig")
     */
    public function listAction($tournament)
    {
        $this->get('util')->setupController($tournament);
        $tournament = $this->get('util')->getTournament();
        $counts = $this->get('tmnt')->getStatTournamentCounts($tournament->getId());
        $playgroundCounts = $this->get('tmnt')->getStatPlaygroundCounts($tournament->getId());
        $teamCounts = $this->get('tmnt')->getStatTeamCounts($tournament->getId());
        $teamCounts2 = $this->get('tmnt')->getStatTeamCountsChildren($tournament->getId());
        $matchCounts = $this->get('tmnt')->getStatMatchCounts($tournament->getId());
        $statmap = array_merge($counts[0], $playgroundCounts[0], $teamCounts[0], $teamCounts2[0], $matchCounts[0]);

        $statmap['adultteams'] = $statmap['teams'] - $statmap['childteams'];
        $statmap['maleteams'] = $statmap['teams'] - $statmap['femaleteams'];

        $teams = $this->get('tmnt')->getStatTeams($tournament->getId());
        $teamResults = $this->get('tmnt')->getStatTeamResults($tournament->getId());
        $teamsList = $this->get('orderTeams')->generateStat($teams, $teamResults);

        $maxTrophy = null;
        $countries = array();
        foreach ($teamsList as $teamStat) {
            if (key_exists($teamStat->country, $countries)) {
                $countries[$teamStat->country]++;
            }
            else {
                $countries[$teamStat->country] = 1;
            }
            if ($maxTrophy == null) {
                $maxTrophy = $teamStat->country;
            }
            else {
                if ($countries[$maxTrophy] < $countries[$teamStat->country]) {
                    $maxTrophy = $teamStat->country;
                }
            }
        }

        if ($maxTrophy != null) {
            $statmap['mosttrophys'] = $countries[$maxTrophy];
        }
        else {
            $statmap['mosttrophys'] = 0;
        }
        
        return array(
            'tournament' => $tournament,
            'statistics' => $statmap,
            'order' => array(
                'teams' => array('countries','clubs','teams','femaleteams','maleteams','adultteams','childteams'),
                'tournament' => array('categories','groups','sites','playgrounds','matches','days'),
                'top' => array('goals','mostgoals','mosttrophys')));
    }
}

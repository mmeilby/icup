<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Tournament;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class StatisticsController extends Controller
{
    /**
     * @Route("/tmnt/stt/{tournament}", name="_tournament_statistics")
     * @Template("ICupPublicSiteBundle:Tournament:statistics.html.twig")
     */
    public function listAction($tournament)
    {
        $this->get('util')->setTournamentKey($tournament);
        $tournament = $this->get('util')->getTournament();
        if ($tournament == null) {
            return $this->redirect($this->generateUrl('_tournament_select'));
        }
        $counts = $this->get('tmnt')->getStatTournamentCounts($tournament->getId());
        $playgroundCounts = $this->get('tmnt')->getStatPlaygroundCounts($tournament->getId());
        $teamCounts = $this->get('tmnt')->getStatTeamCounts($tournament->getId());
        $teamCounts2 = $this->get('tmnt')->getStatTeamCountsChildren($tournament->getId());
        $matchCounts = $this->get('tmnt')->getStatMatchCounts($tournament->getId());
        $statmap = array_merge($counts[0], $playgroundCounts[0], $teamCounts[0], $teamCounts2[0], $matchCounts[0]);

        $statmap['adultteams'] = $statmap['teams'] - $statmap['childteams'];
        $statmap['maleteams'] = $statmap['teams'] - $statmap['femaleteams'];
        
        $statmap['mosttrophys'] = $this->getMostTrophys($tournament->getId());
        
        return array(
            'tournament' => $tournament,
            'statistics' => $statmap,
            'order' => $this->getOrder());
    }

    private function getMostTrophys($tournamentid) {
        $teams = $this->get('tmnt')->getStatTeams($tournamentid);
        $teamResults = $this->get('tmnt')->getStatTeamResults($tournamentid);
        $teamsList = $this->get('orderTeams')->generateStat($teams, $teamResults);

        $countries = array();
        foreach ($teamsList as $teamStat) {
            if (key_exists($teamStat->country, $countries)) {
                $countries[$teamStat->country]--;
            }
            else {
                $countries[$teamStat->country] = -1;
            }
        }
        sort($countries);
        return count($countries) > 0 ? -$countries[0] : '';
    }
    
    private function getOrder() {
        return array(
                'teams' => array('countries','clubs','teams','femaleteams','maleteams','adultteams','childteams'),
                'tournament' => array('categories','groups','sites','playgrounds','matches','days'),
                'top' => array('goals','mostgoals','mosttrophys'));
    }
}

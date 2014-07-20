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

        $famemap = array();
        
        $mostTrophysClub = $this->getMostTrophysClub($tournament->getId());
        $statmap['mosttrophysbyclub'] = $mostTrophysClub['trophys'];
        if ($mostTrophysClub['country'] != '') {
            $famemap['mosttrophysbyclub']['country'] = $mostTrophysClub['country'];
            $famemap['mosttrophysbyclub']['desc'] = $mostTrophysClub['club'];
        }
        $mostTrophys = $this->getMostTrophys($tournament->getId());
        $statmap['mosttrophys'] = $mostTrophys['trophys'];
        if ($mostTrophys['country'] != '') {
            $famemap['mosttrophys']['country'] = $mostTrophys['country'];
            $famemap['mosttrophys']['desc'] = '';
        }
        $mostGoals = $this->getMostGoals($tournament->getId());
        $statmap['mostgoals'] = $mostGoals['goals'];
        if ($mostGoals['country'] != '') {
            $famemap['mostgoals']['country'] = $mostGoals['country'];
            $famemap['mostgoals']['desc'] = $mostGoals['club'];
        }
        
        return array(
            'tournament' => $tournament,
            'statistics' => $statmap,
            'halloffame' => $famemap,
            'order' => $this->getOrder());
    }

    private function getMostTrophys($tournamentid) {
        $trophies = $this->get('tmnt')->getTrophysByCountry($tournamentid);
        if (count($trophies) > 0) {
            $trophys = $trophies[0]['trophys'];
            $country = $trophies[0]['country'];
        }
        else {
            $trophys = '';
            $country = '';
        }
        return array('trophys' => $trophys, 'country' => $country);
    }
    
    private function getMostTrophysClub($tournamentid) {
        $trophies = $this->get('tmnt')->getTrophysByClub($tournamentid);
        if (count($trophies) > 0) {
            $trophys = $trophies[0]['trophys'];
            $club = $trophies[0]['club'];
            $country = $trophies[0]['country'];
        }
        else {
            $trophys = '';
            $club = '';
            $country = '';
        }
        return array('trophys' => $trophys, 'country' => $country, 'club' => $club);
    }
    
    private function getMostGoals($tournamentid) {
        $trophies = $this->get('tmnt')->getMostGoals($tournamentid);
        if (count($trophies) > 0) {
            $goals = $trophies[0]['mostgoals'];
            $club = $trophies[0]['club'];
            $country = $trophies[0]['country'];
        }
        else {
            $goals = '';
            $club = '';
            $country = '';
        }
        return array('goals' => $goals, 'country' => $country, 'club' => $club);
    }
    
    private function getOrder() {
        return array(
                'teams' => array('countries','clubs','teams','femaleteams','maleteams','adultteams','childteams'),
                'tournament' => array('categories','groups','sites','playgrounds','matches','days'),
                'top' => array('goals','mostgoals','mosttrophys','mosttrophysbyclub'));
    }
}

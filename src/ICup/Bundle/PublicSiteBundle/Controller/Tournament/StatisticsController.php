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
            $famemap['mosttrophysbyclub']['id'] = '';
        }
        $mostTrophys = $this->getMostTrophys($tournament->getId());
        $statmap['mosttrophys'] = $mostTrophys['trophys'];
        if ($mostTrophys['country'] != '') {
            $famemap['mosttrophys']['country'] = $mostTrophys['country'];
            $famemap['mosttrophys']['desc'] = '';
            $famemap['mosttrophys']['id'] = '';
        }
        $mostGoals = $this->getMostGoals($tournament->getId());
        $statmap['mostgoals'] = $mostGoals['goals'];
        if ($mostGoals['country'] != '') {
            $famemap['mostgoals']['country'] = $mostGoals['country'];
            $famemap['mostgoals']['desc'] = $this->formatCategory($mostGoals);
            $famemap['mostgoals']['club'] = $mostGoals['club'];
            $famemap['mostgoals']['id'] = $mostGoals['id'];
        }
        $mostGoalsTotal = $this->getMostGoalsTotal($tournament->getId());
        $statmap['mostgoalstotal'] = $mostGoalsTotal['goals'];
        if ($mostGoalsTotal['country'] != '') {
            $famemap['mostgoalstotal']['country'] = $mostGoalsTotal['country'];
            $famemap['mostgoalstotal']['desc'] = $this->formatCategory($mostGoalsTotal);
            $famemap['mostgoalstotal']['club'] = $mostGoalsTotal['club'];
            $famemap['mostgoalstotal']['id'] = $mostGoalsTotal['id'];
        }
        
        return array(
            'tournament' => $tournament,
            'statistics' => $statmap,
            'halloffame' => $famemap,
            'order' => $this->getOrder());
    }

    private function formatCategory($array) {
        $category = $this->get('entity')->getCategoryById($array['cid']);
        $name = $this->get('translator')->trans('CATEGORY', array(), 'tournament').
                " ".
                $category->getName().
                " - ".
                $this->get('translator')->transChoice(
                        'GENDER.'.$category->getGender().$category->getClassification(),
                        $category->getAge(),
                        array('%age%' => $category->getAge()),
                        'tournament');
        return $name;
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
            $cid = $trophies[0]['cid'];
            $id = $trophies[0]['id'];
        }
        else {
            $goals = '';
            $club = '';
            $country = '';
            $cid = 0;
            $id = 0;
        }
        return array('goals' => $goals, 'country' => $country, 'club' => $club, 'id' => $id, 'cid' => $cid);
    }
    
    private function getMostGoalsTotal($tournamentid) {
        $trophies = $this->get('tmnt')->getMostGoalsTotal($tournamentid);
        if (count($trophies) > 0) {
            $goals = $trophies[0]['mostgoals'];
            $club = $trophies[0]['club'];
            $country = $trophies[0]['country'];
            $cid = $trophies[0]['cid'];
            $id = $trophies[0]['id'];
        }
        else {
            $goals = '';
            $club = '';
            $country = '';
            $cid = 0;
            $id = 0;
        }
        return array('goals' => $goals, 'country' => $country, 'club' => $club, 'id' => $id, 'cid' => $cid);
    }
    
    private function getOrder() {
        return array(
                'teams' => array('countries','clubs','teams','femaleteams','maleteams','adultteams','childteams'),
                'tournament' => array('categories','groups','sites','playgrounds','matches','goals','days'),
                'top' => array('mostgoals','mostgoalstotal','mosttrophys','mosttrophysbyclub'));
    }
}

<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Tournament;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class WinnersController extends Controller
{
    /**
     * @Route("/tmnt/wn/{tournament}", name="_tournament_winners")
     * @Template("ICupPublicSiteBundle:Tournament:winners.html.twig")
     */
    public function listAction($tournament)
    {
        $this->get('util')->setTournamentKey($tournament);
        $tournament = $this->get('util')->getTournament();
        if ($tournament == null) {
            return $this->redirect($this->generateUrl('_tournament_select'));
        }
        $championList = array();
        $groups = $this->get('tmnt')->listChampionsByTournament($tournament->getId());
        foreach ($groups as $group) {
            $teamsList = $this->get('orderTeams')->sortGroup($group['id']);
            if ($group['classification'] == 10) {
                $championList[$group['catid']]['group'] = $group;
                $championList[$group['catid']]['first'][] = $teamsList[0];
                $championList[$group['catid']]['second'][] = $teamsList[1];
                $championList[$group['catid']]['third'] = array();
                $championList[$group['catid']]['forth'] = array();
            }
            else {
                $championList[$group['catid']]['third'][] = $teamsList[0];
                $championList[$group['catid']]['forth'][] = $teamsList[1];
            }
        }
        return array('tournament' => $tournament, 'championlist' => $championList);
    }

    /**
     * @Route("/tmnt/cwn/{tournament}", name="_tournament_winners_countries")
     * @Template("ICupPublicSiteBundle:Tournament:winners_countries.html.twig")
     */
    public function listCountriesAction($tournament)
    {
        $this->get('util')->setTournamentKey($tournament);
        $tournament = $this->get('util')->getTournament();
        if ($tournament == null) {
            return $this->redirect($this->generateUrl('_tournament_select'));
        }
        $groups = $this->get('tmnt')->listChampionsByTournament($tournament->getId());
        $countryList = array();
        foreach ($groups as $group) {
            $teamsList = $this->get('orderTeams')->sortGroup($group['id']);
            if (key_exists($teamsList[0]->country, $countryList))
                $winner_country = $countryList[$teamsList[0]->country];
            else
                $winner_country = array('country' => $teamsList[0]->country, 'first' => 0, 'second' => 0, 'third' => 0, 'forth' => 0);
            if (key_exists($teamsList[1]->country, $countryList))
                $looser_country = $countryList[$teamsList[1]->country];
            else
                $looser_country = array('country' => $teamsList[1]->country, 'first' => 0, 'second' => 0, 'third' => 0, 'forth' => 0);
            if ($group['classification'] == 10) {
                $winner_country['first']++;
                $looser_country['second']++;
            }
            else {
                $winner_country['third']++;
                $looser_country['forth']++;
            }
            $countryList[$teamsList[0]->country] = $winner_country;
            $countryList[$teamsList[1]->country] = $looser_country;
        }
        $championList = array();
        foreach ($countryList as $rank) {
            $insert = false;
            for ($index = 0; $index < count($championList); $index++) {
                $crank = $championList[$index];
                if ($rank['first'] > $crank['first'])
                    $insert = true;
                else if ($rank['first'] == $crank['first']) {
                    if ($rank['second'] > $crank['second'])
                        $insert = true;
                    else if ($rank['second'] == $crank['second']) {
                        if ($rank['third'] > $crank['third'])
                            $insert = true;
                        else if ($rank['third'] == $crank['third'] && $rank['forth'] > $crank['forth']) {
                            $insert = true;
                        }
                    }
                }
                if ($insert) {
                    $first = array_slice($championList, 0, $index);
                    $second = array_slice($championList, $index);
                    $championList = array_merge($first, array($rank), $second);
                    break;
                }
            }
            if (!$insert) {
                $championList[] = $rank;
            }
        }
        
        return array('tournament' => $tournament, 'championlist' => $championList);
    }
    
    /**
     * @Route("/tmnt/clbwn/{tournament}", name="_tournament_winners_clubs")
     * @Template("ICupPublicSiteBundle:Tournament:winners_clubs.html.twig")
     */
    public function listClubsAction($tournament)
    {
        $this->get('util')->setTournamentKey($tournament);
        $tournament = $this->get('util')->getTournament();
        if ($tournament == null) {
            return $this->redirect($this->generateUrl('_tournament_select'));
        }
        $groups = $this->get('tmnt')->listChampionsByTournament($tournament->getId());
        $countryList = array();
        foreach ($groups as $group) {
            $teamsList = $this->get('orderTeams')->sortGroup($group['id']);
            if (key_exists($teamsList[0]->club, $countryList))
                $winner_country = $countryList[$teamsList[0]->club];
            else
                $winner_country = array('club' => $teamsList[0]->club, 'country' => $teamsList[0]->country, 'first' => 0, 'second' => 0, 'third' => 0, 'forth' => 0);
            if (key_exists($teamsList[1]->club, $countryList))
                $looser_country = $countryList[$teamsList[1]->club];
            else
                $looser_country = array('club' => $teamsList[1]->club, 'country' => $teamsList[1]->country, 'first' => 0, 'second' => 0, 'third' => 0, 'forth' => 0);
            if ($group['classification'] == 10) {
                $winner_country['first']++;
                $looser_country['second']++;
            }
            else {
                $winner_country['third']++;
                $looser_country['forth']++;
            }
            $countryList[$teamsList[0]->club] = $winner_country;
            $countryList[$teamsList[1]->club] = $looser_country;
        }
        $championList = array();
        foreach ($countryList as $rank) {
            $insert = false;
            for ($index = 0; $index < count($championList); $index++) {
                $crank = $championList[$index];
                if ($rank['first'] > $crank['first'])
                    $insert = true;
                else if ($rank['first'] == $crank['first']) {
                    if ($rank['second'] > $crank['second'])
                        $insert = true;
                    else if ($rank['second'] == $crank['second']) {
                        if ($rank['third'] > $crank['third'])
                            $insert = true;
                        else if ($rank['third'] == $crank['third'] && $rank['forth'] > $crank['forth']) {
                            $insert = true;
                        }
                    }
                }
                if ($insert) {
                    $first = array_slice($championList, 0, $index);
                    $second = array_slice($championList, $index);
                    $championList = array_merge($first, array($rank), $second);
                    break;
                }
            }
            if (!$insert) {
                $championList[] = $rank;
            }
        }
        
        return array('tournament' => $tournament, 'championlist' => $championList);
    }    
}

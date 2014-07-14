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
        $grpclass = array(9 => array('third', 'forth'), 10 => array('first', 'second'));
        $championList = array();
        $groups = $this->get('tmnt')->listChampionsByTournament($tournament->getId());
        foreach ($groups as $group) {
            $teamsList = $this->get('orderTeams')->sortGroup($group['id']);
            $plcList = $grpclass[$group['classification']];
            foreach ($teamsList as $i => $team) {
                if ($i < 2) {
                    $this->updateList($championList, $group['catid'], $plcList[$i], $group, $team);
                }
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
        $grpclass = array(9 => array('third', 'forth'), 10 => array('first', 'second'));
        $championList = array();
        $groups = $this->get('tmnt')->listChampionsByTournament($tournament->getId());
        foreach ($groups as $group) {
            $teamsList = $this->get('orderTeams')->sortGroup($group['id']);
            $plcList = $grpclass[$group['classification']];
            foreach ($teamsList as $i => $team) {
                if ($i < 2) {
                    $this->updateList($championList, $team->country, $plcList[$i], $group, $team);
                }
            }
        }
        usort($championList, array("ICup\Bundle\PublicSiteBundle\Controller\Tournament\WinnersController", "winnerOrder"));
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
        $grpclass = array(9 => array('third', 'forth'), 10 => array('first', 'second'));
        $championList = array();
        $groups = $this->get('tmnt')->listChampionsByTournament($tournament->getId());
        foreach ($groups as $group) {
            $teamsList = $this->get('orderTeams')->sortGroup($group['id']);
            $plcList = $grpclass[$group['classification']];
            foreach ($teamsList as $i => $team) {
                if ($i < 2) {
                    $this->updateList($championList, $team->club, $plcList[$i], $group, $team);
                }
            }
        }
        usort($championList, array("ICup\Bundle\PublicSiteBundle\Controller\Tournament\WinnersController", "winnerOrder"));
        return array('tournament' => $tournament, 'championlist' => $championList);
    }

    private function updateList(&$list, $key, $order, $group, $team) {
        if (!array_key_exists($key, $list)) {
            $list[$key] = array(
                'country' => $team->country,
                'group' => $group,
                'club' => $team->club,
                'first' => array(),
                'second' => array(),
                'third' => array(),
                'forth' => array()
            );
        }
        $list[$key][$order][] = $team;
    }
    
    static function winnerOrder(array $country1, array $country2) {
        $order = array('first', 'second', 'third', 'forth');
        foreach ($order as $i) {
            $o = count($country1[$i]) - count($country2[$i]);
            if ($o < 0) {
                return 1;
            }
            else if ($o > 0) {
                return -1;
            }
        }
        return 0;
    }
}

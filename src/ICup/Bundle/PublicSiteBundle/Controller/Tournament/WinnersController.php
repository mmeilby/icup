<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team;
use ICup\Bundle\PublicSiteBundle\Entity\TeamStat;
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
        $order = array('first', 'second', 'third', 'forth');
        $championList = array();
        $teams = $this->get('tmnt')->listChampionsByTournament($tournament);
        foreach ($teams as $categoryid => $categoryChamps) {
            /* @var $team Team */
            foreach ($categoryChamps as $champion => $team) {
                $this->updateList($championList, $categoryid, $order[$champion-1], $team->getPreliminaryGroup(), $team);
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
        $order = array('first', 'second', 'third', 'forth');
        $championList = array();
        $teams = $this->get('tmnt')->listChampionsByTournament($tournament);
        foreach ($teams as $categoryid => $categoryChamps) {
            /* @var $team Team */
            foreach ($categoryChamps as $champion => $team) {
                $this->updateList($championList, $team->getClub()->getCountryCode(), $order[$champion-1], $team->getPreliminaryGroup(), $team);
            }
        }
        usort($championList,
            function (array $country1, array $country2) {
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
        );
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
        $order = array('first', 'second', 'third', 'forth');
        $championList = array();
        $teams = $this->get('tmnt')->listChampionsByTournament($tournament);
        foreach ($teams as $categoryid => $categoryChamps) {
            /* @var $team Team */
            foreach ($categoryChamps as $champion => $team) {
                $this->updateList($championList, $team->getClub()->getName(), $order[$champion-1], $team->getPreliminaryGroup(), $team);
            }
        }
        usort($championList,
            function (array $club1, array $club2) {
                $order = array('first', 'second', 'third', 'forth');
                foreach ($order as $i) {
                    $o = count($club1[$i]) - count($club2[$i]);
                    if ($o < 0) {
                        return 1;
                    }
                    else if ($o > 0) {
                        return -1;
                    }
                }
                return 0;
            }
        );
        return array('tournament' => $tournament, 'championlist' => $championList);
    }

    private function updateList(&$list, $key, $order, Group $group, Team $team) {
        if (!array_key_exists($key, $list)) {
            $list[$key] = array(
                'country' => $team->getClub()->getCountryCode(),
                'group' => $group,
                'club' => $team->getClub()->getName(),
                'first' => array(),
                'second' => array(),
                'third' => array(),
                'forth' => array()
            );
        }
        $list[$key][$order][] = array(
            'id' => $team->getId(),
            'name' => $team->getTeamName($this->container->get('translator')->trans('VACANT_TEAM', array(), 'teamname')),
            'country' => $team->getClub()->getCountryCode(),
            'matches' => 1
        );
    }
}

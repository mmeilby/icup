<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Tournament;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class TournamentController extends Controller
{
    /**
     * @Route("/tmnt/{tournament}/ctgr", name="_tournament_categories")
     * @Template("ICupPublicSiteBundle:Tournament:categories.html.twig")
     */
    public function listCategoriesAction($tournament)
    {
        $this->get('util')->setTournamentKey($tournament);
        $tournament = $this->get('util')->getTournament();
        $categories = $this->get('logic')->listCategories($tournament->getId());
        $classMap = array();
        $categoryMap = array();
        foreach ($categories as $category) {
            $classification = $category->getClassification() . $category->getAge();
            $classMap[$classification] = $classification;
            $cls = $category->getGender() . $classification;
            $categoryMap[$cls][] = $category;
        }
        return array('tournament' => $tournament, 'classifications' => $classMap, 'categories' => $categoryMap);
    }

    /**
     * @Route("/tmnt/{tournament}/pgrnd", name="_tournament_playgrounds")
     * @Template("ICupPublicSiteBundle:Tournament:playgrounds.html.twig")
     */
    public function listPlaygroundsAction($tournament)
    {
        $this->get('util')->setTournamentKey($tournament);
        $tournament = $this->get('util')->getTournament();
        $playgrounds = $this->get('tmnt')->listPlaygroundsByTournament($tournament->getId());
        $playgroundList = array();
        foreach ($playgrounds as $playground) {
            $site = $playground['site'];
            $playgroundList[$site][$playground['id']] = $playground['name'];
        }
        return array('tournament' => $tournament, 'playgrounds' => $playgroundList);
    }

    /**
     * @Route("/tmnt/{tournament}/clb", name="_tournament_clubs")
     * @Template("ICupPublicSiteBundle:Tournament:clubs.html.twig")
     */
    public function listClubsAction($tournament)
    {
        $this->get('util')->setTournamentKey($tournament);
        $tournament = $this->get('util')->getTournament();
        $clubs = $this->get('logic')->listClubsByTournament($tournament->getId());
        $teamList = array();
        foreach ($clubs as $club) {
            $country = $club->getCountry();
            $name = $club->getName();
            $teamList[$country][$club->getId()] = $name;
        }

        $teamcount = count($teamList, COUNT_RECURSIVE)/3;
        $teamColumns = array();
        $ccount = 0;
        $column = 0;
        foreach ($teamList as $country => $clubs) {
            $teamColumns[$column][] = array($country => $clubs);
            $ccount += count($clubs) + 1;
            if ($ccount > $teamcount && $column < 2) {
                $column++;
                $ccount = 0;
            }
        }
        return array('tournament' => $tournament, 'teams' => $teamColumns);
    }
    
    /**
     * @Route("/tmnt/{tournament}/tms/{clubId}", name="_tournament_teams")
     * @Template("ICupPublicSiteBundle:Tournament:teams.html.twig")
     */
    public function listTeamsAction($tournament, $clubId)
    {
        $this->get('util')->setTournamentKey($tournament);
        $tournament = $this->get('util')->getTournament();
        $categories = $this->get('logic')->listCategories($tournament->getId());
        $categoryList = array();
        foreach ($categories as $category) {
            $categoryList[$category->getId()] = $category;
        }
        $club = $this->get('entity')->getClubById($clubId);
        $teams = $this->get('tmnt')->listTeamsByClub($tournament->getId(), $clubId);
        $teamList = array();
        foreach ($teams as $team) {
            $name = $team['name'];
            if ($team['division'] != '') {
                $name.= ' "'.$team['division'].'"';
            }
            $team['name'] = $name;
            $teamList[$team['catid']][$team['id']] = $team;
        }

        return array('tournament' => $tournament, 'club' => $club, 'teams' => $teamList, 'categories' => $categoryList);
    }
}

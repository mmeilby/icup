<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Playground;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class TournamentController extends Controller
{
    /**
     * @Route("/tmnt/ctgr/{tournament}", name="_tournament_categories")
     * @Template("ICupPublicSiteBundle:Tournament:categories.html.twig")
     */
    public function listCategoriesAction($tournament)
    {
        $this->get('util')->setTournamentKey($tournament);
        /* @var $tournament Tournament */
        $tournament = $this->get('util')->getTournament();
        if ($tournament == null) {
            return $this->redirect($this->generateUrl('_tournament_select'));
        }
        $classMap = array();
        $categoryMap = array();
        foreach ($tournament->getCategories() as $category) {
            $classification = $category->getClassification() . $category->getAge();
            $classMap[$classification] = $classification;
            $cls = $category->getGender() . $classification;
            $categoryMap[$cls][] = $category;
        }
        return array('tournament' => $tournament, 'classifications' => $classMap, 'categories' => $categoryMap);
    }

    /**
     * @Route("/tmnt/pgrnds/{tournament}", name="_tournament_playgrounds")
     * @Template("ICupPublicSiteBundle:Tournament:playgrounds.html.twig")
     */
    public function listPlaygroundsAction($tournament)
    {
        $this->get('util')->setTournamentKey($tournament);
        /* @var $tournament Tournament */
        $tournament = $this->get('util')->getTournament();
        if ($tournament == null) {
            return $this->redirect($this->generateUrl('_tournament_select'));
        }
        $playgroundList = array();
        foreach ($tournament->getPlaygrounds() as $playground) {
            /* @var $playground Playground */
            $playgroundList[$playground->getSite()->getName()][$playground->getId()] = $playground->getName();
        }
        return array('tournament' => $tournament, 'playgrounds' => $playgroundList);
    }

    /**
     * @Route("/tmnt/clb/{tournament}", name="_tournament_clubs")
     * @Template("ICupPublicSiteBundle:Tournament:clubs.html.twig")
     */
    public function listClubsAction($tournament)
    {
        $this->get('util')->setTournamentKey($tournament);
        $tournament = $this->get('util')->getTournament();
        if ($tournament == null) {
            return $this->redirect($this->generateUrl('_tournament_select'));
        }
        return array('tournament' => $tournament);
    }
    
    /**
     * @Route("/tmnt/tms/{tournament}/{clubId}", name="_tournament_teams", options={"expose"=true})
     * @Template("ICupPublicSiteBundle:Tournament:teams.html.twig")
     */
    public function listTeamsAction($tournament, $clubId)
    {
        $this->get('util')->setTournamentKey($tournament);
        /* @var $tournament Tournament */
        $tournament = $this->get('util')->getTournament();
        if ($tournament == null) {
            return $this->redirect($this->generateUrl('_tournament_select'));
        }
        $categories = $tournament->getCategories();
        $categoryList = array();
        foreach ($categories as $category) {
            $categoryList[$category->getId()] = $category;
        }
        /* @var $club Club */
        $club = $this->get('entity')->getClubById($clubId);
        $teams = $club->getTeams();
        $teamList = array();
        foreach ($teams as $team) {
            /* @var $team Team */
            if ($team->getCategory()->getTournament()->getId() == $tournament->getId()) {
                $teamList[$team->getCategory()->getId()][] = array(
                    'id' => $team->getId(),
                    'name' => $team->getTeamName(),
                    'group' => $team->getPreliminaryGroup()->getName()
                );
            }
        }

        return array('tournament' => $tournament, 'club' => $club, 'teams' => $teamList, 'categories' => $categoryList);
    }
}

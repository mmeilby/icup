<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Tournament;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use DateTime;

class SelectController extends Controller
{
    /**
     * @Route("/tmnts", name="_tournament_select")
     * @Template("ICupPublicSiteBundle:Tournament:select.html.twig")
     */
    public function selectAction()
    {
        $tournaments = $this->get('logic')->listAvailableTournaments();
        $tournamentList = array();
        $today = new DateTime();
        foreach ($tournaments as $tournament) {
            $stat = $this->get('tmnt')->getTournamentStatus($tournament->getId(), $today);
            $tournamentList[$tournament->getId()] = array('tournament' => $tournament, 'status' => $stat);
        }
        return array('tournaments' => $tournamentList);
    }
    
    /**
     * @Route("/_{tournamentkey}", name="_tournament_select_this")
     */
    public function selectThisAction($tournamentkey)
    {
        $this->get('util')->setTournamentKey($tournamentkey);
        $tournament = $this->get('util')->getTournament();
        if ($tournament != null) {
            return $this->redirect($this->generateUrl('_tournament_overview', array('tournament' => $tournamentkey)));
        }
        else {
            return $this->redirect($this->generateUrl('_tournament_select'));
        }
    }
}

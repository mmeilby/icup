<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Tournament;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ICup\Bundle\PublicSiteBundle\Entity\MatchPlan;
use ICup\Bundle\PublicSiteBundle\Entity\Match;
use DateTime;

class MatchPlanningController extends Controller
{
    /**
     * List the latest matches for a tournament
     * @Route("/edit/m/plan/{tournamentid}", name="_edit_match_planning")
     * @Template("ICupPublicSiteBundle:Edit:planmatch.html.twig")
     */
    public function listMatchesAction($tournamentid) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $tournament = $this->get('entity')->getTournamentById($tournamentid);
        $utilService->validateEditorAdminUser($user, $tournament->getPid());

        $matchList = $this->get('planning')->populateTournament($tournament->getId());
        $masterplan = $this->get('planning')->planTournament($tournament->getId(), $matchList);
        
        $matches = array();
        foreach ($masterplan['plan'] as $match) {
            $matches[date_format($match->getSchedule(), "Y/m/d")][] = $match;
        }
        $unassignedCategories = array();
        foreach ($masterplan['unassigned'] as $categoryCount) {
            $unassignedCategories[] = array(
                'category' => reset($categoryCount),
                'matchcount' => count($categoryCount)
            );
        }
        $timeslots = array();
        foreach ($masterplan['available_timeslots'] as $ts) {
            $timeslots[date_format($ts['slotschedule'], "Y/m/d")][] = $ts;
        }

        $host = $this->get('entity')->getHostById($tournament->getPid());
        return array('host' => $host,
                     'tournament' => $tournament,
                     'matchlist' => $matches,
                     'shortmatchlist' => $matches,
                     'unassigned' => $unassignedCategories,
                     'available_timeslots' => $timeslots);
    }
}

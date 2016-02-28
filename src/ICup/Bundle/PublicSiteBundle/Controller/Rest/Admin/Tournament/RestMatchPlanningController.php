<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Rest\Admin\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;
use ICup\Bundle\PublicSiteBundle\Services\Entity\PlanningOptions;
use ICup\Bundle\PublicSiteBundle\Services\Util;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use DateTime;

class RestMatchPlanningController extends Controller
{
    /**
     * Plan matches according to assigned groups and match configuration
     * @Route("/edit/rest/m/plan/plan/{tournamentid}/{level}", name="_rest_match_planning_plan", options={"expose"=true})
     */
    public function planMatchesAction($tournamentid, $level, Request $request) {
        try {
            $tournament = $this->checkArgs($tournamentid);
        }
        catch (ValidationException $e) {
            return new Response(json_encode(array('success' => false, 'done' => true, 'unresolved' => 0)));
        }
        try {
            $planningCard = $this->get('planning')->planTournamentByStep($tournament, $level);
        }
        catch (\Exception $e) {
            return new Response(json_encode(array('success' => false, 'done' => true, 'unresolved' => 0)));
        }
        $unresolved = 0;
        if (isset($planningCard['preliminary'])) {
            $unresolved += $planningCard['preliminary']->unresolved();
        }
        if (isset($planningCard['elimination'])) {
            $unresolved += $planningCard['elimination']->unresolved();
        }
        $done = $planningCard['level'] >= 100;
        return new Response(json_encode(array('success' => true, 'done' => $done, 'unresolved' => $unresolved, 'level' => $planningCard['level'])));
    }

    /**
     * Plan matches according to assigned groups and match configuration
     * @Route("/edit/rest/m/plan/listq/{tournamentid}", name="_rest_match_planning_list_qualified", options={"expose"=true})
     */
    public function listQualifiedAction($tournamentid, Request $request) {
        try {
            $tournament = $this->checkArgs($tournamentid);
        }
        catch (ValidationException $e) {
            return new Response(json_encode(array('success' => false, 'error' => $e->getMessage(), 'info' => $e->getDebugInfo())));
        }
        try {
            $matches = $this->get("tmnt")->listQualifiedTeamsByTournament($tournament);
        }
        catch (\Exception $e) {
            return new Response(json_encode(array('success' => false, 'error' => $e->getMessage(), 'info' => '')));
        }
        $result = array();
        foreach ($matches as $matchrec) {
            /* @var $match Match */
            $match = $matchrec['match'];
            /* @var $teamA Team */
            $teamA = $matchrec['home'];
            /* @var $teamB Team */
            $teamB = $matchrec['away'];
            $result[] = array(
                'matchno' => $match->getMatchno(),
                'home' => array(
                    'id' => $teamA->getId(),
                    'name' => $teamA->getTeamName()." (".$teamA->getClub()->getCountry().")",
                    'country' => $this->get('translator')->trans($teamA->getClub()->getCountry(), array(), 'lang')
                ),
                'away' => array(
                    'id' => $teamB->getId(),
                    'name' => $teamB->getTeamName()." (".$teamB->getClub()->getCountry().")",
                    'country' => $this->get('translator')->trans($teamB->getClub()->getCountry(), array(), 'lang')
                )
            );
        }
        return new Response(json_encode(array('success' => true, 'matches' => $result)));
    }

    /**
     * Check tournament id and validate current user rights to change tournament
     * @param $tournamentid
     * @return Tournament
     */
    private function checkArgs($tournamentid) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        /* @var $user User */
        $user = $utilService->getCurrentUser();
        /* @var $tournament Tournament */
        $tournament = $this->get('entity')->getTournamentById($tournamentid);
        $host = $tournament->getHost();
        $utilService->validateEditorAdminUser($user, $host);
        return $tournament;
    }
}

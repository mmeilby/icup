<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Services\Util;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ICup\Bundle\PublicSiteBundle\Services\Doctrine\TournamentSupport;
use DateTime;

class MasterController extends Controller
{
    /**
     * Wipe all teams from a tournament
     * @Route("/admin/wipe/teams/{tournamentid}", name="_admin_wipe_teams")
     */
    public function wipeTeamAction($tournamentid) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $returnUrl = $utilService->getReferer();
        // Validate tournament id
        /* @var $tournament Tournament */
        $tournament = $this->get('entity')->getTournamentById($tournamentid);
        // Only if tournament has not been started we are allowed to wipe the teams
        $status = $this->get('tmnt')->getTournamentStatus($tournamentid, new DateTime());
        if ($status == TournamentSupport::$TMNT_ENROLL || $status == TournamentSupport::$TMNT_ANNOUNCE) {
            $this->get('tmnt')->wipeTeams($tournament->getId());
        }
        
        return $this->redirect($returnUrl);
    }
    
    /**
     * Wipe all matches from a tournament
     * @Route("/admin/wipe/matches/{tournamentid}", name="_admin_wipe_matches")
     */
    public function wipeMatchAction($tournamentid) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $returnUrl = $utilService->getReferer();
        // Validate tournament id
        /* @var $tournament Tournament */
        $tournament = $this->get('entity')->getTournamentById($tournamentid);
        // Only if tournament has not been started we are allowed to wipe the teams
        $status = $this->get('tmnt')->getTournamentStatus($tournamentid, new DateTime());
        if ($status == TournamentSupport::$TMNT_ENROLL || $status == TournamentSupport::$TMNT_ANNOUNCE) {
            $this->get('tmnt')->wipeMatches($tournament->getId());
        }
        
        return $this->redirect($returnUrl);
    }

    /**
     * Wipe all qmatches from a tournament
     * @Route("/admin/wipe/qmatches/{tournamentid}", name="_admin_wipe_qmatches")
     */
    public function wipeQMatchAction($tournamentid) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $returnUrl = $utilService->getReferer();
        // Validate tournament id
        /* @var $tournament Tournament */
        $tournament = $this->get('entity')->getTournamentById($tournamentid);
        // Only if tournament has not been started we are allowed to wipe the teams
        $status = $this->get('tmnt')->getTournamentStatus($tournamentid, new DateTime());
        if ($status == TournamentSupport::$TMNT_ENROLL || $status == TournamentSupport::$TMNT_ANNOUNCE) {
            $this->get('tmnt')->wipeQMatches($tournament->getId());
        }
        
        return $this->redirect($returnUrl);
    }
}

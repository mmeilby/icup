<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Tournament;

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
        $this->get('entity')->getTournamentById($tournamentid);
        // Only if tournament has not been started we are allowed to wipe the teams
        if ($this->get('tmnt')->getTournamentStatus($tournamentid, new DateTime()) == TournamentSupport::$TMNT_ENROLL) {
            $this->get('tmnt')->wipeTeams($tournamentid);
        }
        
        return $this->redirect($returnUrl);
    }
}

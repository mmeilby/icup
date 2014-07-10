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
    
    /**
     * Wipe all matches from a tournament
     * @Route("/admin/wipe/matches/{tournamentid}", name="_admin_wipe_matches")
     */
    public function wipeMatchAction($tournamentid) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $returnUrl = $utilService->getReferer();
        // Validate tournament id
        $this->get('entity')->getTournamentById($tournamentid);
        // Only if tournament has not been started we are allowed to wipe the teams
        if ($this->get('tmnt')->getTournamentStatus($tournamentid, new DateTime()) == TournamentSupport::$TMNT_ENROLL) {
            $this->get('tmnt')->wipeMatches($tournamentid);
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
        $this->get('entity')->getTournamentById($tournamentid);
        // Only if tournament has not been started we are allowed to wipe the teams
        if ($this->get('tmnt')->getTournamentStatus($tournamentid, new DateTime()) == TournamentSupport::$TMNT_ENROLL) {
            $this->get('tmnt')->wipeQMatches($tournamentid);
        }
        
        return $this->redirect($returnUrl);
    }

    /**
     * Wipe all qmatches from a tournament
     * @Route("/admin/matchfix/{tournamentid}", name="_admin_matchfix")
     */
    public function matchfix($tournamentid) {
        /* @var $utilService Util */
/*        
        $utilService = $this->get('util');
        $returnUrl = $utilService->getReferer();
        // Validate tournament id
        $this->get('entity')->getTournamentById($tournamentid);
        
        $matches = $this->get('match')->listMatchesUnresolved($tournamentid);
        foreach ($matches as $match) {
            $qmh = $this->get('match')->getQMatchRelationByMatch($match->getId(), false);
            $teamList = $this->get('orderTeams')->sortGroup($qmh->getCid());
            var_dump($qmh);
            var_dump($teamList[$qmh->getRank()]);
            $qma = $this->get('match')->getQMatchRelationByMatch($match->getId(), true);
            $teamList = $this->get('orderTeams')->sortGroup($qma->getCid());
            var_dump($qma);
            var_dump($teamList[$qma->getRank()]);
        }
        die();
        return $this->redirect($returnUrl);
 * 
 */
    }
}

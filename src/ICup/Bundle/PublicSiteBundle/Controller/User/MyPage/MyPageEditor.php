<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\User\MyPage;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Enrollment;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Host;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\SocialRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Exceptions\RedirectException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ICup\Bundle\PublicSiteBundle\Services\Doctrine\TournamentSupport;
use DateTime;

/**
 * myPage - myICup - user's home page with context dependent content
 */
class MyPageEditor implements MyPageInterface
{
    /* @var $container Controller */
    protected $container;
    /* @var $user User */
    private $user;

    /**
     * Show myICup page for authenticated users
     */
    public function getTwig() {
        return 'ICupPublicSiteBundle:User:mypage.html.twig';
    }

    // getMyPageParameters
    public function getParms() {
        $parms = array();
        $host = $this->user->getHost();
        $users = $host->getEditors();
        $parms = array(
            'host' => $host,
            'users' => $users,
            'tournamentlist' => $this->getEnrollments($this->user)
        );

        return array_merge($parms,
            array('currentuser' => $this->user),
            $this->getTournaments());
    }

    public function __construct(Controller $container, User $user) {
        $this->container = $container;
        $this->user = $user;
    }

    private function getEnrollments(User $user) {
        $today = new DateTime();
        $tournaments = $this->container->get('logic')->listAvailableTournaments();
        $tournamentList = array();
        foreach ($tournaments as $tournament) {
            $stat = $this->container->get('tmnt')->getTournamentStatus($tournament->getId(), $today);
            if ($stat != TournamentSupport::$TMNT_HIDE) {
                $tournamentList[$tournament->getId()] = array('tournament' => $tournament, 'enrolled' => 0);
            }
        }

        /* @var $relation SocialRelation */
        foreach ($user->getSocialRelations()->toArray() as $relation) {
            $teams = $relation->getGroup()->getTeams();
            /* @var $team Team */
            foreach ($teams as $team) {
                /* @var $enroll Enrollment */
                foreach ($team->getEnrollments() as $enroll) {
                    $tid = $enroll->getCategory()->getTournament()->getId();
                    if (isset($tournamentList[$tid])) {
                        $tournamentList[$tid]['enrolled']++;
                    }
                }
            }
        }
        return $tournamentList;
    }

    private function getTournaments() {
        $tournaments = $this->container->get('logic')->listAvailableTournaments();
        $tournamentList = array();
        $keyList = array(
            TournamentSupport::$TMNT_ENROLL => 'enroll',
            TournamentSupport::$TMNT_GOING => 'active',
            TournamentSupport::$TMNT_DONE => 'done',
            TournamentSupport::$TMNT_ANNOUNCE => 'announce'
        );
        $statusList = array();
        foreach ($keyList as $keylabel) {
            $statusList[$keylabel] = array();
        }
        $today = new DateTime();
        foreach ($tournaments as $tournament) {
            $stat = $this->container->get('tmnt')->getTournamentStatus($tournament->getId(), $today);
            if ($stat != TournamentSupport::$TMNT_HIDE) {
                $tournamentList[$tournament->getId()] = array('tournament' => $tournament, 'status' => $stat);
                $statusList[$keyList[$stat]][] = $tournament;
            }
        }
        return array('tournaments' => $tournamentList, 'statuslist' => $statusList);
    }

    private function redirectMyEditorPage(User $user) {
       if ($user->isEditor()) {
            $rexp = new RedirectException();
            $rexp->setResponse($this->container->redirect($this->container->generateUrl('_edit_dashboard')));
            throw $rexp;
            /* @var $host Host */
/*            
            $host = $user->getHost();
            $users = $host->getUsers();
            $tournaments = $host->getTournaments();
            $tstat = array();
            $today = new DateTime();
            foreach ($tournaments as $tournament) {
                $tstat[$tournament->getId()] = $this->get('tmnt')->getTournamentStatus($tournament->getId(), $today);
            }
            // Editors should get a different view
            $rexp = new RedirectException();
            $rexp->setResponse($this->render('ICupPublicSiteBundle:User:mypage_editor.html.twig',
                                             array('host' => $host,
                                                   'tournaments' => $tournaments,
                                                   'tstat' => $tstat,
                                                   'users' => $users,
                                                   'currentuser' => $user)));
            throw $rexp;
 */
        }
    }
}

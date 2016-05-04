<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\User\MyPage;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Host;
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
class MyPageUser implements MyPageInterface
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
        /*
                if ($user->isClub() && $user->isRelated()) {
                    $club = $user->getClub();
                    $users = $club->getUsers();
                    $prospectors = array();
                    foreach ($users as $usr) {
                        if (($usr->getRole() === User::$CLUB || $usr->getRole() === User::$CLUB_ADMIN) && $usr->getStatus() === User::$PRO) {
                            $prospectors[] = $usr;
                        }
                    }
                    $parms = array_merge(
                                array(
                                    'club' => $club,
                                    'prospectors' => $prospectors,
                                    'tournamentlist' => $this->getEnrollments($user)
                                ),
                                $this->listTeams($club)
                             );
                }
        */
        if ($this->user->isEditor()) {
            $host = $this->user->getHost();
            $users = $host->getEditors();
            $parms = array(
                'host' => $host,
                'users' => $users,
                'tournamentlist' => $this->getEnrollments($this->user)
            );
        }

        return array_merge($parms,
            array('currentuser' => $this->user),
            $this->getTournaments());
    }

    public function __construct(Controller $container, User $user) {
        $this->container = $container;
        $this->user = $user;
    }

    private function listTeams($club)
    {
        $today = new DateTime();
        $tournaments = $this->container->get('logic')->listAvailableTournaments();
        /* @var $tournament Tournament */
        foreach ($tournaments as $tournament) {
            $stat = $this->container->get('tmnt')->getTournamentStatus($tournament->getId(), $today);
            if ($stat == TournamentSupport::$TMNT_GOING || $stat == TournamentSupport::$TMNT_DONE) {
                $categories = $tournament->getCategories();
                $categoryList = array();
                foreach ($categories as $category) {
                    $categoryList[$category->getId()] = $category;
                }
                /* @var $club Club */
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
                return array('teams' => $teamList, 'categories' => $categoryList);
            }
        }
        return array('teams' => array());
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

        if ($user->getClub()) {
            $enrolled =  $this->container->get('logic')->listAnyEnrolledByClub($user->getClub()->getId());
            foreach ($enrolled as $enroll) {
                $tid = $enroll['tid'];
                if (isset($tournamentList[$tid])) {
                    $tournamentList[$tid]['enrolled'] = $enroll['enrolled'];
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
        $statusList = array(
            'enroll' => array(),
            'active' => array(),
            'done' => array(),
            'announce' => array()
        );
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

    private function redirectMyUserPage(User $user) {
        if (!$user->isClubUser() || !$user->isRelated()) {
            // Non related users get a different view
            $rexp = new RedirectException();
            $rexp->setResponse($this->container->render('ICupPublicSiteBundle:User:mypage_nonrel.html.twig',
                array_merge(array('currentuser' => $user), $this->getTournaments())));
            throw $rexp;
        }
    }
}

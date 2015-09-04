<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\User;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Host;
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
class MyPageController extends Controller
{
    /**
     * Show myICup page for authenticated users
     * @Route("/user/mypage", name="_user_my_page")
     * @Method("GET")
     */
    public function myPageAction()
    {
        $user = $this->get('util')->getCurrentUser();
        $parms = $this->getMyPageParameters($user);
        return $this->render('ICupPublicSiteBundle:User:mypage.html.twig', $parms);
    }

    /**
     * Show myICup page for club admin users
     * @Route("/club/mypage/users", name="_user_my_page_users")
     * @Method("GET")
     */
    public function myPageUsersAction()
    {
        $user = $this->get('util')->getCurrentUser();
        /* @var $club Club */
        $club = $user->getClub();
        $this->get('util')->validateClubAdminUser($user, $club);
        $users = $club->getUsers();
        usort($users, function (User $user1, User $user2) {
            // sort users
            return 1;
        });
        // Redirect to my page users list
        return $this->render('ICupPublicSiteBundle:User:mypage_users.html.twig',
                array('club' => $club,
                      'users' => $users,
                      'currentuser' => $user));
    }

    private function redirectMyAdminPage(User $user) {
        if ($user->isAdmin()) {
            // Admins should get a dashboard
            $rexp = new RedirectException();
            $rexp->setResponse($this->redirect($this->generateUrl('_edit_dashboard')));
            throw $rexp;
        }
    }

    private function redirectMyEditorPage(User $user) {
       if ($user->isEditor()) {
            $rexp = new RedirectException();
            $rexp->setResponse($this->redirect($this->generateUrl('_edit_dashboard')));
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
    
    private function redirectMyUserPage(User $user) {
        if (!$user->isClub() || !$user->isRelated()) {
            // Non related users get a different view
            $rexp = new RedirectException();
            $rexp->setResponse($this->render('ICupPublicSiteBundle:User:mypage_nonrel.html.twig',
                                             array_merge(array('currentuser' => $user), $this->getTournaments())));
            throw $rexp;
        }
    }

    private function getMyPageParameters(User $user) {
        $parms = array();
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
        elseif ($user->isEditor()) {
            $host = $user->getHost();
            $users = $host->getEditors();
            $parms = array(
                        'host' => $host,
                        'users' => $users,
                        'tournamentlist' => $this->getEnrollments($user)
                     );
        }

        return array_merge($parms,
                           array('currentuser' => $user),
                           $this->getTournaments());
    }

    private function listTeams($club)
    {
        $today = new DateTime();
        $tournaments = $this->get('logic')->listAvailableTournaments();
        /* @var $tournament Tournament */
        foreach ($tournaments as $tournament) {
            $stat = $this->get('tmnt')->getTournamentStatus($tournament->getId(), $today);
            if ($stat == TournamentSupport::$TMNT_GOING || $stat == TournamentSupport::$TMNT_DONE) {
                $categories = $tournament->getCategories();
                $categoryList = array();
                foreach ($categories as $category) {
                    $categoryList[$category->getId()] = $category;
                }
                $teams = $this->get('tmnt')->listTeamsByClub($tournament->getId(), $club->getId());
                $teamList = array();
                foreach ($teams as $team) {
                    $name = $team['name'];
                    if ($team['division'] != '') {
                        $name.= ' "'.$team['division'].'"';
                    }
                    $team['name'] = $name;
                    $teamList[$team['catid']][$team['id']] = $team;
                }

                return array('teams' => $teamList, 'categories' => $categoryList);
            }
        }
        return array('teams' => array());
    }

    private function getEnrollments(User $user) {
        $today = new DateTime();
        $tournaments = $this->get('logic')->listAvailableTournaments();
        $tournamentList = array();
        foreach ($tournaments as $tournament) {
            $stat = $this->get('tmnt')->getTournamentStatus($tournament->getId(), $today);
            if ($stat != TournamentSupport::$TMNT_HIDE) {
                $tournamentList[$tournament->getId()] = array('tournament' => $tournament, 'enrolled' => 0);
            }
        }

        if ($user->getClub()) {
            $enrolled =  $this->get('logic')->listAnyEnrolledByClub($user->getClub()->getId());
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
        $tournaments = $this->get('logic')->listAvailableTournaments();
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
            $stat = $this->get('tmnt')->getTournamentStatus($tournament->getId(), $today);
            if ($stat != TournamentSupport::$TMNT_HIDE) {
                $tournamentList[$tournament->getId()] = array('tournament' => $tournament, 'status' => $stat);
                $statusList[$keyList[$stat]][] = $tournament;
            }
        }
        return array('tournaments' => $tournamentList, 'statuslist' => $statusList);
    }
}

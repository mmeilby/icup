<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\User;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Host;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Exceptions\RedirectException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

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
        $this->get('util')->setupController();
        $user = $this->get('util')->getCurrentUser();
        try {
            // If user is an admin user throw RedirectException and redirect to admin myPage
            $this->redirectMyAdminPage($user);
            // If user is an editor user throw RedirectException and redirect to editor myPage
            $this->redirectMyEditorPage($user);
            // If user is an unrelated user throw RedirectException and redirect to myPage for unrelated users
            $this->redirectMyUserPage($user);
            // At this point - user is a related club user/admin
            return $this->getMyClubUserPage($user);
        }
        catch (RedirectException $e) {
            return $e->getResponse();
        }
    }

    /**
     * Show myICup page for club admin users
     * @Route("/club/mypage/users", name="_user_my_page_users")
     * @Method("GET")
     */
    public function myPageUsersAction()
    {
        $this->get('util')->setupController();
        $user = $this->get('util')->getCurrentUser();
        $clubid = $user->getCid();
        $this->get('util')->validateClubAdminUser($user, $clubid);
        $club = $this->get('entity')->getClubById($clubid);
        $users = $this->get('logic')->listUsersByClub($clubid);
        // Redirect to my page users list
        return $this->render('ICupPublicSiteBundle:User:mypage_users.html.twig',
                array('club' => $club,
                      'users' => $users,
                      'currentuser' => $user));
    }

    private function redirectMyAdminPage($user) {
        if (!($user instanceof User)) {
            // Controller is called by default admin
            $rexp = new RedirectException();
            $rexp->setResponse($this->render('ICupPublicSiteBundle:User:mypage_def_admin.html.twig'));
            throw $rexp;
        }
        /* @var $user User */
        if ($user->isAdmin()) {
            // Admins should get a different view
            $rexp = new RedirectException();
            $rexp->setResponse($this->render('ICupPublicSiteBundle:User:mypage_admin.html.twig',
                                             array('currentuser' => $user)));
            throw $rexp;
        }
    }

    private function redirectMyEditorPage(User $user) {
       if ($user->isEditor()) {
            /* @var $host Host */
            $host = $this->get('entity')->getHostById($user->getPid());
            $users = $this->get('logic')->listUsersByHost($host->getId());
            // Editors should get a different view
            $rexp = new RedirectException();
            $rexp->setResponse($this->render('ICupPublicSiteBundle:User:mypage_editor.html.twig',
                                             array('host' => $host, 'users' => $users, 'currentuser' => $user)));
            throw $rexp;
        }
    }
    
    private function redirectMyUserPage(User $user) {
        if (!$user->isClub() || !$user->isRelated()) {
            // Non related users get a different view
            $rexp = new RedirectException();
            $rexp->setResponse($this->render('ICupPublicSiteBundle:User:mypage_nonrel.html.twig',
                                             array('currentuser' => $user)));
            throw $rexp;
        }
    }

    private function getMyClubUserPage(User $user) {
        $clubid = $user->getCid();
        $club = $this->get('entity')->getClubById($clubid);
        $users = $this->get('logic')->listUsersByClub($clubid);
        $prospectors = array();
        foreach ($users as $usr) {
            if ($usr->getStatus() === User::$PRO) {
                $prospectors[] = $usr;
            }
        }
        $tournamentList = $this->getEnrollments($user);
        // Redirect to my page
        return $this->render('ICupPublicSiteBundle:User:mypage.html.twig',
                array('club' => $club,
                      'prospectors' => $prospectors,
                      'currentuser' => $user,
                      'tournaments' => $tournamentList));
    }

    private function getEnrollments(User $user) {
        $enrolled = $this->get('logic')->listAnyEnrolledByClub($user->getCid());
        $tournaments = $this->get('logic')->listAvailableTournaments();
        $tournamentList = array();
        foreach ($tournaments as $tournament) {
            $tournamentList[$tournament->getId()] = array('tournament' => $tournament, 'enrolled' => 0);
        }
        
        foreach ($enrolled as $enroll) {
            $tid = $enroll['tid'];
            if (key_exists($tid, $tournamentList)) {
                $tournamentList[$tid]['enrolled'] = $enroll['enrolled'];
            }
        }
        return $tournamentList;
    }
}

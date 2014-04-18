<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\User;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Host;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Exceptions\RedirectException;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Yaml\Exception\RuntimeException;

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
        $user = $this->getUser();
        if ($user == null) {
            throw new RuntimeException("This controller is not available for anonymous users");
        }
        try {
            // If user is an admin user throw RedirectException and redirect to admin myPage
            $this->redirectMyAdminPage($user);
            // If user is an editor user throw RedirectException and redirect to editor myPage
            $this->redirectMyEditorPage($user);
            // If user is an unrelated user throw RedirectException and redirect to myPage for unrelated users
            $this->redirectMyUserPage($user);
            // At this point - user is a related club user/admin
            return $this->getMyClubUserPage($user);
        } catch (RedirectException $rexc) {
            return $rexc->getResponse();
        } catch (ValidationException $vexc) {
            $this->get('logger')->addError("User CID/PID is invalid: " . $user->dump());
            return $this->render('ICupPublicSiteBundle:Errors:' . $vexc->getMessage(), array('redirect' => $this->generateUrl('_user_my_page')));
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
        $user = $this->getUser();
        if ($user == null) {
            throw new RuntimeException("This controller is not available for anonymous users");
        }
        try {
            if (!is_a($user, 'ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User')) {
                // Local admins should not get access to this function
                return $this->render('ICupPublicSiteBundle:Errors:nolocaladmin.html.twig', array('redirect' => $this->generateUrl('_user_my_page')));
            }
            if ($user->getRole() !== User::$CLUB_ADMIN) {
                // 
                throw new ValidationException("notclubadmin.html.twig");
            }
            $em = $this->getDoctrine()->getManager();
            $club = $this->getClubById($user->getCid());
            $users = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User')
                            ->findBy(array('cid' => $club->getId()), array('status' => 'asc', 'role' => 'desc', 'name' => 'asc'));
            // Redirect to my page users list
            return $this->render('ICupPublicSiteBundle:User:mypage_users.html.twig',
                    array('club' => $club,
                          'users' => $users,
                          'currentuser' => $user));
        } catch (RedirectException $rexc) {
            return $rexc->getResponse();
        } catch (ValidationException $vexc) {
            $this->get('logger')->addError("User CID/PID is invalid: " . $user->dump());
            return $this->render('ICupPublicSiteBundle:Errors:' . $vexc->getMessage(), array('redirect' => $this->generateUrl('_user_my_page')));
        } 
    }

    private function redirectMyAdminPage($user) {
        if (!is_a($user, 'ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User')) {
            // Controller is called by default admin
            $rexp = new RedirectException();
            $rexp->setResponse($this->render('ICupPublicSiteBundle:User:mypage_def_admin.html.twig'));
            throw $rexp;
        }
        /* @var $user User */
        if ($user->isAdmin()) {
            // Admins should get a different view
            $rexp = new RedirectException();
            $rexp->setResponse($this->render('ICupPublicSiteBundle:User:mypage_admin.html.twig', array('currentuser' => $user)));
            throw $rexp;
        }
    }

    private function redirectMyEditorPage(User $user) {
       if ($user->isEditor()) {
            $em = $this->getDoctrine()->getManager();
            /* @var $host Host */
            $host = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Host')->find($user->getPid());
            if ($host == null) {
                // User was related to a missing host
                throw new ValidationException("badhost.html.twig");
            }
            $users = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User')
                            ->findBy(array('pid' => $host->getId()), array('name' => 'asc'));
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
            $rexp->setResponse($this->render('ICupPublicSiteBundle:User:mypage_nonrel.html.twig', array('currentuser' => $user)));
            throw $rexp;
        }
    }

    private function getMyClubUserPage(User $user) {
        $em = $this->getDoctrine()->getManager();
        $club = $this->getClubById($user->getCid());
        $users = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User')
                        ->findBy(array('cid' => $club->getId()), array('status' => 'asc', 'role' => 'desc', 'name' => 'asc'));
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
        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQuery("select c.pid as tid,count(e) as enrolled ".
                               "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Enrollment e, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category c, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team t ".
                               "where e.pid=c.id and e.cid=t.id and t.pid=:club ".
                               "group by c.pid order by c.pid asc");
        $qb->setParameter('club', $user->getCid());
        $enrolled = $qb->getResult();

        $tournaments = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')
                            ->findAll();
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
    
    private function getClubById($clubid) {
        $em = $this->getDoctrine()->getManager();
        /* @var $club Club */
        $club = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')->find($clubid);
        if ($club == null) {
            // User was related to a missing club
            throw new ValidationException("badclub.html.twig");
        }
        return $club;
    }
}

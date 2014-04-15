<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Club;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Enrollment;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;
use RuntimeException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * List the categories and groups available
 */
class ClubEnrollController extends Controller
{
    /**
     * List the current enrollments for a club
     * @Route("/club/enroll/list/{tournament}", name="_club_enroll_list")
     * @Method("GET")
     * @Template("ICupPublicSiteBundle:Club:listenrolled.html.twig")
     */
    public function listAction($tournament) {
        $this->get('util')->setupController();
        $em = $this->getDoctrine()->getManager();
        
        $tmnt = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')->find($tournament);
        if ($tmnt == null) {
            return $this->render('ICupPublicSiteBundle:Errors:needatournament.html.twig');
        }

        /* @var $user User */
        $user = $this->getUser();
        if ($user == null) {
            throw new RuntimeException("This controller is not available for anonymous users");
        }
        if (!is_a($user, 'ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User')) {
            // Controller is called by default admin - switch to select club view
            return $this->redirect($this->generateUrl('_edit_club_list'));
        }
        if (!$user->isClub()) {
            // Controller is called by editor or admin - switch to select club view
            return $this->redirect($this->generateUrl('_edit_club_list'));
        }
        if (!$user->isRelated()) {
            // User is not related to a club yet - explain the problem...
            return $this->render('ICupPublicSiteBundle:Errors:needtoberelated.html.twig');
        }
        /* @var $club Club */
        $club = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')->find($user->getCid());
        if ($club == null) {
            // User is not related to a valid club - explain the problem...
            $this->get('logger')->addError("User CID is invalid: " . $user->dump());
            return $this->render('ICupPublicSiteBundle:Errors:needtoberelated.html.twig');
        }
        
        return $this->listEnrolled($tmnt, $club);
    }

    /**
     * List the current enrollments for a club explicit
     * @Route("/host/enroll/list/{tournament}/{club}", name="_club_enroll_list_admin")
     * @Method("GET")
     * @Template("ICupPublicSiteBundle:Host:listenrolled.html.twig")
     */
    public function listActionHost($tournament, $club) {
        $this->get('util')->setupController();
        $em = $this->getDoctrine()->getManager();
        
        /* @var $user User */
        $user = $this->getUser();
        if ($user == null) {
            throw new RuntimeException("This controller is not available for anonymous users");
        }
        if ($user->getRole() !== User::$EDITOR_ADMIN) {
            return $this->render('ICupPublicSiteBundle:Errors:noteditoradmin.html.twig');
        }
        /* @var $tmnt Tournament */
        $tmnt = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')->find($tournament);
        if ($tmnt == null) {
            return $this->render('ICupPublicSiteBundle:Errors:badtournament.html.twig');
        }

        /* @var $clb Club */
        $clb = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')->find($club);
        if ($clb == null) {
            return $this->render('ICupPublicSiteBundle:Errors:badclub.html.twig');
        }
        
        return $this->listEnrolled($tmnt, $clb);
    }

    /**
     * Check for tournament before enroll
     * @Route("/club/enroll/check", name="_club_enroll_check")
     * @Method("GET")
     */
    public function checkAction() {
        $this->get('util')->setupController();
        $em = $this->getDoctrine()->getManager();
        
        $tmnt = $this->get('util')->getTournament();
        if ($tmnt == null) {
            return $this->render('ICupPublicSiteBundle:Errors:needatournament.html.twig');
        }
        return $this->redirect($this->generateUrl('_club_enroll_list', array('tournament' => $tmnt->getId())));
    }
    
    private function listEnrolled(Tournament $tmnt, Club $club) {
        $em = $this->getDoctrine()->getManager();

        $host = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Host')
                            ->find($tmnt->getPid());
        
        /* @var $category Category */
        $categories = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category')
                            ->findBy(array('pid' => $tmnt->getId()), array('classification' => 'asc', 'gender' => 'asc'));
        
        $qb = $em->createQuery("select e ".
                               "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Enrollment e, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category c, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team t ".
                               "where c.pid=:tournament and e.pid=c.id and e.cid=t.id and t.pid=:club ".
                               "order by e.pid");
        $qb->setParameter('tournament', $tmnt->getId());
        $qb->setParameter('club', $club->getId());
        $enrolled = $qb->getResult();

        $enrolledList = array();
        foreach ($enrolled as $enroll) {
            $enrolledList[$enroll->getPid()][] = $enroll;
        }
        
        $classMap = array();
        $categoryMap = array();
        foreach ($categories as $category) {
            $classMap[$category->getClassification()] = $category->getClassification();
            $cls = $category->getGender() . $category->getClassification();
            $categoryMap[$cls][] = $category;
        }
        return array(
            'host' => $host,
            'tournament' => $tmnt,
            'club' => $club,
            'classifications' => $classMap,
            'enrolled' => $enrolledList,
            'categories' => $categoryMap);
    }
    
    /**
     * Enrolls a club in a tournament by adding new team to category
     * @Route("/club/enroll/add/{categoryid}", name="_club_enroll_add")
     * @Method("GET")
     */
    public function addEnrollAction($categoryid) {
        $this->get('util')->setupController();
        $em = $this->getDoctrine()->getManager();

        /* @var $user User */
        $user = $this->getUser();
        if ($user == null) {
            throw new RuntimeException("This controller is not available for anonymous users");
        }
        if (!is_a($user, 'ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User')) {
            // Controller is called by default admin - switch to select club view
            return $this->redirect($this->generateUrl('_edit_club_list'));
        }
        if (!$user->isClub()) {
            // Controller is called by editor or admin - switch to select club view
            return $this->redirect($this->generateUrl('_edit_club_list'));
        }
        if (!$user->isRelated()) {
            // User is not related to a club yet - explain the problem...
            return $this->render('ICupPublicSiteBundle:Errors:needtoberelated.html.twig');
        }
        /* @var $club Club */
        $club = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')->find($user->getCid());
        if ($club == null) {
            // User is not related to a valid club - explain the problem...
            $this->get('logger')->addError("User CID is invalid: " . $user->dump());
            return $this->render('ICupPublicSiteBundle:Errors:needtoberelated.html.twig');
        }
        
        /* @var $category Category */
        $category = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category')->find($categoryid);
        if ($category == null) {
            return $this->render('ICupPublicSiteBundle:Errors:badcategory.html.twig');
        }
        $tournamentid = $category->getPid();
        
        try {
            $this->get('logic')->addEnrolled($category, $club, $user);
            return $this->redirect($this->generateUrl('_club_enroll_list', 
                    array('tournament' => $tournamentid)));
        } catch (ValidationException $vexc) {
            return $this->render('ICupPublicSiteBundle:Errors:' . $vexc->getMessage(), 
                    array('redirect' => $this->generateUrl('_club_enroll_list', 
                            array('tournament' => $tournamentid))));
        } 
    }
    
    /**
     * Enrolls a club in a tournament by adding new team to category
     * @Route("/host/enroll/add/{categoryid}/{clubid}", name="_club_enroll_add_admin")
     * @Method("GET")
     */
    public function addEnrollActionHost($categoryid, $clubid) {
        $this->get('util')->setupController();
        $em = $this->getDoctrine()->getManager();

        /* @var $user User */
        $user = $this->getUser();
        if ($user == null) {
            throw new RuntimeException("This controller is not available for anonymous users");
        }
        if ($user->getRole() !== User::$EDITOR_ADMIN) {
            return $this->render('ICupPublicSiteBundle:Errors:noteditoradmin.html.twig');
        }
        /* @var $club Club */
        $club = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')->find($clubid);
        if ($club == null) {
            return $this->render('ICupPublicSiteBundle:Errors:badclub.html.twig');
        }
        
        /* @var $category Category */
        $category = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category')->find($categoryid);
        if ($category == null) {
            return $this->render('ICupPublicSiteBundle:Errors:badcategory.html.twig');
        }
        $tournamentid = $category->getPid();
        
        try {
            $this->get('logic')->addEnrolled($category, $club, $user);
            return $this->redirect($this->generateUrl('_club_enroll_list_admin', 
                    array('tournament' => $tournamentid, 'club' => $clubid)));
        } catch (ValidationException $vexc) {
            return $this->render('ICupPublicSiteBundle:Errors:' . $vexc->getMessage(), 
                    array('redirect' => $this->generateUrl('_club_enroll_list_admin', 
                            array('tournament' => $tournamentid, 'club' => $clubid))));
        } 
    }
    
    /**
     * Remove last team from category - including all related match results
     * @Route("/club/enroll/del/{categoryid}", name="_club_enroll_del")
     * @Method("GET")
     */
    public function delEnrollAction($categoryid) {
        $this->get('util')->setupController();
        $em = $this->getDoctrine()->getManager();

        /* @var $user User */
        $user = $this->getUser();
        if ($user == null) {
            throw new RuntimeException("This controller is not available for anonymous users");
        }
        if (!is_a($user, 'ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User')) {
            // Controller is called by default admin - switch to select club view
            return $this->redirect($this->generateUrl('_edit_club_list'));
        }
        if (!$user->isClub()) {
            // Controller is called by editor or admin - switch to select club view
            return $this->redirect($this->generateUrl('_edit_club_list'));
        }
        if (!$user->isRelated()) {
            // User is not related to a club yet - explain the problem...
            return $this->render('ICupPublicSiteBundle:Errors:needtoberelated.html.twig');
        }
        /* @var $club Club */
        $club = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')->find($user->getCid());
        if ($club == null) {
            // User is not related to a valid club - explain the problem...
            $this->get('logger')->addError("User CID is invalid: " . $user->dump());
            return $this->render('ICupPublicSiteBundle:Errors:needtoberelated.html.twig');
        }
        
        /* @var $category Category */
        $category = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category')->find($categoryid);
        if ($category == null) {
            return $this->render('ICupPublicSiteBundle:Errors:badcategory.html.twig');
        }
        $tournamentid = $category->getPid();
        
        try {
            $this->get('logic')->deleteEnrolled($categoryid, $club->getId());
            return $this->redirect($this->generateUrl('_club_enroll_list', 
                    array('tournament' => $tournamentid)));
        } catch (ValidationException $vexc) {
            return $this->render('ICupPublicSiteBundle:Errors:' . $vexc->getMessage(), 
                    array('redirect' => $this->generateUrl('_club_enroll_list', 
                            array('tournament' => $tournamentid))));
        } 
    }
    
    /**
     * Remove last team from category - including all related match results
     * @Route("/host/enroll/del/{categoryid}/{clubid}", name="_club_enroll_del_admin")
     * @Method("GET")
     */
    public function delEnrollActionHost($categoryid, $clubid) {
        $this->get('util')->setupController();
        $em = $this->getDoctrine()->getManager();

        /* @var $user User */
        $user = $this->getUser();
        if ($user == null) {
            throw new RuntimeException("This controller is not available for anonymous users");
        }
        if ($user->getRole() !== User::$EDITOR_ADMIN) {
            return $this->render('ICupPublicSiteBundle:Errors:noteditoradmin.html.twig');
        }
        /* @var $club Club */
        $club = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')->find($clubid);
        if ($club == null) {
            return $this->render('ICupPublicSiteBundle:Errors:badclub.html.twig');
        }
        
        /* @var $category Category */
        $category = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category')->find($categoryid);
        if ($category == null) {
            return $this->render('ICupPublicSiteBundle:Errors:badcategory.html.twig');
        }
        $tournamentid = $category->getPid();
        
        try {
            $this->get('logic')->deleteEnrolled($categoryid, $club->getId());
            return $this->redirect($this->generateUrl('_club_enroll_list_admin', 
                    array('tournament' => $tournamentid, 'club' => $clubid)));
        } catch (ValidationException $vexc) {
            return $this->render('ICupPublicSiteBundle:Errors:' . $vexc->getMessage(), 
                    array('redirect' => $this->generateUrl('_club_enroll_list_admin', 
                            array('tournament' => $tournamentid, 'club' => $clubid))));
        } 
    }
}

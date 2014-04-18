<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Club;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;
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
     * List the enrollments for the club of the current user in a tournament
     * @Route("/club/enroll/list/{tournament}", name="_club_enroll_list")
     * @Method("GET")
     * @Template("ICupPublicSiteBundle:Club:listenrolled.html.twig")
     */
    public function listAction($tournament) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $utilService->setupController();

        try {
            /* @var $user User */
            $user = $utilService->getCurrentUser();
            if ($utilService->isAdmin($user)) {
                // Controller is called by admin - switch to select club view
                return $this->redirect($this->generateUrl('_edit_club_list'));
            }
            if (!$user->isClub()) {
                // Controller is called by editor - switch to select club view
                return $this->redirect($this->generateUrl('_edit_club_list'));
            }
            if (!$user->isRelated()) {
                // User is not related to a club yet - explain the problem...
                throw new ValidationException("needtoberelated.html.twig");
            }
            $tmnt = $this->get('entity')->getTournamentById($tournament);
            $club = $this->get('entity')->getClubById($user->getCid());
            return $this->listEnrolled($tmnt, $club);
        } catch (ValidationException $vexc) {
            return $this->render('ICupPublicSiteBundle:Errors:' . $vexc->getMessage(), array('redirect' => $this->generateUrl('_host_list_clubs', array('tournamentid' => $tournamentid))));
        }
    }

    /**
     * List the current enrollments for a specific club in a tournament
     * @Route("/edit/enroll/list/{tournament}/{club}", name="_club_enroll_list_admin")
     * @Method("GET")
     * @Template("ICupPublicSiteBundle:Host:listenrolled.html.twig")
     */
    public function listActionHost($tournament, $club) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $utilService->setupController();

        try {
            /* @var $user User */
            $user = $utilService->getCurrentUser();
            /* @var $tournament Tournament */
            $tmnt = $this->get('entity')->getTournamentById($tournament);
            // Check that user is editor
            $utilService->validateEditorUser($user, $tmnt->getPid());

            $clb = $this->get('entity')->getClubById($club);
            return $this->listEnrolled($tmnt, $clb);
        } catch (ValidationException $vexc) {
            return $this->render('ICupPublicSiteBundle:Errors:' . $vexc->getMessage(), array('redirect' => $this->generateUrl('_host_list_clubs', array('tournamentid' => $tournamentid))));
        }
    }

    /**
     * Check for tournament before enroll
     * @Route("/club/enroll/check", name="_club_enroll_check")
     * @Method("GET")
     */
    public function checkAction() {
        $this->get('util')->setupController();
        $tmnt = $this->get('util')->getTournament();
        if ($tmnt == null) {
            return $this->render('ICupPublicSiteBundle:Errors:needatournament.html.twig');
        }
        return $this->redirect($this->generateUrl('_club_enroll_list', array('tournament' => $tmnt->getId())));
    }
    
    private function listEnrolled(Tournament $tmnt, Club $club) {
        $host = $this->get('entity')->getHostById($tmnt->getPid());
        $categories = $this->get('logic')->listCategories($tmnt->getId());
        $enrolled = $this->get('logic')->listEnrolledByClub($tmnt->getId(), $club->getId());

        $enrolledList = array();
        foreach ($enrolled as $enroll) {
            $enrolledList[$enroll->getPid()][] = $enroll;
        }

        $classMap = array();
        $categoryMap = array();
        /* @var $category Category */
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
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $utilService->setupController();

        try {
            /* @var $user User */
            $user = $utilService->getCurrentUser();
            if ($utilService->isAdmin($user)) {
                // Controller is called by admin - switch to select club view
                return $this->redirect($this->generateUrl('_edit_club_list'));
            }
            if (!$user->isClub()) {
                // Controller is called by editor - switch to select club view
                return $this->redirect($this->generateUrl('_edit_club_list'));
            }
            if (!$user->isRelated()) {
                // User is not related to a club yet - explain the problem...
                throw new ValidationException("needtoberelated.html.twig");
            }
            /* @var $category Category */
            $category = $this->get('entity')->getCategoryById($categoryid);
            $tournamentid = $category->getPid();
            $club = $this->get('entity')->getClubById($user->getCid());
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
     * @Route("/edit/enroll/add/{categoryid}/{clubid}", name="_club_enroll_add_admin")
     * @Method("GET")
     */
    public function addEnrollActionHost($categoryid, $clubid) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $utilService->setupController();

        try {
            /* @var $user User */
            $user = $utilService->getCurrentUser();
            /* @var $club Club */
            $club = $this->get('entity')->getClubById($clubid);
            /* @var $category Category */
            $category = $this->get('entity')->getCategoryById($categoryid);
            $tournamentid = $category->getPid();
            // Check that user is editor
            $utilService->validateEditorUser($user, $tournamentid);
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
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $utilService->setupController();

        try {
            /* @var $user User */
            $user = $utilService->getCurrentUser();
            if ($utilService->isAdmin($user)) {
                // Controller is called by admin - switch to select club view
                return $this->redirect($this->generateUrl('_edit_club_list'));
            }
            if (!$user->isClub()) {
                // Controller is called by editor - switch to select club view
                return $this->redirect($this->generateUrl('_edit_club_list'));
            }
            if (!$user->isRelated()) {
                // User is not related to a club yet - explain the problem...
                throw new ValidationException("needtoberelated.html.twig");
            }
            /* @var $category Category */
            $category = $this->get('entity')->getCategoryById($categoryid);
            $tournamentid = $category->getPid();
            $club = $this->get('entity')->getClubById($user->getCid());
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
     * @Route("/edit/enroll/del/{categoryid}/{clubid}", name="_club_enroll_del_admin")
     * @Method("GET")
     */
    public function delEnrollActionHost($categoryid, $clubid) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $utilService->setupController();

        try {
            /* @var $user User */
            $user = $utilService->getCurrentUser();
            /* @var $club Club */
            $club = $this->get('entity')->getClubById($clubid);
            /* @var $category Category */
            $category = $this->get('entity')->getCategoryById($categoryid);
            $tournamentid = $category->getPid();
            // Check that user is editor
            $utilService->validateEditorUser($user, $tournamentid);
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

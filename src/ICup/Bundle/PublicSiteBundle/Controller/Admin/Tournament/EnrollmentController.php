<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * List the categories and groups available
 */
class EnrollmentController extends Controller
{
    /**
     * Enrolls a club in a tournament by adding new team to category
     * @Route("/club/enroll/add/{categoryid}", name="_club_enroll_add")
     * @Method("GET")
     */
    public function addEnrollAction($categoryid) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $utilService->setupController();
        $returnUrl = $utilService->getReferer();

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
            throw new ValidationException("NEEDTOBERELATED", "userid=".$user->getId());
        }
        /* @var $category Category */
        $category = $this->get('entity')->getCategoryById($categoryid);
        $club = $this->get('entity')->getClubById($user->getCid());
        $this->get('logic')->addEnrolled($category, $club, $user);
        return $this->redirect($returnUrl);
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
        $returnUrl = $utilService->getReferer();

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        /* @var $club Club */
        $club = $this->get('entity')->getClubById($clubid);
        /* @var $category Category */
        $category = $this->get('entity')->getCategoryById($categoryid);
        // Check that user is editor
        $tournament = $this->get('entity')->getTournamentById($category->getPid());
        $utilService->validateEditorAdminUser($user, $tournament->getPid());
        
        $this->get('logic')->addEnrolled($category, $club, $user);
        return $this->redirect($returnUrl);
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
        $returnUrl = $utilService->getReferer();

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
            throw new ValidationException("NEEDTOBERELATED", "userid=".$user->getId());
        }
        /* @var $category Category */
        $category = $this->get('entity')->getCategoryById($categoryid);
        $club = $this->get('entity')->getClubById($user->getCid());
        $this->get('logic')->deleteEnrolled($category->getId(), $club->getId());
        return $this->redirect($returnUrl);
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
        $returnUrl = $utilService->getReferer();

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        /* @var $club Club */
        $club = $this->get('entity')->getClubById($clubid);
        /* @var $category Category */
        $category = $this->get('entity')->getCategoryById($categoryid);
        // Check that user is editor
        $tournament = $this->get('entity')->getTournamentById($category->getPid());
        $utilService->validateEditorAdminUser($user, $tournament->getPid());

        $this->get('logic')->deleteEnrolled($categoryid, $club->getId());
        return $this->redirect($returnUrl);
     }
}

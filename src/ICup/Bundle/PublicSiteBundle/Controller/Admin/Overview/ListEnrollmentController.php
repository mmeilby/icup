<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Overview;

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
class ListEnrollmentController extends Controller
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

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $utilService->validateClubUser($user);

        $tmnt = $this->get('entity')->getTournamentById($tournament);
        $club = $this->get('entity')->getClubById($user->getCid());
        return $this->listEnrolled($tmnt, $club);
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

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $tmnt = $this->get('entity')->getTournamentById($tournament);
        $utilService->validateEditorAdminUser($user, $tmnt->getPid());

        $clb = $this->get('entity')->getClubById($club);
        return $this->listEnrolled($tmnt, $clb);
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
}

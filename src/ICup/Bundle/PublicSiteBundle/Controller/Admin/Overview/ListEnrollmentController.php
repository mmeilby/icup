<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Overview;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Enrollment;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Services\Util;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

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
        
        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $utilService->validateClubUser($user);

        $tmnt = $this->get('entity')->getTournamentById($tournament);
        $club = $user->getClub();
        return $this->listEnrolled($tmnt, $club);
    }

    /**
     * Check for tournament before enroll
     * @Route("/club/enroll/check", name="_club_enroll_check")
     * @Method("GET")
     */
    public function checkAction() {
        
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
        /* @var $user User */
        $user = $utilService->getCurrentUser();
        /* @var $tmnt Tournament */
        $tmnt = $this->get('entity')->getTournamentById($tournament);
        $host = $tmnt->getHost();
        $utilService->validateEditorAdminUser($user, $host);
        $clb = $this->get('entity')->getClubById($club);
        return $this->listEnrolled($tmnt, $clb);
    }

    /**
     * Select club from list of matched club names rather than adding a new club
     * Current user must be an editor
     * NOTE: this action will be requested from javascript and can not be parameterized the traditional Symfony way
     * @Route("/edit/enroll/list/check", name="_host_select_club")
     * @Method("GET")
     */
    public function selectClubAction(Request $request)
    {
        $clubid = $request->get('clubid', '');
        $tournamentid = $request->get('tournamentid', '');
        return $this->redirect($this->generateUrl('_club_enroll_list_admin', array('tournament' => $tournamentid, 'club' => $clubid)));
    }

    private function listEnrolled(Tournament $tmnt, Club $club) {
        $host = $tmnt->getHost();
        $enrolled = $this->get('logic')->listEnrolledByClub($tmnt->getId(), $club->getId());

        $enrolledList = array();
        /* @var $enroll Enrollment */
        foreach ($enrolled as $enroll) {
            $enrolledList[$enroll->getCategory()->getId()][] = $enroll;
        }

        $classMap = array();
        $categoryMap = array();
        /* @var $category Category */
        foreach ($tmnt->getCategories() as $category) {
            $classification = $category->getClassification() . $category->getAge();
            $classMap[$classification] = $classification;
            $cls = $category->getGender() . $classification;
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

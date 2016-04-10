<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Rest\Admin\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\GroupOrder;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;
use ICup\Bundle\PublicSiteBundle\Services\Util;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class RestGroupPlanningController extends Controller
{
    /**
     * Assigns a team enrolled in a category to a specific group
     * @Route("/rest/assign/add/{teamid}/{groupid}", name="_rest_assign_add", options={"expose"=true})
     * @Method("GET")
     * @param $teamid
     * @param $groupid
     * @return Response
     */
    public function addAssignAction($teamid, $groupid) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        
        /* @var $user User */
        $user = $utilService->getCurrentUser();
        /* @var $group Group */
        $group = $this->get('entity')->getGroupById($groupid);
        /* @var $category Category */
        $category = $group->getCategory();
        /* @var $tournament Tournament */
        $tournament = $category->getTournament();
        $host = $tournament->getHost();
        $utilService->validateEditorAdminUser($user, $host);

        $this->get('logic')->assignEnrolled($teamid, $groupid);
        return new Response(json_encode(array('status' => 'OK')));
    }

    /**
     * Removes a team assigned to a specific group
     * @Route("/rest/assign/del/{teamid}/{groupid}", name="_rest_assign_del", options={"expose"=true})
     * @Method("GET")
     * @param $teamid
     * @param $groupid
     * @return Response
     */
    public function delAssignAction($teamid, $groupid) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        
        /* @var $user User */
        $user = $utilService->getCurrentUser();
        /* @var $group Group */
        $group = $this->get('entity')->getGroupById($groupid);
        /* @var $category Category */
        $category = $group->getCategory();
        /* @var $tournament Tournament */
        $tournament = $category->getTournament();
        $host = $tournament->getHost();
        $utilService->validateEditorAdminUser($user, $host);

        if ($this->get('logic')->isTeamInGame($groupid, $teamid)) {
            throw new ValidationException("TEAMISACTIVE", "Team has matchresults - team=".$teamid.", group=".$groupid);
        }
        elseif ($this->get('logic')->isTeamActive($groupid, $teamid)) {
            /* @var $groupOrder GroupOrder */
            $groupOrder = $this->get('logic')->assignVacant($groupid, $user);
            $this->get('logic')->moveMatches($groupid, $teamid, $groupOrder->getTeam()->getId());
        }
        $this->get('logic')->removeAssignment($teamid, $groupid);
        return new Response(json_encode(array('status' => 'OK')));
    }

    /**
     * Assigns a vacant spot to a specific group
     * @Route("/rest/assign/vacant/{groupid}", name="_rest_assign_vacant", options={"expose"=true})
     * @Method("GET")
     * @param $groupid
     * @return Response
     */
    public function addVacantAction($groupid) {
        /* @var $utilService Util */
        $utilService = $this->get('util');

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        /* @var $group Group */
        $group = $this->get('entity')->getGroupById($groupid);
        /* @var $category Category */
        $category = $group->getCategory();
        /* @var $tournament Tournament */
        $tournament = $category->getTournament();
        $host = $tournament->getHost();
        $utilService->validateEditorAdminUser($user, $host);

        $this->get('logic')->assignVacant($groupid, $user);
        return new Response(json_encode(array('status' => 'OK')));
    }

    /**
     * Removes a team enrolled to a specific category. The team must be unassigned.
     * The divisions of the club in this category will be reassigned.
     * @Route("/rest/enroll/del/{teamid}", name="_rest_enroll_del", options={"expose"=true})
     * @Method("GET")
     * @param $teamid
     * @return Response
     */
    public function delEnrollAction($teamid) {
        /* @var $utilService Util */
        $utilService = $this->get('util');

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        /* @var $category Category */
        $category = $this->get('logic')->getEnrolledCategory($teamid);
        /* @var $tournament Tournament */
        $tournament = $category->getTournament();
        $host = $tournament->getHost();
        $utilService->validateEditorAdminUser($user, $host);

        $this->get('logic')->removeEnrolled($teamid, $category->getId());
        return new Response(json_encode(array('status' => 'OK')));
    }
}

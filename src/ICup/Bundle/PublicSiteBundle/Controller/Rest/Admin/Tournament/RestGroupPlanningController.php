<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Rest\Admin\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\GroupOrder;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team;
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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use RuntimeException;

class RestGroupPlanningController extends Controller
{
    /**
     * Assigns a team enrolled in a category to a specific group
     * @Route("/rest/assign/add/{teamid}/{groupid}", name="_rest_assign_add", options={"expose"=true})
     * @Method("GET")
     * @param $teamid
     * @param $groupid
     * @return JsonResponse
     */
    public function addAssignAction($teamid, $groupid) {
        /* @var $team Team */
        try {
            $team = $this->get('entity')->getTeamById($teamid);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }
        try {
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
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }
        catch (RuntimeException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }
        try {
            $this->get('logic')->assignEnrolled($team->getId(), $group->getId());
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_BAD_REQUEST);
        }
        return new JsonResponse(array(), Response::HTTP_NO_CONTENT);
    }

    /**
     * Removes a team assigned to a specific group
     * @Route("/rest/assign/del/{teamid}/{groupid}", name="_rest_assign_del", options={"expose"=true})
     * @Method("GET")
     * @param $teamid
     * @param $groupid
     * @return JsonResponse
     */
    public function delAssignAction($teamid, $groupid) {
        /* @var $team Team */
        try {
            $team = $this->get('entity')->getTeamById($teamid);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }
        try {
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
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }
        catch (RuntimeException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }
        try {
            if ($this->get('logic')->isTeamInGame($group->getId(), $team->getId())) {
                throw new ValidationException("TEAMISACTIVE", "Team has matchresults - team=".$team->getId().", group=".$group->getId());
            }
            elseif ($this->get('logic')->isTeamActive($group->getId(), $team->getId())) {
                /* @var $groupOrder GroupOrder */
                $groupOrder = $this->get('logic')->assignVacant($group->getId(), $user);
                $this->get('logic')->moveMatches($group->getId(), $team->getId(), $groupOrder->getTeam()->getId());
            }
            $this->get('logic')->removeAssignment($team->getId(), $group->getId());
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_BAD_REQUEST);
        }
        return new JsonResponse(array(), Response::HTTP_NO_CONTENT);
    }

    /**
     * Assigns a vacant spot to a specific group
     * @Route("/rest/assign/vacant/{groupid}", name="_rest_assign_vacant", options={"expose"=true})
     * @Method("GET")
     * @param $groupid
     * @return JsonResponse
     */
    public function addVacantAction($groupid) {
        try {
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
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }
        catch (RuntimeException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }
        try {
            $this->get('logic')->assignVacant($group->getId(), $user);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_BAD_REQUEST);
        }
        return new JsonResponse(array(), Response::HTTP_NO_CONTENT);
    }

    /**
     * Removes a team enrolled to a specific category. The team must be unassigned.
     * The divisions of the club in this category will be reassigned.
     * @Route("/rest/enroll/del/{teamid}", name="_rest_enroll_del", options={"expose"=true})
     * @Method("GET")
     * @param $teamid
     * @return JsonResponse
     */
    public function delEnrollAction($teamid) {
        /* @var $team Team */
        try {
            $team = $this->get('entity')->getTeamById($teamid);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }
        try {
            /* @var $utilService Util */
            $utilService = $this->get('util');
            /* @var $user User */
            $user = $utilService->getCurrentUser();
            /* @var $category Category */
            $category = $this->get('logic')->getEnrolledCategory($team->getId());
            /* @var $tournament Tournament */
            $tournament = $category->getTournament();
            $host = $tournament->getHost();
            $utilService->validateEditorAdminUser($user, $host);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }
        catch (RuntimeException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }
        try {
            $this->get('logic')->removeEnrolled($team->getId(), $category->getId());
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_BAD_REQUEST);
        }
        return new JsonResponse(array(), Response::HTTP_NO_CONTENT);
    }
}

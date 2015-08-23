<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Tournament;

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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class GroupPlanningController extends Controller
{
    /**
     * List the clubs by groups assigned in the category
     * @Route("/edit/list/grps/{categoryid}", name="_host_list_groups")
     * @Template("ICupPublicSiteBundle:Host:listcategory.html.twig")
     */
    public function listByCategoryAction($categoryid, Request $request)
    {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        /* @var $user User */
        $user = $utilService->getCurrentUser();
        /* @var $category Category */
        $category = $this->get('entity')->getCategoryById($categoryid);
        /* @var $tournament Tournament */
        $tournament = $category->getTournament();
        $host = $tournament->getHost();
        $utilService->validateEditorAdminUser($user, $host);

        $groups = $this->get('logic')->listGroups($categoryid);
        $teamsUnassigned = $this->get('logic')->listTeamsEnrolledUnassigned($categoryid);
        $groupList = array();
        $selectedGroup = null;
        $preferredGroup = $this->getSelectedGroup($request);
        foreach ($groups as $group) {
            $teamsList = $this->get('logic')->listTeamsByGroup($group->getId());
            $groupList[$group->getName()] = array('group' => $group, 'teams' => $teamsList);
            if ($preferredGroup == $group->getId()) {
                $selectedGroup = $group;
            }
            elseif ($selectedGroup === null) {
                $selectedGroup = $group;
            }
        }
        return array('host' => $host,
                     'tournament' => $tournament,
                     'category' => $category,
                     'grouplist' => $groupList,
                     'unassigned' => $teamsUnassigned,
                     'selectedgroup' => $selectedGroup);
    }

    private function getSelectedGroup(Request $request) {
        /* @var $session Session */
        $session = $request->getSession();
        return $session->get('SelectedGroup', "0");
    }
    
    private function setSelectedGroup($selectedGroup, Request $request) {
        /* @var $session Session */
        $session = $request->getSession();
        $session->set('SelectedGroup', $selectedGroup);
    }

    /**
     * Select a group and unfold it for manipulation
     * @Route("/edit/assign/select/{groupid}", name="_host_assign_select_group")
     * @Method("GET")
     */
    public function selectAssignAction($groupid, Request $request) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        
        $returnUrl = $utilService->getReferer();
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

        $this->setSelectedGroup($groupid, $request);
        return $this->redirect($returnUrl);
    }
    
    /**
     * Assigns a team enrolled in a category to a specific group
     * @Route("/edit/assign/add/{teamid}/{groupid}", name="_host_assign_add")
     * @Method("GET")
     */
    public function addAssignAction($teamid, $groupid) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        
        $returnUrl = $utilService->getReferer();
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
        return $this->redirect($returnUrl);
    }
    
    /**
     * Removes a team assigned to a specific group
     * @Route("/edit/assign/del/{teamid}/{groupid}", name="_host_assign_del")
     * @Method("GET")
     */
    public function delAssignAction($teamid, $groupid) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        
        $returnUrl = $utilService->getReferer();
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
        return $this->redirect($returnUrl);
    }

    /**
     * Assigns a vacant spot to a specific group
     * @Route("/edit/assign/vacant/{groupid}", name="_host_assign_vacant")
     * @Method("GET")
     */
    public function addVacantAction($groupid) {
        /* @var $utilService Util */
        $utilService = $this->get('util');

        $returnUrl = $utilService->getReferer();
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
        return $this->redirect($returnUrl);
    }

    /**
     * Removes a team enrolled to a specific category. The team must be unassigned.
     * The divisions of the club in this category will be reassigned.
     * @Route("/edit/enroll/del/{teamid}", name="_host_enroll_del")
     * @Method("GET")
     */
    public function delEnrollAction($teamid) {
        /* @var $utilService Util */
        $utilService = $this->get('util');

        $returnUrl = $utilService->getReferer();
        /* @var $user User */
        $user = $utilService->getCurrentUser();
        /* @var $category Category */
        $category = $this->get('logic')->getEnrolledCategory($teamid);
        /* @var $tournament Tournament */
        $tournament = $category->getTournament();
        $host = $tournament->getHost();
        $utilService->validateEditorAdminUser($user, $host);

        $this->get('logic')->removeEnrolled($teamid, $category->getId());
        return $this->redirect($returnUrl);
    }
}

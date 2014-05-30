<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class GroupPlanningController extends Controller
{
    /**
     * List the clubs by groups assigned in the category
     * @Route("/edit/list/grps/{categoryid}", name="_host_list_groups")
     * @Template("ICupPublicSiteBundle:Host:listcategory.html.twig")
     */
    public function listByCategoryAction($categoryid)
    {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        /* @var $user User */
        $user = $utilService->getCurrentUser();
        /* @var $category Category */
        $category = $this->get('entity')->getCategoryById($categoryid);
        $tournament = $this->get('entity')->getTournamentById($category->getPid());
        $host = $this->get('entity')->getHostById($tournament->getPid());
        $utilService->validateEditorAdminUser($user, $tournament->getPid());

        $groups = $this->get('logic')->listGroups($categoryid);
        $teamsUnassigned = $this->get('logic')->listTeamsEnrolledUnassigned($categoryid);
        $groupList = array();
        $selectedGroup = null;
        $preferredGroup = $this->getSelectedGroup();
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

    private function getSelectedGroup() {
        /* @var $request Request */
        $request = $this->getRequest();
        /* @var $session Session */
        $session = $request->getSession();
        return $session->get('SelectedGroup', "0");
    }
    
    private function setSelectedGroup($selectedGroup) {
        /* @var $request Request */
        $request = $this->getRequest();
        /* @var $session Session */
        $session = $request->getSession();
        $session->set('SelectedGroup', $selectedGroup);
    }

    /**
     * Select a group and unfold it for manipulation
     * @Route("/edit/assign/select/{groupid}", name="_host_assign_select_group")
     * @Method("GET")
     */
    public function selectAssignAction($groupid) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        
        $returnUrl = $utilService->getReferer();
        /* @var $user User */
        $user = $utilService->getCurrentUser();
        /* @var $group Group */
        $group = $this->get('entity')->getGroupById($groupid);
        /* @var $category Category */
        $category = $this->get('entity')->getCategoryById($group->getPid());
        $tournament = $this->get('entity')->getTournamentById($category->getPid());
        $utilService->validateEditorAdminUser($user, $tournament->getPid());

        $this->setSelectedGroup($groupid);
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
        $category = $this->get('entity')->getCategoryById($group->getPid());
        $tournament = $this->get('entity')->getTournamentById($category->getPid());
        $utilService->validateEditorAdminUser($user, $tournament->getPid());

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
        $category = $this->get('entity')->getCategoryById($group->getPid());
        $tournament = $this->get('entity')->getTournamentById($category->getPid());
        $utilService->validateEditorAdminUser($user, $tournament->getPid());

        $this->get('logic')->removeEnrolled($teamid, $groupid);
        return $this->redirect($returnUrl);
    }
}

<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Host;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Host;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;

class HostTournamentController extends Controller
{
    /**
     * List the tournaments available for current editor
     * @Route("/host/list/tournaments", name="_host_list_tournaments")
     * @Method("GET")
     * @Template("ICupPublicSiteBundle:Host:listtournaments.html.twig")
     */
    public function listAction() {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $utilService->setupController();

        try {
            /* @var $user User */
            $user = $utilService->getCurrentUser();
            // Validate current user - is it an editor?
            $utilService->validateHostUser($user);
            // Get the host from current user
            $hostid = $user->getPid();
            $host = $this->get('entity')->getHostById($hostid);
            // Find list of tournaments for this host
            $tournaments = $this->get('entity')->getTournamentRepo()
                                ->findBy(array('pid' => $hostid), array('name' => 'asc'));
            return array('tournaments' => $tournaments, 'host' => $host);
        } catch (ValidationException $vexc) {
            return $this->render('ICupPublicSiteBundle:Errors:' . $vexc->getMessage(), array('redirect' => $this->generateUrl('_user_my_page')));
        } 
    }

    /**
     * List the clubs enrolled for a tournament
     * @Route("/host/list/clubs/{tournamentid}", name="_host_list_clubs")
     * @Method("GET")
     * @Template("ICupPublicSiteBundle:Host:listclubs.html.twig")
     */
    public function listClubsAction($tournamentid) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $utilService->setupController();

        try {
            /* @var $user User */
            $user = $utilService->getCurrentUser();
            // Validate current user - is it an editor?
            $utilService->validateHostUser($user);
            // Get the host from current user
            $hostid = $user->getPid();
            $host = $this->get('entity')->getHostById($hostid);
            
            $tmnt = $this->get('entity')->getTournamentById($tournamentid);
            
            if ($tmnt->getPid() != $hostid) {
                throw new ValidationException("noteditoradmin.html.twig");
            }
            
            $clubs = $this->get('logic')->listEnrolled($tmnt->getId());
            $teamcount = 0;
            $teamList = array();
            foreach ($clubs as $clb) {
                $club = $clb['club'];
                $country = $club->getCountry();
                $teamList[$country][$club->getId()] = $clb;
                $teamcount++;
            }

            $teamcount /= 2;
            $teamColumns = array();
            $ccount = 0;
            $column = 0;
            foreach ($teamList as $country => $clubs) {
                $teamColumns[$column][] = array($country => $clubs);
                $ccount += count($clubs);
                if ($ccount > $teamcount && $column < 1) {
                    $column++;
                    $ccount = 0;
                }
            }
            return array('host' => $host, 'tournament' => $tmnt, 'teams' => $teamColumns);
        } catch (ValidationException $vexc) {
            return $this->render('ICupPublicSiteBundle:Errors:' . $vexc->getMessage(), array('redirect' => $this->generateUrl('_user_my_page')));
        } 
    }
    
    /**
     * List the clubs by groups assigned in the category
     * @Route("/host/list/grps/{categoryid}", name="_host_list_groups")
     * @Template("ICupPublicSiteBundle:Host:listcategory.html.twig")
     */
    public function listByCategoryAction($categoryid)
    {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $utilService->setupController();

        try {
            /* @var $user User */
            $user = $utilService->getCurrentUser();
            /* @var $category Category */
            $category = $this->get('entity')->getCategoryById($categoryid);
            $tournament = $this->get('entity')->getTournamentById($category->getPid());
            $host = $this->get('entity')->getHostById($tournament->getPid());
            if (!$utilService->isAdminUser($user)) {
                // Validate current user - is it an editor?
                $this->validateEditorUser($user, $tournament->getPid());
            }

            $groups = $this->get('logic')->listGroups($category);
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
        } catch (ValidationException $vexc) {
            return $this->render('ICupPublicSiteBundle:Errors:' . $vexc->getMessage(), array('redirect' => $this->generateUrl('_user_my_page')));
        }
    }

    private function validateEditorUser($user, $hostid) {
        $this->get('util')->validateHostUser($user);
        if ($user->getPid() != $hostid) {
            throw new ValidationException("noteditoradmin.html.twig");
        }
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
     * @Route("/host/assign/select/{groupid}", name="_host_assign_select_group")
     * @Method("GET")
     */
    public function selectAssignAction($groupid) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $utilService->setupController();
        try {
            /* @var $user User */
            $user = $utilService->getCurrentUser();
            /* @var $group Group */
            $group = $this->get('entity')->getGroupById($groupid);
            /* @var $category Category */
            $category = $this->get('entity')->getCategoryById($group->getPid());
            $tournament = $this->get('entity')->getTournamentById($category->getPid());
            
            if (!$utilService->isAdminUser($user)) {
                // Validate current user - is it an editor?
                $this->validateEditorUser($user, $tournament->getPid());
            }
            
            $this->setSelectedGroup($groupid);
            return $this->redirect(
                    $this->generateUrl('_host_list_groups', 
                                        array('categoryid' => $category->getId())));
            
        } catch (ValidationException $vexc) {
            return $this->render('ICupPublicSiteBundle:Errors:' . $vexc->getMessage(),
                                 array('redirect' => $this->generateUrl('_user_my_page')));
        } 
    }
    
    /**
     * Assigns a team enrolled in a category to a specific group
     * @Route("/host/assign/add/{teamid}/{groupid}", name="_host_assign_add")
     * @Method("GET")
     */
    public function addAssignAction($teamid, $groupid) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $utilService->setupController();

        try {
            /* @var $user User */
            $user = $utilService->getCurrentUser();
            /* @var $group Group */
            $group = $this->get('entity')->getGroupById($groupid);
            /* @var $category Category */
            $category = $this->get('entity')->getCategoryById($group->getPid());
            $tournament = $this->get('entity')->getTournamentById($category->getPid());
            
            if (!$utilService->isAdminUser($user)) {
                // Validate current user - is it an editor?
                $this->validateEditorUser($user, $tournament->getPid());
            }
            
            $this->get('logic')->assignEnrolled($teamid, $groupid);
            return $this->redirect(
                    $this->generateUrl('_host_list_groups', 
                                        array('categoryid' => $category->getId())));
            
        } catch (ValidationException $vexc) {
            return $this->render('ICupPublicSiteBundle:Errors:' . $vexc->getMessage(),
                                 array('redirect' => $this->generateUrl('_user_my_page')));
        } 
    }
    
    /**
     * Removes a team assigned to a specific group
     * @Route("/host/assign/del/{teamid}/{groupid}", name="_host_assign_del")
     * @Method("GET")
     */
    public function delAssignAction($teamid, $groupid) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $utilService->setupController();

        try {
            /* @var $user User */
            $user = $utilService->getCurrentUser();
            /* @var $group Group */
            $group = $this->get('entity')->getGroupById($groupid);
            /* @var $category Category */
            $category = $this->get('entity')->getCategoryById($group->getPid());
            $tournament = $this->get('entity')->getTournamentById($category->getPid());
            
            if (!$utilService->isAdminUser($user)) {
                // Validate current user - is it an editor?
                $this->validateEditorUser($user, $tournament->getPid());
            }
            
            $this->get('logic')->removeEnrolled($teamid, $groupid);
            return $this->redirect(
                    $this->generateUrl('_host_list_groups', 
                                        array('categoryid' => $category->getId())));
            
        } catch (ValidationException $vexc) {
            return $this->render('ICupPublicSiteBundle:Errors:' . $vexc->getMessage(),
                                 array('redirect' => $this->generateUrl('_user_my_page')));
        } 
    }
}

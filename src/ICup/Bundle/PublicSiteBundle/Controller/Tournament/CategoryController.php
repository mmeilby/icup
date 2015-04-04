<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Tournament;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class CategoryController extends Controller
{
    /**
     * @Route("/tmnt/ctgr/{categoryid}/prm", name="_showcategory")
     * @Template("ICupPublicSiteBundle:Tournament:category.html.twig")
     */
    public function listAction($categoryid)
    {
        $category = $this->get('entity')->getCategoryById($categoryid);
        $tournament = $this->get('entity')->getTournamentById($category->getPid());
        $groups = $this->get('logic')->listGroups($categoryid);
        $groupList = array();
        foreach ($groups as $group) {
            $teamsList = $this->get('orderTeams')->sortGroup($group->getId());
            $groupList[$group->getName()] = array('group' => $group, 'teams' => $teamsList);
        }
        $grpc = $this->get('logic')->listGroupsClassification($categoryid);
        $grpf = $this->get('logic')->listGroupsFinals($categoryid);
        return array(
            'tournament' => $tournament,
            'category' => $category,
            'grouplist' => $groupList,
            'classifications' => count($grpc),
            'finals' => count($grpf));
    }
    
    /**
     * @Route("/tmnt/ctgr/{categoryid}/clss", name="_showcategory_classification")
     * @Template("ICupPublicSiteBundle:Tournament:category_class.html.twig")
     */
    public function listClassAction($categoryid)
    {
        $category = $this->get('entity')->getCategoryById($categoryid);
        $tournament = $this->get('entity')->getTournamentById($category->getPid());
        $groups = $this->get('logic')->listGroupsClassification($categoryid);
        $groupList = array();
        foreach ($groups as $group) {
            $teamsList = $this->get('orderTeams')->sortGroup($group->getId());
            $groupList[$group->getId()] = array('group' => $group, 'teams' => $teamsList);
        }
        $grpf = $this->get('logic')->listGroupsFinals($categoryid);
        return array(
            'tournament' => $tournament,
            'category' => $category,
            'grouplist' => $groupList,
            'finals' => count($grpf));
    }
    
    /**
     * @Route("/tmnt/ctgr/{categoryid}/fnls", name="_showcategory_finals")
     * @Template("ICupPublicSiteBundle:Tournament:category_finals_test.html.twig")
     */
    public function listFinalsAction($categoryid)
    {
        $category = $this->get('entity')->getCategoryById($categoryid);
        $tournament = $this->get('entity')->getTournamentById($category->getPid());
        $groups = $this->get('logic')->listGroupsFinals($categoryid);
        $groupList = array();
        $pyramid = array();
        foreach ($groups as $group) {
            // make list for small devices
            $teamsList = $this->get('orderTeams')->sortGroup($group->getId());
            $groupList[$group->getId()] = array('group' => $group, 'teams' => $teamsList);
            // make list for larger devices
            $matchList = $this->get('Match')->listMatchesByGroup($group->getId());
            $this->updateLists($pyramid, $group, $matchList);
        }
        // count number of playoff matches - if any - required to show the playoff tab
        $grpc = $this->get('logic')->listGroupsClassification($categoryid);
        return array(
            'tournament' => $tournament,
            'category' => $category,
            'grouplist' => $groupList,              // list for small devices
            'pyramid' => $this->fold($pyramid),     // list for larger devices
            'classifications' => count($grpc));
    }
    
    private function updateLists(&$pyramid, $group, $matchList) {
        foreach ($matchList as &$match) {
            $match['group'] = $group;
            switch ($group->getClassification()) {
                case 10:
                    $pyramid['F'] = $match;
                    break;
                case 9:
                    $pyramid['B'] = $match;
                    break;
                default:
                    $this->crawl($pyramid['F'], 8 - $group->getClassification(), $match);
                    break;
            }
        }
    }
    
    private function crawl(&$pyramid, $key, $match) {
        if ($key == 0) {
            if ($pyramid['home']['id'] == $match['home']['id'] || $pyramid['home']['id'] == $match['away']['id']) {
                $pyramid['home']['L'] = $match;
            }
            elseif ($pyramid['away']['id'] == $match['home']['id'] || $pyramid['away']['id'] == $match['away']['id']) {
                $pyramid['away']['L'] = $match;
            }
        }
        else {
            $this->crawl($pyramid['home']['L'], $key - 1, $match);
            $this->crawl($pyramid['away']['L'], $key - 1, $match);
        }
    }
    
    private function fold($pyramid) {
        $left = $this->unfold($pyramid['F']['home'], $pyramid['F']);
        $right = $this->unfold($pyramid['F']['away'], $pyramid['F']);
        $teams = array();
        foreach ($left as $team) {
            $teams[] = array('left' => $team, 'right' => $right[count($teams)]);
        }
        $finals = array(
            'teams' => $teams,
            'levels' => 0,
            'bronze' => array_key_exists('B', $pyramid) ? $pyramid['B'] : null
        );
        $this->populateResults($finals, $pyramid['F'], 0, 'F');
        return $finals;
    }
    
    private function unfold($pyramid, $match) {
        if (array_key_exists('L', $pyramid)) {
            return array_merge(
                $this->unfold($pyramid['L']['home'], $pyramid['L']),
                $this->unfold($pyramid['L']['away'], $pyramid['L'])
            );
        }
        else {
            return array(array('team' => $pyramid, 'group' => $match['group']));
        }
    }
    
    private function populateResults(&$finals, $pyramid, $level, $wing) {
        if ($finals['levels'] < $level+1) {
            $finals['levels'] = $level+1;
        }
        $finals[$wing] = array_merge(array_diff_key($pyramid, array('home' => '', 'away' => '')),
                                      array('home' => array_diff_key($pyramid['home'], array('L' => ''))),
                                      array('away' => array_diff_key($pyramid['away'], array('L' => ''))));
        if (array_key_exists('L', $pyramid['home'])) {
            $this->populateResults($finals, $pyramid['home']['L'], $level + 1, $wing.'H');
        }
        if (array_key_exists('L', $pyramid['away'])) {
            $this->populateResults($finals, $pyramid['away']['L'], $level + 1, $wing.'A');
        }
    }
}

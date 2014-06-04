<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Tournament;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class CategoryController extends Controller
{
    /**
     * @Route("/tmnt/{tournament}/ctgr/{categoryid}/prm", name="_showcategory")
     * @Template("ICupPublicSiteBundle:Tournament:category.html.twig")
     */
    public function listAction($tournament, $categoryid)
    {
        $this->get('util')->setTournamentKey($tournament);
        $tournament = $this->get('util')->getTournament();
        $category = $this->get('entity')->getCategoryById($categoryid);
        $groups = $this->get('logic')->listGroups($categoryid);
        $groupList = array();
        foreach ($groups as $group) {
            $teamsList = $this->get('orderTeams')->sortGroup($group->getId());
            $groupList[$group->getName()] = array('group' => $group, 'teams' => $teamsList);
        }
        return array('tournament' => $tournament, 'category' => $category, 'grouplist' => $groupList);
    }
    
    /**
     * @Route("/tmnt/{tournament}/ctgr/{categoryid}/clss", name="_showcategory_classification")
     * @Template("ICupPublicSiteBundle:Tournament:category_class.html.twig")
     */
    public function listClassAction($tournament, $categoryid)
    {
        $this->get('util')->setTournamentKey($tournament);
        $tournament = $this->get('util')->getTournament();
        $category = $this->get('entity')->getCategoryById($categoryid);
        $groups = $this->get('logic')->listGroupsClassification($categoryid);
        $groupList = array();
        foreach ($groups as $group) {
            $teamsList = $this->get('orderTeams')->sortGroup($group->getId());
            $groupList[$group->getId()] = array('group' => $group, 'teams' => $teamsList);
        }
        return array('tournament' => $tournament, 'category' => $category, 'grouplist' => $groupList);
    }
    
    /**
     * @Route("/tmnt/{tournament}/ctgr/{categoryid}/fnls", name="_showcategory_finals")
     * @Template("ICupPublicSiteBundle:Tournament:category_finals.html.twig")
     */
    public function listFinalsAction($tournament, $categoryid)
    {
        $this->get('util')->setTournamentKey($tournament);
        $tournament = $this->get('util')->getTournament();
        $category = $this->get('entity')->getCategoryById($categoryid);
        $groups = $this->get('logic')->listGroupsFinals($categoryid);
        $classes = array(10 => 'F', 9 => 'B', 8 => 'S');
        $champions = array();
        foreach ($groups as $group) {
            $matchList = $this->get('Match')->listMatchesByGroup($group->getId());
            foreach ($matchList as &$match) {
                $match['group'] = $group;
            }
            $key = $classes[$group->getClassification()];
            if (array_key_exists($key, $champions)) {
                $champions[$key] = array_merge($champions[$key], $matchList);
            }
            else {
                $champions[$key] = $matchList;
            }
/*            
            $teamsList = $this->get('orderTeams')->sortGroup($group->getId());
            foreach ($teamsList as $team) {
                $keys = array();
                switch ($group->getClassification()) {
                    case 10:
                        // finals
                        $keys = array('FA', 'FB');
                        break;
                    case 9:
                        // 3/4 position
                        $keys = array('FC', 'FD');
                        break;
                    case 8:
                        // semifinals
                        $keys = array('SA', 'SB', 'SC', 'SD');
                        break;
                }
                foreach ($keys as $key) {
                    if ($champions[$key] == null) {
                        $champions[$key] = $team;
                        break;
                    }
                }
            }
 * 
 */
        }
        return array('tournament' => $tournament, 'category' => $category, 'champions' => $champions);
    }
}

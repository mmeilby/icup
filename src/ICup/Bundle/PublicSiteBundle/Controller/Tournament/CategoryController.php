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
     * @Template("ICupPublicSiteBundle:Tournament:category_finals.html.twig")
     */
    public function listFinalsAction($categoryid)
    {
        $category = $this->get('entity')->getCategoryById($categoryid);
        $tournament = $this->get('entity')->getTournamentById($category->getPid());
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
        }
        $grpc = $this->get('logic')->listGroupsClassification($categoryid);
        return array(
            'tournament' => $tournament,
            'category' => $category,
            'champions' => $champions,
            'classifications' => count($grpc));
    }
}

<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Rest\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;
use ICup\Bundle\PublicSiteBundle\Entity\TeamInfo;
use ICup\Bundle\PublicSiteBundle\Services\Util;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class RestGroupController extends Controller
{
    /**
     * Get the playground identified by playground id
     * @Route("/rest/group/list/{categoryid}", name="_rest_list_groups", options={"expose"=true})
     */
    public function restListGroupAction($categoryid)
    {
        /* @var $category Category */
        $category = $this->get('entity')->getCategoryById($categoryid);
        $groups = $category->getGroupsClassified(Group::$PRE);
        return new Response(json_encode($groups));
    }

    /**
     * List the clubs by groups assigned in the category
     * @Route("/rest/group/list/assigned/{categoryid}", name="_rest_list_groups_with_teams", options={"expose"=true})
     */
    public function restListTeamsByGroupAction($categoryid)
    {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        /* @var $category Category */
        $category = $this->get('entity')->getCategoryById($categoryid);

        $groups = array();
        foreach ($category->getGroupsClassified(Group::$PRE) as $group) {
            $teams = array();
            foreach ($this->get('logic')->listTeamsByGroup($group->getId()) as $team) {
                /* @var $team TeamInfo */
                $teams[] = array(
                    'id' => $team->getId(),
                    'name' => $team->getName(),
                    'country' => $this->get('translator')->trans($team->getCountry(), array(), 'lang'),
                    'flag' => $utilService->getFlag($team->getCountry())
                );
            }
            $groups[] = array('group' => $group, 'teams' => $teams);
        }
        $teamsUnassigned = array();
        foreach ($this->get('logic')->listTeamsEnrolledUnassigned($categoryid) as $team) {
            /* @var $team TeamInfo */
            $teamsUnassigned[] = array(
                'id' => $team->getId(),
                'name' => $team->getName(),
                'country' => $this->get('translator')->trans($team->getCountry(), array(), 'lang'),
                'flag' => $utilService->getFlag($team->getCountry())
            );
        }
        return new Response(json_encode(array('groups' => $groups, 'unassigned' => $teamsUnassigned)));
    }
}

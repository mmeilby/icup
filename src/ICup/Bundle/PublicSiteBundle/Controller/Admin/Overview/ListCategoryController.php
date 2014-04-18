<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Overview;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ICup\Bundle\PublicSiteBundle\Services\Util;

/**
 * List the categories and groups available
 */
class ListCategoryController extends Controller
{
    /**
     * List the items available for a tournament
     * @Route("/edit/category/list/{tournamentid}", name="_edit_category_list")
     * @Method("GET")
     * @Template("ICupPublicSiteBundle:Host:listcategories.html.twig")
     */
    public function listAction($tournamentid) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $utilService->setupController();

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $tournament = $this->get('entity')->getTournamentById($tournamentid);
        $utilService->validateEditorAdminUser($user, $tournament->getPid());

        $host = $this->get('entity')->getHostById($tournament->getPid());
        $categories = $this->get('logic')->listCategories($tournamentid);
        $groups = $this->get('logic')->listGroupsByTournament($tournamentid);
        $groupList = array();
        foreach ($groups as $group) {
            $groupList[$group->getPid()][] = $group;
        }
        return array('host' => $host, 'tournament' => $tournament, 'groups' => $groupList, 'categories' => $categories);
    }
}

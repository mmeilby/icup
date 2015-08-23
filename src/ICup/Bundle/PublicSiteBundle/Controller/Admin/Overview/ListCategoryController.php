<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Overview;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
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
        /* @var $user User */
        $user = $utilService->getCurrentUser();
        /* @var $tournament Tournament */
        $tournament = $this->get('entity')->getTournamentById($tournamentid);
        $host = $tournament->getHost();
        $utilService->validateEditorAdminUser($user, $host);
        $categories = $tournament->getCategories();
        $groups = $this->get('logic')->listGroupsByTournament($tournamentid);
        $groupList = array();
        /* @var $group Group */
        foreach ($groups as $group) {
            $groupList[$group->getCategory()->getId()][] = $group;
        }
        return array('host' => $host, 'tournament' => $tournament, 'groups' => $groupList, 'categories' => $categories);
    }
}

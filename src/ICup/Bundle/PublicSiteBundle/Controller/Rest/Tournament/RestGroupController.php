<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Rest\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;
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
}

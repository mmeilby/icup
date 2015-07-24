<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Rest\Tournament;

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
        $groups = $this->get('logic')->listGroups($categoryid);
        return new Response(json_encode($groups));
    }
}

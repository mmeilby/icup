<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Rest\Tournament;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class RestPlaygroundController extends Controller
{
    /**
     * Get the playground identified by playground id
     * @Route("/rest/admin/playground/get/{playgroundid}", name="_rest_get_playground", options={"expose"=true})
     */
    public function restGetPlaygroundAction($playgroundid)
    {
        // Validate that user is logged in...
        $this->get('util')->getCurrentUser();
        
        $playground = $this->get('entity')->getPlaygroundById($playgroundid);
        return new Response(json_encode(
            array('id' => $playground->getId(),
                  'name' => $playground->getName(),
                  'no' => $playground->getNo())
                ));
    }
}

<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Rest\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Playground;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class RestPlaygroundController extends Controller
{
    /**
     * Get the venue identified by playground id
     * @Route("/rest/playground/get/{playgroundid}", name="_rest_get_playground", options={"expose"=true})
     * @param $playgroundid
     * @return Response
     */
    public function restGetPlaygroundAction($playgroundid)
    {
        /* @var $playground Playground */
        $playground = $this->get('entity')->getPlaygroundById($playgroundid);
        return new Response(json_encode($playground));
    }

    /**
     * List the venues identified by tournament id
     * @Route("/rest/playground/list/{tournamentid}", name="_rest_list_playgrounds", options={"expose"=true})
     * @param $tournamentid
     * @return Response
     */
    public function restListPlaygroundAction($tournamentid)
    {
        /* @var $tournament Tournament */
        $tournament = $this->get('entity')->getTournamentById($tournamentid);
        return new Response(json_encode($tournament->getPlaygrounds()));
    }
}

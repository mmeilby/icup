<?php

namespace APIBundle\Controller\Tournament;

use APIBundle\Controller\APIController;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Host;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Doctrine\Tournament controller.
 *
 * @Route("/tournament")
 */
class APITournamentController extends APIController
{
    /**
     * List all the tournaments connected to the host identified by APIkey
     * @Route("/", name="_api_list_tournaments", options={"expose"=true})
     * @Method("GET")
     * @return JsonResponse
     */
    public function indexAction(Request $request)
    {
        return $this->executeAPImethod($request, function (User $user, Host $host) {
            return new JsonResponse($host->getTournaments()->getValues());
        });
    }

    /**
     * Finds and displays a Doctrine\Tournament entity.
     *
     * @Route("/{key}", name="_api_get_tournament", options={"expose"=true})
     * @Method("GET")
     * @param $key
     * @return JsonResponse
     */
    public function showAction($key, Request $request)
    {
        return $this->executeAPImethod($request, function (User $user, Host $host) use ($key) {
            /* @var $tournament Tournament */
            $tournament = $this->get('logic')->getTournamentByKey($key);
            if ($tournament == null || $tournament->getHost()->getId() != $host->getId()) {
                return $this->makeErrorObject("TMNTINV", "Tournament is not found for this host.", Response::HTTP_NOT_FOUND);
            }
            return new JsonResponse($tournament);
        });
    }
}


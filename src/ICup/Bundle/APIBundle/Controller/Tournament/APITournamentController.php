<?php

namespace APIBundle\Controller\Tournament;

use APIBundle\Controller\APIController;
use APIBundle\Entity\Form\GetKeyType;
use APIBundle\Entity\GetKeyForm;
use APIBundle\Entity\Wrapper\Doctrine\TournamentWrapper;
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
     * @Route("/", name="_api_tournament")
     * @Method("POST")
     * @return JsonResponse
     */
    public function indexAction(Request $request)
    {
        $keyForm = new GetKeyForm();
        $form = $this->createForm(new GetKeyType(), $keyForm);
        $form->handleRequest($request);
        if ($keyForm->checkForm($form)) {
            return $this->executeAPImethod($request, function (User $user, Host $host) use ($keyForm) {
                /* @var $tournament Tournament */
                $tournament = $this->get('logic')->getTournamentByKey($keyForm->getKey());
                if ($tournament == null || $tournament->getHost()->getId() != $host->getId()) {
                    return $this->makeErrorObject("TMNTINV", "Tournament is not found for this host.", Response::HTTP_NOT_FOUND);
                }
                return new JsonResponse(new TournamentWrapper($tournament));
            });
        }
        else {
            return $this->executeAPImethod($request, function (User $user, Host $host) {
                return new JsonResponse(new TournamentWrapper($host->getTournaments()->getValues()));
            });
        }
    }
}


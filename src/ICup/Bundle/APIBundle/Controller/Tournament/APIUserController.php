<?php

namespace APIBundle\Controller\Tournament;

use APIBundle\Controller\APIController;
use APIBundle\Entity\Form\GetCombinedKeyType;
use APIBundle\Entity\Wrapper\Doctrine\HostWrapper;
use APIBundle\Entity\Wrapper\Doctrine\SiteWrapper;
use APIBundle\Entity\Wrapper\Doctrine\TournamentWrapper;
use APIBundle\Entity\Wrapper\Doctrine\UserWrapper;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Doctrine\Site controller.
 *
 */
class APIUserController extends APIController
{
    /**
     * Checks the user login and returns role and host connection
     * @Route("/v1/user", name="_api_user")
     * @Method("POST")
     * @return JsonResponse
     */
    public function indexAction(Request $request) {
        $keyForm = $this->getKeyForm($request);
        $form = $this->createForm(new GetCombinedKeyType(), $keyForm);
        $form->handleRequest($request);
        if ($keyForm->checkForm($form)) {
            return $this->executeAPImethod($request, function (APIController $api) use ($keyForm) {
                if ($keyForm->getEntity() === "Host") {
                    return new JsonResponse(array(
                        "host" => new HostWrapper($api->host),
                        "user" => new UserWrapper($api->user)
                    ));
                }
                else {
                    return $api->makeErrorObject("KEYINV", "Key is not found for this host and entity.", Response::HTTP_NOT_FOUND);
                }
            });
        }
        else {
            return $this->makeErrorObject("KEYMISS", "Key and entity must be defined for this request.", Response::HTTP_NOT_FOUND);
        }
    }
}

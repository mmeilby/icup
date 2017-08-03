<?php

namespace APIBundle\Controller\Tournament;

use APIBundle\Controller\APIController;
use APIBundle\Entity\Form\GetCombinedKeyType;
use APIBundle\Entity\Wrapper\Doctrine\MatchWrapper;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Playground;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Doctrine\Match controller.
 *
 */
class APIMatchController extends APIController
{
    /**
     * List the matches for the host identified by APIkey
     * @Route("/v1/match", name="_api_match")
     * @Method("POST")
     * @return JsonResponse
     */
    public function indexAction(Request $request)
    {
        $keyForm = $this->getKeyForm($request);
        $form = $this->createForm(new GetCombinedKeyType(), $keyForm);
        $form->handleRequest($request);
        if ($keyForm->checkForm($form)) {
            return $this->executeAPImethod($request, function (APIController $api) use ($keyForm) {
                $entity = $api->get('entity')->getEntityByExternalKey($keyForm->getEntity(), $keyForm->getKey());
                if ($entity instanceof Tournament) {
                    /* @var $tournament Tournament */
                    $tournament = $entity;
                    if ($tournament->getHost()->getId() != $api->host->getId()) {
                        return $api->makeErrorObject("TMNTINV", "Tournament is not found for this host.", Response::HTTP_NOT_FOUND);
                    }
                    return new JsonResponse(new MatchWrapper($tournament->getMatches()));
                }
                else if ($entity instanceof Group) {
                    /* @var $group Group */
                    $group = $entity;
                    if ($group->getCategory()->getTournament()->getHost()->getId() != $api->host->getId()) {
                        return $api->makeErrorObject("GRPINV", "Group is not found for this host.", Response::HTTP_NOT_FOUND);
                    }
                    return new JsonResponse(new MatchWrapper($group->getMatches()->getValues()));
                }
                else if ($entity instanceof Playground) {
                    /* @var $playground Playground */
                    $playground = $entity;
                    if ($playground->getSite()->getTournament()->getHost()->getId() != $api->host->getId()) {
                        return $api->makeErrorObject("VENINV", "Venue is not found for this host.", Response::HTTP_NOT_FOUND);
                    }
                    return new JsonResponse(new MatchWrapper($playground->getMatches()->getValues()));
                }
                else if ($entity instanceof Match) {
                    /* @var $match Match */
                    $match = $entity;
                    if ($match->getGroup()->getCategory()->getTournament()->getHost()->getId() != $api->host->getId()) {
                        return $api->makeErrorObject("MCHINV", "Match is not found for this host.", Response::HTTP_NOT_FOUND);
                    }
                    return new JsonResponse(new MatchWrapper($match));
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

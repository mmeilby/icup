<?php

namespace APIBundle\Controller\Tournament;

use APIBundle\Controller\APIController;
use APIBundle\Entity\Form\GetCombinedKeyType;
use APIBundle\Entity\GetCombinedKeyForm;
use APIBundle\Entity\Wrapper\Doctrine\GroupWrapper;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Doctrine\Group controller.
 *
 * @Route("/group")
 */
class APIGroupController extends APIController
{
    /**
     * List all the tournaments connected to the host identified by APIkey
     * @Route("/", name="_api_group")
     * @Method("POST")
     * @return JsonResponse
     */
    public function indexAction(Request $request)
    {
        $keyForm = new GetCombinedKeyForm();
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
                    $response = array_map(function (Category $category) {
                        return new GroupWrapper($category->getGroups()->getValues());
                    }, $tournament->getCategories()->getValues());
                    return new JsonResponse($response);
                }
                else if ($entity instanceof Category) {
                    /* @var $category Category */
                    $category = $entity;
                    if ($category->getTournament()->getHost()->getId() != $api->host->getId()) {
                        return $api->makeErrorObject("CATINV", "Category is not found for this host.", Response::HTTP_NOT_FOUND);
                    }
                    return new JsonResponse(new GroupWrapper($category->getGroups()->getValues()));
                }
                else if ($entity instanceof Group) {
                    /* @var $group Group */
                    $group = $entity;
                    if ($group->getCategory()->getTournament()->getHost()->getId() != $api->host->getId()) {
                        return $api->makeErrorObject("GROUPINV", "Group is not found for this host.", Response::HTTP_NOT_FOUND);
                    }
                    return new JsonResponse(new GroupWrapper($group));
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

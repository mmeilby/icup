<?php

namespace APIBundle\Controller\Tournament;

use APIBundle\Controller\APIController;
use APIBundle\Entity\Form\GetCombinedKeyType;
use APIBundle\Entity\Wrapper\Doctrine\CategoryWrapper;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Doctrine\Category controller.
 *
 */
class APICategoryController extends APIController
{
    /**
     * List all the tournaments connected to the host identified by APIkey
     * @Route("/v1/category", name="_api_category")
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
                    $categories = $tournament->getCategories()->getValues();
                    usort($categories, function (Category $category1, Category $category2) {
                        $classification1 = $category1->getGender() . $category1->getClassification() . $category1->getAge();
                        $classification2 = $category2->getGender() . $category2->getClassification() . $category2->getAge();
                        return $classification1 > $classification2 ? 1 : -1;
                    });
                    return new JsonResponse(new CategoryWrapper($categories));
                }
                else if ($entity instanceof Category) {
                    /* @var $category Category */
                    $category = $entity;
                    if ($category->getTournament()->getHost()->getId() != $api->host->getId()) {
                        return $api->makeErrorObject("CATINV", "Category is not found for this host.", Response::HTTP_NOT_FOUND);
                    }
                    return new JsonResponse(new CategoryWrapper($category));
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


<?php

namespace APIBundle\Controller\Tournament;

use APIBundle\Controller\APIController;
use APIBundle\Entity\Form\GetCombinedKeyType;
use APIBundle\Entity\GetCombinedKeyForm;
use APIBundle\Entity\Wrapper\Doctrine\NewsWrapper;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;
use ICup\Bundle\PublicSiteBundle\Services\Util;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\News;
use ICup\Bundle\PublicSiteBundle\Form\Doctrine\NewsType;
use RuntimeException;

/**
 * Doctrine\News controller.
 *
 */
class APINewsController extends APIController
{
    /**
     * List the news for the tournament and host identified by APIkey
     * @Route("/v1/news", name="_api_news")
     * @Method("POST")
     * @return JsonResponse
     */
    public function indexAction(Request $request) {
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
                    return new JsonResponse(new NewsWrapper($tournament->getNews()->getValues()));
                } else {
                    return $api->makeErrorObject("KEYINV", "Key is not found for this host and entity.", Response::HTTP_NOT_FOUND);
                }
            });
        } else {
            return $this->makeErrorObject("KEYMISS", "Key and entity must be defined for this request.", Response::HTTP_NOT_FOUND);
        }
    }
}

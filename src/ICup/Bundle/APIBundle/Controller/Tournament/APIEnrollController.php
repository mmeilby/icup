<?php

namespace APIBundle\Controller\Tournament;

use APIBundle\Controller\APIController;
use APIBundle\Entity\Form\GetCombinedKeyType;
use APIBundle\Entity\GetCombinedKeyForm;
use APIBundle\Entity\Wrapper\Doctrine\CategoryWrapper;
use APIBundle\Entity\Wrapper\Doctrine\EnrollmentWrapper;
use APIBundle\Entity\Wrapper\Doctrine\GroupWrapper;
use APIBundle\Entity\Wrapper\Doctrine\TeamWrapper;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Doctrine\Enroll controller.
 *
 */
class APIEnrollController extends APIController
{
    /**
     * List the enrolled teams for the tournament and host identified by APIkey
     * @Route("/v1/enrollment", name="_api_enroll")
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
                    $enrollments = array();
                    foreach ($tournament->getCategories() as $category) {
                        /* @var $category Category */
                        $teams_assigned = array();
                        foreach ($category->getGroups() as $group) {
                            /* @var $group Group */
                            $teams_assigned[] = array(
                                "group" => new GroupWrapper($group),
                                "teams" => new TeamWrapper($group->getTeams())
                            );
                        }
                        $enrollments[] = array(
                            "category" => new CategoryWrapper($category),
                            "enrollments" => new EnrollmentWrapper($category->getEnrollments()->getValues()),
                            "assignments" => $teams_assigned
                        );
                    }
                    return new JsonResponse($enrollments);
                } else {
                    return $api->makeErrorObject("KEYINV", "Key is not found for this host and entity.", Response::HTTP_NOT_FOUND);
                }
            });
        } else {
            return $this->makeErrorObject("KEYMISS", "Key and entity must be defined for this request.", Response::HTTP_NOT_FOUND);
        }
    }
}

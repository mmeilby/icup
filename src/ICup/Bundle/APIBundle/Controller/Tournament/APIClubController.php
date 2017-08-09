<?php
namespace APIBundle\Controller\Tournament;

use APIBundle\Controller\APIController;
use APIBundle\Entity\Form\GetCombinedKeyType;
use APIBundle\Entity\Wrapper\Doctrine\CategoryWrapper;
use APIBundle\Entity\Wrapper\Doctrine\ClubWrapper;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Enrollment;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Doctrine\Club controller.
 *
 */
class APIClubController extends APIController
{
    /**
     * List the clubs enrolled for the host identified by APIkey
     * @Route("/v1/club", name="_api_club")
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
                    $clubs = array();
                    $enrollments = array();
                    foreach ($tournament->getCategories() as $category) {
                        /* @var $category Category */
                        foreach ($category->getEnrollments() as $enrollment) {
                            /* @var $enrollment Enrollment */
                            $clubs[$enrollment->getTeam()->getClub()->getId()] = $enrollment->getTeam()->getClub();
                            $enrollments[$enrollment->getTeam()->getClub()->getId()][$category->getId()] = $category;
                        }
                    }
                    usort($clubs, function (Club $club1, Club $club2) {
                        return $club1->getCountryCode() == $club2->getCountryCode() ?
                            ($club1->getName() > $club2->getName() ? 1 : -1) :
                            ($club1->getCountryCode() > $club2->getCountryCode() ? 1 : -1);
                    });
                    $response = array();
                    foreach ($clubs as $club) {
                        /* @var $club Club */
                        $wrapped_club = new ClubWrapper($club);
                        $response[] = array_merge($wrapped_club->jsonSerialize(), array(
                            "categories" =>
                                isset($enrollments[$club->getId()]) ?
                                new CategoryWrapper($enrollments[$club->getId()]) :
                                array()
                        ));
                    }
                    return new JsonResponse($response);
                }
                else if ($entity instanceof Club) {
                    /* @var $club Club */
                    $club = $entity;
                    foreach ($club->getTeams() as $team) {
                        /* @var $team Team */
                        if ($team->getCategory()->getTournament()->getHost()->getId() == $api->host->getId()) {
                            return new JsonResponse(new ClubWrapper($club));
                        }
                    }
                    return $api->makeErrorObject("CLBINV", "Club is not found for this host.", Response::HTTP_NOT_FOUND);
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

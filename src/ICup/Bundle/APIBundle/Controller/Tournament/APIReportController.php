<?php

namespace APIBundle\Controller\Tournament;

use APIBundle\Controller\APIController;
use APIBundle\Entity\Form\ReportMatchType;
use APIBundle\Entity\ReportMatchForm;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchRelation;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use DateTime;

/**
 * Doctrine\Site controller.
 *
 */
class APIReportController extends APIController
{
    /**
     * Report result for specific match
     * @Route("/v1/report", name="_api_report")
     * @Method("POST")
     * @return JsonResponse
     */
    public function indexAction(Request $request) {
        $keyForm = new ReportMatchForm();
        $keyForm->getJsonParams($request);
        $form = $this->createForm(new ReportMatchType(), $keyForm);
        $form->handleRequest($request);
        if ($keyForm->checkForm($form)) {
            return $this->executeAPImethod($request, function (APIController $api) use ($keyForm) {
                $entity = $api->get('entity')->getEntityByExternalKey($keyForm->getEntity(), $keyForm->getKey());
                if ($entity instanceof Match) {
                    /* @var $match Match */
                    $match = $entity;
                    if ($match->getGroup()->getCategory()->getTournament()->getHost()->getId() != $api->host->getId()) {
                        return $api->makeErrorObject("MTCHINV", "Match is not found for this host.", Response::HTTP_NOT_FOUND);
                    }
                    else if ($match->getMatchRelations()->count() != 2) {
                        return $api->makeErrorObject("MTCHNPL", "Match is not scheduled yet.", Response::HTTP_BAD_REQUEST);
                    }
                    else if ($api->get('match')->isMatchResultValid($match->getId())) {
                        return $api->makeErrorObject("MTCHDONE", "Match is played and can not be updated.", Response::HTTP_BAD_REQUEST);
                    }
                    else {
                        $today = new DateTime();
                        $matchdate = Date::getDateTime($match->getDate(), $match->getTime());
                        if ($matchdate > $today) {
                            return $api->makeErrorObject("MTCHTE", "Match is not played yet and can not be updated.", Response::HTTP_BAD_REQUEST);
                        }
                    }
                    /* @var $homeRel MatchRelation */
                    $homeRel = $api->get('match')->getMatchRelationByMatch($match->getId(), false);
                    /* @var $awayRel MatchRelation */
                    $awayRel = $api->get('match')->getMatchRelationByMatch($match->getId(), true);
                    switch ($keyForm->getEvent()) {
                        case ReportMatchForm::$EVENT_MATCH_PLAYED:
                            $homeRel->setScore($keyForm->getHomeScore());
                            $awayRel->setScore($keyForm->getAwayScore());
                            $api->get('match')->updatePoints($match->getGroup()->getCategory()->getTournament(), $homeRel, $awayRel);
                            break;
                        case ReportMatchForm::$EVENT_HOME_DISQ:
                            $api->get('match')->disqualify($match->getGroup()->getCategory()->getTournament(), $awayRel, $homeRel);
                            break;
                        case ReportMatchForm::$EVENT_AWAY_DISQ:
                            $api->get('match')->disqualify($match->getGroup()->getCategory()->getTournament(), $homeRel, $awayRel);
                            break;
                        case ReportMatchForm::$EVENT_NOT_PLAYED:
                            $homeRel->setPoints(0);
                            $homeRel->setScore(0);
                            $awayRel->setPoints(0);
                            $awayRel->setScore(0);
                            break;
                    }
                    $homeRel->setScorevalid(true);
                    $awayRel->setScorevalid(true);
                    $api->getDoctrine()->getManager()->flush();

                    return new JsonResponse("", Response::HTTP_NO_CONTENT);
                } else {
                    return $api->makeErrorObject("KEYINV", "Key is not found for this host and entity.", Response::HTTP_NOT_FOUND);
                }
            });
        }
        else {
            return $this->makeErrorObject($form->getErrors()->current()->getMessage(), "Form failed", Response::HTTP_BAD_REQUEST);
        }
    }
}

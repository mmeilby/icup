<?php

namespace APIBundle\Controller\Tournament;

use APIBundle\Controller\APIController;
use APIBundle\Entity\Form\GetCombinedKeyType;
use APIBundle\Entity\Wrapper\Doctrine\MatchWrapper;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Playground;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match;
use DateTime;
use DateInterval;
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
                    $matches = $tournament->getMatches();
                    usort($matches, APIMatchController::sortingFunction());
                    return new JsonResponse(new MatchWrapper($matches));
                }
                else if ($entity instanceof Category) {
                    /* @var $category Category */
                    $category = $entity;
                    if ($category->getTournament()->getHost()->getId() != $api->host->getId()) {
                        return $api->makeErrorObject("CATINV", "Category is not found for this host.", Response::HTTP_NOT_FOUND);
                    }
                    $matches = array();
                    $category->getGroups()->forAll(function ($n, Group $group) use (&$matches) {
                        $matches = array_merge($matches, $group->getMatches()->toArray());
                        return true;
                    });
                    usort($matches, APIMatchController::sortingDateFunction());
                    return new JsonResponse(new MatchWrapper($matches));
                }
                else if ($entity instanceof Group) {
                    /* @var $group Group */
                    $group = $entity;
                    if ($group->getCategory()->getTournament()->getHost()->getId() != $api->host->getId()) {
                        return $api->makeErrorObject("GRPINV", "Group is not found for this host.", Response::HTTP_NOT_FOUND);
                    }
                    $matches = $group->getMatches()->getValues();
                    usort($matches, APIMatchController::sortingFunction());
                    return new JsonResponse(new MatchWrapper($matches));
                }
                else if ($entity instanceof Playground) {
                    /* @var $playground Playground */
                    $playground = $entity;
                    if ($playground->getSite()->getTournament()->getHost()->getId() != $api->host->getId()) {
                        return $api->makeErrorObject("VENINV", "Venue is not found for this host.", Response::HTTP_NOT_FOUND);
                    }
                    $matches = $playground->getMatches()->getValues();
                    usort($matches, APIMatchController::sortingFunction());
                    return new JsonResponse(new MatchWrapper($matches));
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

    /**
     * List the matches for today for the host identified by APIkey
     * @Route("/v1/match/today", name="_api_match_today")
     * @Method("POST")
     * @return JsonResponse
     */
    public function todayAction(Request $request)
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
                    $today = new DateTime();
                    $day = $api->get('match')->getMatchDate($tournament->getId(), $today);
                    $matches = $tournament->getMatches($day);
                    usort($matches, APIMatchController::sortingFunction());
                    return new JsonResponse(new MatchWrapper($matches));
                } else {
                    return $api->makeErrorObject("KEYINV", "Key is not found for this host and entity.", Response::HTTP_NOT_FOUND);
                }
            });
        }
        else {
            return $this->makeErrorObject("KEYMISS", "Key and entity must be defined for this request.", Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * List the matches for today for the host identified by APIkey
     * @Route("/v1/match/nextday", name="_api_match_next")
     * @Method("POST")
     * @return JsonResponse
     */
    public function nextAction(Request $request)
    {
        $keyForm = $this->getKeyForm($request);
        $form = $this->createForm(new GetCombinedKeyType(), $keyForm);
        $form->handleRequest($request);
        if ($keyForm->checkForm($form)) {
            return $this->executeAPImethod($request, function (APIController $api) use ($keyForm) {
                $entity = $api->get('entity')->getEntityByExternalKey($keyForm->getEntity(), $keyForm->getKey());
                if ($entity instanceof Match) {
                    /* @var $match Match */
                    $match = $entity;
                    if ($match->getGroup()->getCategory()->getTournament()->getHost()->getId() != $api->host->getId()) {
                        return $api->makeErrorObject("MCHINV", "Match is not found for this host.", Response::HTTP_NOT_FOUND);
                    }
                    $day = $match->getSchedule();
                    $day = date_add($day, new DateInterval("P1D"));
                    /* @var $tournament Tournament */
                    $tournament = $match->getGroup()->getCategory()->getTournament();
                    $day = $api->get('match')->getMatchDate($tournament->getId(), $day);
                    $matches = $tournament->getMatches($day);
                    usort($matches, APIMatchController::sortingFunction());
                    return new JsonResponse(new MatchWrapper($matches));
                } else {
                    return $api->makeErrorObject("KEYINV", "Key is not found for this host and entity.", Response::HTTP_NOT_FOUND);
                }
            });
        }
        else {
            return $this->makeErrorObject("KEYMISS", "Key and entity must be defined for this request.", Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * List the matches for today for the host identified by APIkey
     * @Route("/v1/match/prevday", name="_api_match_prev")
     * @Method("POST")
     * @return JsonResponse
     */
    public function prevAction(Request $request)
    {
        $keyForm = $this->getKeyForm($request);
        $form = $this->createForm(new GetCombinedKeyType(), $keyForm);
        $form->handleRequest($request);
        if ($keyForm->checkForm($form)) {
            return $this->executeAPImethod($request, function (APIController $api) use ($keyForm) {
                $entity = $api->get('entity')->getEntityByExternalKey($keyForm->getEntity(), $keyForm->getKey());
                if ($entity instanceof Match) {
                    /* @var $match Match */
                    $match = $entity;
                    if ($match->getGroup()->getCategory()->getTournament()->getHost()->getId() != $api->host->getId()) {
                        return $api->makeErrorObject("MCHINV", "Match is not found for this host.", Response::HTTP_NOT_FOUND);
                    }
                    $day = $match->getSchedule();
                    $day = date_sub($day, new DateInterval("P1D"));
                    /* @var $tournament Tournament */
                    $tournament = $match->getGroup()->getCategory()->getTournament();
                    $day = $api->get('match')->getMatchDate($tournament->getId(), $day);
                    $matches = $tournament->getMatches($day);
                    usort($matches, APIMatchController::sortingFunction());
                    return new JsonResponse(new MatchWrapper($matches));
                } else {
                    return $api->makeErrorObject("KEYINV", "Key is not found for this host and entity.", Response::HTTP_NOT_FOUND);
                }
            });
        }
        else {
            return $this->makeErrorObject("KEYMISS", "Key and entity must be defined for this request.", Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Get the match with matchno for the host identified by APIkey
     * @Route("/v1/match/no", name="_api_match_no")
     * @Method("POST")
     * @return JsonResponse
     */
    public function searchAction(Request $request)
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
                    $match = $api->get('match')->getMatchByNo($tournament, $keyForm->getParam());
                    if ($match == null) {
                        return $api->makeErrorObject("PARAMINV", "Match no. is not found for this host and entity.", Response::HTTP_NOT_FOUND);
                    }
                    return new JsonResponse(new MatchWrapper($match));
                } else {
                    return $api->makeErrorObject("KEYINV", "Key is not found for this host and entity.", Response::HTTP_NOT_FOUND);
                }
            });
        }
        else {
            return $this->makeErrorObject("KEYMISS", "Key and entity must be defined for this request.", Response::HTTP_NOT_FOUND);
        }
    }

    static function sortingFunction() {
        return function (Match $match1, Match $match2) {
            $stack[] = array($match1->getPlayground()->getNo(), $match2->getPlayground()->getNo());
            $stack[] = array($match1->getDate(), $match2->getDate());
            $stack[] = array($match1->getTime(), $match2->getTime());
            $stack[] = array($match1->getMatchno(), $match2->getMatchno());
            foreach ($stack as $criteria) {
                list($crit1, $crit2) = $criteria;
                $norm = min(1, max(-1, $crit1 - $crit2));
                if ($norm != 0) {
                    return $norm;
                }
            }
            return 0;
        };
    }

    static function sortingDateFunction() {
        return function (Match $match1, Match $match2) {
            $stack[] = array($match1->getDate(), $match2->getDate());
            $stack[] = array($match1->getTime(), $match2->getTime());
            $stack[] = array($match1->getPlayground()->getNo(), $match2->getPlayground()->getNo());
            $stack[] = array($match1->getMatchno(), $match2->getMatchno());
            foreach ($stack as $criteria) {
                list($crit1, $crit2) = $criteria;
                $norm = min(1, max(-1, $crit1 - $crit2));
                if ($norm != 0) {
                    return $norm;
                }
            }
            return 0;
        };
    }
}

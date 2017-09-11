<?php

namespace APIBundle\Controller\Tournament;

use APIBundle\Controller\APIController;
use APIBundle\Entity\Form\GetCombinedKeyType;
use APIBundle\Entity\Wrapper\Doctrine\CategoryWrapper;
use APIBundle\Entity\Wrapper\Doctrine\ClubWrapper;
use APIBundle\Entity\Wrapper\Doctrine\GroupWrapper;
use APIBundle\Entity\Wrapper\Doctrine\ResultWrapper;
use APIBundle\Entity\Wrapper\Doctrine\TeamWrapper;
use APIBundle\Entity\Wrapper\Doctrine\TournamentWrapper;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\TeamStat;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Statistics controller.
 *
 */
class APIStatisticsController extends APIController
{
    /**
     * List the statistics for the host identified by APIkey
     * @Route("/v1/statistics", name="_api_statistics")
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
                    $tmnt_api = $api->get('tmnt');
                    $wrapped_tournament = new TournamentWrapper($tournament);
                    $counts = $tmnt_api->getStatTournamentCounts($tournament->getId());
                    $playgroundCounts = $tmnt_api->getStatPlaygroundCounts($tournament->getId());
                    $teamCounts = $tmnt_api->getStatTeamCounts($tournament->getId());
                    $teamCounts2 = $tmnt_api->getStatTeamCountsChildren($tournament->getId());
                    $matchCounts = $tmnt_api->getStatMatchCounts($tournament->getId());
                    $statmap = array_merge($counts[0], $playgroundCounts[0], $teamCounts[0], $teamCounts2[0], $matchCounts[0]);

                    $statmap['adultteams'] = $statmap['teams'] - $statmap['childteams'];
                    $statmap['maleteams'] = $statmap['teams'] - $statmap['femaleteams'];

                    $famemap = array();

                    $trophies = $tmnt_api->getTrophysByClub($tournament);
                    if (count($trophies) > 0) {
                        $trophy = $trophies[0];
                        $statmap['mosttrophysbyclub'] = $trophy['trophys'];
                        $famemap['mosttrophysbyclub']['club'] = new ClubWrapper($trophy['club_entity']);
                    }
                    else {
                        $statmap['mosttrophysbyclub'] = "";
                    }

                    $trophies = $tmnt_api->getTrophysByCountry($tournament);
                    if (count($trophies) > 0) {
                        $trophy = $trophies[0];
                        $statmap['mosttrophys'] = $trophy['trophys'];
                        $famemap['mosttrophys']['country'] = array(
                            "entity" => "Country",
                            "country_code" => $trophy['country'],
                            "flag" => $api->get('util')->getFlag($trophy['country'])
                        );
                    }
                    else {
                        $statmap['mosttrophys'] = "";
                    }

                    $trophies = $tmnt_api->getMostGoals($tournament->getId());
                    if (count($trophies) > 0) {
                        $trophy = $trophies[0];
                        $statmap['mostgoals'] = $trophy['mostgoals'];
                        $category = $api->get('entity')->getCategoryById($trophy['cid']);
                        $famemap['mostgoals']['category'] = new CategoryWrapper($category);
                        $team = $api->get('entity')->getTeamById($trophy['id']);
                        $famemap['mostgoals']['team'] = new TeamWrapper($team);
                    }
                    else {
                        $statmap['mostgoals'] = "";
                    }

                    $trophies = $tmnt_api->getMostGoalsTotal($tournament->getId());
                    if (count($trophies) > 0) {
                        $trophy = $trophies[0];
                        $statmap['mostgoalstotal'] = $trophy['mostgoals'];
                        $category = $api->get('entity')->getCategoryById($trophy['cid']);
                        $famemap['mostgoalstotal']['category'] = new CategoryWrapper($category);
                        $team = $api->get('entity')->getTeamById($trophy['id']);
                        $famemap['mostgoalstotal']['team'] = new TeamWrapper($team);
                    }
                    else {
                        $statmap['mostgoalstotal'] = "";
                    }

                    $order = array(
                        'teams' => array('countries','clubs','teams','femaleteams','maleteams','adultteams','childteams'),
                        'tournament' => array('categories','groups','sites','playgrounds','matches','goals','days'),
                        'top' => array('mostgoals','mostgoalstotal','mosttrophys','mosttrophysbyclub')
                    );

                    $response = $wrapped_tournament->jsonSerialize();
                    foreach ($order as $itemKey => $itemList) {
                        $valueList = array();
                        foreach ($itemList as $item) {
                            if (isset($statmap[$item])) {
                                if (isset($famemap[$item])) {
                                    $valueList[$item] = array("value" => $statmap[$item], "champion" => $famemap[$item]);
                                }
                                else {
                                    $valueList[$item] = array("value" => $statmap[$item]);
                                }
                            }
                        }
                        $response["statistics"][$itemKey] = $valueList;
                    }
                    return new JsonResponse($response);
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

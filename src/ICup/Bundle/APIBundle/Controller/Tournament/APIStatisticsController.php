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
                        $trophys = $trophies[0]['trophys'];
                        $club = $trophies[0]['club'];
                        $country = $trophies[0]['country'];
                    }
                    else {
                        $trophys = '';
                        $club = '';
                        $country = '';
                    }
                    $mostTrophysClub = array('trophys' => $trophys, 'country' => $country, 'club' => $club);
                    $statmap['mosttrophysbyclub'] = $mostTrophysClub['trophys'];
                    if ($mostTrophysClub['country'] != '') {
                        $famemap['mosttrophysbyclub']['country'] = $mostTrophysClub['country'];
                        $famemap['mosttrophysbyclub']['desc'] = $mostTrophysClub['club'];
                        $famemap['mosttrophysbyclub']['id'] = '';
                    }
                    $trophies = $tmnt_api->getTrophysByCountry($tournament);
                    if (count($trophies) > 0) {
                        $trophys = $trophies[0]['trophys'];
                        $country = $trophies[0]['country'];
                    }
                    else {
                        $trophys = '';
                        $country = '';
                    }
                    $mostTrophys = array('trophys' => $trophys, 'country' => $country);
                    $statmap['mosttrophys'] = $mostTrophys['trophys'];
                    if ($mostTrophys['country'] != '') {
                        $famemap['mosttrophys']['country'] = $mostTrophys['country'];
                        $famemap['mosttrophys']['desc'] = '';
                        $famemap['mosttrophys']['id'] = '';
                    }
                    $trophies = $tmnt_api->getMostGoals($tournament->getId());
                    if (count($trophies) > 0) {
                        $goals = $trophies[0]['mostgoals'];
                        $club = $trophies[0]['club'];
                        $country = $trophies[0]['country'];
                        $cid = $trophies[0]['cid'];
                        $id = $trophies[0]['id'];
                    }
                    else {
                        $goals = '';
                        $club = '';
                        $country = '';
                        $cid = 0;
                        $id = 0;
                    }
                    $mostGoals = array('goals' => $goals, 'country' => $country, 'club' => $club, 'id' => $id, 'cid' => $cid);
                    $statmap['mostgoals'] = $mostGoals['goals'];
                    if ($mostGoals['country'] != '') {
                        $category = $api->get('entity')->getCategoryById($mostGoals['cid']);
                        $famemap['mostgoals']['country'] = $mostGoals['country'];
                        $famemap['mostgoals']['desc'] = new CategoryWrapper($category);
                        $famemap['mostgoals']['club'] = $mostGoals['club'];
                        $famemap['mostgoals']['id'] = $mostGoals['id'];
                    }
                    $trophies = $tmnt_api->getMostGoalsTotal($tournament->getId());
                    if (count($trophies) > 0) {
                        $goals = $trophies[0]['mostgoals'];
                        $club = $trophies[0]['club'];
                        $country = $trophies[0]['country'];
                        $cid = $trophies[0]['cid'];
                        $id = $trophies[0]['id'];
                    }
                    else {
                        $goals = '';
                        $club = '';
                        $country = '';
                        $cid = 0;
                        $id = 0;
                    }
                    $mostGoalsTotal = array('goals' => $goals, 'country' => $country, 'club' => $club, 'id' => $id, 'cid' => $cid);
                    $statmap['mostgoalstotal'] = $mostGoalsTotal['goals'];
                    if ($mostGoalsTotal['country'] != '') {
                        $category = $api->get('entity')->getCategoryById($mostGoalsTotal['cid']);
                        $famemap['mostgoalstotal']['country'] = $mostGoalsTotal['country'];
                        $famemap['mostgoalstotal']['desc'] = new CategoryWrapper($category);
                        $famemap['mostgoalstotal']['club'] = $mostGoalsTotal['club'];
                        $famemap['mostgoalstotal']['id'] = $mostGoalsTotal['id'];
                    }

                    $response[] = array_merge($wrapped_tournament->jsonSerialize(), array(
                        "statistics" => $statmap,
                        "halloffame" => $famemap,
                        "order" => array(
                            'teams' => array('countries','clubs','teams','femaleteams','maleteams','adultteams','childteams'),
                            'tournament' => array('categories','groups','sites','playgrounds','matches','goals','days'),
                            'top' => array('mostgoals','mostgoalstotal','mosttrophys','mosttrophysbyclub')
                        )
                    ));
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

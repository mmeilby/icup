<?php

namespace APIBundle\Controller\Tournament;

use APIBundle\Controller\APIController;
use APIBundle\Entity\Form\GetCombinedKeyType;
use APIBundle\Entity\Wrapper\Doctrine\CategoryWrapper;
use APIBundle\Entity\Wrapper\Doctrine\ClubWrapper;
use APIBundle\Entity\Wrapper\Doctrine\GroupWrapper;
use APIBundle\Entity\Wrapper\Doctrine\ResultWrapper;
use APIBundle\Entity\Wrapper\Doctrine\TournamentWrapper;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Result controller.
 *
 */
class APIResultController extends APIController
{
    /**
     * List the results for the host identified by APIkey
     * @Route("/v1/result", name="_api_result")
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
                    $order = array('first', 'second', 'third', 'forth');
                    $championList = array();
                    $championByCountryList = array();
                    $championByClubList = array();
                    $teams = $api->get('tmnt')->listChampionsByTournament($tournament);
                    foreach ($teams as $categoryid => $categoryChamps) {
                        /* @var $team Team */
                        foreach ($categoryChamps as $champion => $team) {
                            APIResultController::updateList($championList, $categoryid, $order[$champion-1], $team->getPreliminaryGroup(), $team);
                            APIResultController::updateList($championByCountryList, $team->getClub()->getCountryCode(), $order[$champion-1], $team->getPreliminaryGroup(), $team);
                            APIResultController::updateList($championByClubList, $team->getClub()->getName(), $order[$champion-1], $team->getPreliminaryGroup(), $team);
                        }
                    }
                    $wrapped_tournament = new TournamentWrapper($tournament);
                    $champions = array_merge($wrapped_tournament->jsonSerialize(), array("champions" => APIResultController::filterChampions($tournament, $championList, $order)));
                    usort($championByCountryList,
                        function (array $country1, array $country2) use ($order) {
                            foreach ($order as $i) {
                                $o = count($country1[$i]) - count($country2[$i]);
                                if ($o < 0) {
                                    return 1;
                                }
                                else if ($o > 0) {
                                    return -1;
                                }
                            }
                            return 0;
                        }
                    );
                    $champions = array_merge($champions, array("championsByCountry" => APIResultController::filterCountryChampions($championByCountryList, $order)));
                    usort($championByClubList,
                        function (array $club1, array $club2) use ($order) {
                            foreach ($order as $i) {
                                $o = count($club1[$i]) - count($club2[$i]);
                                if ($o < 0) {
                                    return 1;
                                }
                                else if ($o > 0) {
                                    return -1;
                                }
                            }
                            return 0;
                        }
                    );
                    $response[] = array_merge($champions, array("championsByClub" => APIResultController::filterClubChampions($championByClubList, $order)));
                    return new JsonResponse($response);
                }
                else if ($entity instanceof Group) {
                    /* @var $group Group */
                    $group = $entity;
                    if ($group->getCategory()->getTournament()->getHost()->getId() != $api->host->getId()) {
                        return $api->makeErrorObject("GRPINV", "Group is not found for this host.", Response::HTTP_NOT_FOUND);
                    }
                    $teamsList = $api->get('orderTeams')->sortGroupFinals($group->getId());
                    $wrapped_group = new GroupWrapper($group);
                    $response[] = array_merge($wrapped_group->jsonSerialize(), array("results" => $teamsList));
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

    static function updateList(&$list, $key, $order, Group $group, Team $team) {
        if (!array_key_exists($key, $list)) {
            $list[$key] = array(
                'country' => $team->getClub()->getCountryCode(),
                'group' => $group,
                'club' => $team->getClub()->getName(),
                'first' => array(),
                'second' => array(),
                'third' => array(),
                'forth' => array()
            );
        }
        $list[$key][$order][] = array(
            'id' => $team->getId(),
            'name' => $team->getTeamName("<VACANT_TEAM>"),
            'country' => $team->getClub()->getCountryCode(),
            'matches' => 1
        );
    }

    static function filterChampions(Tournament $tournament, $list, $order) {
        $resultList = array();
        foreach ($list as $categoryid => $item) {
            $result = array();
            $result["category"] = new CategoryWrapper($tournament->getCategories()->filter(function (Category $category) use ($categoryid) { return $category->getId() == $categoryid; })->first());
            foreach ($order as $i) {
                if (count($item[$i]) > 0) {
                    $result[$i] = array("name" => $item[$i][0]["name"], "countryCode" => $item[$i][0]["country"]);
                }
                else {
                    $result[$i] = array();
                }
            }
            $resultList[] = $result;
        }
        return $resultList;
    }

    static function filterCountryChampions($list, $order) {
        $resultList = array();
        foreach ($list as $item) {
            $result = array();
            $result["countryCode"] = $item["country"];
            foreach ($order as $i) {
                $result[$i] = count($item[$i]);
            }
            $resultList[] = $result;
        }
        return $resultList;
    }

    static function filterClubChampions($list, $order) {
        $resultList = array();
        foreach ($list as $item) {
            $result = array();
            $result["club"] = array("name" => $item["club"], "countryCode" => $item["country"]);
            foreach ($order as $i) {
                $result[$i] = count($item[$i]);
            }
            $resultList[] = $result;
        }
        return $resultList;
    }
}

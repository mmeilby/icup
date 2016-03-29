<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Rest\Admin\Tournament;

use Doctrine\ORM\EntityManager;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\GroupOrder;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchSchedule;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchSchedulePlan;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Playground;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\PlaygroundAttribute;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\QMatchSchedule;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Entity\MatchPlan;
use ICup\Bundle\PublicSiteBundle\Entity\QMatchPlan;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;
use ICup\Bundle\PublicSiteBundle\Services\Doctrine\MatchSupport;
use ICup\Bundle\PublicSiteBundle\Services\Entity\PlanningOptions;
use ICup\Bundle\PublicSiteBundle\Services\Entity\QRelation;
use ICup\Bundle\PublicSiteBundle\Services\Util;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use DateTime;
use DateInterval;

class RestMatchPlanningController extends Controller
{
    /**
     * Plan matches according to assigned groups and match configuration
     * @Route("/edit/rest/m/plan/plan/{tournamentid}/{level}", name="_rest_match_planning_plan", options={"expose"=true})
     */
    public function planMatchesAction($tournamentid, $level, Request $request) {
        try {
            $tournament = $this->checkArgs($tournamentid);
        }
        catch (ValidationException $e) {
            return new Response(json_encode(array('success' => false, 'done' => true, 'unresolved' => 0)));
        }
        try {
            $planningCard = $this->get('planning')->planTournamentByStep($tournament, $level);
        }
        catch (\Exception $e) {
            return new Response(json_encode(array('success' => false, 'done' => true, 'unresolved' => 0)));
        }
        $unresolved = 0;
        if (isset($planningCard['preliminary'])) {
            $unresolved += $planningCard['preliminary']->unresolved();
        }
        if (isset($planningCard['elimination'])) {
            $unresolved += $planningCard['elimination']->unresolved();
        }
        $done = $planningCard['level'] >= 100;
        return new Response(json_encode(array('success' => true, 'done' => $done, 'unresolved' => $unresolved, 'level' => $planningCard['level'])));
    }

    /**
     * Plan matches according to assigned groups and match configuration
     * @Route("/edit/rest/m/plan/listq/{tournamentid}", name="_rest_match_planning_list_qualified", options={"expose"=true})
     */
    public function listQualifiedAction($tournamentid, Request $request) {
        try {
            $tournament = $this->checkArgs($tournamentid);
        }
        catch (ValidationException $e) {
            return new Response(json_encode(array('success' => false, 'error' => $e->getMessage(), 'info' => $e->getDebugInfo())));
        }
        try {
            $matches = $this->get("tmnt")->listQualifiedTeamsByTournament($tournament);
        }
        catch (\Exception $e) {
            return new Response(json_encode(array('success' => false, 'error' => $e->getMessage(), 'info' => '')));
        }
        $result = array();
        foreach ($matches as $matchrec) {
            /* @var $match Match */
            $match = $matchrec['match'];
            /* @var $teamA Team */
            $teamA = $matchrec['home'];
            /* @var $teamB Team */
            $teamB = $matchrec['away'];
            $result[] = array(
                'matchno' => $match->getMatchno(),
                'home' => array(
                    'id' => $teamA->getId(),
                    'name' => $teamA->getTeamName()." (".$teamA->getClub()->getCountry().")",
                    'country' => $this->get('translator')->trans($teamA->getClub()->getCountry(), array(), 'lang')
                ),
                'away' => array(
                    'id' => $teamB->getId(),
                    'name' => $teamB->getTeamName()." (".$teamB->getClub()->getCountry().")",
                    'country' => $this->get('translator')->trans($teamB->getClub()->getCountry(), array(), 'lang')
                )
            );
        }
        return new Response(json_encode(array('success' => true, 'matches' => $result)));
    }

    /**
     * Return planned and unassigned matches for a specified venue and match date
     * @Route("/edit/rest/m/plan/listm/{playgroundid}/{date}", name="_rest_match_planning_list_matches", options={"expose"=true})
     */
    public function listMatchesAction($playgroundid, $date, Request $request) {
        try {
            /* @var $utilService Util */
            $utilService = $this->get('util');
            /* @var $playground Playground */
            $playground = $this->get('entity')->getPlaygroundById($playgroundid);
            $site = $playground->getSite();
            /* @var $user User */
            $user = $utilService->getCurrentUser();
            /* @var $tournament Tournament */
            $tournament = $site->getTournament();
            $host = $tournament->getHost();
            $utilService->validateEditorAdminUser($user, $host);

            $matchDate = DateTime::createFromFormat('d-m-Y', $date);
            if ($matchDate == null) {
                throw new ValidationException("INVALIDDATE", "Match date invalid: date=".$date);
            }
        }
        catch (ValidationException $e) {
            return new Response(json_encode(array('success' => false, 'error' => $e->getMessage(), 'info' => $e->getDebugInfo())));
        }
        try {
            $matches = $this->get('planning')->listMatchesByPlaygroundDate($playground, Date::getDate($matchDate));
        }
        catch (\Exception $e) {
            return new Response(json_encode(array('success' => false, 'error' => $e->getMessage(), 'info' => '')));
        }
        $result = array();
        foreach ($matches as $paid => $pamatches) {
            $playgroundattr = $this->getJsonPAttr($paid);
            $mresult = array();
            foreach ($pamatches as $match) {
                $mresult[] = $this->getJsonMatchPlan($match, true);
            }
            $result[] = array(
                'pa' => $playgroundattr,
                'matches' => $mresult
            );
        }
        usort($result, function($ar1, $ar2) {
            return $ar1['pa']['start'] > $ar2['pa']['start'] ? 1 : -1;
        });
        return new Response(json_encode(array('success' => true, 'matches' => $result)));
    }

    /**
     * @param $paid
     * @return array
     */
    private function getJsonPAttr($paid) {
        if ($paid > 0) {
            /* @var $pattr PlaygroundAttribute */
            $pattr = $this->get('entity')->getPlaygroundAttributeById($paid);
            $playgroundattr = array(
                'id' => $pattr->getId(),
                'timeslot' => $pattr->getTimeslot()->getName(),
                'start' => date_format($pattr->getStartSchedule(), $this->get('translator')->trans('FORMAT.TIME')),
                'end' => date_format($pattr->getEndSchedule(), $this->get('translator')->trans('FORMAT.TIME')));
        } else {
            $playgroundattr = array('id' => 0, 'timeslot' => '', 'start' => '', 'end' => '');
        }
        return $playgroundattr;
    }

    /**
     * @param $match
     * @return array
     */
    private function getJsonMatchPlan($match, $validmatch) {
        if ($match instanceof QMatchPlan) {
            /* @var $match QMatchPlan */
            $relA = $match->getRelA();
            $relB = $match->getRelB();
            $result = array(
                'uid' => 'Q'.$match->getId(),
                'id' => $match->getId(),
                'matchno' => $match->getMatchno() ? $match->getMatchno() : '',
                'elimination' => true,
                'status' => $match->isAssigned() ? ($validmatch ? '' : 'A') : 'W',
                'time' => $match->getTime() ? date_format($match->getSchedule(), $this->get('translator')->trans('FORMAT.TIME')) : '',
                'classification' => $match->getClassification(),
                'category' => array('id' => $match->getCategory()->getId(), 'name' => $match->getCategory()->getName()),
                'group' => array('id' => -1, 'name' => $this->getGroupNameFromLitra($match->getLitra(), $match->getClassification()), 'classification' => $match->getClassification()),
                'home' => array(
                    'name' => $this->getGroupName($relA),
                    'group' => $relA->getGroup() ? $relA->getGroup()->getId() : -1,
                    'classification' => $relA->getClassification(),
                    'litra' => $relA->getLitra() . $relA->getBranch(),
                    'rank' => $relA->getRank()
                ),
                'away' => array(
                    'name' => $this->getGroupName($relB),
                    'group' => $relB->getGroup() ? $relB->getGroup()->getId() : -1,
                    'classification' => $relB->getClassification(),
                    'litra' => $relB->getLitra() . $relB->getBranch(),
                    'rank' => $relB->getRank()
                )
            );
        } else {
            /* @var $match MatchPlan */
            $teamA = $match->getTeamA();
            $teamB = $match->getTeamB();
            $result = array(
                'uid' => 'M'.$match->getId(),
                'id' => $match->getId(),
                'matchno' => $match->getMatchno() ? $match->getMatchno() : '',
                'elimination' => false,
                'status' => $match->isAssigned() ? ($validmatch ? '' : 'A') : 'W',
                'time' => array('text' => $match->getTime() ? date_format($match->getSchedule(), $this->get('translator')->trans('FORMAT.TIME')) : '', 'raw' => $match->getTime()),
                'classification' => Group::$PRE,
                'category' => array('id' => $match->getCategory()->getId(), 'name' => $match->getCategory()->getName()),
                'group' => array('id' => $match->getGroup()->getId(), 'name' => $match->getGroup()->getName(), 'classification' => $match->getGroup()->getClassification()),
                'home' => array(
                    'id' => $teamA->getId(),
                    'name' => $teamA->getTeamName(),
                    'country' => $this->get('translator')->trans($teamA->getClub()->getCountry(), array(), 'lang'),
                    'flag' => $this->get('util')->getFlag($teamA->getClub()->getCountry())
                ),
                'away' => array(
                    'id' => $teamB->getId(),
                    'name' => $teamB->getTeamName(),
                    'country' => $this->get('translator')->trans($teamB->getClub()->getCountry(), array(), 'lang'),
                    'flag' => $this->get('util')->getFlag($teamB->getClub()->getCountry())
                )
            );
        }
        return $result;
    }

    private function getGroupName(QRelation $rel) {
        if ($rel->getClassification() > Group::$PRE) {
            $groupname = $this->get('translator')->trans('GROUPCLASS.'.$rel->getClassification(), array(), 'tournament');
        }
        else {
            $groupname = $this->get('translator')->trans('GROUP', array(), 'tournament');
        }
        $rankTxt = $this->get('translator')->transChoice('RANK', $rel->getRank(),
            array('%rank%' => $rel->getRank(),
                  '%group%' => strtolower($groupname).' '.($rel->getGroup() ? $rel->getGroup()->getName() : $rel->getLitra().$rel->getBranch())), 'tournament');
        return $rankTxt;
    }

    private function getGroupNameFromLitra($litra, $classification) {
        $groupname = $this->get('translator')->trans('GROUPCLASS.'.$classification, array(), 'tournament');
        return $groupname." ".$litra;
    }

    /**
     * Return planned and unassigned matches for a specified venue and match date
     * @Route("/edit/rest/m/plan/movem/{matchtype}/{matchid}/{paid}/{matchtime}", name="_rest_match_planning_move_match", options={"expose"=true})
     * @param $matchtype
     * @param $matchid
     * @param $paid
     * @param $matchtime
     * @return Response
     */
    public function moveMatch($matchtype, $matchid, $paid, $matchtime) {
        try {
            /* @var $utilService Util */
            $utilService = $this->get('util');
            /* @var $pattr PlaygroundAttribute */
            $pattr = $this->get('entity')->getPlaygroundAttributeById($paid);
            $playground = $pattr->getPlayground();
            $matchDate = $pattr->getDate();
            $site = $playground->getSite();
            /* @var $user User */
            $user = $utilService->getCurrentUser();
            /* @var $tournament Tournament */
            $tournament = $site->getTournament();
            $host = $tournament->getHost();
            $utilService->validateEditorAdminUser($user, $host);
        } catch (ValidationException $e) {
            return new Response(json_encode(array('success' => false, 'error' => $e->getMessage(), 'info' => $e->getDebugInfo())));
        }
        try {
            if ($matchtype == 'Q') {
                /* @var $sourcematch QMatchSchedule */
                $sourcematch = $this->get('entity')->getQMatchScheduleById($matchid);
                $validityStatus = $this->checkValidityQ($sourcematch, $pattr);
            }
            else {
                /* @var $sourcematch MatchSchedule */
                $sourcematch = $this->get('entity')->getMatchScheduleById($matchid);
                $validityStatus = $this->checkValidity($sourcematch, $pattr);
            }
            if ($sourcematch->getPlan()) {
                if ($sourcematch->getPlan()->getPlaygroundAttribute()->getId() != $pattr->getId()) {
                    $this->ResetMatchSchedules($sourcematch, '9999');
                    $sourcematch->getPlan()->setPlaygroundAttribute($pattr);
                }
            }
            else {
                $plan = new MatchSchedulePlan();
                $plan->setPlaygroundAttribute($pattr);
                $plan->setFixed(false);
                $sourcematch->setPlan($plan);
            }
            $this->ResetMatchSchedules($sourcematch, $matchtime);
            /* @var $em EntityManager */
            $em = $this->get('doctrine')->getManager();
            $em->flush();
        } catch (\Exception $e) {
            return new Response(json_encode(array('success' => false, 'error' => $e->getMessage(), 'info' => '')));
        }
        try {
            $matches = $this->get('planning')->listMatchesByPlaygroundDate($playground, $matchDate);
        } catch (\Exception $e) {
            return new Response(json_encode(array('success' => false, 'error' => $e->getMessage(), 'info' => '')));
        }
        $result = array();
        foreach ($matches as $paid => $pamatches) {
            $playgroundattr = $this->getJsonPAttr($paid);
            $mresult = array();
            foreach ($pamatches as $match) {
                /* @var $match MatchPlan */
                $mresult[] = $this->getJsonMatchPlan($match, !(($match instanceof QMatchPlan) == ($matchtype == 'Q') && $match->getId() == $sourcematch->getId()) || $validityStatus);
            }
            $result[] = array(
                'pa' => $playgroundattr,
                'matches' => $mresult
            );
        }
        usort($result, function($ar1, $ar2) {
            return $ar1['pa']['start'] > $ar2['pa']['start'] ? 1 : -1;
        });
        return new Response(json_encode(array('success' => true, 'matches' => $result)));
    }

    private function checkValidity(MatchSchedule $sourcematch, PlaygroundAttribute $pattr) {
        $homecnt = 0;
        $awaycnt = 0;
        $teams = array();
        foreach ($sourcematch->getMatchRelations() as $matchRelation) {
            /* @var $matchRelation MatchRelation */
            $teams[$matchRelation->getAwayteam()] = $matchRelation->getTeam()->getId();
        }
        foreach ($pattr->getTimeslot()->getPlaygroundattributes() as $playgroundattribute) {
            /* @var $playgroundattribute PlaygroundAttribute */
            if ($playgroundattribute->getDate() != $pattr->getDate()) {
                continue;
            }
            $matchschedules = $this->get('logic')->listMatchSchedulesByPlaygroundAttribute($playgroundattribute);
            foreach ($matchschedules as $matchschedule) {
                /* @var $matchschedule MatchSchedule */
                if ($matchschedule->getId() == $sourcematch->getId()) {
                    continue;
                }
                foreach ($matchschedule->getMatchRelations() as $matchRelation) {
                    /* @var $matchRelation MatchRelation */
                    if ($matchRelation->getTeam()->getId() == $teams[MatchSupport::$HOME]) {
                        $homecnt++;
                    }
                    elseif ($matchRelation->getTeam()->getId() == $teams[MatchSupport::$AWAY]) {
                        $awaycnt++;
                    }
                }
            }
        }
        $capacity = $pattr->getTimeslot()->getCapacity();
        return $homecnt < $capacity && $awaycnt < $capacity;
    }

    private function checkValidityQ(QMatchSchedule $sourcematch, PlaygroundAttribute $pattr) {
        return true;
    }

    /**
     * @param $sourcematch
     * @param $matchtime
     * @param $pattr
     */
    private function ResetMatchSchedules($sourcematch, $matchtime) {
        /* @var $sourcematch MatchSchedule|QMatchSchedule */
        $pattr = $sourcematch->getPlan()->getPlaygroundAttribute();
        $matches = $this->get('logic')->listMatchSchedulesByPlaygroundAttribute($pattr);
        $qmatches = $this->get('logic')->listQMatchSchedulesByPlaygroundAttribute($pattr);
        $sorted_matches = array_filter(array_merge($matches, $qmatches), function ($match) use ($sourcematch) {
            /* @var $match MatchSchedule|QMatchSchedule */
            return !(get_class($match) == get_class($sourcematch) && $match->getId() == $sourcematch->getId());
        });
        usort($sorted_matches, function ($match1, $match2) {
            /* @var $match1 MatchSchedule|QMatchSchedule */
            /* @var $match2 MatchSchedule|QMatchSchedule */
            return $match1->getPlan()->getMatchstart() > $match2->getPlan()->getMatchstart() ? 1 : -1;
        });
        if (preg_match('/\d{4}/', $matchtime)) {
            /* @var $match MatchSchedule|QMatchSchedule */
            foreach ($sorted_matches as $index => $match) {
                if ($match->getPlan()->getMatchstart() >= $matchtime) {
                    array_splice($sorted_matches, $index + 1, 0, array($sourcematch));
                    break;
                }
            }
        }
        else {
            array_unshift($sorted_matches, $sourcematch);
        }
        /* @var $mtime DateTime */
        $mtime = $pattr->getStartSchedule();
        /* @var $match MatchSchedule|QMatchSchedule */
        foreach ($sorted_matches as $match) {
            $match->getPlan()->setMatchstart(Date::getTime($mtime));
            $mtime = Date::addTime($mtime, $match->getCategory()->getMatchtime());
        }
    }

    /**
     * Get the match calendar for the tournament identified by tournament id
     * @Route("/edit/rest/m/plan/get/calendar/{tournamentid}", name="_rest_get_match_calendar", options={"expose"=true})
     * @param $tournamentid
     * @return Response
     */
    public function restMatchCalendarAction($tournamentid)
    {
        /* @var $tournament Tournament */
        $tournament = $this->get('entity')->getTournamentById($tournamentid);
        $dates = $this->get('match')->listMatchCalendar($tournament->getId());
        return new Response(json_encode(array("start" => date_format($dates[0], "m/d/Y"), "end" => date_format($dates[count($dates)-1], "m/d/Y"))));
    }
    
    /**
     * Check tournament id and validate current user rights to change tournament
     * @param $tournamentid
     * @return Tournament
     */
    private function checkArgs($tournamentid) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        /* @var $user User */
        $user = $utilService->getCurrentUser();
        /* @var $tournament Tournament */
        $tournament = $this->get('entity')->getTournamentById($tournamentid);
        $host = $tournament->getHost();
        $utilService->validateEditorAdminUser($user, $host);
        return $tournament;
    }
}

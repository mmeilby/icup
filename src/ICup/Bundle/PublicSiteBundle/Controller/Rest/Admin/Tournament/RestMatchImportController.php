<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Rest\Admin\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchSchedule;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchSchedulePlan;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchScheduleRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Playground;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\QMatchSchedule;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\QMatchScheduleRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Entity\TeamInfo;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;
use ICup\Bundle\PublicSiteBundle\Services\Doctrine\MatchSupport;
use ICup\Bundle\PublicSiteBundle\Services\Util;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use RuntimeException;

/**
 * Doctrine\Category controller.
 *
 * @Route("/rest/upload")
 */
class RestMatchImportController extends Controller
{
    /**
     * Import the match schedule for the tournament identified by tournament id
     * @Route("/schedule/{tournamentid}", name="_rest_import_match_schedule", options={"expose"=true})
     * @param $tournamentid
     * @param Request $request
     * @return JsonResponse
     */
    public function restImportMatchScheduleAction($tournamentid, Request $request) {
        /* @var $tournament Tournament */
        try {
            $tournament = $this->get('entity')->getTournamentById($tournamentid);
        } catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }
        try {
            /* @var $utilService Util */
            $utilService = $this->get('util');
            /* @var $user User */
            $user = $utilService->getCurrentUser();
            $host = $tournament->getHost();
            $utilService->validateEditorAdminUser($user, $host);
        } catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        } catch (RuntimeException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }
        /* @var $uploadedFile UploadedFile */
        $uploadedFile = $request->get('file');
        if (isset($uploadedFile)) {
            try {
                $matchListRaw = $this->import($uploadedFile);
                $matchList = $this->validateData($tournament, $matchListRaw);
                $this->commitImport($tournament, $matchList);
            } catch (ValidationException $exc) {
                return new JsonResponse(array('errors' => array($this->get('translator')->trans('FORM.ERROR.' . $exc->getMessage(), array(), 'admin') . " [" . $exc->getDebugInfo() . "]")), Response::HTTP_BAD_REQUEST);
            }
            return new JsonResponse(array('status' => array('count' => count($matchList))));
        }
        return new JsonResponse(array('errors' => array()), Response::HTTP_BAD_REQUEST);
    }
    
    /**
     * Import match plan from text file
     * @param Tournament $tournament Import related to tournament
     * @param String $date Date of match
     * @param String $importStr Match plan - must follow this syntax:
     *                          - Match no
     *                          - Match date (local format - j-m-Y)
     *                          - Match time (local format - G.i)
     *                          - Category name
     *                          - Group name
     *                          - Playground no
     *                          - Home team
     *                                  team name 'division' (country)
     *                                  rank group name
     *                          - Away team
     *                                  team name 'division' (country)
     *                                  rank group name
     *
     * Examples:    385;10-7-2015;13.00;C;(A);7;1 A;2 B
     *              361;11-7-2015;9.00;F;10:1A;3;8:1A#1;8:2A#1
     *              212;5-7-2015;9.15;C;A;7;AETNA MASCALUCIA (ITA);TVIS KFUM 'A' (DNK)
     *
     * Country is only used if team name is ambigious - however syntax must be maintained.
     * Division can be ommitted.
     */
    public function import($uploadedFile) {
        $keys = array("matchno","date","time","category","group","playground","teamA","teamB");
        $matches = array_map(function ($str) { return str_getcsv($str, ";"); }, explode("\n", $uploadedFile));
        array_walk($matches, function(&$a) use ($keys) {
            $a = array_combine($keys, $a);
        });
        return $matches;
    }

    private function validateData(Tournament $tournament, $matchListRaw) {
        $matchList = array();
        foreach ($matchListRaw as $matchRaw) {
            $isFinal = false;
            /* @var $category Category */
            $category = $this->get('logic')->getCategoryByName($tournament->getId(), $matchRaw['category']);
            if ($category == null) {
                throw new ValidationException("BADCATEGORY", "tournament=".$tournament->getId()." category=".$matchRaw['category']);
            }
            /* @var $playground Playground */
            $playground = $this->get('logic')->getPlaygroundByNo($tournament->getId(), $matchRaw['playground']);
            if ($playground == null) {
                throw new ValidationException("BADPLAYGROUND", "tournament=".$tournament->getId()." no=".$matchRaw['playground']);
            }
            $groupname = $matchRaw['group'];
            if (preg_match('/(?<classification>\d+)-(?<litra>\d+)(?<branch>\w*)/', $groupname, $args)) {
                $isFinal = true;
                $group = $args;
            }
            else {
                $group = $this->get('logic')->getGroupByCategory($tournament->getId(), $matchRaw['category'], $groupname);
                if ($group == null) {
                    throw new ValidationException("BADGROUP", "tournament=".$tournament->getId()." category=".$matchRaw['category']." group=".$groupname);
                }
            }
            $matchdate = date_create_from_format($this->get('translator')->trans('FORMAT.DATE'), $matchRaw['date']);
            $matchtime = date_create_from_format($this->get('translator')->trans('FORMAT.TIME'), $matchRaw['time']);
            if ($matchdate === false || $matchtime === false) {
                throw new ValidationException("BADDATE", "date=".$matchRaw['date']." time=".$matchRaw['time']);
            }
            $date = Date::getDate($matchdate);
            $time = Date::getTime($matchtime);
            $paList = $playground->getPlaygroundAttributes();
            $pattr = null;
            foreach ($paList as $pa) {
                if ($pa->getDate() == $date && $pa->getStart() <= $time && $pa->getEnd() >= $time) {
                    $pattr = $pa;
                    break;
                }
            }
            if (!$pattr) {
                throw new ValidationException("BADDATE", "No playground attribute for date=".$matchRaw['date']);
            }
            if ($isFinal) {
                $teamA = $this->getQRel($category, $this->parseImportTeam($matchRaw['teamA']), MatchSupport::$HOME);
                $teamB = $this->getQRel($category, $this->parseImportTeam($matchRaw['teamB']), MatchSupport::$AWAY);
            }
            else {
                /* @var $group Group */
                $teamA = $this->getTeam($group->getId(), $this->parseImportTeam($matchRaw['teamA']), MatchSupport::$HOME);
                $teamB = $this->getTeam($group->getId(), $this->parseImportTeam($matchRaw['teamB']), MatchSupport::$AWAY);
            }
            $match = array(
                'matchno' => $matchRaw['matchno'],
                'date' => $date,
                'time' => $time,
                'pa' => $pattr,
                'category' => $category,
                'group' => $group,
                'playground' => $playground,
                'teamA' => $teamA,
                'teamB' => $teamB,
                'final' => $isFinal
            );
            $matchList[] = $match;
        }
        return $matchList;
    }

    private function parseImportTeam($token) {
        if (preg_match('/0:(?<litra>[^\#]+)#(?<rank>\d+)/', $token, $args)) {
            $args['classification'] = Group::$PRE;
            $args['branch'] = "";
        }
        elseif (preg_match('/(?<classification>\d+):(?<litra>\d+)(?<branch>[AB]*)#(?<rank>\d+)/', $token, $args)) {}
        elseif (preg_match('/(?<name>[^\|\(]+) \|(?<division>\w+)\| \((?<country>\w+)\)/', $token, $args)) {}
        elseif (preg_match('/(?<name>[^\|\(]+) \((?<country>\w+)\)/', $token, $args)) {}
        else {
            $args = array('name' => $token);
        }
        return $args;
    }

    private function getQRel(Category $category, $teamRaw, $away) {
        $qrel = new QMatchScheduleRelation();
        $qrel->setClassification($teamRaw['classification']);
        $qrel->setLitra($teamRaw['litra']);
        $qrel->setBranch($teamRaw['branch']);
        if ($teamRaw['classification'] == Group::$PRE) {
            /* @var $group Group */
            foreach ($category->getGroupsClassified(Group::$PRE)->getValues() as $nth => $group) {
                if ($teamRaw['litra'] == $group->getName()) {
                    $qrel->setLitra($nth+1);
                    $qrel->setBranch("");
                    break;
                }
            }
        }
        if (!is_numeric($teamRaw['rank']) || $teamRaw['rank'] < 1) {
            throw new ValidationException("BADRANK", "rank=".$teamRaw['rank']);
        }
        $qrel->setRank($teamRaw['rank']);
        $qrel->setAwayteam($away);
        return $qrel;
    }

    private function getTeam($groupid, $teamRaw, $away) {
        /* @var $infoteam TeamInfo */
        $infoteam = null;
        $teamList = $this->get('logic')->getTeamByGroup(
            $groupid,
            $teamRaw['name'],
            isset($teamRaw['division']) ? $teamRaw['division'] : '');
        if (count($teamList) == 1) {
            $infoteam = reset($teamList);
        }
        else {
            foreach ($teamList as $team) {
                if (isset($teamRaw['country']) && $team->country == $teamRaw['country']) {
                    $infoteam = $team;
                    break;
                }
            }
        }
        if (!$infoteam) {
            throw new ValidationException("BADTEAM", "group=".$groupid." team=".$teamRaw['name'].
                (isset($teamRaw['division']) ? " '".$teamRaw['division']."'" : "").
                (isset($teamRaw['country']) ? " (".$teamRaw['country'].")" : ""));
        }
        $relation = new MatchScheduleRelation();
        $relation->setTeam($this->get('entity')->getTeamById($infoteam->getId()));
        $relation->setAwayteam($away);
        return $relation;
    }

    private function commitImport(Tournament $tournament, $matchList) {
        $em = $this->getDoctrine()->getManager();

        foreach ($matchList as $match) {
            if ($match['final']) {
                $matchrec = new QMatchSchedule();
                $matchrec->setCategory($match['category']);
                $matchrec->setClassification($match['group']['classification']);
                $matchrec->setLitra($match['group']['litra']);
                $matchrec->setBranch($match['group']['branch']);
                $matchrec->addQMatchRelation($match['teamA']);
                $matchrec->addQMatchRelation($match['teamB']);
            }
            else {
                $matchrec = new MatchSchedule();
                $matchrec->setGroup($match['group']);
                $matchrec->addMatchRelation($match['teamA']);
                $matchrec->addMatchRelation($match['teamB']);
            }
            $matchrec->setTournament($tournament);
            $matchPlan = new MatchSchedulePlan();
            $matchPlan->setPlaygroundAttribute($match['pa']);
            $matchPlan->setMatchstart($match['time']);
            $matchPlan->setFixed(true);
            $matchrec->setPlan($matchPlan);
            $em->persist($matchrec);
        }
        $em->flush();
    }
}
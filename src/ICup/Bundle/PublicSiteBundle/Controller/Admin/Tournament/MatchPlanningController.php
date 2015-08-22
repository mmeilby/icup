<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Tournament;

use Doctrine\Common\Collections\ArrayCollection;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchSchedule;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchSchedulePlan;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchScheduleRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\PlaygroundAttribute;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\QMatchScheduleRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\TeamInfo;
use ICup\Bundle\PublicSiteBundle\Services\Doctrine\MatchSupport;
use ICup\Bundle\PublicSiteBundle\Services\Doctrine\TournamentSupport;
use ICup\Bundle\PublicSiteBundle\Entity\MatchSearchForm;
use ICup\Bundle\PublicSiteBundle\Entity\ResultForm;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;
use ICup\Bundle\PublicSiteBundle\Services\Entity\PlaygroundAttribute as PA;
use ICup\Bundle\PublicSiteBundle\Entity\MatchPlan;
use ICup\Bundle\PublicSiteBundle\Services\Entity\PlanningOptions;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use SplFileObject;
use DateTime;

class MatchPlanningController extends Controller
{
    /**
     * Configure options for match planning
     * @Route("/edit/m/options/plan/{tournamentid}", name="_edit_match_planning_options")
     * @Template("ICupPublicSiteBundle:Edit:planoptions.html.twig")
     */
    public function configureOptionsAction($tournamentid, Request $request) {
        $tournament = $this->checkArgs($tournamentid);
        $returnUrl = $this->get('util')->getReferer();

        $form = $this->makePlanForm($tournament);
        $form->handleRequest($request);
        if ($form->isValid()) {
            /* @var $options PlanningOptions */
            $options = $form->getData();
            $tournament->getOption()->setDrr($options->isDoublematch());
            $em = $this->getDoctrine()->getManager();
            $em->flush();
            $request->getSession()->set('planning.options', $options);
            return $this->redirect($this->generateUrl("_edit_match_planning_result", array('tournamentid' => $tournament->getId())));
        }
        $host = $tournament->getHost();
        return array('form' => $form->createView(), 'host' => $host, 'tournament' => $tournament);
    }
    
    private function makePlanForm(Tournament $tournament) {
        $options = new PlanningOptions();
        $options->setDoublematch($tournament->getOption()->isDrr());
        $formDef = $this->createFormBuilder($options);
        $formDef->add('doublematch',
                      'checkbox', array('label' => 'FORM.MATCHPLANNING.DOUBLEMATCH.PROMPT',
                                        'help' => 'FORM.MATCHPLANNING.DOUBLEMATCH.HELP',
                                        'required' => false,
                                        'disabled' => false,
                                        'translation_domain' => 'admin'));
        $formDef->add('preferpg',
                      'checkbox', array('label' => 'FORM.MATCHPLANNING.PREFERPG.PROMPT',
                                        'help' => 'FORM.MATCHPLANNING.PREFERPG.HELP',
                                        'required' => false,
                                        'disabled' => false,
                                        'translation_domain' => 'admin'));
        $formDef->add('save', 'submit', array('label' => 'FORM.MATCHPLANNING.SUBMIT',
                                                'translation_domain' => 'admin',
                                                'icon' => 'fa fa-check'));
        return $formDef->getForm();
    }

    /**
     * Plan assignment of teams to groups
     * @Route("/edit/m/groups/plan/{tournamentid}", name="_edit_match_planning_groups")
     * @Template("ICupPublicSiteBundle:Edit:plangroups.html.twig")
     */
    public function planGroupsAction($tournamentid, Request $request) {
        /* @var $tournament Tournament */
        $tournament = $this->checkArgs($tournamentid);
        $host = $tournament->getHost();
        $categoryList = array();
        $categories = $tournament->getCategories();
        /* @var $category Category */
        foreach ($categories as $category) {
            $categoryList[$category->getId()] = array('category' => $category, 'group' => array());
            $groups = $category->getGroups();
            /* @var $group Group */
            foreach ($groups as $group) {
                $teams = count($this->get('logic')->listTeamsByGroup($group->getId()));
                $categoryList[$category->getId()]['group'][] = array('obj' => $group, 'cnt' => $teams);
            }
        }
        uasort($categoryList, function ($cat1, $cat2) {
            return count($cat1['group']) < count($cat2['group']) ? 1 : (count($cat1['group']) == count($cat2['group']) ? 0 : -1);
        });

        return array('host' => $host,
                     'tournament' => $tournament,
                     'catlist' => $categoryList);
    }

    /**
     * Show planning results overview
     * @Route("/edit/m/result/plan/{tournamentid}", name="_edit_match_planning_result")
     * @Template("ICupPublicSiteBundle:Edit:planmatch.html.twig")
     */
    public function resultMatchesAction($tournamentid, Request $request) {
        $tournament = $this->checkArgs($tournamentid);
        $form = $this->createFormBuilder(array('file' => null))
                            ->add('file', 'file', array(
                                                    'label' => 'FORM.MATCHPLANNING.MATCHIMPORT.FILE',
                                                    'required' => false,
                                                    'disabled' => false,
                                                    'translation_domain' => 'admin'))
                            ->getForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            /* @var $uploadedFile UploadedFile */
            $uploadedFile = $request->files->get('form');
            try {
                $matchListRaw = $this->import($uploadedFile['file']);
                $matchList = $this->validateData($tournament, $matchListRaw);
                $this->commitImport($tournament, $matchList);
            } catch (ValidationException $exc) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.ERROR.'.$exc->getMessage(), array(), 'admin')." [".$exc->getDebugInfo()."]"));
            }
        }
        $result = $this->get('planning')->getSchedule($tournamentid);
        $host = $tournament->getHost();
        return array('host' => $host,
                     'tournament' => $tournament,
                     'unassigned' => $result['unassigned_by_category'],
                     'planned' => count($result['matches']) > 0,
                     'upload_form' => $form->createView());
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
     *              212;5-7-2015;9.15;C;A;7;AETNA MASCALUCIA (ITA);TVIS KFUM 'A' (DNK)
     *
     * Country is only used if team name is ambigious - however syntax must be maintained.
     * Division can be ommitted.
     */
    public function import(UploadedFile $uploadedFile) {
        $keys = array("matchno","date","time","category","group","playground","teamA","teamB");
        $matches = array();
        if ($uploadedFile->isValid() && $uploadedFile->isFile()) {
            /* @var $file SplFileObject */
            $file = $uploadedFile->openFile();
            while (!$file->eof()) {
                $csv = $file->fgetcsv(";");
                $match = array();
                foreach ($csv as $idx => $data) {
                    if ($data) {
                        if (array_key_exists($idx, $keys)) {
                            if ($keys[$idx] == 'teamA' || $keys[$idx] == 'teamB') {
                                $match[$keys[$idx]] = $this->parseImportTeam($data);
                            }
                            else {
                                $match[$keys[$idx]] = $data;
                            }
                        }
                        else {
                            $match[] = $data;
                        }
                    }
                }
                if (count($match) > 0) {
                    $matches[] = $match;
                }
            }
        }
        return $matches;
    }

    private function parseImportTeam($token) {
        if (preg_match('/#(?<rank>\d+) (?<group>\w+)/', $token, $args)) {}
        elseif (preg_match('/(?<name>[^\'\(]+) \'(?<division>\w+)\' \((?<country>\w+)\)/', $token, $args)) {}
        elseif (preg_match('/(?<name>[^\'\(]+) \((?<country>\w+)\)/', $token, $args)) {}
        else {
            $args = array('name' => $token);
        }
        return $args;
    }

    private function validateData(Tournament $tournament, $matchListRaw) {
        $matchList = array();
        foreach ($matchListRaw as $matchRaw) {
            $playground = $this->get('logic')->getPlaygroundByNo($tournament->getId(), $matchRaw['playground']);
            if ($playground == null) {
                throw new ValidationException("BADPLAYGROUND", "tournament=".$tournament->getId()." no=".$matchRaw['playground']);
            }
            $group = $this->get('logic')->getGroupByCategory($tournament->getId(), $matchRaw['category'], $matchRaw['group']);
            if ($group == null) {
                throw new ValidationException("BADGROUP", "tournament=".$tournament->getId()." category=".$matchRaw['category']." group=".$matchRaw['group']);
            }
            $matchdate = date_create_from_format($this->get('translator')->trans('FORMAT.DATE'), $matchRaw['date']);
            $matchtime = date_create_from_format($this->get('translator')->trans('FORMAT.TIME'), $matchRaw['time']);
            if ($matchdate === false || $matchtime === false) {
                throw new ValidationException("BADDATE", "date=".$matchRaw['date']." time=".$matchRaw['time']);
            }
            $date = Date::getDate($matchdate);
            $time = Date::getTime($matchtime);
            $paList = $this->get('logic')->listPlaygroundAttributes($playground->getId());
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
            $teamA = $this->getTeam($group->getId(), $matchRaw['teamA']);
            $teamB = $this->getTeam($group->getId(), $matchRaw['teamB']);
            $match = array(
                'matchno' => $matchRaw['matchno'],
                'date' => $date,
                'time' => $time,
                'pa' => $pattr,
                'category' => $matchRaw['category'],
                'group' => $group,
                'playground' => $playground,
                'teamA' => $teamA,
                'teamB' => $teamB
            );
            $matchList[] = $match;
        }
        return $matchList;
    }

    private function getTeam($groupid, $teamRaw) {
        if (isset($teamRaw['rank'])) {
            $rankingGroup = $this->get('logic')->getGroup($groupid, $teamRaw['group']);
            if ($rankingGroup == null) {
                throw new ValidationException("BADGROUP", "group=".$teamRaw['group']);
            }
            if (!is_numeric($teamRaw['rank']) || $teamRaw['rank'] < 1) {
                throw new ValidationException("BADRANK", "rank=".$teamRaw['rank']);
            }
            $relation = new QMatchScheduleRelation();
            $relation->setRank($teamRaw['rank']);
            $relation->setGroup($rankingGroup);
        }
        else {
            $infoteam = null;
            $teamList = $this->get('logic')->getTeamByGroup(
                $groupid,
                $teamRaw['name'],
                isset($teamRaw['division']) ? $teamRaw['division'] : '');
            if (count($teamList) == 1) {
                $infoteam = $teamList[0];
            }
            foreach ($teamList as $team) {
                if (isset($teamRaw['country']) && $team->country == $teamRaw['country']) {
                    $infoteam = $team;
                    break;
                }
            }
            if (!$infoteam) {
                throw new ValidationException("BADTEAM", "group=".$groupid." team=".$teamRaw['name'].
                    (isset($teamRaw['division']) ? " '".$teamRaw['division']."'" : "").
                    (isset($teamRaw['country']) ? " (".$teamRaw['country'].")" : ""));
            }
            $relation = new MatchScheduleRelation();
            $relation->setTeam($this->get('entity')->getTeamById($infoteam->getId()));
        }
        return $relation;
    }

    private function commitImport(Tournament $tournament, $matchList) {
        $em = $this->getDoctrine()->getManager();

        foreach ($matchList as $match) {
            $matchrec = new MatchSchedule();
            $matchrec->setTournament($tournament);
            $matchrec->setGroup($match['group']);
            $hr = $match['teamA'];
            $hr->setAwayteam(MatchSupport::$HOME);
            $matchrec->addMatchRelation($hr);
            $ar = $match['teamB'];
            $ar->setAwayteam(MatchSupport::$AWAY);
            $matchrec->addMatchRelation($ar);
            $matchPlan = new MatchSchedulePlan();
            $matchPlan->setPlaygroundAttribute($match['pa']);
            $matchPlan->setMatchstart($match['time']);
            $matchPlan->setFixed(true);
            $matchrec->setPlan($matchPlan);
            $em->persist($matchrec);
        }
        $em->flush();
    }

    /**
     * Clear match plan and make space for new plans
     * @Route("/edit/m/reset/plan/{tournamentid}", name="_edit_match_planning_reset")
     * @Template("ICupPublicSiteBundle:Edit:planmatch.html.twig")
     */
    public function resetMatchesAction($tournamentid) {
        $tournament = $this->checkArgs($tournamentid);
        $this->get('logic')->removeMatchSchedules($tournamentid);
        return $this->redirect($this->generateUrl("_edit_match_planning_result", array('tournamentid' => $tournament->getId())));
    }

    /**
     * Plan matches according to assigned groups and match configuration
     * @Route("/edit/m/plan/plan/{tournamentid}", name="_edit_match_planning_plan")
     * @Template("ICupPublicSiteBundle:Edit:planmatch.html.twig")
     */
    public function planMatchesAction($tournamentid) {
        $tournament = $this->checkArgs($tournamentid);
        $options = new PlanningOptions();
        $options->setDoublematch($tournament->getOption()->isDrr());
        $options->setFinals(false);
        $options->setPreferpg(false);
        $this->get('planning')->planTournament($tournament->getId(), $options);
        return $this->redirect($this->generateUrl("_edit_match_planning_result", array('tournamentid' => $tournament->getId())));
    }

    /**
     * List the latest matches for a tournament
     * @Route("/edit/m/save/plan/{tournamentid}", name="_edit_match_planning_save")
     * @Method("GET")
     */
    public function saveMatchesAction($tournamentid, Request $request) {
        $tournament = $this->checkArgs($tournamentid);
        $result = $this->get('planning')->getSchedule($tournamentid);

        // Only if tournament has not been started we are allowed to wipe the teams
        if ($this->get('tmnt')->getTournamentStatus($tournamentid, new DateTime()) == TournamentSupport::$TMNT_ENROLL) {
            $this->get('tmnt')->wipeMatches($tournamentid);

            $em = $this->getDoctrine()->getEntityManager();
            $em->beginTransaction();
            try {
                /* @var $match MatchPlan */
                foreach ($result['matches'] as $match) {
                    $matchrec = new Match();
                    $matchrec->setMatchno($match->getMatchno());
                    $matchrec->setDate($match->getDate());
                    $matchrec->setTime($match->getTime());
                    $matchrec->setGroup($match->getGroup());
                    $matchrec->setPlayground($match->getPlayground()->getId());

                    $resultreqA = new MatchRelation();
                    $resultreqA->setCid($match->getTeamA()->getId());
                    $resultreqA->setAwayteam(MatchSupport::$HOME);
                    $resultreqA->setScorevalid(false);
                    $resultreqA->setScore(0);
                    $resultreqA->setPoints(0);
                    $matchrec->addMatchRelation($resultreqA);

                    $resultreqB = new MatchRelation();
                    $resultreqB->setCid($match->getTeamB()->getId());
                    $resultreqB->setAwayteam(MatchSupport::$AWAY);
                    $resultreqB->setScorevalid(false);
                    $resultreqB->setScore(0);
                    $resultreqB->setPoints(0);
                    $matchrec->addMatchRelation($resultreqB);

                    $em->persist($matchrec);
                }
                $em->flush();
                $em->commit();
            } catch (Exception $e) {
                $em->rollback();
                throw $e;
            }

            $request->getSession()->getFlashBag()->add(
                'data_saved',
                'FORM.MATCHPLANNING.PLAN_SAVED'
            );
        }
        return $this->redirect($this->generateUrl("_edit_match_planning_result", array('tournamentid' => $tournament->getId())));
    }
    
    /**
     * List the latest matches for a tournament
     * @Route("/edit/m/view/plan/{tournamentid}", name="_edit_match_planning_view")
     * @Template("ICupPublicSiteBundle:Edit:planmatchlist.html.twig")
     */
    public function viewMatchesAction($tournamentid) {
        $tournament = $this->checkArgs($tournamentid);
        $result = $this->get('planning')->getSchedule($tournamentid);

        $matches = array();
        /* @var $match MatchPlan */
        foreach ($result['matches'] as $match) {
            $matches[$match->getDate()][] = $match;
        }

        $host = $tournament->getHost();
        return array('host' => $host,
                     'tournament' => $tournament,
                     'matchlist' => $matches,
                     'shortmatchlist' => $matches);
    }

    /**
     * List the latest matches for a tournament
     * @Route("/edit/m/advice/plan/{tournamentid}", name="_edit_match_planning_advice")
     * @Template("ICupPublicSiteBundle:Edit:planmatchadvice.html.twig")
     */
    public function listAdvicesAction($tournamentid) {
        $tournament = $this->checkArgs($tournamentid);
        $result = $this->get('planning')->getSchedule($tournamentid);

        $timeslots = array();
        foreach ($result['timeslots'] as $ts) {
            $timeslots[date_format($ts->getSchedule(), "Y/m/d")][] = $ts;
        }

        $host = $tournament->getHost();
        return array(
            'host' => $host,
            'tournament' => $tournament,
            'available_timeslots' => $timeslots,
            'advices' => $result['advices']);
    }

    /**
     * List the latest matches for a tournament
     * @Route("/edit/m/solve/plan/{tournamentid}/{matchid}", name="_edit_match_planning_solve")
     * @Template("ICupPublicSiteBundle:Edit:planmatchadvice.html.twig")
     */
    public function solveAction($tournamentid, $matchid) {
        $tournament = $this->checkArgs($tournamentid);
        $result = $this->get('planning')->getSchedule($tournamentid);
        $this->get('planning')->solveMatch($tournamentid, $matchid, $result);
        return $this->redirect($this->generateUrl("_edit_match_planning_result", array('tournamentid' => $tournament->getId())));
    }

    /**
     * Search an existing match by number, match date, group or playground
     * @Route("/edit/m/maint/plan/{tournamentid}", name="_edit_match_maint")
     * @Template("ICupPublicSiteBundle:Edit:planmaint.html.twig")
     */
    public function matchMaintAction($tournamentid) {
        $tournament = $this->checkArgs($tournamentid);
        // Prepare form fields for javascript form
        $form = $this->makeResultForm($tournament->getId());
        $host = $tournament->getHost();
        return array('form' => $form->createView(), 'host' => $host, 'tournament' => $tournament);
    }

    private function makeResultForm($tournamentid) {
        $categoryList = $this->get('logic')->listCategories($tournamentid);
        $categories = array();
        foreach ($categoryList as $category) {
            $categories[$category->getId()] =
                $this->get('translator')->trans('CATEGORY', array(), 'tournament')." ".
                $category->getName()." - ".
                $this->get('translator')->transChoice(
                    'GENDER.'.$category->getGender().$category->getClassification(),
                    $category->getAge(),
                    array('%age%' => $category->getAge()),
                    'tournament');
        }
        $playgroundList = $this->get('logic')->listPlaygroundsByTournament($tournamentid);
        $playgrounds = array();
        foreach ($playgroundList as $playground) {
            $playgrounds[$playground->getId()] = $playground->getName();
        }

        $formDef = $this->createFormBuilder();
        $formDef->add('matchno', 'text', array('label' => 'FORM.MATCHPLANNING.MATCHNO',
            'required' => false,
            'help' => 'FORM.MATCHPLANNING.HELP.MATCHNO',
            'icon' => 'fa fa-lg fa-tag',
            'translation_domain' => 'admin'));
        $formDef->add('date', 'text', array('label' => 'FORM.MATCHPLANNING.DATE',
            'required' => false,
            'help' => 'FORM.MATCHPLANNING.HELP.DATE',
            'icon' => 'fa fa-lg fa-calendar',
            'translation_domain' => 'admin'));
        $formDef->add('category', 'choice', array('label' => 'FORM.MATCHPLANNING.CATEGORY',
            'choices' => $categories, 'empty_value' => 'FORM.MATCHPLANNING.DEFAULT',
            'required' => false,
            'help' => 'FORM.MATCHPLANNING.HELP.CATEGORY',
            'icon' => 'fa fa-lg fa-sitemap',
            'translation_domain' => 'admin'));
        $formDef->add('group', 'choice', array('label' => 'FORM.MATCHPLANNING.GROUP',
            'choices' => array(), 'empty_value' => 'FORM.MATCHPLANNING.DEFAULT',
            'required' => false,
            'help' => 'FORM.MATCHPLANNING.HELP.GROUP',
            'icon' => 'fa fa-lg fa-bookmark',
            'translation_domain' => 'admin'));
        $formDef->add('playground', 'choice', array('label' => 'FORM.MATCHPLANNING.PLAYGROUND',
            'choices' => $playgrounds, 'empty_value' => 'FORM.MATCHPLANNING.DEFAULT',
            'required' => false,
            'help' => 'FORM.MATCHPLANNING.HELP.PLAYGROUND',
            'icon' => 'fa fa-lg fa-futbol-o',
            'translation_domain' => 'admin'));
        return $formDef->getForm();
    }

    /**
     * Download planned matches as CSV file
     * @Route("/edit/m/download/plan/{tournamentid}", name="_edit_match_planning_download")
     * @Method("GET")
     */
    public function downloadFileAction($tournamentid)
    {
        $tournament = $this->checkArgs($tournamentid);
        $result = $this->get('planning')->getSchedule($tournamentid);

        $outputar = $this->getResponses($result);
        $tmpfname = tempnam("/tmp", "icup_match_plan_");

        $fp = fopen($tmpfname, "w");
        foreach ($outputar as $output) {
            fputs($fp, iconv("UTF-8", "ISO-8859-1", $output));
            fputs($fp, "\r\n");
        }
        fclose($fp);
        
        $response = new BinaryFileResponse($tmpfname);
        $response->headers->set('Content-Type', 'text/plain');
        $response->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                str_replace(' ', '-', $tournament->getName()).'_'.date("j-m-Y").'.txt');
        return $response;
    }
    
    private function getResponses($result) {
        $outputar = array("matchno;date;time;category;group;playground;teamA;teamB");
        /* @var $match MatchPlan */
        foreach ($result['matches'] as $match) {
            $schedule = Date::getDateTime($match->getDate(), $match->getTime());
            $date = date_format($schedule, "j-n-Y");
            $time = date_format($schedule, "G.i");
            $outputstr = $match->getMatchno().';'.$date.';'.$time.
                    ';"'.$match->getCategory()->getName().
                    '";"'.$match->getGroup()->getName().
                    '";"'.$match->getPlayground()->getNo().
                    '";"'.str_replace('"', "'", $match->getTeamA()->getName())." (".$match->getTeamA()->getCountry().")".
                    '";"'.str_replace('"', "'", $match->getTeamB()->getName())." (".$match->getTeamB()->getCountry().")".
                    '"';
            $outputar[] = $outputstr;
        }
        if (count($result['unassigned']) > 0) {
            $outputar[] = ";;;;;;;";
            foreach ($result['unassigned'] as $match) {
                $outputstr =
                        ';;;"'.$match->getCategory()->getName().
                        '";"'.$match->getGroup()->getName().
                        '";;"'.str_replace('"', "'", $match->getTeamA()->getName())." (".$match->getTeamA()->getCountry().")".
                        '";"'.str_replace('"', "'", $match->getTeamB()->getName())." (".$match->getTeamB()->getCountry().")".
                        '"';
                $outputar[] = $outputstr;
            }
        }
        $outputar[] = ";;;;;;;";
        $tid = array();
        foreach ($result['matches'] as $match) {
            if (!isset($tid[$match->getTeamA()->getId()])) {
                $outputstr = '"'.$match->getCategory()->getName().
                        '";"'.$match->getGroup()->getName().
                        '";"'.str_replace('"', "'", $match->getTeamA()->getName())." (".$match->getTeamA()->getCountry().")".'"';
                $tid[$match->getTeamA()->getId()] = $match->getTeamA()->getName();
                $outputar[] = $outputstr;
            }
        }
        
        return $outputar;
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
        $utilService->validateEditorAdminUser($user, $host->getId());
        return $tournament;
    }
}

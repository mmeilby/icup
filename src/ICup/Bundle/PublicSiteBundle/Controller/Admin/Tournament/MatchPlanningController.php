<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Tournament;

use Doctrine\Common\Collections\ArrayCollection;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Champion;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchSchedule;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchSchedulePlan;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchScheduleRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Playground;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\PlaygroundAttribute;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\QMatchRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\QMatchSchedule;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\QMatchSchedulePlan;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\QMatchScheduleRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Entity\QMatchPlan;
use ICup\Bundle\PublicSiteBundle\Entity\TeamInfo;
use ICup\Bundle\PublicSiteBundle\Services\Doctrine\MatchSupport;
use ICup\Bundle\PublicSiteBundle\Services\Doctrine\TournamentSupport;
use ICup\Bundle\PublicSiteBundle\Entity\MatchSearchForm;
use ICup\Bundle\PublicSiteBundle\Entity\ResultForm;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;
use ICup\Bundle\PublicSiteBundle\Services\Entity\PlaygroundAttribute as PA;
use ICup\Bundle\PublicSiteBundle\Entity\MatchPlan;
use ICup\Bundle\PublicSiteBundle\Services\Entity\PlanningOptions;
use ICup\Bundle\PublicSiteBundle\Services\Util;
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
use Exception;

class MatchPlanningController extends Controller
{
    /**
     * Configure options for match planning
     * @Route("/edit/m/options/plan/{tournamentid}", name="_edit_match_planning_options")
     * @Template("ICupPublicSiteBundle:Edit:planoptions.html.twig")
     */
    public function configureOptionsAction($tournamentid, Request $request) {
        $tournament = $this->checkArgs($tournamentid);
        $form = $this->makePlanForm($tournament);
        $form->handleRequest($request);
        if ($form->isValid()) {
            /* @var $options PlanningOptions */
            $options = $form->getData();
            $tournament->getOption()->setDrr($options->isDoublematch());
            $tournament->getOption()->setSvd($options->isPreferpg());
            $em = $this->getDoctrine()->getManager();
            $em->flush();
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
    public function planGroupsAction($tournamentid) {
        /* @var $tournament Tournament */
        $tournament = $this->checkArgs($tournamentid);
        $host = $tournament->getHost();
        return array(
            'host' => $host,
            'tournament' => $tournament);
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
            if (isset($uploadedFile['file'])) {
                try {
                    $matchListRaw = $this->import($uploadedFile['file']);
                    $matchList = $this->validateData($tournament, $matchListRaw);
                    $this->commitImport($tournament, $matchList);
                } catch (ValidationException $exc) {
                    $form->addError(new FormError($this->get('translator')->trans('FORM.ERROR.'.$exc->getMessage(), array(), 'admin')." [".$exc->getDebugInfo()."]"));
                }
            }
            else {
                $form->addError(new FormError($this->get('translator')->trans('FORM.MATCHPLANNING.NOFILE', array(), 'admin')));
            }
        }
        $result = $this->get('planning')->getSchedule($tournament);
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
     *              361;11-7-2015;9.00;F;10:1A;3;8:1A#1;8:2A#1
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
                        if (isset($keys[$idx])) {
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
                throw new ValidationException("BADDATE", "No playground attribute for date=".$matchRaw['date'].' '.$matchRaw['time']." (#".$matchRaw['matchno'].")");
            }
            if ($isFinal) {
                $teamA = $this->getQRel($category, $matchRaw['teamA'], MatchSupport::$HOME);
                $teamB = $this->getQRel($category, $matchRaw['teamB'], MatchSupport::$AWAY);
            }
            else {
                $teamA = $this->getTeam($group->getId(), $matchRaw['teamA'], MatchSupport::$HOME);
                $teamB = $this->getTeam($group->getId(), $matchRaw['teamB'], MatchSupport::$AWAY);
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
        $qrel->setRank($teamRaw['rank']);
        $qrel->setAwayteam($away);
        return $qrel;
    }

    private function getTeam($groupid, $teamRaw, $away) {
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
            $relation->setAwayteam($away);
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
            $relation->setAwayteam($away);
        }
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

    /**
     * Clear match plan and make space for new plans
     * @Route("/edit/m/reset/plan/{tournamentid}", name="_edit_match_planning_reset")
     * @Template("ICupPublicSiteBundle:Edit:planmatch.html.twig")
     */
    public function resetMatchesAction($tournamentid) {
        $tournament = $this->checkArgs($tournamentid);
        $this->get('logic')->removeMatchSchedules($tournament);
        $this->get('logic')->removeQMatchSchedules($tournament);
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
        $options->setPreferpg(false);
        $this->get('planning')->planTournament($tournament, $options);
        return $this->redirect($this->generateUrl("_edit_match_planning_result", array('tournamentid' => $tournament->getId())));
    }

    /**
     * List the latest matches for a tournament
     * @Route("/edit/m/save/plan/{tournamentid}", name="_edit_match_planning_save")
     * @Method("GET")
     */
    public function saveMatchesAction($tournamentid, Request $request) {
        $tournament = $this->checkArgs($tournamentid);
        // Only if tournament has not been started we are allowed to wipe the matches and qualifying groups
        if ($this->get('tmnt')->getTournamentStatus($tournamentid, new DateTime()) == TournamentSupport::$TMNT_ENROLL) {
            $this->get('planning')->publishSchedule($tournament);
            $request->getSession()->getFlashBag()->add(
                'data_saved',
                'FORM.MATCHPLANNING.PLAN_SAVED'
            );
        }
        else {
            $request->getSession()->getFlashBag()->add(
                'data_saved',
                'FORM.MATCHPLANNING.PLAN_NOT_SAVED'
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
        $host = $tournament->getHost();
        return array('host' => $host, 'tournament' => $tournament);
    }

    /**
     * List the latest matches for a tournament
     * @Route("/edit/m/advice/plan/{tournamentid}", name="_edit_match_planning_advice")
     * @Template("ICupPublicSiteBundle:Edit:planmatchadvice.html.twig")
     */
    public function listAdvicesAction($tournamentid) {
        $tournament = $this->checkArgs($tournamentid);
        $result = $this->get('planning')->getSchedule($tournament);

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
        $result = $this->get('planning')->getSchedule($tournament);
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
        $form = $this->makeResultForm($tournament);
        $host = $tournament->getHost();
        return array('form' => $form->createView(), 'host' => $host, 'tournament' => $tournament);
    }

    private function makeResultForm(Tournament $tournament) {
        $categories = array();
        foreach ($tournament->getCategories() as $category) {
            $categories[$category->getId()] =
                $this->get('translator')->trans('CATEGORY', array(), 'tournament')." ".
                $category->getName()." - ".
                $this->get('translator')->transChoice(
                    'GENDER.'.$category->getGender().$category->getClassification(),
                    $category->getAge(),
                    array('%age%' => $category->getAge()),
                    'tournament');
        }
        $playgrounds = array();
        foreach ($tournament->getPlaygrounds() as $playground) {
            /* @var $playground Playground */
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
        $result = $this->get('planning')->getSchedule($tournament);

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
        foreach ($result['matches'] as $match) {
            if ($match instanceof QMatchPlan) {
                /* @var $match QMatchPlan */
                $schedule = Date::getDateTime($match->getDate(), $match->getTime());
                $date = date_format($schedule, "j-n-Y");
                $time = date_format($schedule, "G.i");
                $outputstr = $match->getMatchno().';'.$date.';'.$time.
                    ';"'.$match->getCategory()->getName().
                    '";"'.$match->getClassification()."-".$match->getLitra().
                    '";"'.$match->getPlayground()->getNo().
                    '";"'.$match->getRelA().
                    '";"'.$match->getRelB().
                    '"';
                $outputar[] = $outputstr;
            }
            else {
                /* @var $match MatchPlan */
                $schedule = Date::getDateTime($match->getDate(), $match->getTime());
                $date = date_format($schedule, "j-n-Y");
                $time = date_format($schedule, "G.i");
                $outputstr = $match->getMatchno().';'.$date.';'.$time.
                    ';"'.$match->getCategory()->getName().
                    '";"'.$match->getGroup()->getName().
                    '";"'.$match->getPlayground()->getNo().
                    '";"'.$this->getTeamRecord($match->getTeamA()).
                    '";"'.$this->getTeamRecord($match->getTeamB()).
                    '"';
                $outputar[] = $outputstr;
            }
        }
        if (count($result['unassigned']) > 0) {
            foreach ($result['unassigned'] as $match) {
                if ($match instanceof QMatchPlan) {
                    /* @var $match QMatchPlan */
                    $outputstr =
                        ';;;"'.$match->getCategory()->getName().
                        '";"'.$match->getClassification()."-".$match->getLitra().
                        '";;"'.$match->getRelA().
                        '";"'.$match->getRelB().
                        '"';
                    $outputar[] = $outputstr;
                }
                else {
                    /* @var $match MatchPlan */
                    $outputstr =
                        ';;;"'.$match->getCategory()->getName().
                        '";"'.$match->getGroup()->getName().
                        '";;"'.$this->getTeamRecord($match->getTeamA()).
                        '";"'.$this->getTeamRecord($match->getTeamB()).
                        '"';
                    $outputar[] = $outputstr;
                }
            }
        }
        return $outputar;
    }

    private function getTeamRecord($team) {
        /* @var $team Team */
        if ($team) {
            return str_replace('"', "|", $team->getTeamName())." (".$team->getClub()->getCountryCode().")";
        }
        else {
            return $this->get('translator')->trans("VACANT_TEAM", array(), 'teamname');
        }
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

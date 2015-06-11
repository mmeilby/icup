<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchSchedule;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\PlaygroundAttribute;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;
use ICup\Bundle\PublicSiteBundle\Services\Entity\PlaygroundAttribute as PA;
use ICup\Bundle\PublicSiteBundle\Entity\MatchPlan;
use ICup\Bundle\PublicSiteBundle\Services\Entity\PlanningOptions;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class MatchPlanningController extends Controller
{
    /**
     * List the latest matches for a tournament
     * @Route("/edit/m/plan/{tournamentid}", name="_edit_match_planning")
     * @Template("ICupPublicSiteBundle:Edit:plantournament.html.twig")
     */
    public function planMatchesAction($tournamentid, Request $request) {
        $tournament = $this->checkArgs($tournamentid);
        $returnUrl = $this->get('util')->getReferer();

        $form = $this->makePlanForm();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($form->isValid()) {
            $options = $form->getData();
            $this->get('planning')->planTournament($tournament->getId(), $options);
            return $this->redirect($this->generateUrl("_edit_match_planning_result", array('tournamentid' => $tournament->getId())));
        }
        $host = $this->get('entity')->getHostById($tournament->getPid());
        $categoryList = array();
        $categories = $this->get('logic')->listCategories($tournamentid);
        /* @var $category Category */
        foreach ($categories as $category) {
            $categoryList[$category->getId()] = array('category' => $category, 'group' => array());
            $groups = $this->get('logic')->listGroups($category->getId());
            /* @var $group Group */
            foreach ($groups as $group) {
                $teams = count($this->get('logic')->listTeamsByGroup($group->getId()));
                $categoryList[$category->getId()]['group'][] = array('obj' => $group, 'cnt' => $teams);
            }
        }

        return array('form' => $form->createView(), 'host' => $host, 'tournament' => $tournament, 'catlist' => $categoryList);
    }
    
    private function makePlanForm() {
        $formDef = $this->createFormBuilder(new PlanningOptions());
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
        $formDef->add('finals',
                      'checkbox', array('label' => 'FORM.MATCHPLANNING.FINALS.PROMPT',
                                        'help' => 'FORM.MATCHPLANNING.FINALS.HELP',
                                        'required' => false,
                                        'disabled' => false,
                                        'translation_domain' => 'admin'));
        $formDef->add('cancel', 'submit', array('label' => 'FORM.MATCHPLANNING.CANCEL',
                                                'translation_domain' => 'admin',
                                                'buttontype' => 'btn btn-default',
                                                'icon' => 'fa fa-times'));
        $formDef->add('save', 'submit', array('label' => 'FORM.MATCHPLANNING.SUBMIT',
                                                'translation_domain' => 'admin',
                                                'icon' => 'fa fa-check'));
        return $formDef->getForm();
    }
    
    /**
     * List the latest matches for a tournament
     * @Route("/edit/m/result/plan/{tournamentid}", name="_edit_match_planning_result")
     * @Template("ICupPublicSiteBundle:Edit:planmatch.html.twig")
     */
    public function resultMatchesAction($tournamentid) {
        $tournament = $this->checkArgs($tournamentid);
        $result = $this->get('planning')->getSchedule($tournamentid);

        $matches = array();
        /* @var $match MatchPlan */
        foreach ($result['matches'] as $match) {
            $matches[$match->getDate()][] = $match;
        }

        $timeslots = array();
        foreach ($result['timeslots'] as $ts) {
            $timeslots[date_format($ts->getSchedule(), "Y/m/d")][] = $ts;
        }

        $unassigned_by_category = $result['unassigned_by_category'];
        $host = $this->get('entity')->getHostById($tournament->getPid());
        return array('host' => $host,
                     'tournament' => $tournament,
                     'unassigned' => $unassigned_by_category,
                     'available_timeslots' => $timeslots);
    }

    /**
     * List the latest matches for a tournament
     * @Route("/edit/m/save/plan/{tournamentid}", name="_edit_match_planning_save")
     * @Method("GET")
     */
    public function saveMatchesAction($tournamentid, Request $request) {
        $tournament = $this->checkArgs($tournamentid);
        $result = $this->get('planning')->getSchedule($tournamentid);

        $em = $this->getDoctrine()->getManager();
        /* @var $match MatchPlan */
        foreach ($result['matches'] as $match) {
            $matchrec = new Match();
            $matchrec->setMatchno($match->getMatchno());
            $matchrec->setDate($match->getDate());
            $matchrec->setTime($match->getTime());
            $matchrec->setPid($match->getGroup()->getId());
            $matchrec->setPlayground($match->getPlayground()->getId());

            $em->persist($matchrec);
            $em->flush();

            $resultreqA = new MatchRelation();
            $resultreqA->setPid($matchrec->getId());
            $resultreqA->setCid($match->getTeamA()->getId());
            $resultreqA->setAwayteam(false);
            $resultreqA->setScorevalid(false);
            $resultreqA->setScore(0);
            $resultreqA->setPoints(0);

            $resultreqB = new MatchRelation();
            $resultreqB->setPid($matchrec->getId());
            $resultreqB->setCid($match->getTeamB()->getId());
            $resultreqB->setAwayteam(true);
            $resultreqB->setScorevalid(false);
            $resultreqB->setScore(0);
            $resultreqB->setPoints(0);

            $em->persist($resultreqA);
            $em->persist($resultreqB);
        }
        $em->flush();

        $request->getSession()->getFlashBag()->add(
            'data_saved',
            'FORM.MATCHPLANNING.PLAN_SAVED'
        );
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

        $host = $this->get('entity')->getHostById($tournament->getPid());
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

        $host = $this->get('entity')->getHostById($tournament->getPid());
        return array(
            'host' => $host,
            'tournament' => $tournament,
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
            if (!array_key_exists($match->getTeamA()->getId(), $tid)) {
                $outputstr = '"'.$match->getCategory()->getName().
                        '";"'.$match->getGroup()->getName().
                        '";"'.str_replace('"', "'", $match->getTeamA()->getName())." (".$match->getTeamA()->getCountry().")".'"';
                $tid[$match->getTeamA()->getId()] = $match->getTeamA()->getName();
                $outputar[] = $outputstr;
            }
        }
        
        return $outputar;
    }

    private function checkArgs($tournamentid) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $tournament = $this->get('entity')->getTournamentById($tournamentid);
        $utilService->validateEditorAdminUser($user, $tournament->getPid());
        return $tournament;
    }
}

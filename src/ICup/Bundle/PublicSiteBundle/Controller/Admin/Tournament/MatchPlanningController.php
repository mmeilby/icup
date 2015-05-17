<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchRelation;
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
            $matchList = $this->get('planning')->populateTournament($tournament->getId(), $options);
            return $this->render("ICupPublicSiteBundle:Edit:planmatch.html.twig", $this->planTournament($request, $tournament, $matchList, $options));
        }
        $host = $this->get('entity')->getHostById($tournament->getPid());
        return array('form' => $form->createView(), 'host' => $host, 'tournament' => $tournament);
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
    
    private function planTournament(Request $request, $tournament, $matchList, $options) {
        $masterplan = $this->get('planning')->planTournament($tournament->getId(), $matchList, $options);
        $session = $request->getSession();
        $session->set('icup.matchplanning.masterplan', $masterplan);
        
        $matches = array();
        foreach ($masterplan['plan'] as $match) {
            $matches[date_format($match->getSchedule(), "Y/m/d")][] = $match;
        }

        $timeslots = array();
        /* @var $ts PA */
        foreach ($masterplan['available_timeslots'] as $ts) {
            $timeslots[date_format($ts->getSchedule(), "Y/m/d")][] = $ts;
        }

        $host = $this->get('entity')->getHostById($tournament->getPid());
        return array('host' => $host,
                     'tournament' => $tournament,
                     'unassigned' => $masterplan['unassigned_by_category'],
                     'available_timeslots' => $timeslots);
    }
    
    /**
     * List the latest matches for a tournament
     * @Route("/edit/m/result/plan/{tournamentid}", name="_edit_match_planning_result")
     * @Template("ICupPublicSiteBundle:Edit:planmatch.html.twig")
     */
    public function resultMatchesAction($tournamentid, Request $request) {
        $tournament = $this->checkArgs($tournamentid);
        
        $session = $request->getSession();
        $masterplan = $session->get('icup.matchplanning.masterplan', array('plan' => array(), 'unassigned_by_category' => array(), 'available_timeslots' => array()));

        $matches = array();
        foreach ($masterplan['plan'] as $match) {
            $matches[date_format($match->getSchedule(), "Y/m/d")][] = $match;
        }

        $timeslots = array();
        foreach ($masterplan['available_timeslots'] as $ts) {
            $timeslots[date_format($ts->getSchedule(), "Y/m/d")][] = $ts;
        }

        $host = $this->get('entity')->getHostById($tournament->getPid());
        return array('host' => $host,
                     'tournament' => $tournament,
                     'unassigned' => $masterplan['unassigned_by_category'],
                     'available_timeslots' => $timeslots);
    }
    
    /**
     * List the latest matches for a tournament
     * @Route("/edit/m/save/plan/{tournamentid}", name="_edit_match_planning_save")
     * @Template("ICupPublicSiteBundle:Edit:planmatch.html.twig")
     */
    public function saveMatchesAction($tournamentid, Request $request) {
        $tournament = $this->checkArgs($tournamentid);
        
        $session = $request->getSession();
        $masterplan = $session->get('icup.matchplanning.masterplan', array('plan' => array(), 'unassigned_by_category' => array(), 'available_timeslots' => array()));

        $em = $this->getDoctrine()->getManager();
        $matches = array();
        /* @var $match MatchPlan */
        foreach ($masterplan['plan'] as $match) {
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

            $matches[date_format($match->getSchedule(), "Y/m/d")][] = $match;
        }
        $em->flush();

        $request->getSession()->getFlashBag()->add(
            'data_saved',
            'FORM.MATCHPLANNING.PLAN_SAVED'
        );
        
        $timeslots = array();
        foreach ($masterplan['available_timeslots'] as $ts) {
            $timeslots[date_format($ts->getSchedule(), "Y/m/d")][] = $ts;
        }

        $host = $this->get('entity')->getHostById($tournament->getPid());
        return array('host' => $host,
                     'tournament' => $tournament,
                     'unassigned' => $masterplan['unassigned_by_category'],
                     'available_timeslots' => $timeslots);
    }
    
    /**
     * List the latest matches for a tournament
     * @Route("/edit/m/view/plan/{tournamentid}", name="_edit_match_planning_view")
     * @Template("ICupPublicSiteBundle:Edit:planmatchlist.html.twig")
     */
    public function viewMatchesAction($tournamentid, Request $request) {
        $tournament = $this->checkArgs($tournamentid);
        
        $session = $request->getSession();
        $masterplan = $session->get('icup.matchplanning.masterplan', array('plan' => array()));

        $matches = array();
        foreach ($masterplan['plan'] as $match) {
            $matches[date_format($match->getSchedule(), "Y/m/d")][] = $match;
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
    public function listAdvicesAction($tournamentid, Request $request) {
        $tournament = $this->checkArgs($tournamentid);

        $session = $request->getSession();
        $masterplan = $session->get('icup.matchplanning.masterplan', array('advices' => array()));

        $host = $this->get('entity')->getHostById($tournament->getPid());
        return array('host' => $host,
            'tournament' => $tournament,
            'advices' => $masterplan['advices']);
    }

    /**
     * Download planned matches as CSV file
     * @Route("/edit/m/download/plan/{tournamentid}", name="_edit_match_planning_download")
     * @Method("GET")
     */
    public function downloadFileAction($tournamentid, Request $request)
    {
        $tournament = $this->checkArgs($tournamentid);
        
        $session = $request->getSession();
        $masterplan = $session->get('icup.matchplanning.masterplan', array('plan' => array(), 'unassigned' => array()));
        
        $outputar = $this->getResponses($masterplan);
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
    
    private function getResponses($masterplan) {
        $outputar = array("matchno;date;time;category;group;playground;teamA;teamB");
        /* @var $match MatchPlan */
        foreach ($masterplan['plan'] as $match) {
            $date = date_format($match->getSchedule(), "j-n-Y");
            $time = date_format($match->getSchedule(), "G.i");
            $outputstr = $match->getMatchno().';'.$date.';'.$time.
                    ';"'.$match->getCategory()->getName().
                    '";"'.$match->getGroup()->getName().
                    '";"'.$match->getPlayground()->getNo().
                    '";"'.str_replace('"', "'", $match->getTeamA()->getName())." (".$match->getTeamA()->getCountry().")".
                    '";"'.str_replace('"', "'", $match->getTeamB()->getName())." (".$match->getTeamB()->getCountry().")".
                    '"';
            $outputar[] = $outputstr;
        }
        if (count($masterplan['unassigned']) > 0) {
            $outputar[] = ";;;;;;;";
            foreach ($masterplan['unassigned'] as $match) {
                $date = date_format($match->getSchedule(), "j-n-Y");
                $time = date_format($match->getSchedule(), "G.i");
                $outputstr = $match->getMatchno().';'.$date.';'.$time.
                        ';"'.$match->getCategory()->getName().
                        '";"'.$match->getGroup()->getName().
                        '";"'.$match->getPlayground()->getNo().
                        '";"'.str_replace('"', "'", $match->getTeamA()->getName())." (".$match->getTeamA()->getCountry().")".
                        '";"'.str_replace('"', "'", $match->getTeamB()->getName())." (".$match->getTeamB()->getCountry().")".
                        '"';
                $outputar[] = $outputstr;
            }
        }
        $outputar[] = ";;;;;;;";
        $tid = array();
        foreach ($masterplan['plan'] as $match) {
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

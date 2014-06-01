<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Tournament;

use DateTime;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ICup\Bundle\PublicSiteBundle\Services\Util;

class MatchResultController extends Controller
{
    /**
     * @Route("/host/edit/match/score/{playgroundid}/{date}", name="_edit_match_score")
     * @Template("ICupPublicSiteBundle:Host:listmatchesinteractive.html.twig")
     * @Method("GET")
     */
    public function listAction($playgroundid, $date)
    {
        /* @var $utilService Util */
        $utilService = $this->get('util');

        $playground = $this->get('entity')->getPlaygroundById($playgroundid);
        $site = $this->get('entity')->getSiteById($playground->getPid());
        $tournament = $this->get('entity')->getTournamentById($site->getPid());
        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $utilService->validateEditorAdminUser($user, $tournament->getPid());

        $matchDate = DateTime::createFromFormat('d-m-Y', $date);
        if ($matchDate == null) {
            throw new ValidationException("INVALIDDATE", "Match date invalid: date=".$date);
        }
        /* @var $request Request */
        $request = $this->getRequest();
        $session = $request->getSession();
        $session->set('icup.matchedit.date', $matchDate);
        $session->set('icup.matchedit.playground', $playgroundid);
            
        $host = $this->get('entity')->getHostById($tournament->getPid());
        $matchList = $this->get('match')->listMatchesByPlaygroundDate($playgroundid, $matchDate);

        $playgrounds = $this->get('logic')->listPlaygroundsByTournament($tournament->getId());
        $playgroundsList = array();
        foreach ($playgrounds as $playground) {
            $playgroundsList[$playground->getId()] = $playground;
        }

        return array('host' => $host,
                     'tournament' => $tournament,
                     'playgrounds' => $playgroundsList,
                     'playground' => $playgroundsList[$playgroundid],
                     'dates' => $this->get('match')->listMatchCalendar($tournament->getId()),
                     'matchdate' => $matchDate,
                     'matchlist' => $matchList
                );
    }

    /**
     * @Route("/host/edit/match/score", name="_edit_match_score_post")
     * @Method("POST")
     */
    public function postAction()
    {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $returnUrl = $utilService->getReferer();

        /* @var $request Request */
        $request = $this->getRequest();
        $session = $request->getSession();
        $date = $session->get('icup.matchedit.date');
        $playgroundid = $session->get('icup.matchedit.playground');

        $playground = $this->get('entity')->getPlaygroundById($playgroundid);
        $site = $this->get('entity')->getSiteById($playground->getPid());
        $tournament = $this->get('entity')->getTournamentById($site->getPid());
        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $utilService->validateEditorAdminUser($user, $tournament->getPid());

        $form = $this->makeResultForm();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        
        $updatedRelations = array();
        foreach ($request->request as $key => $value) {
            $keyar = explode("_", $key);
            if ($keyar[0] !== "score") {
                continue;
            }
            $mr = $this->get('entity')->getMatchRelationById($keyar[1]);
            if ($value != "") {
                $mr->setScorevalid(true);
                $mr->setScore($value);
            }
            else {
                $mr->setScorevalid(false);
            }
            $updatedRelations[$mr->getPid()][$mr->getAwayteam() ? 'A' : 'H'] = $mr;
        }
        $this->commitMatchChanges($updatedRelations);
        
        return $this->redirect($this->generateUrl('_edit_match_score',
                array('playgroundid' => $playgroundid, 'date' => date_format($date, "d-m-Y"))));
    }    
    
    private function commitMatchChanges($updatedRelations) {
        $em = $this->getDoctrine()->getManager();

        foreach ($updatedRelations as $relationslist) {
            $relA = $relationslist['H'];
            $relB = $relationslist['A'];
            if ($relA->getScorevalid() && $relB->getScorevalid()) {
                $this->updatePoints($relA, $relB);
                $em->persist($relA);
                $em->persist($relB);
            }
            else {
                $relA->setScorevalid(false);
                $relB->setScorevalid(false);
                $em->persist($relA);
                $em->persist($relB);
            }
        }
        $em->flush();
    }
    
    private function updatePoints(&$relA, &$relB) {
        $looserPoints = 0;
        $tiePoints = 1;
        $winnerPoints = 3;
        if ($relA->getScore() > $relB->getScore()) {
            $relA->setPoints($winnerPoints);
            $relB->setPoints($looserPoints);
        }
        else if ($relA->getScore() < $relB->getScore()) {
            $relA->setPoints($looserPoints);
            $relB->setPoints($winnerPoints);
        }
        else {
            $relA->setPoints($tiePoints);
            $relB->setPoints($tiePoints);
        }
    }
    
    private function makeResultForm() {
        $formDef = $this->createFormBuilder();
        $formDef->add('cancel', 'submit', array('label' => 'FORM.EDITRESULTS.CANCEL', 'translation_domain' => 'tournament'));
        $formDef->add('save', 'submit', array('label' => 'FORM.EDITRESULTS.SUBMIT', 'translation_domain' => 'tournament'));
        return $formDef->getForm();
    }
}
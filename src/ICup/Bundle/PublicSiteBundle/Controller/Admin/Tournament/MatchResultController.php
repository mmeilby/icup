<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Tournament;

use DateTime;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Playground;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ICup\Bundle\PublicSiteBundle\Services\Util;
use Symfony\Component\HttpFoundation\Request;

class MatchResultController extends Controller
{
    /**
     * @Route("/host/edit/match/score/{playgroundid}/{date}", name="_edit_match_score")
     * @Template("ICupPublicSiteBundle:Host:listmatchesinteractive.html.twig")
     * @Method("GET")
     */
    public function listAction($playgroundid, $date, Request $request)
    {
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
        $session = $request->getSession();
        $session->set('icup.matchedit.date', $matchDate);
        $session->set('icup.matchedit.playground', $playgroundid);
            
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
    public function postAction(Request $request)
    {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $returnUrl = $utilService->getReferer();

        $session = $request->getSession();
        $date = $session->get('icup.matchedit.date');
        $playgroundid = $session->get('icup.matchedit.playground');

        /* @var $playground Playground */
        $playground = $this->get('entity')->getPlaygroundById($playgroundid);
        $site = $playground->getSite();
        /* @var $user User */
        $user = $utilService->getCurrentUser();
        /* @var $tournament Tournament */
        $tournament = $site->getTournament();
        $host = $tournament->getHost();
        $utilService->validateEditorAdminUser($user, $host);

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
            /* @var $mr MatchRelation */
            $mr = $this->get('entity')->getMatchRelationById($keyar[1]);
            if ($value != "") {
                $mr->setScorevalid(true);
                $mr->setScore($value);
            }
            else {
                $mr->setScorevalid(false);
            }
            $updatedRelations[$mr->getMatch()->getId()][$mr->getAwayteam() ? 'A' : 'H'] = $mr;
        }
        $this->commitMatchChanges($tournament, $updatedRelations);
        
        return $this->redirect($this->generateUrl('_edit_match_score',
                array('playgroundid' => $playgroundid, 'date' => date_format($date, "d-m-Y"))));
    }    
    
    private function commitMatchChanges(Tournament $tournament, $updatedRelations) {
        $em = $this->getDoctrine()->getManager();

        foreach ($updatedRelations as $relationslist) {
            $relA = $relationslist['H'];
            $relB = $relationslist['A'];
            if ($relA->getScorevalid() && $relB->getScorevalid()) {
                $this->get('match')->updatePoints($tournament, $relA, $relB);
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
    
    private function makeResultForm() {
        $formDef = $this->createFormBuilder();
        $formDef->add('cancel', 'submit', array('label' => 'FORM.EDITRESULTS.CANCEL',
                                                'translation_domain' => 'tournament',
                                                'buttontype' => 'btn btn-default',
                                                'icon' => 'fa fa-times'));
        $formDef->add('save', 'submit', array('label' => 'FORM.EDITRESULTS.SUBMIT',
                                                'translation_domain' => 'tournament',
                                                'icon' => 'fa fa-check'));
        return $formDef->getForm();
    }
}

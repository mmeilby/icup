<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\GroupOrder;
use ICup\Bundle\PublicSiteBundle\Entity\MatchImport;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormError;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;

class MatchUnresImportController extends Controller
{
    /**
     * Import match for tournament
     * @Route("/edit/import/matchunres/{tournamentid}", name="_edit_import_match_unres")
     * @Template("ICupPublicSiteBundle:Host:matchimport.html.twig")
     */
    public function matchImportAction($tournamentid) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $returnUrl = $utilService->getReferer();

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $tournament = $this->get('entity')->getTournamentById($tournamentid);
        $utilService->validateEditorAdminUser($user, $tournament->getPid());

        $matchImport = new MatchImport();
        $form = $this->makeImportForm($matchImport);
        $request = $this->getRequest();
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($form->isValid()) {
            try {
                $this->import($tournament, $matchImport);
                return $this->redirect($returnUrl);
            } catch (ValidationException $exc) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.ERROR.'.$exc->getMessage(), array(), 'admin')." [".$exc->getDebugInfo()."]"));
            }
        }
        return array('form' => $form->createView(), 'tournament' => $tournament, 'matchimport' => $matchImport);
    }
    
    private function makeImportForm($matchImport) {
        $formDef = $this->createFormBuilder($matchImport);
        $formDef->add('import', 'textarea', array(
            'label' => 'FORM.MATCHIMPORT.IMPORT',
            'help' => 'MATCHNO [TEAM A (ITA)] [TEAM B "DIV" (DNK)]',
            'required' => false,
            'translation_domain' => 'admin',
            'attr' => array('rows' => '10')));
        $formDef->add('cancel', 'submit', array('label' => 'FORM.MATCHIMPORT.CANCEL', 'translation_domain' => 'admin'));
        $formDef->add('save', 'submit', array('label' => 'FORM.MATCHIMPORT.SUBMIT', 'translation_domain' => 'admin'));
        return $formDef->getForm();
    }
    
    /**
     * Import match plan from text string
     * @param Tournament $tournament Import related to tournament
     * @param String $date Date of match
     * @param String $importStr Match plan - must follow this syntax:
     *                          - Match no
     *                          - Playground no
     *                          - Match time (hh:mm)
     *                          - Category name
     *                          - Group name
     *                          - Home team (teamname "division" (country))
     *                          - Away team (teamname "division" (country))
     * 
     * Example:    212 7 09:15 C A AETNA MASCALUCIA (ITA) TVIS KFUM "A" (DNK)
     * 
     * Country is only used if team name is ambigious - however syntax must be maintained.
     * Division can be ommitted.
     */
    private function import(Tournament $tournament, MatchImport $matchImport) {
        $parsedTokens = array();
        $parseObj = array();
        $keywords = preg_split("/[\s]+/", $matchImport->getImport());
        foreach ($keywords as $token) {
            if ($token == '') {
                continue;
            }
            if ($this->parseImport($parseObj, $token)) {
                $this->validateData($tournament, $parseObj);
                $parsedTokens[] = $parseObj;
                $parseObj = array();
            }
        }
        foreach ($parsedTokens as $parseObj) {
            $this->commitImport($parseObj);
        }
        $em = $this->getDoctrine()->getManager();
        $em->flush();
    }

    private function parseImport(&$parseObj, $token) {
        switch (count($parseObj)) {
            case 0:
                $parseObj['id'] = $token;
                break;
            case 1:
                $parseObj['teamA'] = array('name' => $token, 'division' => '');
                break;
            case 2:
                $this->parseImportTeam($parseObj, 'teamA', $token);
                break;
            case 3:
                $parseObj['teamB'] = array('name' => $token, 'division' => '');
                break;
            case 4:
                $this->parseImportTeam($parseObj, 'teamB', $token);
                break;
        }
        return count($parseObj) > 4;
    }

    private function parseImportTeam(&$parseObj, $team, $token) {
        if (preg_match('/\([\w]+\)/', $token)) {
            $parseObj[$team.'Country'] = substr($token, 1, -1);
        }
        elseif (preg_match('/\"[\w]+\"/', $token)) {
            $parseObj[$team]['division'] = substr($token, 1, -1);
        }
        else {
            $parseObj[$team]['name'] .= ' ' . $token;
        }
    }

    private function validateData($tournament, &$parseObj) {
        $match = $this->get('match')->getMatchByNo($tournament->getId(), $parseObj['id']);
        if ($match == null) {
            throw new ValidationException("BADMATCH", "tournament=".$tournament->getId()." match=".$parseObj['id']);
        }
        $qmh = $this->get('match')->getQMatchRelationByMatch($match->getId(), false);
        $teamAid = $this->getTeam($qmh->getCid(), $parseObj, 'teamA');
        $qma = $this->get('match')->getQMatchRelationByMatch($match->getId(), true);
        $teamBid = $this->getTeam($qma->getCid(), $parseObj, 'teamB');
        
        $parseObj['match'] = $match;
        $parseObj['teamAid'] = $teamAid;
        $parseObj['teamBid'] = $teamBid;
    }

    private function getTeam($groupid, $parseObj, $tkey) {
        $teamList = $this->get('logic')->getTeamByGroup($groupid, $parseObj[$tkey]['name'], $parseObj[$tkey]['division']);
        if (count($teamList) == 1) {
            return $teamList[0]->id;
        }
        foreach ($teamList as $team) {
            if ($team->country == $parseObj[$tkey.'Country']) {
                return $team->id;
            }
        }
        throw new ValidationException("BADTEAM", "group=".$groupid." team=".$parseObj[$tkey]['name']." '".$parseObj[$tkey]['division']."' (".$parseObj[$tkey.'Country'].")");
    }
    
    private function commitImport($parseObj) {
        $match = $parseObj['match'];

        $goA = new GroupOrder();
        $goA->setCid($parseObj['teamAid']);
        $goA->setPid($match->getPid());
        
        $resultreqA = new MatchRelation();
        $resultreqA->setPid($match->getId());
        $resultreqA->setCid($parseObj['teamAid']);
        $resultreqA->setAwayteam(false);
        $resultreqA->setScorevalid(false);
        $resultreqA->setScore(0);
        $resultreqA->setPoints(0);

        $goB = new GroupOrder();
        $goB->setCid($parseObj['teamBid']);
        $goB->setPid($match->getPid());

        $resultreqB = new MatchRelation();
        $resultreqB->setPid($match->getId());
        $resultreqB->setCid($parseObj['teamBid']);
        $resultreqB->setAwayteam(true);
        $resultreqB->setScorevalid(false);
        $resultreqB->setScore(0);
        $resultreqB->setPoints(0);

        $em = $this->getDoctrine()->getManager();
        $em->persist($goA);
        $em->persist($goB);
        $em->persist($resultreqA);
        $em->persist($resultreqB);
    }
}

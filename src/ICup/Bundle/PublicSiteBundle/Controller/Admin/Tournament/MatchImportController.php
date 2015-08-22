<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchRelation;
use ICup\Bundle\PublicSiteBundle\Entity\MatchImport;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormError;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;
use Symfony\Component\HttpFoundation\Request;

class MatchImportController extends Controller
{
    /**
     * Import match for tournament
     * @Route("/edit/import/match/{tournamentid}", name="_edit_import_match")
     * @Template("ICupPublicSiteBundle:Host:matchimport.html.twig")
     */
    public function matchImportAction($tournamentid, Request $request) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $returnUrl = $utilService->getReferer();

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        /* @var $tournament Tournament */
        $tournament = $this->get('entity')->getTournamentById($tournamentid);
        $host = $tournament->getHost();
        $utilService->validateEditorAdminUser($user, $host->getId());

        $matchImport = new MatchImport();
        $form = $this->makeImportForm($matchImport);
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($this->checkForm($form, $matchImport)) {
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
        $formDef->add('date', 'text', array('label' => 'FORM.MATCHIMPORT.DATE', 'required' => false, 'translation_domain' => 'admin'));
        $formDef->add('import', 'textarea', array(
            'label' => 'FORM.MATCHIMPORT.IMPORT',
            'help' => 'MATCHNO FIELD TIME CAT GRP [TEAM A (ITA)] [TEAM B "DIV" (DNK)]',
            'required' => false,
            'translation_domain' => 'admin',
            'attr' => array('rows' => '10')));
        $formDef->add('cancel', 'submit', array('label' => 'FORM.MATCHIMPORT.CANCEL',
                                                'translation_domain' => 'admin',
                                                'buttontype' => 'btn btn-default',
                                                'icon' => 'fa fa-times'));
        $formDef->add('save', 'submit', array('label' => 'FORM.MATCHIMPORT.SUBMIT',
                                                'translation_domain' => 'admin',
                                                'icon' => 'fa fa-check'));
        return $formDef->getForm();
    }
    
    private function checkForm($form, MatchImport $matchImport) {
        if ($form->isValid()) {
            if ($matchImport->getDate() == null || trim($matchImport->getDate()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.MATCHIMPORT.NODATE', array(), 'admin')));
            }
            else {
                $date = date_create_from_format($this->get('translator')->trans('FORMAT.DATE'), $matchImport->getDate());
                if ($date === false) {
                    $form->addError(new FormError($this->get('translator')->trans('FORM.MATCHIMPORT.BADDATE', array(), 'admin')));
                }
            }
        }
        return $form->isValid();
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
            $this->commitImport($parseObj, $matchImport->getDate());
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
                $parseObj['playground'] = $token;
                break;
            case 2:
                $parseObj['time'] = $token;
                break;
            case 3:
                $parseObj['category'] = $token;
                break;
            case 4:
                $parseObj['group'] = $token;
                break;
            case 5:
                $parseObj['teamA'] = array('name' => $token, 'division' => '');
                break;
            case 6:
                $this->parseImportTeam($parseObj, 'teamA', $token);
                break;
            case 7:
                $parseObj['teamB'] = array('name' => $token, 'division' => '');
                break;
            case 8:
                $this->parseImportTeam($parseObj, 'teamB', $token);
                break;
        }
        return count($parseObj) > 8;
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
        $playground = $this->get('logic')->getPlaygroundByNo($tournament->getId(), $parseObj['playground']);
        if ($playground == null) {
            throw new ValidationException("BADPLAYGROUND", "tournament=".$tournament->getId()." no=".$parseObj['playground']);
        }
        $group = $this->get('logic')->getGroupByCategory($tournament->getId(), $parseObj['category'], $parseObj['group']);
        if ($group == null) {
            throw new ValidationException("BADGROUP", "tournament=".$tournament->getId()." category=".$parseObj['category']." group=".$parseObj['group']);
        }
        $teamAid = $this->getTeam($group->getId(), $parseObj, 'teamA');
        $teamBid = $this->getTeam($group->getId(), $parseObj, 'teamB');
        
        $parseObj['playgroundid'] = $playground->getId();
        $parseObj['group'] = $group;
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
    
    private function commitImport($parseObj, $date) {
        $matchdate = date_create_from_format($this->get('translator')->trans('FORMAT.DATE'), $date);
        $matchtime = date_create_from_format($this->get('translator')->trans('FORMAT.TIME'), $parseObj['time']);
        if ($matchdate === false || $matchtime === false) {
            throw new ValidationException("BADDATE", "date=".$date." time=".$parseObj['time']);
        }
        
        $matchrec = new Match();
        $matchrec->setMatchno($parseObj['id']);
        $matchrec->setDate(Date::getDate($matchdate));
        $matchrec->setTime(Date::getTime($matchtime));
        $matchrec->setGroup($parseObj['group']);
        $matchrec->setPlayground($parseObj['playgroundid']);

        $resultreqA = new MatchRelation();
        $resultreqA->setCid($parseObj['teamAid']);
        $resultreqA->setAwayteam(false);
        $resultreqA->setScorevalid(false);
        $resultreqA->setScore(0);
        $resultreqA->setPoints(0);
        $matchrec->addMatchRelation($resultreqA);

        $resultreqB = new MatchRelation();
        $resultreqB->setCid($parseObj['teamBid']);
        $resultreqB->setAwayteam(true);
        $resultreqB->setScorevalid(false);
        $resultreqB->setScore(0);
        $resultreqB->setPoints(0);
        $matchrec->addMatchRelation($resultreqB);

        $em = $this->getDoctrine()->getManager();
        $em->persist($matchrec);
        $em->flush();
    }
}

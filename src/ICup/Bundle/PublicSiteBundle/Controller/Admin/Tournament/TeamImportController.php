<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\MatchImport;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Enrollment;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\GroupOrder;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormError;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;
use DateTime;

class TeamImportController extends Controller
{
    /**
     * Import match for tournament
     * @Route("/edit/import/team/{tournamentid}", name="_edit_import_team")
     * @Template("ICupPublicSiteBundle:Host:matchimport.html.twig")
     */
    public function teamImportAction($tournamentid) {
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
                $this->import($tournament, $matchImport, $user->getId());
                return $this->redirect($returnUrl);
            } catch (ValidationException $exc) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.ERROR.'.$exc->getMessage(), array(), 'admin')));
            }
        }
        return array('form' => $form->createView(), 'tournament' => $tournament, 'matchimport' => $matchImport);
    }
    
    private function makeImportForm($matchImport) {
        $formDef = $this->createFormBuilder($matchImport);
        $formDef->add('import', 'textarea', array('label' => 'FORM.MATCHIMPORT.IMPORT', 'required' => false, 'translation_domain' => 'admin'));
        $formDef->add('cancel', 'submit', array('label' => 'FORM.MATCHIMPORT.CANCEL', 'translation_domain' => 'admin'));
        $formDef->add('save', 'submit', array('label' => 'FORM.MATCHIMPORT.SUBMIT', 'translation_domain' => 'admin'));
        return $formDef->getForm();
    }
    
    /**
     * Import match plan from text string
     * @param Tournament $tournament Import related to tournament
     * @param String $date Date of match
     * @param String $importStr Category plan - must follow this syntax:
     *                          - Category name
     *                          - Group name
     *                          - teamname "division" (country)
     * 
     * Example:  C A AETNA MASCALUCIA (ITA)
     *           C B TVIS KFUM "A" (DNK)
     * 
     * Division can be ommitted.
     */
    private function import(Tournament $tournament, MatchImport $matchImport, $userid) {
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
            $this->commitImport($parseObj, $userid);
        }
        $em = $this->getDoctrine()->getManager();
        $em->flush();
    }

    private function parseImport(&$parseObj, $token) {
        switch (count($parseObj)) {
            case 0:
                $parseObj['category'] = $token;
                break;
            case 1:
                $parseObj['group'] = $token;
                break;
            case 2:
                $parseObj['team'] = array('name' => $token, 'division' => '', 'country' => '');
                break;
            case 3:
                $this->parseImportTeam($parseObj, 'team', $token);
                break;
        }
        return count($parseObj) > 3;
    }

    private function parseImportTeam(&$parseObj, $team, $token) {
        if (preg_match('/\([\w]+\)/', $token)) {
            $parseObj[$team]['country'] = substr($token, 1, -1);
            $parseObj['done'] = '';
        }
        elseif (preg_match('/\"[\w]+\"/', $token)) {
            $parseObj[$team]['division'] = substr($token, 1, -1);
        }
        else {
            $parseObj[$team]['name'] .= ' ' . $token;
        }
    }

    private function validateData($tournament, &$parseObj) {
        $category = $this->get('logic')->getCategoryByName($tournament->getId(), $parseObj['category']);
        if ($category == null) {
            throw new ValidationException("BADCATEGORY", "tournament=".$tournament->getId()." category=".$parseObj['category']);
        }
        $group = $this->get('logic')->getGroupByCategory($tournament->getId(), $parseObj['category'], $parseObj['group']);
        if ($group == null) {
            throw new ValidationException("BADGROUP", "tournament=".$tournament->getId()." category=".$parseObj['category']." group=".$parseObj['group']);
        }
        $teamid = $this->getTeam($category->getId(),
                                 $parseObj['team']['name'],
                                 $parseObj['team']['division'],
                                 $parseObj['team']['country']);
        if ($teamid == 0) {
            $club = $this->get('logic')->getClubByName($parseObj['team']['name'], $parseObj['team']['country']);
            if ($club != null) {
                $parseObj['clubid'] = $club->getId();
            }
            else {
                $parseObj['clubid'] = 0;
            }
        }
        $parseObj['categoryid'] = $category->getId();
        $parseObj['groupid'] = $group->getId();
        $parseObj['teamid'] = $teamid;
    }

    private function getTeam($categoryid, $name, $division, $country) {
        $teamList = $this->get('logic')->getTeamByCategory($categoryid, $name, $division);
        if (count($teamList) == 1) {
            return $teamList[0]->id;
        }
        else if (count($teamList) > 0) {
            foreach ($teamList as $team) {
                if ($team->country == $country) {
                    return $team->id;
                }
            }
            throw new ValidationException("BADTEAM", "category=".$categoryid." team=".$name." '".$division."' (".$country.")");
        }
        else {
            return 0;
        }
    }

    private function commitImport($parseObj, $userid) {
        $em = $this->getDoctrine()->getManager();
        $categoryid = $parseObj['categoryid'];
        $teamid = $parseObj['teamid'];
        if ($teamid == 0) {
            $clubid = $parseObj['clubid'];
            if ($clubid == 0) {
                $club = new Club();
                $club->setName($parseObj['team']['name']);
                $club->setCountry($parseObj['team']['country']);
                $em->persist($club);
                $em->flush();
                $clubid = $club->getId();
            }
            $enroll = $this->get('logic')->enrollTeam($categoryid, $userid,
                                                      $clubid,
                                                      $parseObj['team']['name'],
                                                      $parseObj['team']['division']);
            $teamid = $enroll->getCid();
        }

        if (!$this->get('logic')->isTeamAssigned($categoryid, $teamid)) {
            $groupid = $parseObj['groupid'];
            $this->get('logic')->assignEnrolled($teamid, $groupid);
        }
    }
}

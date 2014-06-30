<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\MatchImport;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormError;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;

class TeamImportController extends Controller
{
    /**
     * Import match for tournament
     * @Route("/edit/import/team/{tournamentid}", name="_edit_import_team")
     * @Template("ICupPublicSiteBundle:Host:teamimport.html.twig")
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
                $form->addError(new FormError($this->get('translator')->trans('FORM.ERROR.'.$exc->getMessage(), array(), 'admin')." [".$exc->getDebugInfo()."]"));
            }
        }
        return array('form' => $form->createView(), 'tournament' => $tournament, 'matchimport' => $matchImport);
    }
    
    private function makeImportForm($matchImport) {
        $formDef = $this->createFormBuilder($matchImport);
        $formDef->add('import', 'textarea', array(
            'label' => 'FORM.TEAMIMPORT.IMPORT',
            'help' => 'CAT GRP [TEAM "DIV" (DNK)]',
            'required' => false,
            'translation_domain' => 'admin',
            'attr' => array('rows' => '10')));
        $formDef->add('cancel', 'submit', array('label' => 'FORM.TEAMIMPORT.CANCEL', 'translation_domain' => 'admin'));
        $formDef->add('save', 'submit', array('label' => 'FORM.TEAMIMPORT.SUBMIT', 'translation_domain' => 'admin'));
        return $formDef->getForm();
    }
    
    /**
     * Import match plan from text string
     * @param Tournament $tournament Import related to tournament
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
            throw new ValidationException("BADCATEGORY", $parseObj['category']);
        }
        $group = $this->get('logic')->getGroupByCategory($tournament->getId(), $parseObj['category'], $parseObj['group']);
        if ($group == null) {
            throw new ValidationException("BADGROUP", $parseObj['category'].":".$parseObj['group']);
        }
        $teamid = $this->getTeam($category->getId(),
                                 $parseObj['team']['name'],
                                 $parseObj['team']['division'],
                                 $parseObj['team']['country']);
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
        }
        $countries = $this->get('util')->getCountries();
        if (!array_search($country, $countries)) {
            throw new ValidationException("BADTEAM", $name." '".$division."' (".$country.")");
        }
        return 0;
    }

    private function commitImport($parseObj, $userid) {
        $em = $this->getDoctrine()->getManager();
        $categoryid = $parseObj['categoryid'];
        $teamid = $parseObj['teamid'];
        $name = $parseObj['team']['name'];
        $division = $parseObj['team']['division'];
        $country = $parseObj['team']['country'];
        if ($teamid == 0) {
            $club = $this->get('logic')->getClubByName($name, $country);
            if ($club == null) {
                $club = new Club();
                $club->setName($name);
                $club->setCountry($country);
                $em->persist($club);
                $em->flush();
            }
            $clubid = $club->getId();
            $enroll = $this->get('logic')->enrollTeam($categoryid, $userid,
                                                      $clubid, $name, $division);
            $teamid = $enroll->getCid();
        }

        if (!$this->get('logic')->isTeamAssigned($categoryid, $teamid)) {
            $groupid = $parseObj['groupid'];
            $this->get('logic')->assignEnrolled($teamid, $groupid);
        }
    }
}

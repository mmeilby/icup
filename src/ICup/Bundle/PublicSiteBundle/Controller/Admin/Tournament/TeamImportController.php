<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Entity\MatchImport;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club;
use ICup\Bundle\PublicSiteBundle\Services\Util;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormError;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;
use Symfony\Component\HttpFoundation\Request;

class TeamImportController extends Controller
{
    /**
     * Import match for tournament
     * @Route("/edit/import/team/{tournamentid}", name="_edit_import_team")
     * @Template("ICupPublicSiteBundle:Host:teamimport.html.twig")
     */
    public function teamImportAction($tournamentid, Request $request) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $returnUrl = $utilService->getReferer();

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        /* @var $tournament Tournament */
        $tournament = $this->get('entity')->getTournamentById($tournamentid);
        $host = $tournament->getHost();
        $utilService->validateEditorAdminUser($user, $host);

        $matchImport = new MatchImport();
        $form = $this->makeImportForm($matchImport);
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($form->isValid()) {
            try {
                $this->import($tournament, $matchImport, $user);
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
        $formDef->add('cancel', 'submit', array('label' => 'FORM.TEAMIMPORT.CANCEL',
                                                'translation_domain' => 'admin',
                                                'buttontype' => 'btn btn-default',
                                                'icon' => 'fa fa-times'));
        $formDef->add('save', 'submit', array('label' => 'FORM.TEAMIMPORT.SUBMIT',
                                                'translation_domain' => 'admin',
                                                'icon' => 'fa fa-check'));
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
    private function import(Tournament $tournament, MatchImport $matchImport, User $user) {
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
            $this->commitImport($parseObj, $user);
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
        $team = $this->getTeam($category,
                               $parseObj['team']['name'],
                               $parseObj['team']['division'],
                               $parseObj['team']['country']);
        $parseObj['category'] = $category;
        $parseObj['group'] = $group;
        $parseObj['teamA'] = $team;
    }

    private function getTeam(Category $category, $name, $division, $country) {
        $teamList = $this->get('logic')->findTeamByCategory($category, $name, $division);
        if (count($teamList) == 1) {
            return $teamList[0];
        }
        else if (count($teamList) > 0) {
            foreach ($teamList as $team) {
                if ($team->getClub()->getCountry() == $country) {
                    return $team;
                }
            }
        }
        $countries = $this->get('util')->getCountries();
        if (!array_search($country, $countries)) {
            throw new ValidationException("BADTEAM", $name." '".$division."' (".$country.")");
        }
        return null;
    }

    private function commitImport($parseObj, User $user) {
        $em = $this->getDoctrine()->getManager();
        $category = $parseObj['category'];
        $team = $parseObj['teamA'];
        $name = $parseObj['team']['name'];
        $division = $parseObj['team']['division'];
        $country = $parseObj['team']['country'];
        if (!$team) {
            $club = $this->get('logic')->getClubByName($name, $country);
            if ($club == null) {
                $club = new Club();
                $club->setName($name);
                $club->setCountry($country);
                $em->persist($club);
                $em->flush();
            }
            $enroll = $this->get('logic')->enrollTeam($category, $user,
                                                      $club, $name, $division);
            $team = $enroll->getTeam();
        }

        if (!$this->get('logic')->isTeamAssigned($category->getId(), $team->getId())) {
            $group = $parseObj['group'];
            $this->get('logic')->assignEnrolled($team->getId(), $group->getId());
        }
    }
}

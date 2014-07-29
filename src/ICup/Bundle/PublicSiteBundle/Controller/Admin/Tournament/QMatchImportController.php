<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\QMatchRelation;
use ICup\Bundle\PublicSiteBundle\Entity\MatchImport;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormError;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;

class QMatchImportController extends Controller
{
    /**
     * Import match for tournament
     * @Route("/edit/import/qmatch/{tournamentid}", name="_edit_import_qmatch")
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
            'help' => 'MATCHNO FIELD TIME CAT GRP [RANK GRP] [RANK GRP]',
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
                return false;
            }
            date_parse_from_format("j-n-Y", $matchImport->getDate());
            $date_errors = date_get_last_errors();
            if ($date_errors['error_count'] > 0) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.MATCHIMPORT.BADDATE', array(), 'admin')));
                return false;
            }
            return true;
        }
        return false;
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
     *                          - Rank group name
     *                          - Rank group name
     * 
     * Example:    385 7 09:15 C (A) 1 A 2 B
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
                $parseObj['teamA'] = array('rank' => $token, 'group' => '');
                break;
            case 6:
                $parseObj['teamA']['group'] = $token;
                $parseObj['teamAgroup'] = $token;
                break;
            case 7:
                $parseObj['teamB'] = array('rank' => $token, 'group' => '');
                break;
            case 8:
                $parseObj['teamB']['group'] = $token;
                $parseObj['teamBgroup'] = $token;
                break;
        }
        return count($parseObj) > 8;
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
        $groupRA = $this->get('logic')->getGroupByCategory($tournament->getId(), $parseObj['category'], $parseObj['teamA']['group']);
        if ($group == null) {
            throw new ValidationException("BADGROUP", "tournament=".$tournament->getId()." category=".$parseObj['category']." group=".$parseObj['teamA']['group']);
        }
        $groupRB = $this->get('logic')->getGroupByCategory($tournament->getId(), $parseObj['category'], $parseObj['teamB']['group']);
        if ($group == null) {
            throw new ValidationException("BADGROUP", "tournament=".$tournament->getId()." category=".$parseObj['category']." group=".$parseObj['teamB']['group']);
        }
        
        $parseObj['playgroundid'] = $playground->getId();
        $parseObj['groupid'] = $group->getId();
        $parseObj['teamAgroupid'] = $groupRA->getId();
        $parseObj['teamBgroupid'] = $groupRB->getId();
    }

    private function commitImport($parseObj, $date) {
        $matchdate = date_create_from_format("j-n-Y", $date);
        $matchtime = date_create_from_format("G:i", $parseObj['time']);
        $date_errors = date_get_last_errors();
        if ($date_errors['error_count'] > 0) {
            throw new ValidationException("BADDATE", "date=".$date." time=".$parseObj['time']);
        }
        
        $matchrec = new Match();
        $matchrec->setMatchno($parseObj['id']);
        $matchrec->setDate(date_format($matchdate, $this->container->getParameter('db_date_format')));
        $matchrec->setTime(date_format($matchtime, $this->container->getParameter('db_time_format')));
        $matchrec->setPid($parseObj['groupid']);
        $matchrec->setPlayground($parseObj['playgroundid']);

        $em = $this->getDoctrine()->getManager();
        $em->persist($matchrec);
        $em->flush();

        $resultreqA = new QMatchRelation();
        $resultreqA->setPid($matchrec->getId());
        $resultreqA->setCid($parseObj['teamAgroupid']);
        $resultreqA->setRank($parseObj['teamA']['rank']);
        $resultreqA->setAwayteam(false);

        $resultreqB = new QMatchRelation();
        $resultreqB->setPid($matchrec->getId());
        $resultreqB->setCid($parseObj['teamBgroupid']);
        $resultreqB->setRank($parseObj['teamB']['rank']);
        $resultreqB->setAwayteam(true);

        $em->persist($resultreqA);
        $em->persist($resultreqB);
    }
}

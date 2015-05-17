<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\PlaygroundAttribute;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Entity\PAttrForm;
use ICup\Bundle\PublicSiteBundle\Services\Util;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;

/**
 * Maintain attribute for a playground
 */
class PlaygroundAttributeController extends Controller
{
    /**
     * Add new playground attribute
     * @Route("/edit/pa/add/{playgroundid}", name="_edit_pattr_add")
     * @Template("ICupPublicSiteBundle:Edit:editpattr.html.twig")
     */
    public function addAction($playgroundid, Request $request) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        
        $returnUrl = $utilService->getReferer();

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $playground = $this->get('entity')->getPlaygroundById($playgroundid);
        $site = $this->get('entity')->getSiteById($playground->getPid());
        $tournament = $this->get('entity')->getTournamentById($site->getPid());
        $utilService->validateEditorAdminUser($user, $tournament->getPid());

        $pattrForm = new PAttrForm();
        $pattrForm->setPid($playground->getId());
        $form = $this->makePAttrForm($pattrForm, $tournament, 'add');
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($this->checkForm($form, $pattrForm)) {
            $this->addPAttr($pattrForm);
            return $this->redirect($returnUrl);
        }
        return array('form' => $form->createView(), 'action' => 'add');
    }
    
    /**
     * Change information of an existing timeslot
     * @Route("/edit/pa/chg/{playgroundattributeid}", name="_edit_pattr_chg")
     * @Template("ICupPublicSiteBundle:Edit:editpattr.html.twig")
     */
    public function chgAction($playgroundattributeid, Request $request) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        
        $returnUrl = $utilService->getReferer();

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $pattr = $this->get('entity')->getPlaygroundAttributeById($playgroundattributeid);
        $playground = $this->get('entity')->getPlaygroundById($pattr->getPid());
        $site = $this->get('entity')->getSiteById($playground->getPid());
        $tournament = $this->get('entity')->getTournamentById($site->getPid());
        $utilService->validateEditorAdminUser($user, $tournament->getPid());

        $pattrForm = $this->copyPAttrForm($pattr);
        $form = $this->makePAttrForm($pattrForm, $tournament, 'chg');
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($this->checkForm($form, $pattrForm)) {
            $this->chgPAttr($pattrForm, $pattr);
            return $this->redirect($returnUrl);
        }
        return array('form' => $form->createView(), 'action' => 'chg');
    }
    
    /**
     * Remove timeslot from the register - including all related data
     * @Route("/edit/pa/del/{playgroundattributeid}", name="_edit_pattr_del")
     * @Template("ICupPublicSiteBundle:Edit:editpattr.html.twig")
     */
    public function delAction($playgroundattributeid, Request $request) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        
        $returnUrl = $utilService->getReferer();

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $pattr = $this->get('entity')->getPlaygroundAttributeById($playgroundattributeid);
        $playground = $this->get('entity')->getPlaygroundById($pattr->getPid());
        $site = $this->get('entity')->getSiteById($playground->getPid());
        $tournament = $this->get('entity')->getTournamentById($site->getPid());
        $utilService->validateEditorAdminUser($user, $tournament->getPid());

        $pattrForm = $this->copyPAttrForm($pattr);
        $form = $this->makePAttrForm($pattrForm, $tournament, 'del');
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($form->isValid()) {
            $this->delPAttr($pattr);
            return $this->redirect($returnUrl);
        }
        return array('form' => $form->createView(), 'action' => 'del');
    }
    
    private function addPAttr(PAttrForm $pattrForm) {
        $pattr = new PlaygroundAttribute();
        $pattr->setPid($pattrForm->getPId());
        $this->updatePAttr($pattrForm, $pattr);
        $em = $this->getDoctrine()->getManager();
        $em->persist($pattr);
        $em->flush();
    }
    
    private function chgPAttr(PAttrForm $pattrForm, PlaygroundAttribute &$pattr) {
        $this->updatePAttr($pattrForm, $pattr);
        $em = $this->getDoctrine()->getManager();
        $em->flush();
    }

    private function delPAttr(PlaygroundAttribute $pattr) {
        $em = $this->getDoctrine()->getManager();
        $this->get('logic')->removePARelations($pattr->getId());
        $em->remove($pattr);
        $em->flush();
    }
    
    private function updatePAttr(PAttrForm $pattrForm, PlaygroundAttribute &$pattr) {
        $timeslotid = $pattrForm->getTimeslot();
        $pattr->setTimeslot($timeslotid != null ? $timeslotid : 0);
        $dateformat = $this->get('translator')->trans('FORMAT.DATE');
        $matchdate = date_create_from_format($dateformat, $pattrForm->getDate());
        $pattr->setDate(Date::getDate($matchdate));
        $timeformat = $this->get('translator')->trans('FORMAT.TIME');
        $starttime = date_create_from_format($timeformat, $pattrForm->getStart());
        $pattr->setStart(Date::getTime($starttime));
        $endtime = date_create_from_format($timeformat, $pattrForm->getEnd());
        $pattr->setEnd(Date::getTime($endtime));
    }
    
    private function copyPAttrForm(PlaygroundAttribute $pattr) {
        $pattrForm = new PAttrForm();
        $pattrForm->setId($pattr->getId());
        $pattrForm->setPid($pattr->getPId());
        $pattrForm->setTimeslot($pattr->getTimeslot());
        $matchdate = $pattr->getStartSchedule();
        $dateformat = $this->get('translator')->trans('FORMAT.DATE');
        $pattrForm->setDate(date_format($matchdate, $dateformat));
        $timeformat = $this->get('translator')->trans('FORMAT.TIME');
        $pattrForm->setStart(date_format($matchdate, $timeformat));
        $endtime = $pattr->getEndSchedule();
        $pattrForm->setEnd(date_format($endtime, $timeformat));
        $pattrForm->setCategories($this->get('logic')->listPACategories($pattr->getId()));
        return $pattrForm;
    }

    private function makePAttrForm(PAttrForm $pattrForm, $tournament, $action) {
        $timeslots = $this->get('logic')->listTimeslots($tournament->getId());
        $timeslotList = array();
        foreach ($timeslots as $timeslot) {
            $timeslotList[$timeslot->getId()] = $timeslot->getName();
        }
        
        $formDef = $this->createFormBuilder($pattrForm);
        $formDef->add('timeslot', 'choice',
              array('label' => 'FORM.PLAYGROUNDATTR.TIMESLOT.PROMPT',
                    'help' => 'FORM.PLAYGROUNDATTR.TIMESLOT.HELP',
                    'choices' => $timeslotList,
                    'empty_value' => 'FORM.PLAYGROUNDATTR.DEFAULT',
                    'required' => false,
                    'disabled' => $action == 'del',
                    'translation_domain' => 'admin'));
        $formDef->add('date', 'text',
              array('label' => 'FORM.PLAYGROUNDATTR.DATE.PROMPT',
                    'help' => 'FORM.PLAYGROUNDATTR.DATE.HELP',
                    'required' => false,
                    'disabled' => $action == 'del',
                    'translation_domain' => 'admin'));
        $formDef->add('start', 'text',
              array('label' => 'FORM.PLAYGROUNDATTR.START.PROMPT',
                    'help' => 'FORM.PLAYGROUNDATTR.START.HELP',
                    'required' => false,
                    'disabled' => $action == 'del',
                    'translation_domain' => 'admin'));
        $formDef->add('end', 'text',
              array('label' => 'FORM.PLAYGROUNDATTR.END.PROMPT',
                    'help' => 'FORM.PLAYGROUNDATTR.END.HELP',
                    'required' => false,
                    'disabled' => $action == 'del',
                    'translation_domain' => 'admin'));
        $formDef->add('cancel', 'submit',
              array('label' => 'FORM.PLAYGROUNDATTR.CANCEL.'.strtoupper($action),
                    'translation_domain' => 'admin',
                    'buttontype' => 'btn btn-default',
                    'icon' => 'fa fa-times'));
        $formDef->add('save', 'submit',
              array('label' => 'FORM.PLAYGROUNDATTR.SUBMIT.'.strtoupper($action),
                    'translation_domain' => 'admin',
                    'icon' => 'fa fa-check'));
        return $formDef->getForm();
    }

    private function checkForm($form, PAttrForm $pattrForm) {
        if ($form->isValid()) {
            if ($pattrForm->getTimeslot() == null) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.PLAYGROUNDATTR.NOTIMESLOT', array(), 'admin')));
            }
            if ($pattrForm->getDate() == null || trim($pattrForm->getDate()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.PLAYGROUNDATTR.NODATE', array(), 'admin')));
            }
            else {
                $date = date_create_from_format($this->get('translator')->trans('FORMAT.DATE'), $pattrForm->getDate());
                if ($date === false) {
                    $form->addError(new FormError($this->get('translator')->trans('FORM.PLAYGROUNDATTR.BADDATE', array(), 'admin')));
                }
            }
            if ($pattrForm->getStart() == null || trim($pattrForm->getStart()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.PLAYGROUNDATTR.NOSTART', array(), 'admin')));
            }
            else {
                $start = date_create_from_format($this->get('translator')->trans('FORMAT.TIME'), $pattrForm->getStart());
                if ($start === false) {
                    $form->addError(new FormError($this->get('translator')->trans('FORM.PLAYGROUNDATTR.BADSTART', array(), 'admin')));
                }
            }
            if ($pattrForm->getEnd() == null || trim($pattrForm->getEnd()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.PLAYGROUNDATTR.NOEND', array(), 'admin')));
            }
            else {
                $end = date_create_from_format($this->get('translator')->trans('FORMAT.TIME'), $pattrForm->getEnd());
                if ($end === false) {
                    $form->addError(new FormError($this->get('translator')->trans('FORM.PLAYGROUNDATTR.BADEND', array(), 'admin')));
                }
            }
            if ($form->isValid()) {
                if ($end->getTimestamp() <= $start->getTimestamp()) {
                    $form->addError(new FormError($this->get('translator')->trans('FORM.PLAYGROUNDATTR.BADTIME', array(), 'admin')));
                }
            }
            return $form->isValid();
        }
        return false;
    }
}

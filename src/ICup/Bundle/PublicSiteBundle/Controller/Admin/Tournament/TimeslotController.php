<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Timeslot;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ICup\Bundle\PublicSiteBundle\Services\Util;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;

/**
 * Maintain timeslots for a tournament
 */
class TimeslotController extends Controller
{
    /**
     * Add new timeslot
     * @Route("/edit/timeslot/add/{tournamentid}", name="_edit_timeslot_add")
     * @Template("ICupPublicSiteBundle:Edit:edittimeslot.html.twig")
     */
    public function addAction($tournamentid, Request $request) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        
        $returnUrl = $utilService->getReferer();

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        /* @var $tournament Tournament */
        $tournament = $this->get('entity')->getTournamentById($tournamentid);
        $host = $tournament->getHost();
        $utilService->validateEditorAdminUser($user, $host);

        $timeslot = new Timeslot();
        $timeslot->setTournament($tournament);
        $form = $this->makeTimeslotForm($timeslot, 'add');
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($this->checkForm($form, $timeslot)) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($timeslot);
            $em->flush();
            return $this->redirect($returnUrl);
        }
        return array('form' => $form->createView(), 'action' => 'add', 'timeslot' => $timeslot);
    }
    
    /**
     * Change information of an existing timeslot
     * @Route("/edit/timeslot/chg/{timeslotid}", name="_edit_timeslot_chg")
     * @Template("ICupPublicSiteBundle:Edit:edittimeslot.html.twig")
     */
    public function chgAction($timeslotid, Request $request) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        
        $returnUrl = $utilService->getReferer();

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        /* @var $timeslot Timeslot */
        $timeslot = $this->get('entity')->getTimeslotById($timeslotid);
        /* @var $tournament Tournament */
        $tournament = $timeslot->getTournament();
        $host = $tournament->getHost();
        $utilService->validateEditorAdminUser($user, $host);

        $form = $this->makeTimeslotForm($timeslot, 'chg');
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($this->checkForm($form, $timeslot)) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($timeslot);
            $em->flush();
            return $this->redirect($returnUrl);
        }
        return array('form' => $form->createView(), 'action' => 'chg', 'timeslot' => $timeslot);
    }
    
    /**
     * Remove timeslot from the register - including all related data
     * @Route("/edit/timeslot/del/{timeslotid}", name="_edit_timeslot_del")
     * @Template("ICupPublicSiteBundle:Edit:edittimeslot.html.twig")
     */
    public function delAction($timeslotid, Request $request) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        
        $returnUrl = $utilService->getReferer();

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        /* @var $timeslot Timeslot */
        $timeslot = $this->get('entity')->getTimeslotById($timeslotid);
        /* @var $tournament Tournament */
        $tournament = $timeslot->getTournament();
        $host = $tournament->getHost();
        $utilService->validateEditorAdminUser($user, $host);

        $form = $this->makeTimeslotForm($timeslot, 'del');
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($form->isValid()) {
            if ($this->get('logic')->listPlaygrounds($timeslot->getId()) != null) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.TIMESLOT.PLAYGROUNDSEXIST', array(), 'admin')));
            }
            else {
                $em = $this->getDoctrine()->getManager();
                $em->remove($timeslot);
                $em->flush();
                return $this->redirect($returnUrl);
            }
        }
        return array('form' => $form->createView(), 'action' => 'del', 'timeslot' => $timeslot);
    }
    
    private function makeTimeslotForm($timeslot, $action) {
        $formDef = $this->createFormBuilder($timeslot);
        $formDef->add('name', 'text', array('label' => 'FORM.TIMESLOT.NAME.PROMPT',
                                            'help' => 'FORM.TIMESLOT.NAME.HELP',
                                            'required' => false,
                                            'disabled' => $action == 'del',
                                            'translation_domain' => 'admin'));
        $formDef->add('capacity', 'text', array('label' => 'FORM.TIMESLOT.CAPACITY.PROMPT',
                                                'help' => 'FORM.TIMESLOT.CAPACITY.HELP',
                                                'required' => false,
                                                'disabled' => $action == 'del',
                                                'translation_domain' => 'admin'));
        $formDef->add('restperiod', 'text', array('label' => 'FORM.TIMESLOT.RESTPERIOD.PROMPT',
                                                'help' => 'FORM.TIMESLOT.RESTPERIOD.HELP',
                                                'required' => false,
                                                'disabled' => $action == 'del',
                                                'translation_domain' => 'admin'));
        $formDef->add('penalty', 'checkbox', array('label' => 'FORM.TIMESLOT.PENALTY.PROMPT',
                                                'help' => 'FORM.TIMESLOT.PENALTY.HELP',
                                                'required' => false,
                                                'disabled' => $action == 'del',
                                                'translation_domain' => 'admin'));
        $formDef->add('cancel', 'submit', array('label' => 'FORM.TIMESLOT.CANCEL.'.strtoupper($action),
                                                'translation_domain' => 'admin',
                                                'buttontype' => 'btn btn-default',
                                                'icon' => 'fa fa-times'));
        $formDef->add('save', 'submit', array('label' => 'FORM.TIMESLOT.SUBMIT.'.strtoupper($action),
                                                'translation_domain' => 'admin',
                                                'icon' => 'fa fa-check'));
        return $formDef->getForm();
    }

    private function checkForm($form, Timeslot $timeslot) {
        if ($form->isValid()) {
            if ($timeslot->getName() == null || trim($timeslot->getName()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.TIMESLOT.NONAME', array(), 'admin')));
                return false;
            }
            return true;
        }
        return false;
    }
}

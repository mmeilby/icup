<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Event;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Services\Util;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use ICup\Bundle\PublicSiteBundle\Entity\Event as EventForm;
use Symfony\Component\HttpFoundation\Request;

/**
 * Maintain tournament events
 */
class EventController extends Controller
{
    /**
     * Add new event
     * @Route("/edit/event/add/{tournamentid}", name="_edit_event_add")
     * @Template("ICupPublicSiteBundle:Host:editevent.html.twig")
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

        $eventForm = new EventForm();
        $eventForm->setPid($tournament->getId());
        $form = $this->makeEventForm($eventForm, 'add');
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($this->checkForm($form, $eventForm)) {
            $otherEvent = $this->get('tmnt')->getEventByEvent($tournament->getId(), $eventForm->getEvent());
            if ($otherEvent != null) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.EVENT.EVENTEXISTS', array(), 'admin')));
            }
            else {
                $event = new Event();
                $event->setTournament($tournament);
                $this->updateEvent($eventForm, $event);
                $em = $this->getDoctrine()->getManager();
                $em->persist($event);
                $em->flush();
                return $this->redirect($returnUrl);
            }
        }
        return array('form' => $form->createView(), 'action' => 'add', 'tournament' => $tournament);
    }
    
    /**
     * Change information of an existing event
     * @Route("/edit/event/chg/{eventid}", name="_edit_event_chg")
     * @Template("ICupPublicSiteBundle:Host:editevent.html.twig")
     */
    public function chgAction($eventid, Request $request) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $returnUrl = $utilService->getReferer();

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        /* @var $event Event */
        $event = $this->get('entity')->getEventById($eventid);
        /* @var $tournament Tournament */
        $tournament = $event->getTournament();
        $host = $tournament->getHost();
        $utilService->validateEditorAdminUser($user, $host);

        $eventForm = $this->copyEventForm($event);
        $form = $this->makeEventForm($eventForm, 'chg');
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($this->checkForm($form, $eventForm)) {
            $otherEvent = $this->get('tmnt')->getEventByEvent($tournament->getId(), $eventForm->getEvent());
            if ($otherEvent != null && $otherEvent->getId() != $eventForm->getId()) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.EVENT.CANTCHANGEEVENT', array(), 'admin')));
            }
            else {
                $this->updateEvent($eventForm, $event);
                $em = $this->getDoctrine()->getManager();
                $em->flush();
                return $this->redirect($returnUrl);
            }
        }
        return array('form' => $form->createView(), 'action' => 'chg', 'tournament' => $tournament);
    }
    
    /**
     * Remove event from the register
     * @Route("/edit/event/del/{eventid}", name="_edit_event_del")
     * @Template("ICupPublicSiteBundle:Host:editevent.html.twig")
     */
    public function delAction($eventid, Request $request) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $returnUrl = $utilService->getReferer();

        /* @var $user User */
        $user = $utilService->getCurrentUser();
        $event = $this->get('entity')->getEventById($eventid);
        /* @var $tournament Tournament */
        $tournament = $event->getTournament();
        $host = $tournament->getHost();
        $utilService->validateEditorAdminUser($user, $host);

        $eventForm = $this->copyEventForm($event);
        $form = $this->makeEventForm($eventForm, 'del');
        $form->handleRequest($request);
        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($returnUrl);
        }
        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($event);
            $em->flush();
            return $this->redirect($returnUrl);
        }
        return array('form' => $form->createView(), 'action' => 'del', 'tournament' => $tournament);
    }

    private function updateEvent(EventForm $eventForm, Event &$event) {
        $event->setEvent($eventForm->getEvent());
        $dateformat = $this->get('translator')->trans('FORMAT.DATE');
        $eventdate = date_create_from_format($dateformat, $eventForm->getDate());
        $event->setDate(Date::getDate($eventdate));
    }
    
    private function copyEventForm(Event $event) {
        $eventForm = new EventForm();
        $eventForm->setId($event->getId());
        $eventForm->setPid($event->getTournament()->getId());
        $eventForm->setEvent($event->getEvent());
        $dateformat = $this->get('translator')->trans('FORMAT.DATE');
        $eventdate = Date::getDateTime($event->getDate());
        $eventForm->setDate(date_format($eventdate, $dateformat));
        return $eventForm;
    }

    private function makeEventForm(EventForm $eventForm, $action) {
        $eventnames = array();
        foreach (
            array(
                Event::$ENROLL_START,
                Event::$ENROLL_STOP,
                Event::$MATCH_START,
                Event::$MATCH_STOP,
                Event::$TOURNAMENT_ARCHIVED
            )
        as $id) {
            $eventnames[$id] = 'FORM.EVENT.EVENTS.'.$id;
        }
        $show = $action != 'del';
        
        $formDef = $this->createFormBuilder($eventForm);
        $formDef->add('event', 'choice', array('label' => 'FORM.EVENT.EVENT',
            'choices' => $eventnames, 'empty_value' => 'FORM.EVENT.DEFAULT',
            'required' => false, 'disabled' => !$show, 'translation_domain' => 'admin'));
        $formDef->add('date', 'text', array('label' => 'FORM.EVENT.DATE',
            'required' => false, 'disabled' => !$show, 'translation_domain' => 'admin'));
        $formDef->add('cancel', 'submit', array('label' => 'FORM.EVENT.CANCEL.'.strtoupper($action),
                                                'translation_domain' => 'admin',
                                                'buttontype' => 'btn btn-default',
                                                'icon' => 'fa fa-times'));
        $formDef->add('save', 'submit', array('label' => 'FORM.EVENT.SUBMIT.'.strtoupper($action),
                                                'translation_domain' => 'admin',
                                                'icon' => 'fa fa-check'));
        return $formDef->getForm();
    }
    
    private function checkForm($form, EventForm $eventForm) {
        if (!$form->isValid()) {
            return false;
        }
        if ($eventForm->getEvent() == null) {
            $form->addError(new FormError($this->get('translator')->trans('FORM.EVENT.NOEVENT', array(), 'admin')));
        }
        if ($eventForm->getDate() == null || trim($eventForm->getDate()) == '') {
            $form->addError(new FormError($this->get('translator')->trans('FORM.EVENT.NODATE', array(), 'admin')));
        }
        else {
            $date = date_create_from_format($this->get('translator')->trans('FORMAT.DATE'), $eventForm->getDate());
            if ($date === false) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.EVENT.BADDATE', array(), 'admin')));
            }
        }
        return $form->isValid();
    }
}

<?php

namespace ICup\Bundle\PublicSiteBundle\Controller\Rest\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;
use ICup\Bundle\PublicSiteBundle\Services\Util;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Timeslot;
use ICup\Bundle\PublicSiteBundle\Form\Doctrine\TimeslotType;
use RuntimeException;

/**
 * Doctrine\Timeslot controller.
 *
 * @Route("/rest/timeslot")
 */
class RestTimeslotController extends Controller
{
    /**
     * List all the timeslots identified by tournament id
     * @Route("/list/{tournamentid}", name="_rest_list_timeslots", options={"expose"=true})
     * @Method("GET")
     * @param $tournamentid
     * @return JsonResponse
     */
    public function indexAction($tournamentid)
    {
        /* @var $tournament Tournament */
        try {
            $tournament = $this->get('entity')->getTournamentById($tournamentid);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }
        $timeslots = array();
        foreach ($tournament->getTimeslots() as $timeslot) {
            /* @var $timeslot Timeslot */
            $timeslots[] = $timeslot->jsonSerialize();
        }
        return new JsonResponse(array_values($timeslots));
    }

    /**
     * Finds and displays a Doctrine\Timeslot entity.
     *
     * @Route("/{timeslotid}", name="rest_get_timeslot", options={"expose"=true})
     * @Method("GET")
     * @param $timeslotid
     * @return JsonResponse
     */
    public function showAction($timeslotid)
    {
        /* @var $timeslot Timeslot */
        try {
            $timeslot = $this->get('entity')->getTimeslotById($timeslotid);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }
        return new JsonResponse(array("timeslot" => $timeslot));
    }

    /**
     * Creates a new Doctrine\Timeslot entity.
     * Tournament id must be added to the request parameters
     * @Route("/", name="rest_timeslot_create", options={"expose"=true})
     * @Method("POST")
     * @param Request $request
     * @return JsonResponse
     */
    public function newAction(Request $request)
    {
        /* @var $tournament Tournament */
        try {
            $tournament = $this->get('entity')->getTournamentById($request->get('tournamentid', 0));
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }
        try {
            /* @var $utilService Util */
            $utilService = $this->get('util');
            /* @var $user User */
            $user = $utilService->getCurrentUser();
            $host = $tournament->getHost();
            $utilService->validateEditorAdminUser($user, $host);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }
        catch (RuntimeException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }

        /* @var $timeslot Timeslot */
        $timeslot = new Timeslot();
        $timeslot->setTournament($tournament);
        $form = $this->createForm(new TimeslotType(), $timeslot);
        $form->handleRequest($request);
        if ($this->checkForm($form, $timeslot)) {
            $tournament->getTimeslots()->add($timeslot);
            $em = $this->getDoctrine()->getManager();
            $em->persist($timeslot);
            $em->flush();
            return new JsonResponse(array("id" => $timeslot->getId()), Response::HTTP_CREATED);
        }

        $errors = array();
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }
        return new JsonResponse(array('errors' => $errors), Response::HTTP_BAD_REQUEST);
    }

    /**
     * Edits an existing Doctrine\Timeslot entity.
     *
     * @Route("/{timeslotid}", name="rest_timeslot_update", options={"expose"=true})
     * @Method("POST")
     * @param Request $request
     * @param $timeslotid
     * @return JsonResponse
     */
    public function updateAction(Request $request, $timeslotid)
    {
        /* @var $timeslot Timeslot */
        try {
            $timeslot = $this->get('entity')->getTimeslotById($timeslotid);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }
        try {
            /* @var $utilService Util */
            $utilService = $this->get('util');
            /* @var $user User */
            $user = $utilService->getCurrentUser();
            $host = $timeslot->getTournament()->getHost();
            $utilService->validateEditorAdminUser($user, $host);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }
        catch (RuntimeException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }

        $form = $this->createForm(new TimeslotType(), $timeslot);
        $form->handleRequest($request);

        if ($this->checkForm($form, $timeslot)) {
            $em = $this->getDoctrine()->getManager();
            $em->flush();
            return new JsonResponse(array(), Response::HTTP_NO_CONTENT);
        }

        $errors = array();
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }
        return new JsonResponse(array('errors' => $errors), Response::HTTP_BAD_REQUEST);
    }

    /**
     * Deletes a Doctrine\Timeslot entity.
     *
     * @Route("/{timeslotid}", name="rest_timeslot_delete", options={"expose"=true})
     * @Method("DELETE")
     * @param $timeslotid
     * @return JsonResponse
     */
    public function deleteAction($timeslotid)
    {
        /* @var $timeslot Timeslot */
        try {
            $timeslot = $this->get('entity')->getTimeslotById($timeslotid);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }
        try {
            /* @var $utilService Util */
            $utilService = $this->get('util');
            /* @var $user User */
            $user = $utilService->getCurrentUser();
            $host = $timeslot->getTournament()->getHost();
            $utilService->validateEditorAdminUser($user, $host);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }
        catch (RuntimeException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }

        $errors = array();
        if ($timeslot->getPlaygroundattributes()->count() > 0) {
            $errors[] = $this->get('translator')->trans('FORM.TIMESLOT.PARELATIONSEXIST', array(), 'admin');
        }
        if (count($errors) == 0) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($timeslot);
            $em->flush();
            return new JsonResponse(array(), Response::HTTP_NO_CONTENT);
        }
        return new JsonResponse(array('errors' => $errors), Response::HTTP_BAD_REQUEST);
    }

    private function checkForm(Form $form, Timeslot $timeslot) {
        if ($form->isValid()) {
            if ($timeslot->getName() == null || trim($timeslot->getName()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.TIMESLOT.NONAME', array(), 'admin')));
            }
            else {
                $em = $this->getDoctrine()->getManager();
                /* @var $othertimeslot Timeslot */
                $othertimeslot = $em->getRepository($form->getConfig()->getOption("data_class"))->findOneBy(array('tournament' => $timeslot->getTournament()->getId(), 'name' => $timeslot->getName()));
                if ($othertimeslot != null && $othertimeslot->getId() != $timeslot->getId()) {
                    $form->addError(new FormError($this->get('translator')->trans('FORM.TIMESLOT.NAMEEXISTS', array(), 'admin')));
                }
            }
            if ($timeslot->getCapacity() == null || trim($timeslot->getCapacity()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.TIMESLOT.NOGENDER', array(), 'admin')));
            }
            if ($timeslot->getRestperiod() == null || trim($timeslot->getRestperiod()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.TIMESLOT.NOCLASSIFICATION', array(), 'admin')));
            }
            if ($timeslot->getPenalty() == null || trim($timeslot->getPenalty()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.TIMESLOT.NOAGE', array(), 'admin')));
            }
        }
        return $form->isValid();
    }
}

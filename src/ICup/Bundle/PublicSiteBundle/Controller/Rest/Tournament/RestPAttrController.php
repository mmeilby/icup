<?php

namespace ICup\Bundle\PublicSiteBundle\Controller\Rest\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Playground;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Timeslot;
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
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\PlaygroundAttribute;
use ICup\Bundle\PublicSiteBundle\Form\Doctrine\PlaygroundAttributeType;
use RuntimeException;

/**
 * Doctrine\PlaygroundAttribute controller.
 *
 * @Route("/rest/pattr")
 */
class RestPAttrController extends Controller
{
    /**
     * List the playground attributes related to playground id
     * @Route("/list/{playgroundid}", name="_rest_list_pattrs", options={"expose"=true})
     * @Method("GET")
     * @param $playgroundid
     * @return JsonResponse
     */
    public function indexAction($playgroundid)
    {
        /* @var $playground Playground */
        try {
            $playground = $this->get('entity')->getPlaygroundById($playgroundid);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }
        return new JsonResponse($playground->getPlaygroundAttributes()->toArray());
    }

    /**
     * Finds and displays a Doctrine\PlaygroundAttribute entity.
     *
     * @Route("/{pattrid}", name="rest_get_pattr", options={"expose"=true})
     * @Method("GET")
     * @param $pattrid
     * @return JsonResponse
     */
    public function showAction($pattrid)
    {
        /* @var $pattr PlaygroundAttribute */
        try {
            $pattr = $this->get('entity')->getPlaygroundAttributeById($pattrid);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }
        return new JsonResponse(array("playgroundattribute" => $pattr));
    }

    /**
     * Creates a new Doctrine\PlaygroundAttribute entity.
     * Playground id must be added to the request parameters
     * @Route("/", name="rest_pattr_create", options={"expose"=true})
     * @Method("POST")
     * @param Request $request
     * @return JsonResponse
     */
    public function newAction(Request $request)
    {
        /* @var $playground Playground */
        try {
            $playground = $this->get('entity')->getPlaygroundById($request->get('playgroundid', 0));
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }
        /* @var $timeslot Timeslot */
        try {
            $timeslot = $this->get('entity')->getTimeslotById($request->get('timeslotid', 0));
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }
        $categories = array();
        try {
            foreach ($request->get('categories', array()) as $catrec) {
                /* @var $category Category */
                $category = $this->get('entity')->getCategoryById($catrec['id']);
                $categories[] = $category;
            }
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }
        try {
            /* @var $utilService Util */
            $utilService = $this->get('util');
            /* @var $user User */
            $user = $utilService->getCurrentUser();
            $host = $playground->getSite()->getTournament()->getHost();
            $utilService->validateEditorAdminUser($user, $host);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }
        catch (RuntimeException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }

        // validate the tournament relation
        if ($timeslot->getTournament()->getId() != $playground->getSite()->getTournament()->getId()) {
            return new JsonResponse(array('errors' => array('')), Response::HTTP_BAD_REQUEST);
        }

        /* @var $pattr PlaygroundAttribute */
        $pattr = new PlaygroundAttribute();
        $pattr->setPlayground($playground);
        $pattr->setTimeslot($timeslot);
        $form = $this->createForm(new PlaygroundAttributeType(), $pattr);
        $form->handleRequest($request);
        if ($this->checkForm($form, $pattr)) {
            $playground->getPlaygroundAttributes()->add($pattr);
            $timeslot->getPlaygroundattributes()->add($pattr);
            $em = $this->getDoctrine()->getManager();
            $em->persist($pattr);
            /* add category constraints */
            foreach ($categories as $category) {
                $pattr->getCategories()->add($category);
            }
            $em->flush();
            return new JsonResponse(array("id" => $pattr->getId()), Response::HTTP_CREATED);
        }

        $errors = array();
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }
        return new JsonResponse(array('errors' => $errors), Response::HTTP_BAD_REQUEST);
    }

    /**
     * Edits an existing Doctrine\PlaygroundAttribute entity.
     *
     * @Route("/{pattrid}", name="rest_pattr_update", options={"expose"=true})
     * @Method("POST")
     * @param Request $request
     * @param $pattrid
     * @return JsonResponse
     */
    public function updateAction(Request $request, $pattrid)
    {
        /* @var $pattr PlaygroundAttribute */
        try {
            $pattr = $this->get('entity')->getPlaygroundAttributeById($pattrid);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }
        /* @var $timeslot Timeslot */
        try {
            $timeslot = $this->get('entity')->getTimeslotById($request->get('timeslotid', 0));
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }
        $categories = array();
        try {
            $newlist = array();
            foreach ($request->get('categories', array()) as $catrec) {
                $newlist[$catrec['id']] = $catrec;
            }
            $oldlist = array();
            foreach ($pattr->getCategories() as $catrec) {
                $oldlist[$catrec->getId()] = $catrec;
            }
            /* categories to add */
            foreach (array_diff_key($newlist, $oldlist) as $categoryid => $catrec) {
                /* @var $category Category */
                $category = $this->get('entity')->getCategoryById($categoryid);
                $categories[] = $category;
            }
            /* categories to remove */
            $removeCategories = array_diff_key($oldlist, $newlist);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }
        try {
            /* @var $utilService Util */
            $utilService = $this->get('util');
            /* @var $user User */
            $user = $utilService->getCurrentUser();
            $host = $pattr->getPlayground()->getSite()->getTournament()->getHost();
            $utilService->validateEditorAdminUser($user, $host);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }
        catch (RuntimeException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }

        // validate the tournament relation
        if ($timeslot->getTournament()->getId() != $pattr->getPlayground()->getSite()->getTournament()->getId()) {
            return new JsonResponse(array('errors' => array('')), Response::HTTP_BAD_REQUEST);
        }

        $form = $this->createForm(new PlaygroundAttributeType(), $pattr);
        $form->handleRequest($request);

        if ($this->checkForm($form, $pattr)) {
            /* remove category constraints */
            foreach ($removeCategories as $category) {
                $pattr->getCategories()->removeElement($category);
            }
            /* add category constraints */
            foreach ($categories as $category) {
                $pattr->getCategories()->add($category);
            }
            if ($pattr->getTimeslot() == null || $pattr->getTimeslot()->getId() != $timeslot->getId()) {
                if ($pattr->getTimeslot()) {
                    $pattr->getTimeslot()->getPlaygroundattributes()->removeElement($pattr);
                }
                $pattr->setTimeslot($timeslot);
                $timeslot->getPlaygroundattributes()->add($pattr);
            }
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
     * Deletes a Doctrine\PlaygroundAttribute entity.
     *
     * @Route("/{pattrid}", name="rest_pattr_delete", options={"expose"=true})
     * @Method("DELETE")
     * @param $pattrid
     * @return JsonResponse
     */
    public function deleteAction($pattrid)
    {
        /* @var $pattr PlaygroundAttribute */
        try {
            $pattr = $this->get('entity')->getPlaygroundAttributeById($pattrid);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }
        try {
            /* @var $utilService Util */
            $utilService = $this->get('util');
            /* @var $user User */
            $user = $utilService->getCurrentUser();
            $host = $pattr->getPlayground()->getSite()->getTournament()->getHost();
            $utilService->validateEditorAdminUser($user, $host);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }
        catch (RuntimeException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }

        if ($pattr->getMatchscheduleplans()->isEmpty()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($pattr);
            $em->flush();
        }
        else {
            return new JsonResponse(array('errors' => array($this->get('translator')->trans('FORM.PLAYGROUNDATTR.MATCHEXISTS', array(), 'admin'))), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        return new JsonResponse(array(), Response::HTTP_NO_CONTENT);
    }

    private function checkForm(Form $form, PlaygroundAttribute $pattr) {
        if ($form->isValid()) {
            if ($pattr->getDate() == null || trim($pattr->getDate()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.PLAYGROUNDATTR.NODATE', array(), 'admin')));
            }
            else {
                $date = date_create_from_format("Y-m-d", $pattr->getDate());
                if ($date === false) {
                    $form->addError(new FormError($this->get('translator')->trans('FORM.PLAYGROUNDATTR.BADDATE', array(), 'admin')));
                }
                else {
                    $pattr->setDate(Date::getDate($date));
                }
            }
            if ($pattr->getStart() == null || trim($pattr->getStart()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.PLAYGROUNDATTR.NOSTART', array(), 'admin')));
            }
            else {
                $start = date_create_from_format("H:i", $pattr->getStart());
                if ($start === false) {
                    $form->addError(new FormError($this->get('translator')->trans('FORM.PLAYGROUNDATTR.BADSTART', array(), 'admin')));
                }
                else {
                    $pattr->setStart(Date::getTime($start));
                }
            }
            if ($pattr->getEnd() == null || trim($pattr->getEnd()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.PLAYGROUNDATTR.NOEND', array(), 'admin')));
            }
            else {
                $end = date_create_from_format("H:i", $pattr->getEnd());
                if ($end === false) {
                    $form->addError(new FormError($this->get('translator')->trans('FORM.PLAYGROUNDATTR.BADEND', array(), 'admin')));
                }
                else {
                    $pattr->setEnd(Date::getTime($end));
                }
            }
            if ($form->isValid()) {
                if ($pattr->getEndSchedule()->getTimestamp() <= $pattr->getStartSchedule()->getTimestamp()) {
                    $form->addError(new FormError($this->get('translator')->trans('FORM.PLAYGROUNDATTR.BADTIME', array(), 'admin')));
                }
            }
            if ($pattr->getClassification() < 0 || $pattr->getClassification() > Group::$FINAL) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.PLAYGROUNDATTR.BADCLASS', array(), 'admin')));
            }
        }
        return $form->isValid();
    }
}

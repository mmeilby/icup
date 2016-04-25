<?php

namespace ICup\Bundle\PublicSiteBundle\Controller\Rest\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;
use ICup\Bundle\PublicSiteBundle\Form\Doctrine\GroupType;
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
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use Symfony\Component\HttpKernel\Exception\HttpException;
use RuntimeException;

/**
 * Doctrine\Group controller.
 *
 * @Route("/rest/group")
 */
class RestGroupController extends Controller
{
    /**
     * List all the groups identified by category id
     * @Route("/list/{categoryid}", name="_rest_list_groups", options={"expose"=true})
     * @Method("GET")
     * @param $categoryid
     * @return JsonResponse
     */
    public function indexAction($categoryid)
    {
        /* @var $category Category */
        try {
            $category = $this->get('entity')->getCategoryById($categoryid);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }
        return new JsonResponse($category->getGroupsClassified(Group::$PRE)->toArray());
    }

    /**
     * Finds and displays a Doctrine\Group entity.
     *
     * @Route("/{groupid}", name="rest_get_group", options={"expose"=true})
     * @Method("GET")
     * @param $groupid
     * @return JsonResponse
     */
    public function showAction($groupid)
    {
        /* @var $group Group */
        try {
            $group = $this->get('entity')->getGroupById($groupid);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }
        return new JsonResponse(array("group" => $group));
    }

    /**
     * Creates a new Doctrine\Group entity.
     * Category id must be added to the request parameters
     * @Route("/", name="rest_group_create", options={"expose"=true})
     * @Method("POST")
     * @param Request $request
     * @return JsonResponse
     */
    public function newAction(Request $request)
    {
        /* @var $category Category */
        try {
            $category = $this->get('entity')->getCategoryById($request->get('categoryid', 0));
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }
        try {
            /* @var $utilService Util */
            $utilService = $this->get('util');
            /* @var $user User */
            $user = $utilService->getCurrentUser();
            $host = $category->getTournament()->getHost();
            $utilService->validateEditorAdminUser($user, $host);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }
        catch (RuntimeException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }

        /* @var $group Group */
        $group = new Group();
        $group->setCategory($category);
        $form = $this->createForm(new GroupType(), $group);
        $form->handleRequest($request);
        if ($this->checkForm($form, $group)) {
            $category->getGroups()->add($group);
            $em = $this->getDoctrine()->getManager();
            $em->persist($group);
            $em->flush();
            return new JsonResponse(array("id" => $group->getId()), Response::HTTP_CREATED);
        }

        $errors = array();
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }
        return new JsonResponse(array('errors' => $errors), Response::HTTP_BAD_REQUEST);
    }

    /**
     * Edits an existing Doctrine\Group entity.
     *
     * @Route("/{groupid}", name="rest_group_update", options={"expose"=true})
     * @Method("POST")
     * @param Request $request
     * @param $groupid
     * @return JsonResponse
     */
    public function updateAction(Request $request, $groupid)
    {
        /* @var $group Group */
        try {
            $group = $this->get('entity')->getGroupById($groupid);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }
        try {
            /* @var $utilService Util */
            $utilService = $this->get('util');
            /* @var $user User */
            $user = $utilService->getCurrentUser();
            $host = $group->getCategory()->getTournament()->getHost();
            $utilService->validateEditorAdminUser($user, $host);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }
        catch (RuntimeException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }
        
        $form = $this->createForm(new GroupType(), $group);
        $form->handleRequest($request);

        if ($this->checkForm($form, $group)) {
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
     * Deletes a Doctrine\Group entity.
     *
     * @Route("/{groupid}", name="rest_group_delete", options={"expose"=true})
     * @Method("DELETE")
     * @param $groupid
     * @return JsonResponse
     */
    public function deleteAction($groupid)
    {
        /* @var $group Group */
        try {
            $group = $this->get('entity')->getGroupById($groupid);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }
        try {
            /* @var $utilService Util */
            $utilService = $this->get('util');
            /* @var $user User */
            $user = $utilService->getCurrentUser();
            $host = $group->getCategory()->getTournament()->getHost();
            $utilService->validateEditorAdminUser($user, $host);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }
        catch (RuntimeException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }

        $errors = array();
        if ($group->getGroupOrder()->count() > 0) {
            $errors[] = $this->get('translator')->trans('FORM.GROUP.ORDEREXIST', array(), 'admin');
        }
        if ($group->getMatches()->count() > 0) {
            $errors[] = $this->get('translator')->trans('FORM.GROUP.MATCHESEXIST', array(), 'admin');
        }
        if (count($errors) == 0) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($group);
            $em->flush();
            return new JsonResponse(array(), Response::HTTP_NO_CONTENT);
        }
        return new JsonResponse(array('errors' => $errors), Response::HTTP_BAD_REQUEST);
    }

    private function checkForm(Form $form, Group $group) {
        if ($form->isValid()) {
            if ($group->getName() == null || trim($group->getName()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.GROUP.NONAME', array(), 'admin')));
            }
            else {
                $em = $this->getDoctrine()->getManager();
                /* @var $othergroup Group */
                $othergroup = $em->getRepository($form->getConfig()->getOption("data_class"))->findOneBy(array('category' => $group->getCategory()->getId(), 'name' => $group->getName()));
                if ($othergroup != null && $othergroup->getId() != $group->getId()) {
                    $form->addError(new FormError($this->get('translator')->trans('FORM.GROUP.NAMEEXISTS', array(), 'admin')));
                }
            }
            if ($group->getClassification() === null) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.GROUP.NOCLASSIFICATION', array(), 'admin')));
            }
        }
        return $form->isValid();
    }
}

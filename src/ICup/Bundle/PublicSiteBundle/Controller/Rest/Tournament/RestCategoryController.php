<?php

namespace ICup\Bundle\PublicSiteBundle\Controller\Rest\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Entity\TeamInfo;
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
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Form\Doctrine\CategoryType;

/**
 * Doctrine\Category controller.
 *
 * @Route("/rest/category")
 */
class RestCategoryController extends Controller
{
    /**
     * List all the categories identified by tournament id
     * @Route("/list/{tournamentid}", name="_rest_list_categories", options={"expose"=true})
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
        return new JsonResponse($tournament->getCategories()->toArray());
    }

    /**
     * Finds and displays a Doctrine\Category entity.
     *
     * @Route("/{categoryid}", name="rest_get_category", options={"expose"=true})
     * @Method("GET")
     * @param $categoryid
     * @return JsonResponse
     */
    public function showAction($categoryid)
    {
        /* @var $category Category */
        try {
            $category = $this->get('entity')->getCategoryById($categoryid);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }
        return new JsonResponse(array("category" => $category));
    }

    /**
     * Creates a new Doctrine\Category entity.
     *
     * @Route("/{tournamentid}", name="rest_category_create", options={"expose"=true})
     * @Method("POST")
     * @param Request $request
     * @param $tournamentid
     * @return JsonResponse
     */
    public function newAction(Request $request, $tournamentid)
    {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        /* @var $user User */
        $user = $utilService->getCurrentUser();
        /* @var $tournament Tournament */
        try {
            $tournament = $this->get('entity')->getTournamentById($tournamentid);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }
        try {
            $host = $tournament->getHost();
            $utilService->validateEditorAdminUser($user, $host);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }

        /* @var $category Category */
        $category = new Category();
        $category->setTournament($tournament);
        $form = $this->createForm(new CategoryType(), $category);
        $form->handleRequest($request);
        if ($this->checkForm($form, $category)) {
            $tournament->getCategories()->add($category);
            $em = $this->getDoctrine()->getManager();
            $em->persist($category);
            $em->flush();
            return new JsonResponse(array("id" => $category->getId()), Response::HTTP_CREATED);
        }

        $errors = array();
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }
        return new JsonResponse(array('errors' => $errors), Response::HTTP_BAD_REQUEST);
    }

    /**
     * Edits an existing Doctrine\Category entity.
     *
     * @Route("/{categoryid}", name="rest_category_update", options={"expose"=true})
     * @Method("PUT")
     * @param Request $request
     * @param $categoryid
     * @return JsonResponse
     */
    public function updateAction(Request $request, $categoryid)
    {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        /* @var $user User */
        $user = $utilService->getCurrentUser();
        /* @var $category Category */
        try {
            $category = $this->get('entity')->getCategoryById($categoryid);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }
        try {
            $host = $category->getTournament()->getHost();
            $utilService->validateEditorAdminUser($user, $host);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }

        $form = $this->createForm(new CategoryType(), $category, array('method' => 'PUT'));
        $form->handleRequest($request);

        if ($this->checkForm($form, $category)) {
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
     * Deletes a Doctrine\Category entity.
     *
     * @Route("/{categoryid}", name="rest_category_delete", options={"expose"=true})
     * @Method("DELETE")
     * @param $categoryid
     * @return JsonResponse
     */
    public function deleteAction($categoryid)
    {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        /* @var $user User */
        $user = $utilService->getCurrentUser();
        /* @var $category Category */
        try {
            $category = $this->get('entity')->getCategoryById($categoryid);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }
        try {
            $host = $category->getTournament()->getHost();
            $utilService->validateEditorAdminUser($user, $host);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }

        $errors = array();
        if ($category->getGroups()->count() > 0) {
            $errors[] = $this->get('translator')->trans('FORM.CATEGORY.GROUPSEXIST', array(), 'admin');
        }
        if ($category->getEnrollments()->count() > 0) {
            $errors[] = $this->get('translator')->trans('FORM.CATEGORY.ENROLLEDEXIST', array(), 'admin');
        }
        if ($category->getPlaygroundattributes()->count() > 0) {
            $errors[] = $this->get('translator')->trans('FORM.CATEGORY.PARELATIONSEXIST', array(), 'admin');
        }
        if (count($errors) == 0) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($category);
            $em->flush();
            return new JsonResponse(array(), Response::HTTP_NO_CONTENT);
        }
        return new JsonResponse(array('errors' => $errors), Response::HTTP_BAD_REQUEST);
    }

    private function checkForm(Form $form, Category $category) {
        if ($form->isValid()) {
            if ($category->getName() == null || trim($category->getName()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.CATEGORY.NONAME', array(), 'admin')));
            }
            else {
                $em = $this->getDoctrine()->getManager();
                $othercategory = $em->getRepository($form->getConfig()->getOption("data_class"))->findBy(array('tournament' => $category->getTournament()->getId(), 'name' => $category->getName()));
                if ($othercategory) {
                    $form->addError(new FormError($this->get('translator')->trans('FORM.CATEGORY.NAMEEXISTS', array(), 'admin')));
                }
            }
            if ($category->getGender() == null) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.CATEGORY.NOGENDER', array(), 'admin')));
            }
            if ($category->getClassification() == null) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.CATEGORY.NOCLASSIFICATION', array(), 'admin')));
            }
            if ($category->getAge() == null || trim($category->getAge()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.CATEGORY.NOAGE', array(), 'admin')));
            }
            if ($category->getMatchtime() == null || trim($category->getMatchtime()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.CATEGORY.NOMATCHTIME', array(), 'admin')));
            }
        }
        return $form->isValid();
    }

    /**
     * List the clubs by groups assigned in the category
     * @Route("/list/assigned/{categoryid}", name="_rest_list_groups_with_teams", options={"expose"=true})
     * @param $categoryid
     * @return JsonResponse
     */
    public function restListTeamsByGroupAction($categoryid)
    {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        /* @var $category Category */
        $category = $this->get('entity')->getCategoryById($categoryid);

        $groups = array();
        foreach ($category->getGroupsClassified(Group::$PRE) as $group) {
            $teams = array();
            foreach ($this->get('logic')->listTeamsByGroup($group->getId()) as $team) {
                /* @var $team TeamInfo */
                $teams[] = array(
                    'id' => $team->getId(),
                    'name' => $team->getName(),
                    'country' => $this->get('translator')->trans($team->getCountry(), array(), 'lang'),
                    'flag' => $utilService->getFlag($team->getCountry())
                );
            }
            $groups[] = array('group' => $group, 'teams' => $teams);
        }
        $teamsUnassigned = array();
        foreach ($this->get('logic')->listTeamsEnrolledUnassigned($categoryid) as $team) {
            /* @var $team TeamInfo */
            $teamsUnassigned[] = array(
                'id' => $team->getId(),
                'name' => $team->getName(),
                'country' => $this->get('translator')->trans($team->getCountry(), array(), 'lang'),
                'flag' => $utilService->getFlag($team->getCountry())
            );
        }
        return new JsonResponse(array('groups' => $groups, 'unassigned' => array('teams' => $teamsUnassigned)));
    }
}


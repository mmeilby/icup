<?php

namespace ICup\Bundle\PublicSiteBundle\Controller\Doctrine;

use FOS\RestBundle\View\View;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;
use ICup\Bundle\PublicSiteBundle\Services\Util;
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
class CategoryController extends Controller
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
            throw $this->createNotFoundException($e->getMessage());
        }
        return new JsonResponse($tournament->getCategories()->toArray());
    }

    /**
     * Finds and displays a Doctrine\Category entity.
     *
     * @Route("/{categoryid}", name="rest_get_category", options={"expose"=true})
     * @Method("GET")
     */
    public function showAction($categoryid)
    {
        /* @var $category Category */
        try {
            $category = $this->get('entity')->getCategoryById($categoryid);
        }
        catch (ValidationException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }
        return new JsonResponse(array("category" => $category));
    }

    /**
     * Creates a new Doctrine\Category entity.
     *
     * @Route("/{tournamentid}", name="rest_category_create", options={"expose"=true})
     * @Method("POST")
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
            throw $this->createNotFoundException($e->getMessage());
        }
        try {
            $host = $tournament->getHost();
            $utilService->validateEditorAdminUser($user, $host);
        }
        catch (ValidationException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        /* @var $category Category */
        $category = new Category();
        $form = $this->createForm(new CategoryType(), $category);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $tournament->getCategories()->add($category);
            $em = $this->getDoctrine()->getManager();
            $em->persist($category);
            $em->flush();

            return new JsonResponse(array("id" => $category->getId()), Response::HTTP_CREATED);
        }

        return View::create($form, Response::HTTP_BAD_REQUEST);
    }

    /**
     * Edits an existing Doctrine\Category entity.
     *
     * @Route("/{categoryid}", name="rest_category_update", options={"expose"=true})
     * @Method("PUT")
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
            throw $this->createNotFoundException($e->getMessage());
        }
        try {
            $host = $category->getTournament()->getHost();
            $utilService->validateEditorAdminUser($user, $host);
        }
        catch (ValidationException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }
        
        $form = $this->createForm(new CategoryType(), $category, array('method' => 'PUT'));
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->flush();

            return new JsonResponse(array(), Response::HTTP_NO_CONTENT);
        }

        return View::create($form, Response::HTTP_BAD_REQUEST);
    }

    /**
     * Deletes a Doctrine\Category entity.
     *
     * @Route("/{categoryid}", name="rest_category_delete", options={"expose"=true})
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $categoryid)
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
            throw $this->createNotFoundException($e->getMessage());
        }
        try {
            $host = $category->getTournament()->getHost();
            $utilService->validateEditorAdminUser($user, $host);
        }
        catch (ValidationException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($category);
        $em->flush();
        return new JsonResponse(array(), Response::HTTP_NO_CONTENT);
    }
}

<?php

namespace ICup\Bundle\PublicSiteBundle\Controller\Rest\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Site;
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
use ICup\Bundle\PublicSiteBundle\Form\Doctrine\SiteType;
use RuntimeException;

/**
 * Doctrine\Site controller.
 *
 * @Route("/rest/site")
 */
class RestSiteController extends Controller
{
    /**
     * List the venues identified by tournament id
     * @Route("/list/{tournamentid}", name="_rest_list_sites", options={"expose"=true})
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
        return new JsonResponse($tournament->getSites()->toArray());
    }

    /**
     * Finds and displays a Doctrine\Site entity.
     *
     * @Route("/{siteid}", name="rest_get_site", options={"expose"=true})
     * @Method("GET")
     * @param $siteid
     * @return JsonResponse
     */
    public function showAction($siteid)
    {
        /* @var $site Site */
        try {
            $site = $this->get('entity')->getSiteById($siteid);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }
        return new JsonResponse(array("site" => $site));
    }

    /**
     * Creates a new Doctrine\Site entity.
     * Site id must be added to the request parameters
     * @Route("/", name="rest_site_create", options={"expose"=true})
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

        /* @var $site Site */
        $site = new Site();
        $site->setTournament($tournament);
        $form = $this->createForm(new SiteType(), $site);
        $form->handleRequest($request);
        if ($this->checkForm($form, $site)) {
            $tournament->getSites()->add($site);
            $em = $this->getDoctrine()->getManager();
            $em->persist($site);
            $em->flush();
            return new JsonResponse(array("id" => $site->getId()), Response::HTTP_CREATED);
        }

        $errors = array();
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }
        return new JsonResponse(array('errors' => $errors), Response::HTTP_BAD_REQUEST);
    }

    /**
     * Edits an existing Doctrine\Site entity.
     *
     * @Route("/{siteid}", name="rest_site_update", options={"expose"=true})
     * @Method("POST")
     * @param Request $request
     * @param $siteid
     * @return JsonResponse
     */
    public function updateAction(Request $request, $siteid)
    {
        /* @var $site Site */
        try {
            $site = $this->get('entity')->getSiteById($siteid);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }
        try {
            /* @var $utilService Util */
            $utilService = $this->get('util');
            /* @var $user User */
            $user = $utilService->getCurrentUser();
            $host = $site->getTournament()->getHost();
            $utilService->validateEditorAdminUser($user, $host);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }
        catch (RuntimeException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }

        $form = $this->createForm(new SiteType(), $site);
        $form->handleRequest($request);

        if ($this->checkForm($form, $site)) {
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
     * Deletes a Doctrine\Site entity.
     *
     * @Route("/{siteid}", name="rest_site_delete", options={"expose"=true})
     * @Method("DELETE")
     * @param $siteid
     * @return JsonResponse
     */
    public function deleteAction($siteid)
    {
        /* @var $site Site */
        try {
            $site = $this->get('entity')->getSiteById($siteid);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }
        try {
            /* @var $utilService Util */
            $utilService = $this->get('util');
            /* @var $user User */
            $user = $utilService->getCurrentUser();
            $host = $site->getTournament()->getHost();
            $utilService->validateEditorAdminUser($user, $host);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }
        catch (RuntimeException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }

        $errors = array();
        if ($site->getPlaygrounds()->count() > 0) {
            $errors[] = $this->get('translator')->trans('FORM.SITE.PLAYGROUNDSEXIST', array(), 'admin');
        }
        if (count($errors) == 0) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($site);
            $em->flush();
            return new JsonResponse(array(), Response::HTTP_NO_CONTENT);
        }
        return new JsonResponse(array('errors' => $errors), Response::HTTP_BAD_REQUEST);
    }

    private function checkForm(Form $form, Site $site) {
        if ($form->isValid()) {
            if ($site->getName() == null || trim($site->getName()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.SITE.NONAME', array(), 'admin')));
            }
            else {
                $em = $this->getDoctrine()->getManager();
                /* @var $othersite Site */
                $othersite = $em->getRepository($form->getConfig()->getOption("data_class"))->findOneBy(array('tournament' => $site->getTournament()->getId(), 'name' => $site->getName()));
                if ($othersite != null && $othersite->getId() != $site->getId()) {
                    $form->addError(new FormError($this->get('translator')->trans('FORM.SITE.NAMEEXISTS', array(), 'admin')));
                }
            }
        }
        return $form->isValid();
    }
}

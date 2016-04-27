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
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Playground;
use ICup\Bundle\PublicSiteBundle\Form\Doctrine\PlaygroundType;
use RuntimeException;

/**
 * Doctrine\Playground controller.
 *
 * @Route("/rest/venue")
 */
class RestPlaygroundController extends Controller
{
    /**
     * List the venues identified by tournament id
     * @Route("/list/{tournamentid}", name="_rest_list_playgrounds", options={"expose"=true})
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
        return new JsonResponse($tournament->getPlaygrounds()->toArray());
    }

    /**
     * Finds and displays a Doctrine\Playground entity.
     *
     * @Route("/{playgroundid}", name="rest_get_playground", options={"expose"=true})
     * @Method("GET")
     * @param $playgroundid
     * @return JsonResponse
     */
    public function showAction($playgroundid)
    {
        /* @var $playground Playground */
        try {
            $playground = $this->get('entity')->getPlaygroundById($playgroundid);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }
        return new JsonResponse(array("playground" => $playground));
    }

    /**
     * Creates a new Doctrine\Playground entity.
     * Site id must be added to the request parameters
     * @Route("/", name="rest_playground_create", options={"expose"=true})
     * @Method("POST")
     * @param Request $request
     * @return JsonResponse
     */
    public function newAction(Request $request)
    {
        /* @var $site Site */
        try {
            $site = $this->get('entity')->getSiteById($request->get('siteid', 0));
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

        /* @var $playground Playground */
        $playground = new Playground();
        $playground->setSite($site);
        $form = $this->createForm(new PlaygroundType(), $playground);
        $form->handleRequest($request);
        if ($this->checkForm($form, $playground)) {
            $site->getPlaygrounds()->add($playground);
            $em = $this->getDoctrine()->getManager();
            $em->persist($playground);
            $em->flush();
            return new JsonResponse(array("id" => $playground->getId()), Response::HTTP_CREATED);
        }

        $errors = array();
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }
        return new JsonResponse(array('errors' => $errors), Response::HTTP_BAD_REQUEST);
    }

    /**
     * Edits an existing Doctrine\Playground entity.
     *
     * @Route("/{playgroundid}", name="rest_playground_update", options={"expose"=true})
     * @Method("POST")
     * @param Request $request
     * @param $playgroundid
     * @return JsonResponse
     */
    public function updateAction(Request $request, $playgroundid)
    {
        /* @var $playground Playground */
        try {
            $playground = $this->get('entity')->getPlaygroundById($playgroundid);
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

        $form = $this->createForm(new PlaygroundType(), $playground);
        $form->handleRequest($request);

        if ($this->checkForm($form, $playground)) {
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
     * Deletes a Doctrine\Playground entity.
     *
     * @Route("/{playgroundid}", name="rest_playground_delete", options={"expose"=true})
     * @Method("DELETE")
     * @param $playgroundid
     * @return JsonResponse
     */
    public function deleteAction($playgroundid)
    {
        /* @var $playground Playground */
        try {
            $playground = $this->get('entity')->getPlaygroundById($playgroundid);
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

        $errors = array();
        if ($playground->getMatches()->count() > 0) {
            $errors[] = $this->get('translator')->trans('FORM.PLAYGROUND.MATCHESEXIST', array(), 'admin');
        }
        if ($playground->getPlaygroundAttributes()->count() > 0) {
            $errors[] = $this->get('translator')->trans('FORM.PLAYGROUND.PARELATIONSEXIST', array(), 'admin');
        }
        if (count($errors) == 0) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($playground);
            $em->flush();
            return new JsonResponse(array(), Response::HTTP_NO_CONTENT);
        }
        return new JsonResponse(array('errors' => $errors), Response::HTTP_BAD_REQUEST);
    }

    private function checkForm(Form $form, Playground $playground) {
        if ($form->isValid()) {
            if ($playground->getNo() == null || trim($playground->getNo()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.PLAYGROUND.NONO', array(), 'admin')));
            }
            else {
                $em = $this->getDoctrine()->getManager();
                /* @var $otherplayground Playground */
                $otherplayground = $em->getRepository($form->getConfig()->getOption("data_class"))->findOneBy(array('tournament' => $playground->getSite()->getTournament()->getId(), 'no' => $playground->getNo()));
                if ($otherplayground != null && $otherplayground->getId() != $playground->getId()) {
                    $form->addError(new FormError($this->get('translator')->trans('FORM.PLAYGROUND.NOEXISTS', array(), 'admin')));
                }
            }
            if ($playground->getName() == null || trim($playground->getName()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.PLAYGROUND.NONAME', array(), 'admin')));
            }
            else {
                $em = $this->getDoctrine()->getManager();
                /* @var $otherplayground Playground */
                $otherplayground = $em->getRepository($form->getConfig()->getOption("data_class"))->findOneBy(array('tournament' => $playground->getSite()->getTournament()->getId(), 'name' => $playground->getName()));
                if ($otherplayground != null && $otherplayground->getId() != $playground->getId()) {
                    $form->addError(new FormError($this->get('translator')->trans('FORM.PLAYGROUND.NAMEEXISTS', array(), 'admin')));
                }
            }
            if ($playground->getLocation() == null) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.PLAYGROUND.INVLOCATION', array(), 'admin')));
            }
        }
        return $form->isValid();
    }
}

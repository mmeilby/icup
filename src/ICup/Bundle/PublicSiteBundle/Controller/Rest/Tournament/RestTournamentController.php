<?php

namespace ICup\Bundle\PublicSiteBundle\Controller\Rest\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Host;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\TournamentOption;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;
use ICup\Bundle\PublicSiteBundle\Form\Doctrine\TournamentOptionType;
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
use ICup\Bundle\PublicSiteBundle\Form\Doctrine\TournamentType;
use RuntimeException;

/**
 * Doctrine\Tournament controller.
 *
 * @Route("/rest/tournament")
 */
class RestTournamentController extends Controller
{
    /**
     * List all the tournaments identified by host id
     * @Route("/list/{hostid}", name="_rest_list_tournaments", options={"expose"=true})
     * @Method("GET")
     * @param $hostid
     * @return JsonResponse
     */
    public function indexAction($hostid)
    {
        /* @var $host Host */
        try {
            $host = $this->get('entity')->getHostById($hostid);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }
        return new JsonResponse($host->getTournaments()->getValues());
    }

    /**
     * Finds and displays a Doctrine\Tournament entity.
     *
     * @Route("/{tournamentid}", name="_rest_get_tournament", options={"expose"=true})
     * @Method("GET")
     * @param $tournamentid
     * @return JsonResponse
     */
    public function showAction($tournamentid)
    {
        /* @var $tournament Tournament */
        try {
            $tournament = $this->get('entity')->getTournamentById($tournamentid);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }
        return new JsonResponse(array("tournament" => $tournament));
    }

    /**
     * Creates a new Doctrine\Tournament entity.
     * Tournament id must be added to the request parameters
     * @Route("/", name="_rest_tournament_create", options={"expose"=true})
     * @Method("POST")
     * @param Request $request
     * @return JsonResponse
     */
    public function newAction(Request $request)
    {
        /* @var $host Host */
        try {
            $host = $this->get('entity')->getHostById($request->get('hostid', 0));
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }
        try {
            /* @var $utilService Util */
            $utilService = $this->get('util');
            /* @var $user User */
            $user = $utilService->getCurrentUser();
            $utilService->validateEditorAdminUser($user, $host);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }
        catch (RuntimeException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }

        /* @var $tournament Tournament */
        $tournament = new Tournament();
        $tournament->setHost($host);
        $form = $this->createForm(new TournamentType(), $tournament);
        $form->handleRequest($request);
        if ($this->checkForm($form, $tournament)) {
            $host->getTournaments()->add($tournament);
            $em = $this->getDoctrine()->getManager();
            $em->persist($tournament);
            $em->flush();
            return new JsonResponse(array("id" => $tournament->getId()), Response::HTTP_CREATED);
        }

        $errors = array();
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }
        return new JsonResponse(array('errors' => $errors), Response::HTTP_BAD_REQUEST);
    }

    /**
     * Edits an existing Doctrine\Tournament entity.
     *
     * @Route("/{tournamentid}", name="_rest_tournament_update", options={"expose"=true})
     * @Method("POST")
     * @param Request $request
     * @param $tournamentid
     * @return JsonResponse
     */
    public function updateAction(Request $request, $tournamentid)
    {
        /* @var $tournament Tournament */
        try {
            $tournament = $this->get('entity')->getTournamentById($tournamentid);
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

        $form = $this->createForm(new TournamentType(), $tournament);
        $form->handleRequest($request);

        if ($this->checkForm($form, $tournament)) {
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
     * Deletes a Doctrine\Tournament entity.
     *
     * @Route("/{tournamentid}", name="_rest_tournament_delete", options={"expose"=true})
     * @Method("DELETE")
     * @param $tournamentid
     * @return JsonResponse
     */
    public function deleteAction($tournamentid)
    {
        /* @var $tournament Tournament */
        try {
            $tournament = $this->get('entity')->getTournamentById($tournamentid);
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

        $errors = array();
        if ($tournament->getCategories()->count() > 0) {
            $errors[] = $this->get('translator')->trans('FORM.TOURNAMENT.CATEGORIESEXIST', array(), 'admin');
        }
        if ($tournament->getTimeslots()->count() > 0) {
            $errors[] = $this->get('translator')->trans('FORM.TOURNAMENT.TIMESLOTSEXIST', array(), 'admin');
        }
        if ($tournament->getSites()->count() > 0) {
            $errors[] = $this->get('translator')->trans('FORM.TOURNAMENT.SITESEXIST', array(), 'admin');
        }
        if ($tournament->getEvents()->count() > 0) {
            $errors[] = $this->get('translator')->trans('FORM.TOURNAMENT.EVENTSEXIST', array(), 'admin');
        }
        if ($tournament->getNews()->count() > 0) {
            $errors[] = $this->get('translator')->trans('FORM.TOURNAMENT.NEWSEXIST', array(), 'admin');
        }
        if (count($errors) == 0) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($tournament);
            $em->flush();
            return new JsonResponse(array(), Response::HTTP_NO_CONTENT);
        }
        return new JsonResponse(array('errors' => $errors), Response::HTTP_BAD_REQUEST);
    }

    private function checkForm(Form $form, Tournament $tournament) {
        if ($form->isValid()) {
            if ($tournament->getName() == null || trim($tournament->getName()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.TOURNAMENT.NONAME', array(), 'admin')));
            }
            else {
                $em = $this->getDoctrine()->getManager();
                /* @var $othertournament Tournament */
                $othertournament = $em->getRepository($form->getConfig()->getOption("data_class"))->findOneBy(array('host' => $tournament->getHost()->getId(), 'name' => $tournament->getName()));
                if ($othertournament != null && $othertournament->getId() != $tournament->getId()) {
                    $form->addError(new FormError($this->get('translator')->trans('FORM.TOURNAMENT.NAMEEXISTS', array(), 'admin')));
                }
            }
            if ($tournament->getKey() == null || trim($tournament->getKey()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.TOURNAMENT.NOKEY', array(), 'admin')));
            }
            else {
                $em = $this->getDoctrine()->getManager();
                /* @var $othertournament Tournament */
                $othertournament = $em->getRepository($form->getConfig()->getOption("data_class"))->findOneBy(array('host' => $tournament->getHost()->getId(), 'key' => $tournament->getKey()));
                if ($othertournament != null && $othertournament->getId() != $tournament->getId()) {
                    $form->addError(new FormError($this->get('translator')->trans('FORM.TOURNAMENT.KEYEXIST', array(), 'admin')));
                }
            }
            if ($tournament->getEdition() == null || trim($tournament->getEdition()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.TOURNAMENT.NOEDITION', array(), 'admin')));
            }
        }
        return $form->isValid();
    }

    /**
     * Updates options for an existing Doctrine\Tournament entity.
     *
     * @Route("/options/{tournamentid}", name="_rest_tournament_options_update", options={"expose"=true})
     * @Method("POST")
     * @param Request $request
     * @param $tournamentid
     * @return JsonResponse
     */
    public function updateOptionsAction(Request $request, $tournamentid)
    {
        /* @var $tournament Tournament */
        try {
            $tournament = $this->get('entity')->getTournamentById($tournamentid);
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

        $form = $this->createForm(new TournamentOptionType(), $tournament->getOption());
        $form->handleRequest($request);

        if ($this->checkOptionsForm($form, $tournament->getOption())) {
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
    
    private function checkOptionsForm(Form $form, TournamentOption $options) {
        if ($form->isValid()) {
            if ($options->getWpoints() === null || trim($options->getWpoints()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.TOURNAMENTOPTIONS.NOWP', array(), 'admin')));
            }
            if ($options->getTpoints() === null || trim($options->getTpoints()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.TOURNAMENTOPTIONS.NOTP', array(), 'admin')));
            }
            if ($options->getLpoints() === null || trim($options->getLpoints()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.TOURNAMENTOPTIONS.NOLP', array(), 'admin')));
            }
            if ($options->getDscore() === null || trim($options->getDscore()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.TOURNAMENTOPTIONS.NODS', array(), 'admin')));
            }
            if ($options->getStrategy() === null || trim($options->getStrategy()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.TOURNAMENTOPTIONS.NOSTRATEGY', array(), 'admin')));
            }
            if ($options->getOrder() === null) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.TOURNAMENTOPTIONS.NOORDER', array(), 'admin')));
            }
        }
        return $form->isValid();
    }

}


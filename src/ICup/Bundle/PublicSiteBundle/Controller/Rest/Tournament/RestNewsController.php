<?php

namespace ICup\Bundle\PublicSiteBundle\Controller\Rest\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
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
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\News;
use ICup\Bundle\PublicSiteBundle\Form\Doctrine\NewsType;
use RuntimeException;

/**
 * Doctrine\News controller.
 *
 * @Route("/rest/news")
 */
class RestNewsController extends Controller
{
    /**
     * List all the news identified by tournament id
     * @Route("/list/{tournamentid}", name="_rest_list_news", options={"expose"=true})
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
        $newslist = array();
        foreach ($tournament->getNews() as $news) {
            /* @var $news News */
            $newslist[] = $news->jsonSerialize();
        }
        return new JsonResponse(array_values($newslist));
    }

    /**
     * Finds and displays a Doctrine\News entity.
     *
     * @Route("/{newsid}", name="rest_get_news", options={"expose"=true})
     * @Method("GET")
     * @param $newsid
     * @return JsonResponse
     */
    public function showAction($newsid)
    {
        /* @var $news News */
        try {
            $news = $this->get('entity')->getNewsById($newsid);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }
        return new JsonResponse(array("news" => $news));
    }

    /**
     * Creates a new Doctrine\News entity.
     * Tournament id must be added to the request parameters
     * @Route("/", name="rest_news_create", options={"expose"=true})
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

        /* @var $news News */
        $news = new News();
        $news->setTournament($tournament);
        $form = $this->createForm(new NewsType(), $news);
        $form->handleRequest($request);
        if ($this->checkForm($form, $news)) {
            $em = $this->getDoctrine()->getManager();
            /* @var $othernews News */
            $othernews = $em->getRepository($form->getConfig()->getOption("data_class"))->findBy(array('tournament' => $news->getTournament()->getId(), 'newsno' => $news->getNewsno()));
            foreach ($othernews as $onews) {
                /* @var $onews News */
                if ($onews->getLanguage() == $news->getLanguage()) {
                    $form->addError(new FormError($this->get('translator')->trans('FORM.NEWS.NEWSEXISTS', array(), 'admin')));
                    break;
                }
            }
            if ($form->isValid()) {
                $tournament->getNews()->add($news);
                $em->persist($news);
                $em->flush();
                return new JsonResponse(array("id" => $news->getId()), Response::HTTP_CREATED);
            }
        }

        $errors = array();
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }
        return new JsonResponse(array('errors' => $errors), Response::HTTP_BAD_REQUEST);
    }

    /**
     * Edits an existing Doctrine\News entity.
     *
     * @Route("/{newsid}", name="rest_news_update", options={"expose"=true})
     * @Method("POST")
     * @param Request $request
     * @param $newsid
     * @return JsonResponse
     */
    public function updateAction(Request $request, $newsid)
    {
        /* @var $news News */
        try {
            $news = $this->get('entity')->getNewsById($newsid);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }
        try {
            /* @var $utilService Util */
            $utilService = $this->get('util');
            /* @var $user User */
            $user = $utilService->getCurrentUser();
            $host = $news->getTournament()->getHost();
            $utilService->validateEditorAdminUser($user, $host);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }
        catch (RuntimeException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }

        $form = $this->createForm(new NewsType(), $news);
        $form->handleRequest($request);

        $em = $this->getDoctrine()->getManager();
        if ($this->checkForm($form, $news)) {
            /* @var $othernews News */
            $othernews = $em->getRepository($form->getConfig()->getOption("data_class"))->findBy(array('tournament' => $news->getTournament()->getId(), 'newsno' => $news->getNewsno()));
            foreach ($othernews as $onews) {
                /* @var $onews News */
                if ($onews->getLanguage() == $news->getLanguage() && $onews->getId() != $news->getId()) {
                    $form->addError(new FormError($this->get('translator')->trans('FORM.NEWS.CANTCHANGENEWS', array(), 'admin')));
                    break;
                }
            }
            if ($form->isValid()) {
                $em->flush();
                return new JsonResponse(array(), Response::HTTP_NO_CONTENT);
            }
        }
        // discard changes when the news object has not been validated successfully
        $em->detach($news);

        $errors = array();
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }
        return new JsonResponse(array('errors' => $errors), Response::HTTP_BAD_REQUEST);
    }

    /**
     * Deletes a Doctrine\News entity.
     *
     * @Route("/{newsid}", name="rest_news_delete", options={"expose"=true})
     * @Method("DELETE")
     * @param $newsid
     * @return JsonResponse
     */
    public function deleteAction($newsid)
    {
        /* @var $news News */
        try {
            $news = $this->get('entity')->getNewsById($newsid);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }
        try {
            /* @var $utilService Util */
            $utilService = $this->get('util');
            /* @var $user User */
            $user = $utilService->getCurrentUser();
            $host = $news->getTournament()->getHost();
            $utilService->validateEditorAdminUser($user, $host);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }
        catch (RuntimeException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($news);
        $em->flush();
        return new JsonResponse(array(), Response::HTTP_NO_CONTENT);
    }

    private function checkForm(Form $form, News $news) {
        if ($form->isValid()) {
            if ($news->getNewsno() == null || trim($news->getNewsno()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.NEWS.NONO', array(), 'admin')));
            }
            if ($news->getNewstype() == null) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.NEWS.NONEWS', array(), 'admin')));
            }
            if ($news->getLanguage() == null || trim($news->getLanguage()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.NEWS.NOLANGUAGE', array(), 'admin')));
            }
            if ($news->getTitle() == null || trim($news->getTitle()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.NEWS.NOTITLE', array(), 'admin')));
            }
            if ($news->getContext() == null || trim($news->getContext()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.NEWS.NOCONTEXT', array(), 'admin')));
            }
            if ($news->getDate() == null || trim($news->getDate()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.NEWS.NODATE', array(), 'admin')));
            }
            else {
                $date = date_create_from_format("m/d/Y", $news->getDate());
                if ($date === false) {
                    $form->addError(new FormError($this->get('translator')->trans('FORM.NEWS.BADDATE', array(), 'admin')));
                }
                else {
                    $news->setDate(Date::getDate($date));
                }
            }
        }
        return $form->isValid();
    }

    /**
     * Attach an existing Doctrine\News entity to a team.
     *
     * @Route("/updteam/{newsid}/{teamid}", name="rest_news_update_team", options={"expose"=true})
     * @Method("POST")
     * @param Request $request
     * @param $newsid
     * @param $teamid
     * @return JsonResponse
     */
    public function updateTeamAction(Request $request, $newsid, $teamid)
    {
        /* @var $news News */
        try {
            $news = $this->get('entity')->getNewsById($newsid);
            $team = $this->get('entity')->getTeamById($teamid);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }
        try {
            /* @var $utilService Util */
            $utilService = $this->get('util');
            /* @var $user User */
            $user = $utilService->getCurrentUser();
            $host = $news->getTournament()->getHost();
            $utilService->validateEditorAdminUser($user, $host);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }
        catch (RuntimeException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }

        $news->setTeam($team);
        
        $em = $this->getDoctrine()->getManager();
        $em->flush();
        return new JsonResponse(array(), Response::HTTP_NO_CONTENT);
    }

    /**
     * Attach an existing Doctrine\News entity to a match.
     *
     * @Route("/updmatch/{newsid}/{matchid}", name="rest_news_update_match", options={"expose"=true})
     * @Method("POST")
     * @param Request $request
     * @param $newsid
     * @param $matchid
     * @return JsonResponse
     */
    public function updateMatchAction(Request $request, $newsid, $matchid)
    {
        /* @var $news News */
        try {
            $news = $this->get('entity')->getNewsById($newsid);
            $match = $this->get('entity')->getMatchById($matchid);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }
        try {
            /* @var $utilService Util */
            $utilService = $this->get('util');
            /* @var $user User */
            $user = $utilService->getCurrentUser();
            $host = $news->getTournament()->getHost();
            $utilService->validateEditorAdminUser($user, $host);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }
        catch (RuntimeException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }

        $news->setMatch($match);

        $em = $this->getDoctrine()->getManager();
        $em->flush();
        return new JsonResponse(array(), Response::HTTP_NO_CONTENT);
    }
}

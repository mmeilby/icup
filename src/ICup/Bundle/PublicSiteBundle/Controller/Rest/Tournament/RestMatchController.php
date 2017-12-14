<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Rest\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;
use ICup\Bundle\PublicSiteBundle\Form\Doctrine\MatchType;
use ICup\Bundle\PublicSiteBundle\Services\Util;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match;
use RuntimeException;
use DateTime;

/**
 * Doctrine\Match controller.
 *
 * @Route("/rest/match")
 */
class RestMatchController extends Controller
{
    /**
     * Finds and displays a Doctrine\Match entity.
     *
     * @Route("/{matchid}", name="rest_get_match", options={"expose"=true})
     * @Method("GET")
     * @param $matchid
     * @return JsonResponse
     */
    public function showAction($matchid)
    {
        /* @var $match Match */
        try {
            $match = $this->get('entity')->getMatchById($matchid);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }
        return new JsonResponse(array("match" => $match));
    }

    /**
     * Creates a new Doctrine\Match entity.
     * Group id must be added to the request parameters
     * @Route("/", name="rest_match_create", options={"expose"=true})
     * @Method("POST")
     * @param Request $request
     * @return JsonResponse
     */
    public function newAction(Request $request)
    {
        /* @var $group Group */
        try {
            $group = $this->get('entity')->getGroupById($request->get('groupid', 0));
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

        /* @var $match Match */
        $match = new Match();
        $match->setGroup($group);
        $form = $this->createForm(new MatchType(), $match);
        $form->handleRequest($request);
        if ($this->checkForm($form, $match)) {
            $group->getMatches()->add($match);
            $em = $this->getDoctrine()->getManager();
            $em->persist($match);
            $em->flush();
            return new JsonResponse(array("id" => $match->getId()), Response::HTTP_CREATED);
        }

        $errors = array();
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }
        return new JsonResponse(array('errors' => $errors), Response::HTTP_BAD_REQUEST);
    }

    /**
     * Edits an existing Doctrine\Match entity.
     *
     * @Route("/{matchid}", name="rest_match_update", options={"expose"=true})
     * @Method("POST")
     * @param Request $request
     * @param $matchid
     * @return JsonResponse
     */
    public function updateAction(Request $request, $matchid)
    {
        /* @var $match Match */
        try {
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
            $host = $match->getGroup()->getCategory()->getTournament()->getHost();
            $utilService->validateEditorAdminUser($user, $host);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }
        catch (RuntimeException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }

        $form = $this->createForm(new MatchType(), $match);
        $form->handleRequest($request);

        if ($this->checkForm($form, $match)) {
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
     * Deletes a Doctrine\Match entity.
     *
     * @Route("/{matchid}", name="rest_match_delete", options={"expose"=true})
     * @Method("DELETE")
     * @param $matchid
     * @return JsonResponse
     */
    public function deleteAction($matchid)
    {
        /* @var $match Match */
        try {
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
            $host = $match->getGroup()->getCategory()->getTournament()->getHost();
            $utilService->validateEditorAdminUser($user, $host);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }
        catch (RuntimeException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }

        $errors = array();
        foreach ($match->getMatchRelations() as $matchRelation) {
            /* @var $matchRelation MatchRelation */
            if ($matchRelation->getScorevalid()) {
                $errors[] = $this->get('translator')->trans('FORM.MATCH.SCOREISVALID', array(), 'admin');
            }
        }
        if (count($errors) == 0) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($match);
            $em->flush();
            return new JsonResponse(array(), Response::HTTP_NO_CONTENT);
        }
        return new JsonResponse(array('errors' => $errors), Response::HTTP_BAD_REQUEST);
    }

    private function checkForm(Form $form, Match $match) {
        if ($form->isValid()) {
            if ($match->getMatchno() == null || trim($match->getMatchno()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.MATCH.NONO', array(), 'admin')));
            }
            if ($match->getDate() == null || trim($match->getDate()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.MATCH.NODATE', array(), 'admin')));
            }
            else {
                $date = date_create_from_format($this->get('translator')->trans('FORMAT.DATE'), $match->getDate());
                if ($date === false) {
                    $form->addError(new FormError($this->get('translator')->trans('FORM.MATCH.BADDATE', array(), 'admin')));
                }
            }
            if ($match->getTime() == null || trim($match->getTime()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.MATCH.NOTIME', array(), 'admin')));
            }
            else {
                $time = date_create_from_format($this->get('translator')->trans('FORMAT.TIME'), $match->getTime());
                if ($time === false) {
                    $form->addError(new FormError($this->get('translator')->trans('FORM.MATCH.BADTIME', array(), 'admin')));
                }
            }
            if ($match->getPlayground() == null) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.MATCH.NOPLAYGROUND', array(), 'admin')));
            }
        }
        return $form->isValid();
    }

    /**
     * Get the match identified by tournament and match #
     * @Route("/get/{tournamentid}/{matchno}", name="_rest_get_match", options={"expose"=true})
     * @Method("GET")
     * @param $tournamentid
     * @param $matchno
     * @return JsonResponse
     */
    public function restGetMatchAction($tournamentid, $matchno)
    {
        /* @var $match Match */
        $match = $this->get('match')->getMatchByNo($tournamentid, $matchno);
        if ($match) {
            return new JsonResponse($match);
        }
        else {
            return new JsonResponse(array('errors' => array('NOMATCH')), Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Search matches identified by tournament and matchno, date, category or playground
     * @Route("/search/{tournamentid}", name="_rest_search_match", options={"expose"=true})
     * @Method("GET")
     * @param $tournamentid
     * @param Request $request
     * @return JsonResponse
     */
    public function restSearchMatchAction($tournamentid, Request $request)
    {
        $matches = array();
        if ($request->get('matchno')) {
            $matchno = $request->get('matchno');
            $matches = $this->get('match')->listMatchByNo($tournamentid, $matchno);
        }
        else {
            $key = 0;
            if ($request->get('date')) {
                $date = DateTime::createFromFormat('d-m-Y', $request->get('date'));
                if ($date == null) {
                    throw new ValidationException("INVALIDDATE", "Match date invalid: date=".$request->get('date'));
                }
                $key += 1;
            }
            if ($request->get('group')) {
                $groupid = $request->get('group');
                $key += 2;
            }
            if ($request->get('playground')) {
                $playgroundid = $request->get('playground');
                $key += 4;
            }
            switch ($key) {
                case 0:
                    $matches = $this->get('match')->listMatchesByTournament($tournamentid);
                    break;
                case 1:
                    $matches = $this->get('match')->listMatchesByDate($tournamentid, $date);
                    break;
                case 2:
                    $matches = $this->get('match')->listMatchesByGroup($groupid);
                    break;
                case 3:
                    $matches = $this->get('match')->listMatchesByGroup($groupid, $date);
                    break;
                case 4:
                    $matches = $this->get('match')->listMatchesByPlayground($playgroundid);
                    break;
                case 5:
                    $matches = $this->get('match')->listMatchesByPlaygroundDate($playgroundid, $date);
                    break;
                case 6:
                    $matches = $this->get('match')->listMatchesByGroupPlayground($groupid, $playgroundid);
                    break;
                case 7:
                    $matches = $this->get('match')->listMatchesByGroupPlayground($groupid, $playgroundid, $date);
                    break;
            }
        }
        $dateformat = $this->get('translator')->trans('FORMAT.DATE');
        $timeformat = $this->get('translator')->trans('FORMAT.TIME');
        foreach ($matches as &$match) {
            $match['date'] = date_format($match['schedule'], $dateformat);
            $match['time'] = date_format($match['schedule'], $timeformat);

            $homeflag = $this->get('util')->getFlag($match['home']['country']);
            if ($homeflag) {
                $match['home']['flag'] = $homeflag;
                $match['home']['country'] = $this->get('translator')->trans($match['home']['country'], array(), "lang");
            }
            else {
                $match['home']['flag'] = '';
            }

            $awayflag = $this->get('util')->getFlag($match['away']['country']);
            if ($awayflag) {
                $match['away']['flag'] = $awayflag;
                $match['away']['country'] = $this->get('translator')->trans($match['away']['country'], array(), "lang");
            }
            else {
                $match['away']['flag'] = '';
            }
        }

        return new JsonResponse($matches);
    }
}

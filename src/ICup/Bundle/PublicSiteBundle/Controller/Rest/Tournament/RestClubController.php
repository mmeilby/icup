<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Rest\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Enrollment;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team;
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
use ICup\Bundle\PublicSiteBundle\Form\Doctrine\ClubType;
use RuntimeException;

/**
 * Doctrine\Club controller.
 *
 * @Route("/rest/club")
 */
class RestClubController extends Controller
{
    /**
     * List all the clubs
     * @Route("/list", name="_rest_list_all_clubs", options={"expose"=true})
     * @Method("GET")
     * @return JsonResponse
     */
    public function indexAction()
    {
        try {
            /* @var $utilService Util */
            $utilService = $this->get('util');
            /* @var $user User */
            $user = $utilService->getCurrentUser();
        }
        catch (RuntimeException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }
        if (!$user->isAdmin()) {
            return new JsonResponse(array('errors' => array("NEEDTOBEADMIN")), Response::HTTP_FORBIDDEN);
        }
        $clubs = $this->get('logic')->listClubs();
        return new JsonResponse($this->translate(array_filter($clubs, function (Club $club) {
            return !$club->isVacant();
        })));
    }

    /**
     * List the clubs available for a country matching the pattern given
     * @Route("/search/{countrycode}/{pattern}", name="_rest_search_clubs", options={"expose"=true})
     * @Method("GET")
     * @param $pattern      string with % for wildcard
     * @param $countrycode  string like DNK, DEU, ITA
     * @return JsonResponse
     */
    public function searchClubsAction($pattern, $countrycode)
    {
        $clubs = $this->get('logic')->listClubsByPattern($pattern, $countrycode);
//        return new JsonResponse(array('errors' => array()), Response::HTTP_NOT_FOUND);
        return new JsonResponse(count($clubs) > 0 ? $this->translate(array_slice($clubs, 0, 3)) : array());
    }


    /**
     * List all the clubs enrolled to tournament id
     * @Route("/list/{tournamentid}", name="_rest_list_clubs", options={"expose"=true})
     * @Method("GET")
     * @param $tournamentid
     * @return JsonResponse
     */
    public function listEnrolledClubs($tournamentid)
    {
        /* @var $tournament Tournament */
        try {
            $tournament = $this->get('entity')->getTournamentById($tournamentid);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $list = $this->get('logic')->listEnrolled($tournament->getId());
        $translatedList = array();
        /* @var $club Club */
        foreach ($list as $rec) {
            /* @var $club Club */
            $club = $rec['club'];
            if (!$club->isVacant()) {
                $translatedList[] = array_merge($club->jsonSerialize(), array(
                    'country' => $this->get('translator')->trans($club->getCountry(), array(), 'lang'),
                    'flag' => $utilService->getFlag($club->getCountry()),
                    'enrolled' => $rec['enrolled']
                ));
            }
        }
        return new JsonResponse($translatedList);
    }

    /**
     * List the categories the club is enrolled for in tournament
     * @Route("/list/{tournamentid}/{clubid}", name="_rest_list_enrolled_teams", options={"expose"=true})
     * @Method("GET")
     * @param $tournamentid
     * @param $clubid
     * @return JsonResponse
     */
    public function listEnrolledCategories($tournamentid, $clubid)
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
        /* @var $club Club */
        try {
            $club = $this->get('entity')->getClubById($clubid);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }
        $enrolled = $this->get('logic')->listEnrolledByClub($tournament->getId(), $club->getId());
        $enrolledByCategory = array();
        /* @var $enroll Enrollment */
        foreach ($enrolled as $enroll) {
            $enrolledByCategory[$enroll->getCategory()->getId()][] = $enroll;
        }
        $translatedList = array();
        foreach ($tournament->getCategories() as $category) {
            $translatedList[] = array_merge($category->jsonSerialize(), array(
                'classification_translated' => $this->get('translator')->transChoice(
                                                            'GENDER.'.$category->getGender().$category->getClassification(),
                                                            $category->getAge(),
                                                            array('%age%' => $category->getAge()),
                                                            'tournament'),
                'enrolled' => isset($enrolledByCategory[$category->getId()]) ? count($enrolledByCategory[$category->getId()]) : 0
            ));
        }
        return new JsonResponse($translatedList);
    }

    /**
     * Finds and displays a Doctrine\Club entity.
     *
     * @Route("/{clubid}", name="rest_get_club", options={"expose"=true})
     * @Method("GET")
     * @param $clubid
     * @return JsonResponse
     */
    public function showAction($clubid)
    {
        /* @var $club Club */
        try {
            $club = $this->get('entity')->getClubById($clubid);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }
        return new JsonResponse(array('club' => $this->translate(array($club))));
    }

    /**
     * Creates a new Doctrine\Club entity.
     *
     * @Route("/", name="rest_club_create", options={"expose"=true})
     * @Method("POST")
     * @param Request $request
     * @return JsonResponse
     */
    public function newAction(Request $request)
    {
        try {
            /* @var $utilService Util */
            $utilService = $this->get('util');
            /* @var $user User */
            $user = $utilService->getCurrentUser();
        }
        catch (RuntimeException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }
        if (!$user->isEditor() && !$user->isAdmin()) {
            return new JsonResponse(array('errors' => array("NEEDTOBEEDITOR")), Response::HTTP_FORBIDDEN);
        }

        /* @var $club Club */
        $club = new Club();
        $form = $this->createForm(new ClubType(), $club);
        $form->handleRequest($request);
        if ($this->checkForm($form, $club)) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($club);
            $em->flush();
            return new JsonResponse(array("id" => $club->getId()), Response::HTTP_CREATED);
        }

        $errors = array();
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }
        return new JsonResponse(array('errors' => $errors), Response::HTTP_BAD_REQUEST);
    }

    /**
     * Edits an existing Doctrine\Club entity.
     *
     * @Route("/{clubid}", name="rest_club_update", options={"expose"=true})
     * @Method("POST")
     * @param Request $request
     * @param $clubid
     * @return JsonResponse
     */
    public function updateAction(Request $request, $clubid)
    {
        try {
            /* @var $utilService Util */
            $utilService = $this->get('util');
            /* @var $user User */
            $user = $utilService->getCurrentUser();
        }
        catch (RuntimeException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }
        if (!$user->isEditor() && !$user->isAdmin()) {
            return new JsonResponse(array('errors' => array("NEEDTOBEEDITOR")), Response::HTTP_FORBIDDEN);
        }

        /* @var $club Club */
        try {
            $club = $this->get('entity')->getClubById($clubid);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }

        $form = $this->createForm(new ClubType(), $club);
        $form->handleRequest($request);

        if ($this->checkForm($form, $club)) {
            /* @var $team Team */
            foreach ($club->getTeams() as $team) {
                // in case that the club name has changed - change team name as well
                $team->setName($club->getName());
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
     * Deletes a Doctrine\Club entity.
     *
     * @Route("/{clubid}", name="rest_club_delete", options={"expose"=true})
     * @Method("DELETE")
     * @param $clubid
     * @return JsonResponse
     */
    public function deleteAction($clubid)
    {
        try {
            /* @var $utilService Util */
            $utilService = $this->get('util');
            /* @var $user User */
            $user = $utilService->getCurrentUser();
        }
        catch (RuntimeException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }
        if (!$user->isEditor() && !$user->isAdmin()) {
            return new JsonResponse(array('errors' => array("NEEDTOBEEDITOR")), Response::HTTP_FORBIDDEN);
        }

        /* @var $club Club */
        try {
            $club = $this->get('entity')->getClubById($clubid);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }

        $errors = array();
        if ($club->getTeams()->count() > 0) {
            $errors[] = $this->get('translator')->trans('FORM.CLUB.TEAMSEXIST', array(), 'admin');
        }
        if (count($errors) == 0) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($club);
            $em->flush();
            return new JsonResponse(array(), Response::HTTP_NO_CONTENT);
        }
        return new JsonResponse(array('errors' => $errors), Response::HTTP_BAD_REQUEST);
    }

    private function checkForm(Form $form, Club $club) {
        if ($form->isValid()) {
            if ($club->getName() == null || trim($club->getName()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.CLUB.NONAME', array(), 'admin')));
            }
            else {
                $em = $this->getDoctrine()->getManager();
                /* @var $otherclub Club */
                $otherclub = $em->getRepository($form->getConfig()->getOption("data_class"))->findOneBy(array('name' => $club->getName(), 'country' => $club->getCountry()));
                if ($otherclub != null && $otherclub->getId() != $club->getId()) {
                    $form->addError(new FormError($this->get('translator')->trans('FORM.CLUB.NAMEEXISTS', array(), 'admin')));
                }
            }
            if ($club->getCountry() == null || trim($club->getCountry()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.CLUB.NOCOUNTRY', array(), 'admin')));
            }
            else {
                /* @var $utilService Util */
                $utilService = $this->get('util');
                $countries = $utilService->getCountries();
                if (array_search($club->getCountry(), $countries) === false) {
                    $form->addError(new FormError($this->get('translator')->trans('FORM.CLUB.UNKNOWNCOUNTRY', array(), 'admin')));
                }
            }
        }
        return $form->isValid();
    }

    private function translate($list) {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $translatedList = array();
        /* @var $club Club */
        foreach ($list as $club) {
            $translatedList[] = array_merge($club->jsonSerialize(), array(
                'country' => $this->get('translator')->trans($club->getCountry(), array(), 'lang'),
                'flag' => $utilService->getFlag($club->getCountry())
            ));
        }
        return $translatedList;
    }

    /**
     * Enrolls a club in a tournament by adding new team to category
     * @Route("/enroll/add/{categoryid}/{clubid}", name="_rest_club_enroll_add", options={"expose"=true})
     * @Method("GET")
     * @param $categoryid
     * @param $clubid
     * @return JsonResponse
     */
    public function addEnrollAction($categoryid, $clubid) {
        /* @var $category Category */
        try {
            $category = $this->get('entity')->getCategoryById($categoryid);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }
        // Check that user is editor
        try {
            /* @var $utilService Util */
            $utilService = $this->get('util');
            /* @var $user User */
            $user = $utilService->getCurrentUser();
            /* @var $tournament Tournament */
            $tournament = $category->getTournament();
            $host = $tournament->getHost();
            $utilService->validateEditorAdminUser($user, $host);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }
        catch (RuntimeException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }
        /* @var $club Club */
        try {
            $club = $this->get('entity')->getClubById($clubid);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }

        try {
            $this->get('logic')->addEnrolled($category, $club, $user);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_BAD_REQUEST);
        }
        return new JsonResponse(array(), Response::HTTP_NO_CONTENT);
    }

    /**
     * Remove last team from category - including all related match results
     * @Route("/enroll/del/{categoryid}/{clubid}", name="_rest_club_enroll_del", options={"expose"=true})
     * @Method("GET")
     * @param $categoryid
     * @param $clubid
     * @return JsonResponse
     */
    public function delEnrollAction($categoryid, $clubid) {
        /* @var $category Category */
        try {
            $category = $this->get('entity')->getCategoryById($categoryid);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }
        // Check that user is editor
        try {
            /* @var $utilService Util */
            $utilService = $this->get('util');
            /* @var $user User */
            $user = $utilService->getCurrentUser();
            /* @var $tournament Tournament */
            $tournament = $category->getTournament();
            $host = $tournament->getHost();
            $utilService->validateEditorAdminUser($user, $host);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }
        catch (RuntimeException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_FORBIDDEN);
        }
        /* @var $club Club */
        try {
            $club = $this->get('entity')->getClubById($clubid);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }

        try {
            $this->get('logic')->deleteEnrolled($category->getId(), $club->getId());
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_BAD_REQUEST);
        }
        return new JsonResponse(array(), Response::HTTP_NO_CONTENT);
    }
}

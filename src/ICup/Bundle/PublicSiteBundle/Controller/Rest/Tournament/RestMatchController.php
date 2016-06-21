<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Rest\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;
use ICup\Bundle\PublicSiteBundle\Services\Doctrine\MatchSupport;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match;
use DateTime;

class RestMatchController extends Controller
{
    /**
     * Get the match identified by tournament and match #
     * @Route("/rest/match/get/{tournamentid}/{matchno}", name="_rest_get_match", options={"expose"=true})
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
     * @Route("/rest/match/search/{tournamentid}", name="_rest_search_match", options={"expose"=true})
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

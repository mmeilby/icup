<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Admin\Tournament;

use DateTime;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\PlaygroundAttribute;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ICup\Bundle\PublicSiteBundle\Services\Util;
use Symfony\Component\HttpFoundation\Request;

class MatchPrintController extends Controller
{
    /**
     * @Route("/host/print/matchlist/{tournamentid}/{date}", name="_edit_matchlist_print")
     * @Template("ICupPublicSiteBundle:Host:printmatches.html.twig")
     * @Method("GET")
     */
    public function listAction($tournamentid, $date)
    {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        /* @var $user User */
        $user = $utilService->getCurrentUser();
        /* @var $tournament Tournament */
        $tournament = $this->get('entity')->getTournamentById($tournamentid);
        $host = $tournament->getHost();
        $utilService->validateEditorAdminUser($user, $host->getId());

        $matchDate = DateTime::createFromFormat('d-m-Y', $date);
        if ($matchDate == null) {
            throw new ValidationException("INVALIDDATE", "Match date invalid: date=".$date);
        }
        $matchDate = $this->get('match')->getMatchDate($tournament->getId(), $matchDate);
        $eventdates = $this->get('match')->listMatchCalendar($tournament->getId());
        $timeslots = $tournament->getTimeslots();
        $pattrs = $this->get('logic')->listPlaygroundAttributesByTournament($tournament->getId());
        $pattrList = array();
        /* @var $pattr PlaygroundAttribute */
        foreach ($pattrs as $pattr) {
            $pattrList[$pattr->getPlayground()->getId()][] = $pattr;
        }

        $matches = $this->get('match')->listMatchesByDate($tournament->getId(), $matchDate);
        $matchList = array();
        foreach ($matches as $match) {
            $slotid = 0;
            foreach ($pattrList[$match['playground']['id']] as $pattr) {
                /* @var $diffstart \DateInterval */
                $diffstart = $pattr->getStartSchedule()->getTimestamp() - $match['schedule']->getTimestamp();
                /* @var $diffend \DateInterval */
                $diffend = $pattr->getEndSchedule()->getTimestamp() - $match['schedule']->getTimestamp();
                if ($diffend >= 0 && $diffstart <= 0) {
                    $slotid = $pattr->getTimeslot();
                    $match['timeslot'] = $timeslots[$slotid];
                    $matchList[] = $match;
                    break;
                }
            }
            if (!$slotid) {
                $match['timeslot'] = $timeslots[array_rand($timeslots)];
                $matchList[] = $match;
            }
        }
        usort($matchList, function ($match1, $match2) {
            $p1 = $match2['timeslot']->getId() - $match1['timeslot']->getId();
            $p2 = $match2['playground']['no'] - $match1['playground']['no'];
            $p3 = $match2['schedule']->getTimestamp() - $match1['schedule']->getTimestamp();
            $p4 = 0;
            if ($p1 == 0 && $p2 == 0 && $p3 == 0 && $p4 == 0) {
                return 0;
            } elseif ($p1 < 0 || ($p1 == 0 && $p2 < 0) || ($p1 == 0 && $p2 == 0 && $p3 < 0) || ($p1 == 0 && $p2 == 0 && $p3 == 0 && $p4 < 0)) {
                return 1;
            } else {
                return -1;
            }
        });

        return array(
            'host' => $host,
            'tournament' => $tournament,
            'dates' => $eventdates,
            'matchdate' => $matchDate,
            'matchlist' => $matchList
        );
    }

    /**
     * Map any database object with its id
     * @param array $records List of objects to map
     * @return array A list of objects mapped with object ids (id => object)
     */
    private function map($records) {
        $recordList = array();
        foreach ($records as $record) {
            $recordList[$record->getId()] = $record;
        }
        return $recordList;
    }
}

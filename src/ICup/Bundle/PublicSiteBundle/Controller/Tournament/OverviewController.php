<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Tournament;

use ICup\Bundle\PublicSiteBundle\Services\Util;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ICup\Bundle\PublicSiteBundle\Controller\User\SelectClubController;
use Symfony\Component\HttpFoundation\Request;
use DateTime;

class OverviewController extends Controller
{
    /**
     * @Route("/tmnt/vw/{tournament}", name="_tournament_overview")
     * @Template("ICupPublicSiteBundle:Tournament:overview.html.twig")
     */
    public function overviewAction($tournament)
    {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $utilService->setTournamentKey($tournament);
        $tournament = $utilService->getTournament();
        if ($tournament == null) {
            return $this->redirect($this->generateUrl('_tournament_select'));
        }
        
        $today = new DateTime();
        $matchDate = $this->get('match')->getMatchDate($tournament->getId(), $today);
        $timeslots = $this->map($this->get('logic')->listTimeslots($tournament->getId()));
        $pattrs = $this->get('logic')->listPlaygroundAttributesByTournament($tournament->getId());
        $pattrList = array();
        /* @var $pattr PlaygroundAttribute */
        foreach ($pattrs as $pattr) {
            $pattrList[$pattr->getPid()][] = $pattr;
        }

        $matches = $this->get('match')->listMatchesByDate($tournament->getId(), $matchDate);
        $matchList = array();
        foreach ($matches as $match) {
            $slotid = 0;
            foreach ($pattrList[$match['playground']['id']] as $pattr) {
                /* @var $diffstart DateInterval */
                $diffstart = $pattr->getStartSchedule()->getTimestamp() - $match['schedule']->getTimestamp();
                /* @var $diffend DateInterval */
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

        $newsStream = array(
/*            
            array(
                'date' => time(),
                'text' => 'TEKNOELETTRONICA TERAMO disqualified due to use of players without license.',
                'path' => $this->generateUrl('_tournament_overview', array('tournament' => $tournament->getKey()))
            ),
            array(
                'date' => time(),
                'text' => 'Dimitri Populos, SPE STROVOLOU, male U18 received red card for improper act to game officials.',
                'path' => $this->generateUrl('_tournament_overview', array('tournament' => $tournament->getKey()))
            )
*/
        );
        $teaserList = array(
            array(
                'titletext' => 'FORM.TEASER.TOURNAMENT.GROUPS.TITLE',
                'text' => 'FORM.TEASER.TOURNAMENT.GROUPS.DESC',
                'path' => $this->generateUrl('_tournament_categories', array('tournament' => $tournament->getKey()))
            ),
            array(
                'titletext' => 'FORM.TEASER.TOURNAMENT.PLAYGROUNDS.TITLE',
                'text' => 'FORM.TEASER.TOURNAMENT.PLAYGROUNDS.DESC',
                'path' => $this->generateUrl('_tournament_playgrounds', array('tournament' => $tournament->getKey()))
            ),
            array(
                'titletext' => 'FORM.TEASER.TOURNAMENT.TEAMS.TITLE',
                'text' => 'FORM.TEASER.TOURNAMENT.TEAMS.DESC',
                'path' => $this->generateUrl('_tournament_clubs', array('tournament' => $tournament->getKey()))
            ),
            array(
                'titletext' => 'FORM.TEASER.TOURNAMENT.WINNERS.TITLE',
                'text' => 'FORM.TEASER.TOURNAMENT.WINNERS.DESC',
                'path' => $this->generateUrl('_tournament_winners', array('tournament' => $tournament->getKey()))
            ),
            array(
                'titletext' => 'FORM.TEASER.TOURNAMENT.STATISTICS.TITLE',
                'text' => 'FORM.TEASER.TOURNAMENT.STATISTICS.DESC',
                'path' => $this->generateUrl('_tournament_statistics', array('tournament' => $tournament->getKey()))
            )
        );

        $host = $this->get('entity')->getHostById($tournament->getPid());
        return array(
            'host' => $host,
            'tournament' => $tournament,
            'matchdate' => $matchDate,
            'newsstream' => $newsStream,
            'matchlist' => $matchList,
            'teaserlist' => $teaserList
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

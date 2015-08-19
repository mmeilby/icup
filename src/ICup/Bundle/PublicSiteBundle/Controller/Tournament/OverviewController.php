<?php
namespace ICup\Bundle\PublicSiteBundle\Controller\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\News;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\PlaygroundAttribute;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;
use ICup\Bundle\PublicSiteBundle\Services\Util;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ICup\Bundle\PublicSiteBundle\Controller\User\SelectClubController;
use Symfony\Component\HttpFoundation\Request;
use DateTime;
use DateInterval;

class OverviewController extends Controller
{
    /**
     * @Route("/tmnt/vw/{tournament}", name="_tournament_overview")
     * @Template("ICupPublicSiteBundle:Tournament:overview.html.twig")
     */
    public function overviewAction($tournament, Request $request) {
        return $this->getOverviewResponse($tournament, new DateTime(), $request);
    }

    /**
     * @Route("/tmnt/vw/{tournament}/{date}", name="_tournament_overview_date")
     * @Template("ICupPublicSiteBundle:Tournament:overview.html.twig")
     */
    public function overviewDateAction($tournament, $date, Request $request) {
        $matchDate = DateTime::createFromFormat('d-m-Y', $date);
        if ($matchDate == null) {
            throw new ValidationException("INVALIDDATE", "Match date invalid: date=".$date);
        }
        return $this->getOverviewResponse($tournament, $matchDate, $request);
    }

    private function getOverviewResponse($tournament, $date, Request $request)
    {
        /* @var $utilService Util */
        $utilService = $this->get('util');
        $utilService->setTournamentKey($tournament);
        $tournament = $utilService->getTournament();
        if ($tournament == null) {
            return $this->redirect($this->generateUrl('_tournament_select'));
        }

        /* @var $matchDate DateTime */
        $matchDate = $this->get('match')->getMatchDate($tournament->getId(), $date);
        $timeslots = $this->map($this->get('logic')->listTimeslots($tournament->getId()));
        $pattrs = $this->get('logic')->listPlaygroundAttributesByTournament($tournament->getId());
        $pattrList = array();
        /* @var $pattr PlaygroundAttribute */
        foreach ($pattrs as $pattr) {
            $pattrList[$pattr->getPlayground()->getId()][] = $pattr;
        }

        $newsStream = $this->get('tmnt')->listNewsByTournament($tournament->getId());
        $newsRef = array();
        $newsRefTeam = array();
        $newsGeneral = array();
        $today = new DateTime();
        foreach ($newsStream as $news) {
            $news['newsdate'] = Date::getDateTime($news['date']);
            if ($news['id'] > 0) {
                $newsRefTeam[$news['id']][$news['newsno']][$news['language']] = $news;
                continue;
            }
            if ($news['mid'] > 0) {
                $newsRef[$news['mid']][$news['newsno']][$news['language']] = $news;
                continue;
            }
            /* @var $diff \DateInterval */
            $diff = $today->diff($news['newsdate']);
            if ($news['newstype'] == News::$TYPE_PERMANENT || $diff->days < 2) {
                $newsGeneral[$news['newsno']][$news['language']] = $news;
            }
        }

        $matches = $this->get('match')->listMatchesByDate($tournament->getId(), $matchDate);
        $matchList = array();
        foreach ($matches as $match) {
            $matchNews = array();
            if (array_key_exists($match['id'], $newsRef)) {
                $matchNews = array_merge($matchNews, $this->getNews($newsRef[$match['id']], $request));
            }
            if (array_key_exists($match['home']['id'], $newsRefTeam)) {
                $matchNews = array_merge($matchNews, $this->getNews($newsRefTeam[$match['home']['id']], $request));
            }
            if (array_key_exists($match['away']['id'], $newsRefTeam)) {
                $matchNews = array_merge($matchNews, $this->getNews($newsRefTeam[$match['away']['id']], $request));
            }
            $match['news'] = $matchNews;
            $slotid = 0;
            foreach ($pattrList[$match['playground']['id']] as $pattr) {
                $diffstart = $pattr->getStartSchedule()->getTimestamp() - $match['schedule']->getTimestamp();
                $diffend = $pattr->getEndSchedule()->getTimestamp() - $match['schedule']->getTimestamp();
                if ($diffend >= 0 && $diffstart <= 0) {
                    $match['timeslot'] = $pattr->getTimeslot();
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
        $thisDate = DateTime::createFromFormat(DateTime::ATOM, $matchDate->format(DateTime::ATOM));
        $nextDate = $thisDate->add(new DateInterval("P1D"));
        $nextMatchDate = $this->get('match')->getMatchDate($tournament->getId(), $nextDate);
        /* @var $diff DateInterval */
        $diff = $nextMatchDate->diff($matchDate);
        if ($diff->days > 0) {
            array_unshift($teaserList,
                array(
                    'titletext' => 'FORM.TEASER.TOURNAMENT.MOREMATCHES.TITLE',
                    'text' => 'FORM.TEASER.TOURNAMENT.MOREMATCHES.DESC',
                    'path' => $this->generateUrl('_tournament_overview_date', array('tournament' => $tournament->getKey(), 'date' => date_format($nextMatchDate, "d-m-Y")))
                )
            );
        }
        $host = $this->get('entity')->getHostById($tournament->getPid());
        return array(
            'host' => $host,
            'tournament' => $tournament,
            'matchdate' => $matchDate,
            'newsstream' => $newsStream,
            'newsgeneral' => $this->getNews($newsGeneral, $request),
            'matchlist' => $matchList,
            'teaserlist' => $teaserList
        );
    }

    private function getNews($newsList, Request $request) {
        $locale = $request->getLocale();
        $defaultLocale = $request->getDefaultLocale();
        $newsForLocale = array();
        foreach ($newsList as $news) {
            if (array_key_exists($locale, $news)) {
                $newsForLocale[] = $news[$locale];
            }
            elseif (array_key_exists($defaultLocale, $news)) {
                $newsForLocale[] = $news[$defaultLocale];
            }
        }
        return $newsForLocale;
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

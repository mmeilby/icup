<?php

namespace ICup\Bundle\PublicSiteBundle\Tests\Controller;

use Doctrine\ORM\EntityManager;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\GroupOrder;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Playground;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\PlaygroundAttribute;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Site;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Timeslot;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\TournamentOption;
use ICup\Bundle\PublicSiteBundle\Services\Entity\PlaygroundAttribute as PA;
use ICup\Bundle\PublicSiteBundle\Entity\MatchPlan;
use ICup\Bundle\PublicSiteBundle\Entity\QMatchPlan;
use ICup\Bundle\PublicSiteBundle\Services\Entity\PlanningOptions;
use ICup\Bundle\PublicSiteBundle\Services\Entity\QRelation;
use ICup\Bundle\PublicSiteBundle\Tests\Services\TestSupport;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use DateTime;
use DateInterval;

class MatchPlanningTest extends WebTestCase
{
    /* @var $tournament Tournament */
    private $tournament;
    /* @var $container ContainerInterface */
    private $container;

    public static function setUpBeforeClass() {
    }

    protected function setUp() {
        $client = static::createClient();
        $this->container = $client->getContainer();
        /* @var $ts TestSupport */
        $ts = $this->container->get("test");
        $ts->createDatabase();
        $tournament = $ts->makeTournament();
        $ts->makeCategories($tournament);
        $ts->makeGroups($tournament);
        $ts->makeTeams($tournament);
        $ts->makePlaygrounds($tournament);
        $this->tournament = $tournament;
    }

    public function testDB() {
        $playgrounds = $this->tournament->getPlaygrounds();
        $this->assertEquals(4, count($playgrounds));
    }

    public function testMatchPlanning() {
        $options = new PlanningOptions();
        $options->setDoublematch(false);
        $options->setPreferpg(false);
        $this->container->get("planning")->planTournamentPre($this->tournament, $options);
        $match_schedule = $this->container->get("planning")->getSchedule($this->tournament);

        $teams = array();
        /* @var $match MatchPlan */
        foreach ($match_schedule["matches"] as $match) {
            $teams[$match->getTeamA()->getId()][] = $match;
            $teams[$match->getTeamB()->getId()][] = $match;
        }
        foreach ($teams as $team_matches) {
            usort($team_matches, function (MatchPlan $m1, MatchPlan $m2) {
                /* @var $diff DateInterval */
                $diff = $m1->getSchedule()->diff($m2->getSchedule());
                if ($diff->d == 0 && $diff->h == 0 && $diff->i == 0) {
                    return 0;
                } else {
                    return $diff->invert === 1 ? -1 : 1;
                }
            });
        }
        foreach ($teams as $team_matches) {
            /* @var $schedule DateTime */
            $schedule = null;
            /* @var $match MatchPlan */
            foreach ($team_matches as $match) {
                echo $match->getDate();
                echo "  ";
                echo $match->getTime();
                echo "  ";
                echo $match->getCategory()->getName() . "|" . $match->getGroup()->getName() . ":" . $match->getPlayground()->getName();
                echo "  ";
                echo $match->getTeamA();
                echo " - ";
                echo $match->getTeamB();
                echo "\n";
                $this->assertNotEquals($match->getTeamA()->getId(), $match->getTeamB()->getId());
                if ($schedule) {
                    /* @var $diff DateInterval */
                    $diff = $schedule->diff($match->getSchedule());
                    $rest = $match->getCategory()->getMatchtime()+$match->getPlaygroundAttribute()->getTimeslot()->getRestperiod();
                    $this->assertTrue(
                        $diff->d*24*60 + $diff->h*60 + $diff->i >= $rest,
                        "Time between matches is less than ".$rest." min - actual time is ".($diff->d*24*60 + $diff->h*60 + $diff->i)." min.\n".
                        "Match does not respect rest time: ".$match->getDate()."  ".$match->getTime()."  ".$match->getTeamA()." - ".$match->getTeamB()
                    );
                }
                $schedule = $match->getSchedule();
            }
            echo "\n";
        }

        $this->assertCount(0, $match_schedule["unassigned"], "Not all preliminary matches have been planned.");
        /*
                foreach ($match_schedule["unassigned"] as $match) {
                    $teams[$match->getTeamA()->getId()][] = $match;
                    $teams[$match->getTeamB()->getId()][] = $match;
                }
        */

        foreach ($teams as $team_matches) {
            $this->assertCount(5, $team_matches, "Not all matches have been set up for each team.");
        }
    }

    public function testQMatchPlanning() {
        $options = new PlanningOptions();
        $options->setDoublematch(false);
        $options->setPreferpg(false);
        $this->container->get("planning")->planTournamentFinals($this->tournament, $options);
        $match_schedule = $this->container->get("planning")->getSchedule($this->tournament);

        $groups = array();
        /* @var $match QMatchPlan */
        foreach ($match_schedule["matches"] as $match) {
            $cl = $match->getClassification().':'.$match->getLitra();
            if (!isset($groups[$cl])) {
                $groups[$cl] = $match;
            }
            else {
                // For playoff matches save the latest match to verify the time line
                if ($groups[$cl]->getSchedule() < $match->getSchedule()) {
                    $groups[$cl] = $match;
                }
                // Playoff matches must be played at the same venue
                $this->assertEquals($groups[$cl]->getPlayground()->getId(), $match->getPlayground()->getId(), "Play off matches must be played on the same venue");
            }
        }

        /* Test for proper time line */

        foreach ($match_schedule["matches"] as $match) {
            if ($match->getRelA()->getClassification() > Group::$PRE) {
                $cla = $match->getRelA()->getClassification().':'.$match->getRelA()->getLitra().$match->getRelA()->getBranch();
                $this->assertArrayHasKey($cla, $groups, "No entry for ".$cla." has been added");
                /* @var $qma QMatchPlan */
                $qma = $groups[$cla];
                /* @var $diff DateInterval */
                $diff = $match->getSchedule()->diff($qma->getSchedule());
                $rest = $match->getCategory()->getMatchtime()+$match->getPlaygroundAttribute()->getTimeslot()->getRestperiod();
                $this->assertTrue(
                    $diff->d*24*60 + $diff->h*60 + $diff->i >= $rest,
                    "Time between matches is less than ".$rest." min - actual time is ".($diff->d*24*60 + $diff->h*60 + $diff->i)." min.\n".
                    "Match does not respect rest time: ".$match->getDate()."  ".$match->getTime()."  ".$match->getRelA()." - ".$match->getRelB()
                );
            }
            if ($match->getRelB()->getClassification() > Group::$PRE) {
                $clb = $match->getRelB()->getClassification() . ':' . $match->getRelB()->getLitra() . $match->getRelB()->getBranch();
                $this->assertArrayHasKey($clb, $groups, "No entry for ".$clb." has been added");
                /* @var $qmb QMatchPlan */
                $qmb = $groups[$clb];
                /* @var $diff DateInterval */
                $diff = $match->getSchedule()->diff($qmb->getSchedule());
                $rest = $match->getCategory()->getMatchtime()+$match->getPlaygroundAttribute()->getTimeslot()->getRestperiod();
                $this->assertTrue(
                    $diff->d*24*60 + $diff->h*60 + $diff->i >= $rest,
                    "Time between matches is less than ".$rest." min - actual time is ".($diff->d*24*60 + $diff->h*60 + $diff->i)." min.\n".
                    "Match does not respect rest time: ".$match->getDate()."  ".$match->getTime()."  ".$match->getRelA()." - ".$match->getRelB()
                );
            }
        }

        foreach ($match_schedule["matches"] as $match) {
            if ($match->getClassification() >= Group::$BRONZE) {
                $this->printMatch($match);
                $this->digMatch($match->getRelA(), $groups, 1);
                $this->digMatch($match->getRelB(), $groups, 1);
                echo "\n";
            }
        }

        $this->assertCount(0, $match_schedule["unassigned"], "Not all eliminating matches have been planned.");
    }

    private function digMatch(QRelation $rel, $groups, $level) {
        if ($rel->getClassification() > Group::$PRE) {
            $cla = $rel->getClassification() . ':' . $rel->getLitra() . $rel->getBranch();
            $this->assertArrayHasKey($cla, $groups, "No entry for " . $cla . " has been added");
            /* @var $match QMatchPlan */
            $match = $groups[$cla];
            $this->printMatch($match, $level);
            $this->digMatch($match->getRelA(), $groups, $level+1);
            $this->digMatch($match->getRelB(), $groups, $level+1);
        }
    }

    private function printMatch(QMatchPlan $match, $level = 0) {
        echo $match->getDate();
        echo "  ";
        echo $match->getTime();
        echo str_repeat(" ", $level*4+1);
        echo $match->getCategory()->getName() . "|" . $match->getClassification().":".$match->getLitra() . "|" . $match->getPlayground()->getName();
        echo "  ";
        echo $match->getRelA();
        echo " - ";
        echo $match->getRelB();
        echo "\n";
    }
}

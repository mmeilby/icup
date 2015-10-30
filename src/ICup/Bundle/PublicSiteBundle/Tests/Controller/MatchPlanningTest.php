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
use ICup\Bundle\PublicSiteBundle\Tests\Services\TestSupport;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
        $this->assertTrue(count($playgrounds) === 4);
        /* @var $playground Playground */
        foreach ($playgrounds as $playground) {
            $this->assertTrue($playground->getPlaygroundAttributes()->count() === 8);
        }
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
            $schedule = null;
            /* @var $match MatchPlan */
            foreach ($team_matches as $match) {
                $this->assertNotEquals($match->getTeamA()->getId(), $match->getTeamB()->getId());
                if ($schedule) {
                    /* @var $diff DateInterval */
                    $diff = $schedule->diff($match->getSchedule());
                    $this->assertTrue($diff->d*24*60 + $diff->h*60 + $diff->i >= $match->getCategory()->getMatchtime()*2);
                }
                $schedule = $match->getSchedule();
            }
        }
        foreach ($match_schedule["unassigned"] as $match) {
            $teams[$match->getTeamA()->getId()][] = $match;
            $teams[$match->getTeamB()->getId()][] = $match;
        }
        foreach ($teams as $team_matches) {
            $this->assertCount(5, $team_matches);
        }
    }

    public function testQMatchPlanning() {
        $options = new PlanningOptions();
        $options->setDoublematch(false);
        $options->setPreferpg(false);
        $this->container->get("planning")->planTournamentFinals($this->tournament, $options);
        $match_schedule = $this->container->get("planning")->getSchedule($this->tournament);
        /* @var $match QMatchPlan */
        foreach ($match_schedule["matches"] as $match) {
            echo $match->getDate();
            echo "  ";
            echo $match->getTime();
            echo "  ";
            echo $match->getCategory()->getName() . "|" . $match->getClassification().":".$match->getLitra() . "|" . $match->getPlayground()->getName();
            echo "  ";
            echo $match->getRelA();
            echo " - ";
            echo $match->getRelB();
            echo "\n";
        }
    }

    public function tstMatchPlanningOutput() {
        $options = new PlanningOptions();
        $options->setDoublematch(false);
        $options->setPreferpg(false);
        $this->container->get("planning")->planTournamentPre($this->tournament, $options);
        $match_schedule = $this->container->get("planning")->getSchedule($this->tournament);
        /* @var $match MatchPlan */
        foreach ($match_schedule["matches"] as $match) {
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
        }
        $teams = array();
        /* @var $match MatchPlan */
        foreach ($match_schedule["matches"] as $match) {
            $teams[$match->getTeamA()->getId()][] = $match;
            $teams[$match->getTeamB()->getId()][] = $match;
        }
/*
        foreach ($match_schedule["unassigned"] as $match) {
            $teams[$match->getTeamA()->getId()][] = $match;
            $teams[$match->getTeamB()->getId()][] = $match;
        }
*/
        foreach ($teams as $team_matches) {
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
            }
            echo "\n";
        }
    }
}

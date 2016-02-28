<?php

namespace ICup\Bundle\PublicSiteBundle\Tests\Controller;

use Doctrine\ORM\EntityManager;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\GroupOrder;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Playground;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\PlaygroundAttribute;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Site;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Timeslot;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\TournamentOption;
use ICup\Bundle\PublicSiteBundle\Services\Doctrine\MatchSupport;
use ICup\Bundle\PublicSiteBundle\Services\Entity\PlaygroundAttribute as PA;
use ICup\Bundle\PublicSiteBundle\Entity\MatchPlan;
use ICup\Bundle\PublicSiteBundle\Entity\QMatchPlan;
use ICup\Bundle\PublicSiteBundle\Services\Entity\PlanningOptions;
use ICup\Bundle\PublicSiteBundle\Services\Entity\QRelation;
use ICup\Bundle\PublicSiteBundle\Tests\Services\TestSupport;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use DateInterval;

class MatchPlanningTest extends WebTestCase
{
    /* @var $tournament Tournament */
    private $tournament = null;
    /* @var $container ContainerInterface */
    private $container;

    public static function setUpBeforeClass() {
    }

    protected function setUp() {
        if ($this->tournament == null) {
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
            $options = new PlanningOptions();
            $options->setDoublematch(false);
            $options->setPreferpg(false);
            $this->container->get("planning")->planTournament($tournament, $options);
            $this->container->get("planning")->publishSchedule($tournament);
            $this->tournament = $tournament;
        }
    }

    public function testDB() {
        $matches = $this->tournament->getMatches();
        $this->assertEquals(113, count($matches));
    }

    public function testListQualifiedTeamsByTournament() {
        $testtarget = 0;
        foreach ($this->tournament->getMatches() as $match) {
            /* @var $match Match */
            $rel = array();
            foreach ($match->getMatchRelations() as $matchRelation) {
                /* @var $matchRelation MatchRelation */
                if (!$matchRelation->getScorevalid()) {
                    $matchRelation->setScore($matchRelation->getId()%23);
                    $matchRelation->setScorevalid(true);
                }
                $rel[$matchRelation->getAwayteam()] = $matchRelation;
            }
            if ($match->getMatchRelations()->count() == 2) {
                $this->container->get("match")->updatePoints($this->tournament, $rel[MatchSupport::$HOME], $rel[MatchSupport::$AWAY]);
            }
            if ($match->getQMatchRelations()->count() == 2) {
                if ($match->getQMatchRelations()->first()->getGroup()->getClassification() == Group::$PRE &&
                    $match->getQMatchRelations()->last()->getGroup()->getClassification() == Group::$PRE) {
                    $testtarget++;
                }
            }
        }
        $matches = $this->container->get("tmnt")->listQualifiedTeamsByTournament($this->tournament);
        $this->assertEquals($testtarget, count($matches));
    }
}

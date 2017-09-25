<?php

namespace APIBundle\Tests\Controller;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Services\Entity\PlanningOptions;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class ReportMatchTest extends WebTestCase
{
    public static function setUpBeforeClass() {
    }

    /* @var $client Client */
    private $client;
    /* @var $tournament Tournament */
    private $tournament;

    private $auth;

    protected function setUp() {
        $this->client = static::createClient();
        $container = static::$kernel->getContainer();
        $ts = $container->get("test");

        $this->auth = "Basic ".base64_encode($ts->adminEmail . ':' . $ts->apikey);

        $ts->createDatabase();
        $tournament = $ts->makeTournament();
        $ts->makeCategories($tournament);
        $ts->makeGroups($tournament);
        $ts->makeTeams($tournament);
        $ts->makePlaygrounds($tournament);
        $this->tournament = $tournament;
    }

    protected function getCrawler($uri, $entity = "", $key = "", $event = "", $home_score = "", $away_score = "") {
        return $this->client->request('POST', $uri,
            array(), array(),
            array(
                "HTTP_AUTHORIZATION" => $this->auth,
                "HTTPS" => true,
                "CONTENT_TYPE" => "application/json"
            ),
            json_encode(array(
                "entity" => $entity,
                "key" => $key,
                "event" => $event,
                "home_score" => $home_score,
                "away_score" => $away_score
            ))
        );
    }

    public function testMatchPlayed() {
        $options = new PlanningOptions();
        $options->setDoublematch(false);
        $options->setPreferpg(false);
        $this->client->getContainer()->get("planning")->planTournament($this->tournament, $options);
        $this->client->getContainer()->get("planning")->publishSchedule($this->tournament);
        $matches = $this->tournament->getMatches();
        $match = $matches[0];
        /* @var $match Match */
        $match->setKey("1234");
        $this->getCrawler("/service/api/v1/report", "Match", $match->getKey(), "mp", 10, 20);
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $matches = $this->tournament->getMatches();
        $match = $matches[0];
        foreach ($match->getMatchRelations() as $rel) {
            /* @var $rel MatchRelation */
            $this->assertTrue($rel->getScorevalid());
            $this->assertTrue($rel->getScore() == ($rel->getAwayteam() ? 20 : 10));
            $this->assertTrue($rel->getPoints() == ($rel->getAwayteam() ? $this->tournament->getOption()->getWpoints() : $this->tournament->getOption()->getLpoints()));
        }
    }

    public function testMatchHomeDisq() {
        $options = new PlanningOptions();
        $options->setDoublematch(false);
        $options->setPreferpg(false);
        $this->client->getContainer()->get("planning")->planTournament($this->tournament, $options);
        $this->client->getContainer()->get("planning")->publishSchedule($this->tournament);
        $matches = $this->tournament->getMatches();
        $match = $matches[0];
        /* @var $match Match */
        $match->setKey("1234");
        $this->getCrawler("/service/api/v1/report", "Match", $match->getKey(), "hd");
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $matches = $this->tournament->getMatches();
        $match = $matches[0];
        foreach ($match->getMatchRelations() as $rel) {
            /* @var $rel MatchRelation */
            $this->assertTrue($rel->getScorevalid());
            $this->assertTrue($rel->getScore() == ($rel->getAwayteam() ? $this->tournament->getOption()->getDscore() : 0));
            $this->assertTrue($rel->getPoints() == ($rel->getAwayteam() ? $this->tournament->getOption()->getWpoints() : $this->tournament->getOption()->getLpoints()));
        }
    }

    public function testMatchAwayDisq() {
        $options = new PlanningOptions();
        $options->setDoublematch(false);
        $options->setPreferpg(false);
        $this->client->getContainer()->get("planning")->planTournament($this->tournament, $options);
        $this->client->getContainer()->get("planning")->publishSchedule($this->tournament);
        $matches = $this->tournament->getMatches();
        $match = $matches[0];
        /* @var $match Match */
        $match->setKey("1234");
        $this->getCrawler("/service/api/v1/report", "Match", $match->getKey(), "ad");
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $matches = $this->tournament->getMatches();
        $match = $matches[0];
        foreach ($match->getMatchRelations() as $rel) {
            /* @var $rel MatchRelation */
            $this->assertTrue($rel->getScorevalid());
            $this->assertTrue($rel->getScore() == ($rel->getAwayteam() ? 0 : $this->tournament->getOption()->getDscore()));
            $this->assertTrue($rel->getPoints() == ($rel->getAwayteam() ? $this->tournament->getOption()->getLpoints() : $this->tournament->getOption()->getWpoints()));
        }
    }

    public function testMatchNotPlayed() {
        $options = new PlanningOptions();
        $options->setDoublematch(false);
        $options->setPreferpg(false);
        $this->client->getContainer()->get("planning")->planTournament($this->tournament, $options);
        $this->client->getContainer()->get("planning")->publishSchedule($this->tournament);
        $matches = $this->tournament->getMatches();
        $match = $matches[0];
        /* @var $match Match */
        $match->setKey("1234");
        $this->getCrawler("/service/api/v1/report", "Match", $match->getKey(), "np");
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $matches = $this->tournament->getMatches();
        $match = $matches[0];
        foreach ($match->getMatchRelations() as $rel) {
            /* @var $rel MatchRelation */
            $this->assertTrue($rel->getScorevalid());
            $this->assertTrue($rel->getScore() == 0);
            $this->assertTrue($rel->getPoints() == 0);
        }
    }
}

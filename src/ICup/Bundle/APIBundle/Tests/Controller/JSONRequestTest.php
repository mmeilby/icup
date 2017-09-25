<?php

namespace APIBundle\Tests\Controller;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Services\Entity\PlanningOptions;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class JSONRequestTest extends WebTestCase
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

    protected function getCrawler($uri, $entity = "", $key = "", $param = "") {
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
                "param" => $param
            ))
        );
    }

    public function testTournamentGet()
    {
        $this->getCrawler("/service/api/v1/tournament", "Tournament", $this->tournament->getKey());
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $tournament = json_decode($this->client->getResponse()->getContent());
        $this->assertAttributeEquals("Tournament", "entity", $tournament);
        $this->assertAttributeEquals($this->tournament->getKey(), "key", $tournament);
        $this->assertAttributeEquals($this->tournament->getName(), "name", $tournament);
        $this->assertAttributeEquals($this->tournament->getEdition(), "edition", $tournament);
        $this->assertAttributeEquals($this->tournament->getDescription(), "description", $tournament);
        $this->assertObjectHasAttribute("host", $tournament);
        $this->assertAttributeEquals("Host", "entity", $tournament->host);
        $this->assertAttributeEquals($this->tournament->getHost()->getName(), "name", $tournament->host);
    }

    public function testSearch() {
        $this->getCrawler("/service/api/v1/search", "Tournament", $this->tournament->getKey(), "SALA");
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $club_json = json_decode($this->client->getResponse()->getContent());
        foreach ($club_json as $club) {
            $this->assertAttributeEquals("Club", "entity", $club);
            $this->assertAttributeEquals("SALASPILS SS", "name", $club);
        }
    }

    public function testMatchSearch() {
        $options = new PlanningOptions();
        $options->setDoublematch(false);
        $options->setPreferpg(false);
        $this->client->getContainer()->get("planning")->planTournament($this->tournament, $options);
        $this->client->getContainer()->get("planning")->publishSchedule($this->tournament);
        $this->getCrawler("/service/api/v1/match/no", "Tournament", $this->tournament->getKey(), "10");
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        echo $this->client->getResponse()->getContent();
    }
}

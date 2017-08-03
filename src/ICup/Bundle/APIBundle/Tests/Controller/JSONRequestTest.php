<?php

namespace APIBundle\Tests\Controller;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\News;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Playground;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
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

    protected function getCrawler($uri, $entity = "", $key = "") {
        return $this->client->request('POST', $uri,
            array(), array(),
            array(
                "HTTP_AUTHORIZATION" => $this->auth,
                "HTTPS" => true,
                "CONTENT_TYPE" => "application/json"
            ),
            json_encode(array(
                "key" => $key,
                "entity" => $entity
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
}

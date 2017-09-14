<?php

namespace APIBundle\Tests\Controller;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Services\Entity\PlanningOptions;
use ICup\Bundle\PublicSiteBundle\Tests\Services\TestSupport;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class WrapperTest extends WebTestCase
{
    public static function setUpBeforeClass() {
    }

    /* @var $client Client */
    private $client;
    /* @var $tournament Tournament */
    private $tournament;

    private $auth;
    private $auth_invalid_user;
    private $auth_invalid_key;

    protected function setUp() {
        $this->client = static::createClient();
        $container = static::$kernel->getContainer();
        $ts = $container->get("test");

        $this->auth = "Basic ".base64_encode($ts->adminEmail . ':' . $ts->apikey);
        $this->auth_invalid_user = "Basic ".base64_encode("bademail@test.com" . ':' . $ts->apikey);
        $this->auth_invalid_key = "Basic ".base64_encode($ts->adminEmail . ':' . "badkey888");

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
            array(
                "entity" => $entity,
                "key" => $key
            ), array(),
            array(
                "HTTP_AUTHORIZATION" => $this->auth,
                "HTTPS" => true
            )
        );
    }

    public function testFalseEmail() {
        $crawler = $this->client->request('POST', "/service/api/v1/tournament", array("entity" => "Host", "key" => "*"), array(),
            array(
                "HTTP_AUTHORIZATION" => $this->auth_invalid_user,
                "HTTPS" => true
            )
        );
        $this->assertTrue($this->client->getResponse()->isForbidden());
        $error = json_decode($this->client->getResponse()->getContent());
        $this->assertTrue($error->key == "EMAILNVLD");
    }

    public function testFalseKey() {
        $crawler = $this->client->request('POST', "/service/api/v1/tournament", array("entity" => "Host", "key" => "*"), array(),
            array(
                "HTTP_AUTHORIZATION" => $this->auth_invalid_key,
                "HTTPS" => true
            )
        );
        $this->assertTrue($this->client->getResponse()->isForbidden());
        $error = json_decode($this->client->getResponse()->getContent());
        $this->assertTrue($error->key == "APIKNVLD");
    }

    public function testTournamentList()
    {
        $this->getCrawler("/service/api/v1/tournament", "Host", "*");
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $tournaments = json_decode($this->client->getResponse()->getContent());
        $this->assertCount(1, $tournaments);
        $this->assertAttributeNotEmpty("key", reset($tournaments));
    }

    public function testTournamentGet()
    {
        $this->getCrawler("/service/api/v1/tournament", "Tournament", $this->tournament->getKey());
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $tournament = json_decode($this->client->getResponse()->getContent());
        $this->assertAttributeEquals($this->tournament->getKey(), "key", $tournament);
    }

    public function testCategoryList()
    {
        $this->getCrawler("/service/api/v1/category", "Tournament", $this->tournament->getKey());
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $categories = json_decode($this->client->getResponse()->getContent());
        $this->assertCount($this->tournament->getCategories()->count(), $categories);
        $this->assertAttributeNotEmpty("key", reset($categories));
    }

    public function testCategoryGet()
    {
        $this->tournament->getCategories()->first()->setKey(strtoupper(uniqid()));
        $container = static::$kernel->getContainer();
        $container->get('doctrine')->getManager()->flush();
        $this->getCrawler("/service/api/v1/category", "Category", $this->tournament->getCategories()->first()->getKey());
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $category = json_decode($this->client->getResponse()->getContent());
        $this->assertAttributeEquals($this->tournament->getCategories()->first()->getKey(), "key", $category);
    }

    public function testGroupListA()
    {
        $this->getCrawler("/service/api/v1/group", "Tournament", $this->tournament->getKey());
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $groups = json_decode($this->client->getResponse()->getContent());
        $this->assertCount($this->tournament->getCategories()->count(), $groups);
    }

    public function testGroupListB()
    {
        $this->tournament->getCategories()->first()->setKey(strtoupper(uniqid()));
        $container = static::$kernel->getContainer();
        $container->get('doctrine')->getManager()->flush();
        $this->getCrawler("/service/api/v1/group", "Category", $this->tournament->getCategories()->first()->getKey());
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $groups = json_decode($this->client->getResponse()->getContent());
        $this->assertCount($this->tournament->getCategories()->first()->getGroups()->count(), $groups);
        $this->assertAttributeNotEmpty("key", reset($groups));
    }

    public function testGroupGet()
    {
        $this->tournament->getCategories()->first()->getGroups()->first()->setKey(strtoupper(uniqid()));
        $container = static::$kernel->getContainer();
        $container->get('doctrine')->getManager()->flush();
        $this->getCrawler("/service/api/v1/group", "Group", $this->tournament->getCategories()->first()->getGroups()->first()->getKey());
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $group = json_decode($this->client->getResponse()->getContent());
        $this->assertAttributeEquals($this->tournament->getCategories()->first()->getGroups()->first()->getKey(), "key", $group);
    }

    public function testPlaygroundList()
    {
        $this->getCrawler("/service/api/v1/venue", "Tournament", $this->tournament->getKey());
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $venues = json_decode($this->client->getResponse()->getContent());
        $this->assertCount(count($this->tournament->getPlaygrounds()), $venues);
        $this->assertAttributeNotEmpty("key", reset($venues));
    }

    public function testPlaygroundGet()
    {
        reset($this->tournament->getPlaygrounds())->setKey(strtoupper(uniqid()));
        $container = static::$kernel->getContainer();
        $container->get('doctrine')->getManager()->flush();
        $this->getCrawler("/service/api/v1/venue", "Venue", reset($this->tournament->getPlaygrounds())->getKey());
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $venue = json_decode($this->client->getResponse()->getContent());
        $this->assertAttributeEquals(reset($this->tournament->getPlaygrounds())->getKey(), "key", $venue);
    }

    public function testClubList()
    {
        $this->getCrawler("/service/api/v1/club", "Tournament", $this->tournament->getKey());
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $clubs = json_decode($this->client->getResponse()->getContent());
        $this->assertAttributeNotEmpty("key", reset($clubs));
    }

    public function testClubGet()
    {
        $this->tournament->getCategories()->first()->getEnrollments()->first()->getTeam()->getClub()->setKey(strtoupper(uniqid()));
        $container = static::$kernel->getContainer();
        $container->get('doctrine')->getManager()->flush();
        $this->getCrawler("/service/api/v1/club", "Club", $this->tournament->getCategories()->first()->getEnrollments()->first()->getTeam()->getClub()->getKey());
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $club = json_decode($this->client->getResponse()->getContent());
        $this->assertAttributeEquals($this->tournament->getCategories()->first()->getEnrollments()->first()->getTeam()->getClub()->getKey(), "key", $club);
    }

    public function testNewsList()
    {
        $this->getCrawler("/service/api/v1/news", "Tournament", $this->tournament->getKey());
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $news = json_decode($this->client->getResponse()->getContent());
    }

    public function testSiteList()
    {
        $this->getCrawler("/service/api/v1/site", "Tournament", $this->tournament->getKey());
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $sites = json_decode($this->client->getResponse()->getContent());
    }

    public function testTimeslotList()
    {
        $this->getCrawler("/service/api/v1/timeslot", "Tournament", $this->tournament->getKey());
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $timeslots = json_decode($this->client->getResponse()->getContent());
    }

    public function testEnrollment()
    {
        $options = new PlanningOptions();
        $options->setDoublematch(false);
        $options->setPreferpg(false);
        $this->client->getContainer()->get("planning")->planTournament($this->tournament, $options);
        $this->client->getContainer()->get("planning")->publishSchedule($this->tournament);

        $this->getCrawler("/service/api/v1/enrollment", "Tournament", $this->tournament->getKey());
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $enrollments = json_decode($this->client->getResponse()->getContent());
    }

    public function testMatchListA()
    {
        $options = new PlanningOptions();
        $options->setDoublematch(false);
        $options->setPreferpg(false);
        $this->client->getContainer()->get("planning")->planTournament($this->tournament, $options);
        $this->client->getContainer()->get("planning")->publishSchedule($this->tournament);

        $this->getCrawler("/service/api/v1/match", "Tournament", $this->tournament->getKey());
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $matches = json_decode($this->client->getResponse()->getContent());
        $this->assertCount(count($this->tournament->getMatches()), $matches);
        $this->assertAttributeNotEmpty("key", reset($matches));
    }

    public function testMatchListAA()
    {
        $options = new PlanningOptions();
        $options->setDoublematch(false);
        $options->setPreferpg(false);
        $this->client->getContainer()->get("planning")->planTournament($this->tournament, $options);
        $this->client->getContainer()->get("planning")->publishSchedule($this->tournament);

        $this->getCrawler("/service/api/v1/match/today", "Tournament", $this->tournament->getKey());
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $matches = json_decode($this->client->getResponse()->getContent());
        $this->assertTrue(count($matches) > 0);
        $this->assertAttributeNotEmpty("key", reset($matches));
    }

    public function testMatchListAB()
    {
        $options = new PlanningOptions();
        $options->setDoublematch(false);
        $options->setPreferpg(false);
        $this->client->getContainer()->get("planning")->planTournament($this->tournament, $options);
        $this->client->getContainer()->get("planning")->publishSchedule($this->tournament);

        reset($this->tournament->getMatches())->setKey(strtoupper(uniqid()));
        $container = static::$kernel->getContainer();
        $container->get('doctrine')->getManager()->flush();
        $this->getCrawler("/service/api/v1/match/nextday", "Match", reset($this->tournament->getMatches())->getKey());
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $matches = json_decode($this->client->getResponse()->getContent());
        $this->assertTrue(count($matches) > 0);
        $this->assertAttributeNotEmpty("key", reset($matches));
    }

    public function testMatchListAC()
    {
        $options = new PlanningOptions();
        $options->setDoublematch(false);
        $options->setPreferpg(false);
        $this->client->getContainer()->get("planning")->planTournament($this->tournament, $options);
        $this->client->getContainer()->get("planning")->publishSchedule($this->tournament);

        reset($this->tournament->getMatches())->setKey(strtoupper(uniqid()));
        $container = static::$kernel->getContainer();
        $container->get('doctrine')->getManager()->flush();
        $this->getCrawler("/service/api/v1/match/prevday", "Match", reset($this->tournament->getMatches())->getKey());
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $matches = json_decode($this->client->getResponse()->getContent());
        $this->assertTrue(count($matches) > 0);
        $this->assertAttributeNotEmpty("key", reset($matches));
    }

    public function testMatchListB()
    {
        $options = new PlanningOptions();
        $options->setDoublematch(false);
        $options->setPreferpg(false);
        $this->client->getContainer()->get("planning")->planTournament($this->tournament, $options);
        $this->client->getContainer()->get("planning")->publishSchedule($this->tournament);

        $this->tournament->getCategories()->first()->getGroups()->first()->setKey(strtoupper(uniqid()));
        $container = static::$kernel->getContainer();
        $container->get('doctrine')->getManager()->flush();
        $this->getCrawler("/service/api/v1/match", "Group", $this->tournament->getCategories()->first()->getGroups()->first()->getKey());
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $matches = json_decode($this->client->getResponse()->getContent());
        $this->assertCount(count($this->tournament->getCategories()->first()->getGroups()->first()->getMatches()), $matches);
        $this->assertAttributeNotEmpty("key", reset($matches));
    }

    public function testMatchListC()
    {
        $options = new PlanningOptions();
        $options->setDoublematch(false);
        $options->setPreferpg(false);
        $this->client->getContainer()->get("planning")->planTournament($this->tournament, $options);
        $this->client->getContainer()->get("planning")->publishSchedule($this->tournament);

        $playground = reset($this->tournament->getPlaygrounds());
        $playground->setKey(strtoupper(uniqid()));
        $container = static::$kernel->getContainer();
        $container->get('doctrine')->getManager()->flush();
        $this->getCrawler("/service/api/v1/match", "Venue", $playground->getKey());
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $matches = json_decode($this->client->getResponse()->getContent());
        $this->assertCount(count($playground->getMatches()), $matches);
        $this->assertAttributeNotEmpty("key", reset($matches));
    }

    public function testMatchListD()
    {
        $options = new PlanningOptions();
        $options->setDoublematch(false);
        $options->setPreferpg(false);
        $this->client->getContainer()->get("planning")->planTournament($this->tournament, $options);
        $this->client->getContainer()->get("planning")->publishSchedule($this->tournament);

        $this->tournament->getCategories()->first()->setKey(strtoupper(uniqid()));
        $container = static::$kernel->getContainer();
        $container->get('doctrine')->getManager()->flush();
        $this->getCrawler("/service/api/v1/match", "Category", $this->tournament->getCategories()->first()->getKey());
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $matches = json_decode($this->client->getResponse()->getContent());
        $matchesRef = array();
        $this->tournament->getCategories()->first()->getGroups()->forAll(function ($n, Group $group) use (&$matchesRef) {
            $matchesRef = array_merge($matchesRef, $group->getMatches()->toArray());
            return true;
        });
        $this->assertCount(count($matchesRef), $matches);
        $this->assertAttributeNotEmpty("key", reset($matches));
    }

    public function testMatchGet()
    {
        $options = new PlanningOptions();
        $options->setDoublematch(false);
        $options->setPreferpg(false);
        $this->client->getContainer()->get("planning")->planTournament($this->tournament, $options);
        $this->client->getContainer()->get("planning")->publishSchedule($this->tournament);

        reset($this->tournament->getMatches())->setKey(strtoupper(uniqid()));
        $container = static::$kernel->getContainer();
        $container->get('doctrine')->getManager()->flush();
        $this->getCrawler("/service/api/v1/match", "Match", reset($this->tournament->getMatches())->getKey());
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $match = json_decode($this->client->getResponse()->getContent());
        $this->assertAttributeEquals(reset($this->tournament->getMatches())->getKey(), "key", $match);
    }
}

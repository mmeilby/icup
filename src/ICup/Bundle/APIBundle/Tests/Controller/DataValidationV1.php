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

class DataValidationV1 extends WebTestCase
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
            array(
                "key" => $key,
                "entity" => $entity
            ), array(),
            array(
                "HTTP_AUTHORIZATION" => $this->auth,
                "HTTPS" => true
            )
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
        $this->assertObjectHasAttribute("matchcalendar", $tournament);
    }

    public function testCategoryGet()
    {
        /* @var $category Category */
        $category = $this->tournament->getCategories()->first();
        $category->setKey(strtoupper(uniqid()));
        $container = static::$kernel->getContainer();
        $container->get('doctrine')->getManager()->flush();
        $this->getCrawler("/service/api/v1/category", "Category", $category->getKey());
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $category_json = json_decode($this->client->getResponse()->getContent());
        $this->assertAttributeEquals("Category", "entity", $category_json);
        $this->assertAttributeEquals($category->getKey(), "key", $category_json);
        $this->assertAttributeEquals($category->getName(), "name", $category_json);
        $this->assertAttributeEquals($category->getGender(), "gender", $category_json);
        $this->assertAttributeEquals($category->getClassification(), "classification", $category_json);
        $this->assertAttributeEquals($category->getAge(), "age", $category_json);
        $this->assertObjectHasAttribute("tournament", $category_json);
    }

    public function testGroupGet()
    {
        /* @var $group Group */
        $group = $this->tournament->getCategories()->first()->getGroups()->first();
        $group->setKey(strtoupper(uniqid()));
        $container = static::$kernel->getContainer();
        $container->get('doctrine')->getManager()->flush();
        $this->getCrawler("/service/api/v1/group", "Group", $group->getKey());
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $group_json = json_decode($this->client->getResponse()->getContent());
        $this->assertAttributeEquals("Group", "entity", $group_json);
        $this->assertAttributeEquals($group->getKey(), "key", $group_json);
        $this->assertAttributeEquals($group->getName(), "name", $group_json);
        $this->assertAttributeEquals($group->getClassification(), "classification", $group_json);
        $this->assertObjectHasAttribute("category", $group_json);
    }

    public function testPlaygroundGet()
    {
        /* @var $venue Playground */
        $venue = reset($this->tournament->getPlaygrounds());
        $venue->setKey(strtoupper(uniqid()));
        $container = static::$kernel->getContainer();
        $container->get('doctrine')->getManager()->flush();
        $this->getCrawler("/service/api/v1/venue", "Venue", $venue->getKey());
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $venue_json = json_decode($this->client->getResponse()->getContent());
        $this->assertAttributeEquals("Venue", "entity", $venue_json);
        $this->assertAttributeEquals($venue->getKey(), "key", $venue_json);
        $this->assertAttributeEquals($venue->getNo(), "no", $venue_json);
        $this->assertAttributeEquals($venue->getName(), "name", $venue_json);
        $this->assertObjectHasAttribute("location", $venue_json);
        $location = $venue_json->location;
        $this->assertObjectHasAttribute("latitude", $location);
        $this->assertObjectHasAttribute("longitude", $location);
        $this->assertObjectHasAttribute("site", $venue_json);
    }

    public function testClubGet()
    {
        /* @var $club Club */
        $club = $this->tournament->getCategories()->first()->getEnrollments()->first()->getTeam()->getClub();
        $club->setKey(strtoupper(uniqid()));
        $container = static::$kernel->getContainer();
        $container->get('doctrine')->getManager()->flush();
        $this->getCrawler("/service/api/v1/club", "Club", $club->getKey());
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $club_json = json_decode($this->client->getResponse()->getContent());
        $this->assertAttributeEquals("Club", "entity", $club_json);
        $this->assertAttributeEquals($club->getKey(), "key", $club_json);
        $this->assertObjectHasAttribute("name", $club_json);
        $this->assertObjectHasAttribute("address", $club_json);
        $this->assertObjectHasAttribute("city", $club_json);
        $this->assertObjectHasAttribute("country_code", $club_json);
        $this->assertObjectHasAttribute("flag", $club_json);
    }

    public function testNewsList()
    {
        $news_object = new News();
        $news_object->setDate("")->setTitle("")->setContext("")->setLanguage("")->setNewsno("");
        $news_object->setNewstype(News::$TYPE_FRONTPAGE_PERMANENT);
        $this->tournament->getNews()->add($news_object);
        $container = static::$kernel->getContainer();
        $container->get('doctrine')->getManager()->persist($news_object);
        $container->get('doctrine')->getManager()->flush();
        $this->getCrawler("/service/api/v1/news", "Tournament", $this->tournament->getKey());
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $news = json_decode($this->client->getResponse()->getContent());
        $news_json = reset($news);
        $this->assertAttributeEquals("News", "entity", $news_json);
        $this->assertObjectHasAttribute("date", $news_json);
        $this->assertObjectHasAttribute("title", $news_json);
        $this->assertObjectHasAttribute("context", $news_json);
        $this->assertObjectHasAttribute("language", $news_json);
        $this->assertObjectHasAttribute("no", $news_json);
        $this->assertObjectHasAttribute("type", $news_json);
        $this->assertObjectHasAttribute("team", $news_json);
        $this->assertObjectHasAttribute("match", $news_json);
    }

    public function testSiteList()
    {
        $this->getCrawler("/service/api/v1/site", "Tournament", $this->tournament->getKey());
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $sites = json_decode($this->client->getResponse()->getContent());
        $site_json = reset($sites);
        $this->assertAttributeEquals("Site", "entity", $site_json);
        $this->assertObjectHasAttribute("name", $site_json);
        $this->assertObjectHasAttribute("venues", $site_json);
    }

    public function testTimeslotList()
    {
        $this->getCrawler("/service/api/v1/timeslot", "Tournament", $this->tournament->getKey());
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $timeslots = json_decode($this->client->getResponse()->getContent());
        $timeslot_json = reset($timeslots);
        $this->assertAttributeEquals("Timeslot", "entity", $timeslot_json);
        $this->assertObjectHasAttribute("name", $timeslot_json);
    }

    public function testEnrollment()
    {
        $this->getCrawler("/service/api/v1/enrollment", "Tournament", $this->tournament->getKey());
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $enrollments = json_decode($this->client->getResponse()->getContent());
        $nrollment_json = reset($enrollments);
        $this->assertObjectHasAttribute("category", $nrollment_json);
        $this->assertObjectHasAttribute("enrollments", $nrollment_json);
        $this->assertObjectHasAttribute("assignments", $nrollment_json);
        $enrollment = reset($nrollment_json->enrollments);
        $this->assertAttributeEquals("Enrollment", "entity", $enrollment);
        $this->assertObjectHasAttribute("date", $enrollment);
        $this->assertObjectHasAttribute("category", $enrollment);
        $this->assertObjectHasAttribute("team", $enrollment);
        $assignment = reset($nrollment_json->assignments);
        $this->assertObjectHasAttribute("group", $assignment);
        $this->assertObjectHasAttribute("teams", $assignment);
    }

    public function testMatchGet()
    {
        $match_object = new Match();
        $match_object->setDate("")->setTime("")->setMatchno("")->setKey(strtoupper(uniqid()));
        $match_object->setGroup($this->tournament->getCategories()->first()->getGroups()->first());
        $match_object->setPlayground($this->tournament->getSites()->first()->getPlaygrounds()->first());
        $container = static::$kernel->getContainer();
        $container->get('doctrine')->getManager()->persist($match_object);
        $container->get('doctrine')->getManager()->flush();
        $this->getCrawler("/service/api/v1/match", "Match", $match_object->getKey());
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $match_json = json_decode($this->client->getResponse()->getContent());
        $this->assertAttributeEquals("Match", "entity", $match_json);
        $this->assertObjectHasAttribute("key", $match_json);
        $this->assertObjectHasAttribute("matchno", $match_json);
        $this->assertObjectHasAttribute("matchtype", $match_json);
        $this->assertObjectHasAttribute("date", $match_json);
        $this->assertObjectHasAttribute("time", $match_json);
        $this->assertObjectHasAttribute("category", $match_json);
        $this->assertObjectHasAttribute("group", $match_json);
        $this->assertObjectHasAttribute("venue", $match_json);
        $this->assertObjectHasAttribute("home", $match_json);
        $this->assertObjectHasAttribute("away", $match_json);
        $this->assertObjectHasAttribute("qualifiedrelation", $match_json->home);
        $this->assertObjectHasAttribute("matchrelation", $match_json->home);
        $this->assertObjectHasAttribute("qualifiedrelation", $match_json->away);
        $this->assertObjectHasAttribute("matchrelation", $match_json->away);
    }

    public function testResults() {
        $this->getCrawler("/service/api/v1/result", "Tournament", $this->tournament->getKey());
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }
}

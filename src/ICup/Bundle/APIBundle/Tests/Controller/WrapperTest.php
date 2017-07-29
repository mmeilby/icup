<?php

namespace APIBundle\Tests\Controller;

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
/*
        $options = new PlanningOptions();
        $options->setDoublematch(false);
        $options->setPreferpg(false);
        $this->container->get("planning")->planTournament($tournament, $options);
        $this->container->get("planning")->publishSchedule($tournament);
*/
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

    public function testFalseEmail() {
        $crawler = $this->client->request('POST', "/service/api/tournament/", array(), array(),
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
        $crawler = $this->client->request('POST', "/service/api/tournament/", array(), array(),
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
        $this->getCrawler("/service/api/tournament/");
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $tournaments = json_decode($this->client->getResponse()->getContent());
        $this->assertCount(1, $tournaments);
        $this->assertAttributeNotEmpty("key", reset($tournaments));
    }

    public function testTournamentGet()
    {
        $this->getCrawler("/service/api/tournament/", "Tournament", $this->tournament->getKey());
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $tournament = json_decode($this->client->getResponse()->getContent());
        $this->assertAttributeEquals($this->tournament->getKey(), "key", $tournament);
    }

    public function testCategoryList()
    {
        $this->getCrawler("/service/api/category/", "Tournament", $this->tournament->getKey());
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
        $this->getCrawler("/service/api/category/", "Category", $this->tournament->getCategories()->first()->getKey());
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $category = json_decode($this->client->getResponse()->getContent());
        $this->assertAttributeEquals($this->tournament->getCategories()->first()->getKey(), "key", $category);
    }

    public function testGroupListA()
    {
        $this->getCrawler("/service/api/group/", "Tournament", $this->tournament->getKey());
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $groups = json_decode($this->client->getResponse()->getContent());
        $this->assertCount($this->tournament->getCategories()->count(), $groups);
    }

    public function testGroupListB()
    {
        $this->tournament->getCategories()->first()->setKey(strtoupper(uniqid()));
        $container = static::$kernel->getContainer();
        $container->get('doctrine')->getManager()->flush();
        $this->getCrawler("/service/api/group/", "Category", $this->tournament->getCategories()->first()->getKey());
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
        $this->getCrawler("/service/api/group/", "Group", $this->tournament->getCategories()->first()->getGroups()->first()->getKey());
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $group = json_decode($this->client->getResponse()->getContent());
        $this->assertAttributeEquals($this->tournament->getCategories()->first()->getGroups()->first()->getKey(), "key", $group);
    }
}

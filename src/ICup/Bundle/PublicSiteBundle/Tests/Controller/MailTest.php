<?php

namespace ICup\Bundle\PublicSiteBundle\Tests\Controller;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\EnrollmentTeamCheckoutForm;
use ICup\Bundle\PublicSiteBundle\Tests\Services\TestSupport;
use Stripe\Stripe;
use Stripe\Token;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use DateTime;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

ini_set('xdebug.max_nesting_level', 200);

class MailTest extends WebTestCase
{
    /* @var $tournament Tournament */
    private $tournament;
    /* @var $container ContainerInterface */
    private $container;
    /* @var $client Client */
    private $client;
    private $clubs;

    protected function setUp() {
        $this->client = static::createClient();
        // Enable the profiler for the next request (it does nothing if the profiler is not available)
        $this->client->enableProfiler();
        $this->container = $this->client->getContainer();
        /* @var $ts TestSupport */
        $ts = $this->container->get("test");
        $ts->createDatabase();
        $tournament = $ts->makeTournament();
        $ts->makeCategories($tournament);
        $ts->makeGroups($tournament);
        $this->clubs = $ts->makeTeams($tournament);
        $this->tournament = $tournament;
    }

    public function testMailCard() {
        Stripe::setApiKey("sk_test_BQokikJOvBiI2HlWgH4olfQ2");
        $token = Token::create(array(
            "card" => array(
                "number" => "4242424242424242",
                "exp_month" => 3,
                "exp_year" => 2020,
                "cvc" => "314"
            )
        ));
        $idx = 1;
        $enrolled = array();
        /* @var $category Category */
        foreach ($this->tournament->getCategories() as $category) {
            $enrolled[] = array(
                'id' => $category->getId(),
                'quantity' => $idx
            );
            $idx++;
        }
        /* @var $club Club */
        $club = array_shift($this->clubs);
        $crawler = $this->client->request(
            'POST',
            $this->container->get('router')->generate('rest_enroll_team_checkout', array('tournamentid' => $this->tournament->getId()), UrlGeneratorInterface::ABSOLUTE_PATH),
            array(
                'club' => array('name' => $club->getName(), 'country' => $club->getCountry()->getCountry()),
                'manager' => array('name' => 'Manager', 'mobile' => '1234 5678', 'email' => 'manager@test.com'),
                'enrolled' => $enrolled,
                'tx_timestamp' => date("Y-m-d", $token->created),
                'token' => $token->id
            )
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful(), $this->client->getResponse()->getContent());

        $mailCollector = $this->client->getProfile()->getCollector('swiftmailer');

        // Check that an email was sent
        $this->assertEquals(3, $mailCollector->getMessageCount());

        foreach ($mailCollector->getMessages() as $message) {
            // Asserting email data
            $this->assertInstanceOf('Swift_Message', $message);
            /*
                    $this->assertEquals('Hello Email', $message->getSubject());
                    $this->assertEquals('send@example.com', key($message->getFrom()));
                    $this->assertEquals('recipient@example.com', key($message->getTo()));
                    $this->assertEquals(
                        'You should see me from the profiler!',
                        $message->getBody()
                    );
            */
            $dir = $this->container->getParameter('kernel.root_dir')."/logs/";
            $tmpfname = $dir . "testMailCard_".$message->getSubject().".htm";
            $fp = fopen($tmpfname, "w");
            fputs($fp, $message->getBody());
            fclose($fp);
        }
    }

    public function testMailBank() {
        $idx = 1;
        $enrolled = array();
        /* @var $category Category */
        foreach ($this->tournament->getCategories() as $category) {
            $enrolled[] = array(
                'id' => $category->getId(),
                'quantity' => $idx
            );
            $idx++;
        }
        /* @var $club Club */
        $club = array_shift($this->clubs);
        $crawler = $this->client->request(
            'POST',
            $this->container->get('router')->generate('rest_enroll_team_checkout', array('tournamentid' => $this->tournament->getId()), UrlGeneratorInterface::ABSOLUTE_PATH),
            array(
                'club' => array('name' => 'MisteryClub', 'country' => 'DNK'),
                'manager' => array('name' => 'NewCust', 'mobile' => '1234 5678', 'email' => 'newcust@test.com'),
                'enrolled' => $enrolled,
                'tx_timestamp' => date("Y-m-d", time()),
                'token' => ''
            )
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful(), $this->client->getResponse()->getContent());

        $mailCollector = $this->client->getProfile()->getCollector('swiftmailer');

        // Check that an email was sent
        $this->assertEquals(2, $mailCollector->getMessageCount());

        foreach ($mailCollector->getMessages() as $message) {
            // Asserting email data
            $this->assertInstanceOf('Swift_Message', $message);
            /*
                    $this->assertEquals('Hello Email', $message->getSubject());
                    $this->assertEquals('send@example.com', key($message->getFrom()));
                    $this->assertEquals('recipient@example.com', key($message->getTo()));
                    $this->assertEquals(
                        'You should see me from the profiler!',
                        $message->getBody()
                    );
            */
            $dir = $this->container->getParameter('kernel.root_dir')."/logs/";
            $tmpfname = $dir . "testMailBank_".$message->getSubject().".htm";
            $fp = fopen($tmpfname, "w");
            fputs($fp, $message->getBody());
            fclose($fp);
        }
    }
}

<?php

namespace ICup\Bundle\PublicSiteBundle\Tests\Services;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Enrollment;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\GroupOrder;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Host;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\HostPlan;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchAlternative;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchSchedule;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\News;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Playground;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\PlaygroundAttribute;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Timeslot;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Site;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use DateTime;

class TestSupport
{
    /* @var $em EntityManager */
    protected $em;
    /* @var $logger Logger */
    protected $logger;
    // Path to the doctrine entity classes
    protected $doctrinePath;

    public function __construct(EntityManager $em, $path, Logger $logger)
    {
        $this->em = $em;
        $this->doctrinePath = $path;
        $this->logger = $logger;
    }

    /**
     * Get the entity class metadata from the key
     * @param $repository
     * @return \Doctrine\ORM\Mapping\ClassMetadata
     */
    private function getClassMetadata($repository) {
        return $this->em->getClassMetadata($this->doctrinePath.$repository);
    }

    public function createDatabase() {
        $tool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $classes = array(
            $this->getClassMetadata('Host'),
            $this->getClassMetadata('Tournament'),
            $this->getClassMetadata('Category'),
            $this->getClassMetadata('Group'),
            $this->getClassMetadata('Match'),
            $this->getClassMetadata('Team'),
            $this->getClassMetadata('Club'),
            $this->getClassMetadata('Site'),
            $this->getClassMetadata('Playground'),
            $this->getClassMetadata('PlaygroundAttribute'),
            $this->getClassMetadata('Timeslot'),
            $this->getClassMetadata('GroupOrder'),
            $this->getClassMetadata('Enrollment'),
            $this->getClassMetadata('MatchRelation'),
            $this->getClassMetadata('QMatchRelation'),
            $this->getClassMetadata('Event'),
            $this->getClassMetadata('News'),
            $this->getClassMetadata('User'),
            $this->getClassMetadata('Champion'),
            $this->getClassMetadata('HostPlan'),
            $this->getClassMetadata('MatchAlternative'),
            $this->getClassMetadata('MatchSchedule'),
            $this->getClassMetadata('QMatchSchedule'),
            $this->getClassMetadata('MatchSchedulePlan'),
            $this->getClassMetadata('MatchScheduleRelation'),
            $this->getClassMetadata('QMatchScheduleRelation'),
            $this->getClassMetadata('SocialGroup'),
            $this->getClassMetadata('SocialRelation'),
            $this->getClassMetadata('TournamentOption'),
            $this->getClassMetadata('Template'),
        );
        $tool->updateSchema($classes);
    }

    public function makeTournament() {
        $host = new Host();
        $host->setName("Test host");
//        $host->setHostplan(new HostPlan());
        $tournament = new Tournament();
        $tournament->setName("Test tournament");
        $tournament->setDescription("Test edition of a tournament");
        $tournament->setEdition("2015");
        $tournament->setKey("TST2015");
        $tournament->setHost($host);
        $host->getTournaments()->add($tournament);
        $editor = new User();
        $editor->setUsername("test");
        $editor->setName("Test user");
        $editor->setPassword("");
        $editor->setEmail("test@test.com");
        $editor->addRole(User::ROLE_EDITOR_ADMIN);
        $editor->setEnabled(true);
        $editor->setHost($host);
        $host->getUsers()->add($editor);
        $this->em->persist($host);
        $this->em->flush();
        return $tournament;
    }

    public function makeCategories(Tournament $tournament) {
        $category = new Category();
        $category->setName("F");
        $category->setAge("18");
        $category->setGender("F");
        $category->setClassification("U");
        $category->setMatchtime(60);
        $category->setTopteams(3);
        $category->setTrophys(2);
        $category->setStrategy(0);
        $category->setTournament($tournament);
        $tournament->getCategories()->add($category);
        $this->em->persist($category);
        $category = new Category();
        $category->setName("M");
        $category->setAge("18");
        $category->setGender("M");
        $category->setClassification("U");
        $category->setMatchtime(60);
        $category->setTopteams(0);
        $category->setTrophys(4);
        $category->setStrategy(1);
        $category->setTournament($tournament);
        $tournament->getCategories()->add($category);
        $this->em->persist($category);
        $this->em->flush();
    }

    public function makeGroups(Tournament $tournament) {
        /* @var $category Category */
        foreach ($tournament->getCategories() as $category) {
            foreach (array('A', 'B', 'C') as $groupname) {
                $group = new Group();
                $group->setCategory($category);
                $group->setClassification(Group::$PRE);
                $group->setName($groupname);
                $category->getGroups()->add($group);
                $this->em->persist($group);
            }
        }
        $this->em->flush();
    }

    public function makeTeams(Tournament $tournament) {
        $testclubs = array(
            array("YOSLIK TASHKENT", "UZB"), array("B. URDULIZ", "ESP"), array("ATHENS 2015", "GRC"),
            array("N.H.C. TERAMO", "ITA"), array("GLADSAXE HG", "DNK"), array("UKS SPARTACUS", "POL"),
            array("TSINGHUA UNIV.", "CHN"), array("E.C. PINHEIROS", "BRA"), array("ZAGLEBIE LUBIN", "POL"),
            array("ASD FLAVIONI", "ITA"), array("SALASPILS SS", "LVA"), array("JSG ECHAZ ERMS", "DEU"),
            array("BRASIL REAL", "BRA"), array("HC MELITA", "MLT"), array("POGON ZABRIZE", "POL"),
            array("HC DUNAV BELENE", "BGR"), array("DTJ POLANKA", "CZE"), array("XINZHUANG JHS", "CHN"),
            array("ESBF", "FRA"), array("FALK", "NOR"), array("C.C. ANSIAO", "PRT"),
            array("ETIEC MENDOZA", "ARG"), array("VIKINGUR", "ISL"),
        );
        $clubs = array();
        foreach ($testclubs as $clubinfo) {
            $club = new Club();
            $club->setName($clubinfo[0]);
            $club->setCountry($clubinfo[1]);
            $this->em->persist($club);
            $clubs[] = $club;
        }
        /* @var $user User */
        $user = $tournament->getHost()->getEditors()->first();
        /* @var $category Category */
        foreach ($tournament->getCategories() as $category) {
            $clubDiv = array_shift($clubs);
            /* @var $group Group */
            foreach ($category->getGroupsClassified(Group::$PRE) as $group) {
                for ($n=1; $n<=5; $n++) {
                    /* @var $club Club */
                    $club = array_shift($clubs);
                    $this->addTeam($category, $group, $club, "", $user);
                    array_push($clubs, $club);
                }
                $this->addTeam($category, $group, $clubDiv, $group->getName(), $user);
            }
            array_push($clubs, $clubDiv);
        }
        $this->em->flush();
    }

    private function addTeam(Category $category, Group $group, Club $club, $division, User $user) {
        $team = new Team();
        $team->setName($club->getName());
        $team->setColor($category->getName().$group->getName().($group->getGroupOrder()->count()+1));
        $team->setDivision($division);
        $team->setVacant(false);
        $team->setClub($club);
        $club->getTeams()->add($team);
        $this->em->persist($team);
        $grouporder = new GroupOrder();
        $grouporder->setGroup($group);
        $grouporder->setTeam($team);
        $team->getGroupOrder()->add($grouporder);
        $group->getGroupOrder()->add($grouporder);
        $this->em->persist($grouporder);
        $enrollment = new Enrollment();
        $enrollment->setTeam($team);
        $enrollment->setCategory($category);
        $enrollment->setDate(Date::getDate(new DateTime()));
        $enrollment->setUser($user);
        $team->getEnrollments()->add($enrollment);
        $category->getEnrollments()->add($enrollment);
        $user->getEnrollments()->add($enrollment);
        $this->em->persist($enrollment);
    }

    public function makePlaygrounds(Tournament $tournament) {
        $venues = array();
        $playgroundno = 1;
        foreach (array('SiteA', 'SiteB') as $sitename) {
            $site = new Site();
            $site->setName($sitename);
            $site->setTournament($tournament);
            foreach (array("VenueA", "VenueB") as $venue) {
                $playground = new Playground();
                $playground->setName($sitename."-".$venue);
                $playground->setLocation("");
                $playground->setNo($playgroundno++);
                $playground->setSite($site);
                $site->getPlaygrounds()->add($playground);
                $venues[$playground->getNo()] = $playground;
            }
            $tournament->getSites()->add($site);
            $this->em->persist($site);
        }

        $timeslot = new Timeslot();
        $timeslot->setName("Period AM");
        $timeslot->setTournament($tournament);
        $timeslot->setCapacity(2);
        $timeslot->setPenalty(true);
        $timeslot->setRestperiod(60);
        $tournament->getTimeslots()->add($timeslot);
        $this->addSchedules($tournament, $timeslot,
            date_create_from_format("j-n-Y G.i", "5-7-2015 9.00"),
            date_create_from_format("j-n-Y G.i", "5-7-2015 12.00"));
        $this->addSchedules($tournament, $timeslot,
            date_create_from_format("j-n-Y G.i", "6-7-2015 9.00"),
            date_create_from_format("j-n-Y G.i", "6-7-2015 12.00"));
        $this->addSchedules($tournament, $timeslot,
            date_create_from_format("j-n-Y G.i", "7-7-2015 9.00"),
            date_create_from_format("j-n-Y G.i", "7-7-2015 12.00"));
        $this->addSchedulesFinal($timeslot, $venues[1], 7,
            date_create_from_format("j-n-Y G.i", "8-7-2015 9.00"),
            date_create_from_format("j-n-Y G.i", "8-7-2015 12.00"));
        $this->addSchedulesFinal($timeslot, $venues[2], 7,
            date_create_from_format("j-n-Y G.i", "8-7-2015 9.00"),
            date_create_from_format("j-n-Y G.i", "8-7-2015 12.00"));
        $this->addSchedulesFinal($timeslot, $venues[3], 6,
            date_create_from_format("j-n-Y G.i", "8-7-2015 9.00"),
            date_create_from_format("j-n-Y G.i", "8-7-2015 12.00"));
        $this->addSchedulesFinal($timeslot, $venues[4], Group::$PLAYOFF,
            date_create_from_format("j-n-Y G.i", "8-7-2015 9.00"),
            date_create_from_format("j-n-Y G.i", "8-7-2015 12.00"));
        $this->addSchedulesFinal($timeslot, $venues[1], Group::$SEMIFINAL,
            date_create_from_format("j-n-Y G.i", "9-7-2015 9.00"),
            date_create_from_format("j-n-Y G.i", "9-7-2015 12.00"));
        $this->em->persist($timeslot);

        $timeslot = new Timeslot();
        $timeslot->setName("Period PM");
        $timeslot->setTournament($tournament);
        $timeslot->setCapacity(1);
        $timeslot->setPenalty(false);
        $timeslot->setRestperiod(60);
        $tournament->getTimeslots()->add($timeslot);
        $this->addSchedules($tournament, $timeslot,
            date_create_from_format("j-n-Y G.i", "5-7-2015 13.00"),
            date_create_from_format("j-n-Y G.i", "5-7-2015 19.00"));
        $this->addSchedules($tournament, $timeslot,
            date_create_from_format("j-n-Y G.i", "6-7-2015 13.00"),
            date_create_from_format("j-n-Y G.i", "6-7-2015 19.00"));
        $this->addSchedules($tournament, $timeslot,
            date_create_from_format("j-n-Y G.i", "7-7-2015 13.00"),
            date_create_from_format("j-n-Y G.i", "7-7-2015 19.00"));
        $this->addSchedulesFinal($timeslot, $venues[1], 7,
            date_create_from_format("j-n-Y G.i", "8-7-2015 13.00"),
            date_create_from_format("j-n-Y G.i", "8-7-2015 22.00"));
        $this->addSchedulesFinal($timeslot, $venues[2], Group::$SEMIFINAL,
            date_create_from_format("j-n-Y G.i", "8-7-2015 13.00"),
            date_create_from_format("j-n-Y G.i", "8-7-2015 22.00"));
        $this->addSchedulesFinal($timeslot, $venues[3], Group::$SEMIFINAL,
            date_create_from_format("j-n-Y G.i", "8-7-2015 13.00"),
            date_create_from_format("j-n-Y G.i", "8-7-2015 22.00"));
        $this->addSchedulesFinal($timeslot, $venues[4], Group::$SEMIFINAL,
            date_create_from_format("j-n-Y G.i", "8-7-2015 13.00"),
            date_create_from_format("j-n-Y G.i", "8-7-2015 22.00"));
        $this->addSchedulesFinal($timeslot, $venues[1], Group::$FINAL,
            date_create_from_format("j-n-Y G.i", "9-7-2015 13.00"),
            date_create_from_format("j-n-Y G.i", "9-7-2015 22.00"));
        $this->addSchedulesFinal($timeslot, $venues[2], Group::$BRONZE,
            date_create_from_format("j-n-Y G.i", "9-7-2015 13.00"),
            date_create_from_format("j-n-Y G.i", "9-7-2015 22.00"));
        $this->em->persist($timeslot);
        $this->em->flush();
    }

    private function addSchedules(Tournament $tournament, Timeslot $timeslot, DateTime $start, DateTime $end) {
        /* @var $site Site */
        foreach ($tournament->getSites() as $site) {
            /* @var $playground Playground */
            foreach ($site->getPlaygrounds() as $playground) {
                $pattr = new PlaygroundAttribute();
                $pattr->setTimeslot($timeslot);
                $pattr->setPlayground($playground);
                $pattr->setDate(Date::getDate($start));
                $pattr->setStart(Date::getTime($start));
                $pattr->setEnd(Date::getTime($end));
                $pattr->setFinals(false);
                $pattr->setClassification(Group::$PRE);
                $playground->getPlaygroundAttributes()->add($pattr);
                $timeslot->getPlaygroundattributes()->add($pattr);
            }
        }
    }

    private function addSchedulesFinal(Timeslot $timeslot, Playground $playground, $classification, DateTime $start, DateTime $end) {
        $pattr = new PlaygroundAttribute();
        $pattr->setTimeslot($timeslot);
        $pattr->setPlayground($playground);
        $pattr->setDate(Date::getDate($start));
        $pattr->setStart(Date::getTime($start));
        $pattr->setEnd(Date::getTime($end));
        $pattr->setFinals(true);
        $pattr->setClassification($classification);
        $playground->getPlaygroundAttributes()->add($pattr);
        $timeslot->getPlaygroundattributes()->add($pattr);
    }
}

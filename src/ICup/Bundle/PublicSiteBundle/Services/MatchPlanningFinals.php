<?php
namespace ICup\Bundle\PublicSiteBundle\Services;

use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManager;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchAlternative;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchSchedule;
use ICup\Bundle\PublicSiteBundle\Entity\Match;
use ICup\Bundle\PublicSiteBundle\Entity\MatchPlan;
use ICup\Bundle\PublicSiteBundle\Entity\QMatch;
use ICup\Bundle\PublicSiteBundle\Entity\TeamInfo;
use ICup\Bundle\PublicSiteBundle\Entity\TeamStat;
use ICup\Bundle\PublicSiteBundle\Services\Doctrine\BusinessLogic;
use ICup\Bundle\PublicSiteBundle\Services\Doctrine\MatchSupport;
use ICup\Bundle\PublicSiteBundle\Services\Entity\PlanningOptions;
use ICup\Bundle\PublicSiteBundle\Services\Entity\PlanningResults;
use ICup\Bundle\PublicSiteBundle\Services\Entity\PlaygroundAttribute as PA;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\PlaygroundAttribute;
use ICup\Bundle\PublicSiteBundle\Services\Entity\TeamCheck;
use Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MatchPlanningFinals
{
    /* @var $container ContainerInterface */
    protected $container;
    /* @var $logic BusinessLogic */
    protected $logic;
    /* @var $order OrderTeams */
    protected $order;
    /* @var $match MatchSupport */
    protected $match;
    /* @var $em EntityManager */
    protected $em;
    /* @var $logger Logger */
    protected $logger;

    public function __construct(ContainerInterface $container, Logger $logger)
    {
        $this->container = $container;
        $this->logic = $container->get('logic');
        $this->order = $container->get('orderTeams');
        $this->match = $container->get('match');
        $this->em = $container->get('doctrine')->getManager();
        $this->logger = $logger;
    }

    /**
     * Update tournament schedule with qualified teams
     * @param $tournamentid
     * @return array
     */
    public function updateTournamentSchedule($tournamentid) {
        $groups = $this->map($this->logic->listGroupsByTournament($tournamentid));
        $playgrounds = $this->map($this->logic->listPlaygroundsByTournament($tournamentid));
        $settledGroups = array();
        /* @var $group Group */
        foreach ($groups as $group) {
            $settledGroups[$group->getId()] = $this->order->sortCompletedGroup($group->getId());
        }
        $matches = array();
        $matchList = $this->match->listOpenQMatchesByTournament($tournamentid);
        /* @var $qmatch QMatch */
        foreach ($matchList as $qmatch) {
            $sortedGrpA = $settledGroups[$qmatch->getGroupA()];
            $sortedGrpB = $settledGroups[$qmatch->getGroupB()];
            if ($sortedGrpA && $sortedGrpB) {
                $match = new MatchPlan();
                $match->setMatchno($qmatch->getMatchno());
                $match->setDate($qmatch->getDate());
                $match->setTime($qmatch->getTime());
                $match->setGroup($groups[$qmatch->getPid()]);
                $match->setCategory($match->getGroup()->getCategory());
                $match->setPlayground($playgrounds[$qmatch->getPlayground()]);
                $match->setTeamA($this->getTeam($sortedGrpA[$qmatch->getRankA()]));
                $match->setTeamB($this->getTeam($sortedGrpB[$qmatch->getRankB()]));
                $matches[] = array('Q' => $qmatch, 'M' => $match);
            }
            else {
                $matches[] = array('Q' => $qmatch, 'M' => null);
            }
        }
        return $matches;
    }

    /**
     * @param TeamStat $stat
     * @return TeamInfo
     */
    private function getTeam(TeamStat $stat) {
        $team = new TeamInfo();
        $team->setId($stat->getId());
        $team->setClub($stat->getClub());
        $team->setName($stat->getName());
        $team->setCountry($stat->getCountry());
        $team->setGroup($stat->getGroup());
        return $team;
    }

    /**
     * Map any database object with its id
     * @param array $records List of objects to map
     * @return array A list of objects mapped with object ids (id => object)
     */
    private function map($records) {
        $recordList = array();
        foreach ($records as $record) {
            $recordList[$record->getId()] = $record;
        }
        return $recordList;
    }
}

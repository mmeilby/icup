<?php

namespace ICup\Bundle\PublicSiteBundle\Services\Doctrine;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use ICup\Bundle\PublicSiteBundle\Services\Doctrine\Entity;
use ICup\Bundle\PublicSiteBundle\Services\Doctrine\BusinessLogic;
use ICup\Bundle\PublicSiteBundle\Entity\TeamInfo;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Event;

class TournamentSupport
{
    /* @var $container ContainerInterface */
    protected $container;
    /* @var $em EntityManager */
    protected $em;
    /* @var $logger Logger */
    protected $logger;
   /* @var $entity Entity */
    protected $entity;
   /* @var $entity BusinessLogic */
    protected $logic;

    public function __construct(ContainerInterface $container, Logger $logger)
    {
        $this->container = $container;
        $this->entity = $container->get('entity');
        $this->logic = $container->get('logic');
        $this->em = $container->get('doctrine')->getManager();
        $this->logger = $logger;
    }
    
    public function listEventsByTournament($tournamentid) {
        $qb = $this->em->createQuery(
                "select e.id,e.date,e.event ".
                "from ".$this->entity->getRepositoryPath('Event')." e ".
                "where e.pid=:tournament ".
                "order by e.date asc");
        $qb->setParameter('tournament', $tournamentid);
        $eventList = array();
        foreach ($qb->getResult() as $event) {
            $eventdate = date_create_from_format("d/m/Y", $event['date']);
            $event['schedule'] = $eventdate;
            $eventList[] = $event;
        }
        return $eventList;
    }

    public function getEventByEvent($tournamentid, $event) {
         $qb = $this->em->createQuery(
                "select e ".
                "from ".$this->entity->getRepositoryPath('Event')." e ".
                "where e.pid=:tournament and ".
                      "e.event=:event");
        $qb->setParameter('tournament', $tournamentid);
        $qb->setParameter('event', $event);
        return $qb->getOneOrNullResult();
    }
    
    public function isEnrollmentAllowed($tournamentid, $date) {
        $qb = $this->em->createQuery(
                "select e.id,e.date,e.event ".
                "from ".$this->entity->getRepositoryPath('Event')." e ".
                "where e.pid=:tournament and ".
                      "e.event in (1,2) ".
                "order by e.event asc");
        $qb->setParameter('tournament', $tournamentid);
        $status = false;
        foreach ($qb->getResult() as $event) {
            $eventdate = date_create_from_format("d/m/Y", $event['date']);
            if ($event['event'] == Event::$ENROLL_START && $date < $eventdate) {
                $status = false;
                break;
            }
            else {
                $status = true;
            }
            if ($event['event'] == Event::$ENROLL_STOP && $date < $eventdate) {
                $status = true;
            }
            else {
                $status = false;
                break;
            }
        }
        return $status;
    }

    /* Tournament is open for team enrollment */
    public static $TMNT_ENROLL = 1;
    /* Tournament is in progress */
    public static $TMNT_GOING = 2;
    /* Tournament is over - hall of fame is visual */
    public static $TMNT_DONE = 3;
    
    public function getTournamentStatus($tournamentid, $date) {
        $qb = $this->em->createQuery(
                "select e.id,e.date,e.event ".
                "from ".$this->entity->getRepositoryPath('Event')." e ".
                "where e.pid=:tournament and ".
                      "e.event in (3,4) ".
                "order by e.event asc");
        $qb->setParameter('tournament', $tournamentid);
        $status = TournamentSupport::$TMNT_GOING;
        foreach ($qb->getResult() as $event) {
            $eventdate = date_create_from_format("d/m/Y", $event['date']);
            if ($event['event'] == Event::$MATCH_START && $date < $eventdate) {
                $status = TournamentSupport::$TMNT_ENROLL;
            }
            if ($event['event'] == Event::$MATCH_STOP && $date >= $eventdate) {
                $status = TournamentSupport::$TMNT_DONE;
            }
        }
        return $status;
    }

    public function isTournamentArchived($tournamentid, $date) {
        $qb = $this->em->createQuery(
                "select e.id,e.date,e.event ".
                "from ".$this->entity->getRepositoryPath('Event')." e ".
                "where e.pid=:tournament and e.event=9");
        $qb->setParameter('tournament', $tournamentid);
        $event = $qb->getOneOrNullResult();
        if ($event != null) {
            $eventdate = date_create_from_format("d/m/Y", $event['date']);
            if ($date < $eventdate) {
                $status = false;
            }
            else {
                $status = true;
            }
        }
        else {
            $status = false;
        }
        return $status;
    }
    
    public function listPlaygroundsByTournament($tournamentid) {
        $qb = $this->em->createQuery(
                "select p.id,p.name,s.name as site ".
                "from ".$this->entity->getRepositoryPath('Playground')." p, ".
                        $this->entity->getRepositoryPath('Site')." s ".
                "where s.pid=:tournament and ".
                        "p.pid=s.id ".
                "order by p.no asc");
        $qb->setParameter('tournament', $tournamentid);
        return $qb->getResult();
    }

    public function listTeamsByClub($tournamentid, $clubid) {
        $qb = $this->em->createQuery(
                "select t.id, t.name, t.division, c.id as catid, c.name as category, c.classification, c.gender, g.id as groupid, g.name as grp ".
                "from ".$this->entity->getRepositoryPath('Team')." t, ".
                        $this->entity->getRepositoryPath('GroupOrder')." o, ".
                        $this->entity->getRepositoryPath('Group')." g, ".
                        $this->entity->getRepositoryPath('Category')." c ".
                "where t.pid=:club and ".
                        "o.cid=t.id and ".
                        "o.pid=g.id and ".
                        "g.classification=0 and ".
                        "g.pid=c.id and ".
                        "c.pid=:tournament ".
                "order by c.gender asc, c.classification asc, t.division asc");
        $qb->setParameter('club', $clubid);
        $qb->setParameter('tournament', $tournamentid);
        return $qb->getResult();
    }

    public function listChampionsByTournament($tournamentid) {
        $qb = $this->em->createQuery(
                "select c.id as catid,c.name as category,c.gender,c.classification as class,g.id,g.classification ".
                "from ".$this->entity->getRepositoryPath('Category')." c, ".
                        $this->entity->getRepositoryPath('Group')." g ".
                "where c.pid=:tournament and ".
                        "g.pid=c.id and ".
                        "g.classification>=9 ".
                "order by c.gender asc, c.classification asc, g.classification desc");
        $qb->setParameter('tournament', $tournamentid);
        return $qb->getResult();
    }
    
    public function getStatTournamentCounts($tournamentid) {
        $qb = $this->em->createQuery(
                "select count(distinct t.id) as teams, ".
                        "count(distinct c.id) as clubs, ".
                        "count(distinct c.country) as countries, ".
                        "count(distinct cat.id) as categories, ".
                        "count(distinct g.id) as groups ".
                "from ".$this->entity->getRepositoryPath('Category')." cat, ".
                        $this->entity->getRepositoryPath('Group')." g, ".
                        $this->entity->getRepositoryPath('GroupOrder')." o, ".
                        $this->entity->getRepositoryPath('Team')." t, ".
                        $this->entity->getRepositoryPath('Club')." c ".
                "where cat.pid=:tournament and ".
                        "g.pid=cat.id and ".
                        "g.classification=0 and ".
                        "o.pid=g.id and ".
                        "o.cid=t.id and ".
                        "t.pid=c.id");
        $qb->setParameter('tournament', $tournamentid);
        return $qb->getResult();
    }

    public function getStatPlaygroundCounts($tournamentid) {
        $qb = $this->em->createQuery(
                "select count(distinct s.id) as sites, ".
                        "count(distinct p.id) as playgrounds ".
                "from ".$this->entity->getRepositoryPath('Site')." s, ".
                        $this->entity->getRepositoryPath('Playground')." p ".
                "where s.pid=:tournament and ".
                        "p.pid=s.id");
        $qb->setParameter('tournament', $tournamentid);
        return $qb->getResult();
    }
        
    public function getStatTeamCounts($tournamentid) {
        $qb = $this->em->createQuery(
                "select count(distinct t.id) as femaleteams ".
                "from ".$this->entity->getRepositoryPath('Category')." cat, ".
                        $this->entity->getRepositoryPath('Group')." g, ".
                        $this->entity->getRepositoryPath('GroupOrder')." o, ".
                        $this->entity->getRepositoryPath('Team')." t ".
                "where cat.pid=:tournament and ".
                        "cat.gender='F' and ".
                        "g.pid=cat.id and ".
                        "o.pid=g.id and ".
                        "o.cid=t.id");
        $qb->setParameter('tournament', $tournamentid);
        return $qb->getResult();
    }
        
    public function getStatTeamCountsChildren($tournamentid) {
        $qb = $this->em->createQuery(
                "select count(distinct t.id) as childteams ".
                "from ".$this->entity->getRepositoryPath('Category')." cat, ".
                        $this->entity->getRepositoryPath('Group')." g, ".
                        $this->entity->getRepositoryPath('GroupOrder')." o, ".
                        $this->entity->getRepositoryPath('Team')." t ".
                "where cat.pid=:tournament and ".
                        "cat.classification<'U19' and ".
                        "g.pid=cat.id and ".
                        "o.pid=g.id and ".
                        "o.cid=t.id");
        $qb->setParameter('tournament', $tournamentid);
        return $qb->getResult();
    }
        
    public function getStatMatchCounts($tournamentid) {
        $qb = $this->em->createQuery(
                "select count(distinct m.id) as matches, ".
                        "sum(r.score) as goals, ".
                        "max(r.score) as mostgoals, ".
                        "count(distinct m.date) as days ".
                "from ".$this->entity->getRepositoryPath('Category')." cat, ".
                        $this->entity->getRepositoryPath('Group')." g, ".
                        $this->entity->getRepositoryPath('MatchRelation')." r, ".
                        $this->entity->getRepositoryPath('Match')." m ".
                "where cat.pid=:tournament and ".
                        "r.pid=m.id and ".
                        "m.pid=g.id and ".
                        "g.pid=cat.id");
        $qb->setParameter('tournament', $tournamentid);
        return $qb->getResult();
    }
        
    public function getStatTeams($tournamentid) {
        $qb = $this->em->createQuery(
                "select t.id,t.name,t.division,c.name as club,c.country,g.id as grp ".
                "from ".$this->entity->getRepositoryPath('Category')." cat, ".
                        $this->entity->getRepositoryPath('Group')." g, ".
                        $this->entity->getRepositoryPath('GroupOrder')." o, ".
                        $this->entity->getRepositoryPath('Team')." t, ".
                        $this->entity->getRepositoryPath('Club')." c ".
                "where cat.pid=:tournament and ".
                        "g.pid=cat.id and ".
                        "g.classification>8 and ".
                        "o.pid=g.id and ".
                        "o.cid=t.id and ".
                        "t.pid=c.id ".
                "order by o.id");
        $qb->setParameter('tournament', $tournamentid);
        $teamsList = array();
        foreach ($qb->getResult() as $team) {
            $teamInfo = new TeamInfo();
            $teamInfo->id = $team['id'];
            $teamInfo->name = $this->logic->getTeamName($team['name'], $team['division']);
            $teamInfo->club = $team['club'];
            $teamInfo->country = $team['country'];
            $teamInfo->group = $team['grp'];
            $teamsList[] = $teamInfo;
        }
        return $teamsList;
    }
        
    public function getStatTeamResults($tournamentid) {
        $qb = $this->em->createQuery(
                "select r ".
                "from ".$this->entity->getRepositoryPath('Category')." cat, ".
                        $this->entity->getRepositoryPath('Group')." g, ".
                        $this->entity->getRepositoryPath('MatchRelation')." r, ".
                        $this->entity->getRepositoryPath('Match')." m ".
                "where cat.pid=:tournament and ".
                        "r.pid=m.id and ".
                        "m.pid=g.id and ".
                        "g.pid=cat.id and ".
                        "g.classification>8 ".
                "order by r.pid");
        $qb->setParameter('tournament', $tournamentid);
        return $qb->getResult();
    }
}

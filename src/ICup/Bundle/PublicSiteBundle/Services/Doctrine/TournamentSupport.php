<?php

namespace ICup\Bundle\PublicSiteBundle\Services\Doctrine;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
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
                "where e.tournament=:tournament ".
                "order by e.date asc");
        $qb->setParameter('tournament', $tournamentid);
        $eventList = array();
        foreach ($qb->getResult() as $event) {
            $eventdate = Date::getDateTime($event['date']);
            $event['schedule'] = $eventdate;
            $eventList[] = $event;
        }
        return $eventList;
    }

    public function getEventByEvent($tournamentid, $event) {
         $qb = $this->em->createQuery(
                "select e ".
                "from ".$this->entity->getRepositoryPath('Event')." e ".
                "where e.tournament=:tournament and ".
                      "e.event=:event");
        $qb->setParameter('tournament', $tournamentid);
        $qb->setParameter('event', $event);
        return $qb->getOneOrNullResult();
    }
    
    public function isEnrollmentAllowed($tournamentid, $date) {
        return $this->isDateWithinInterval($tournamentid, $date, Event::$ENROLL_START, Event::$ENROLL_STOP, false);
    }

    public function isTournamentInProgress($tournamentid, $date) {
        return $this->isDateWithinInterval($tournamentid, $date, Event::$MATCH_START, Event::$MATCH_STOP, true);
    }

    public function isTournamentArchived($tournamentid, $date) {
        return $this->isDatePassedEvent($tournamentid, $date, Event::$TOURNAMENT_ARCHIVED);
    }

    public function isTournamentComplete($tournamentid, $date) {
        return $this->isDatePassedEvent($tournamentid, $date, Event::$MATCH_STOP);
    }

    private function isDateWithinInterval($tournamentid, $date, $start, $stop, $default) {
        $qb = $this->em->createQuery(
                "select e.id,e.date,e.event ".
                "from ".$this->entity->getRepositoryPath('Event')." e ".
                "where e.tournament=:tournament and ".
                      "e.event in (:start,:stop) ".
                "order by e.event asc");
        $qb->setParameter('tournament', $tournamentid);
        $qb->setParameter('start', $start);
        $qb->setParameter('stop', $stop);
        $status = $default;
        foreach ($qb->getResult() as $event) {
            $eventdate = Date::getDateTime($event['date']);
            if ($event['event'] == $start) {
                if ($date < $eventdate) {
                    $status = false;
                    break;
                }
                else {
                    $status = true;
                }
            }
            else {
                if ($date < $eventdate) {
                    $status = true;
                }
                else {
                    $status = false;
                    break;
                }
            }
        }
        return $status;
    }

    private function isDatePassedEvent($tournamentid, $date, $eventType) {
        $qb = $this->em->createQuery(
                "select e.id,e.date,e.event ".
                "from ".$this->entity->getRepositoryPath('Event')." e ".
                "where e.tournament=:tournament and ".
                      "e.event=:etype");
        $qb->setParameter('tournament', $tournamentid);
        $qb->setParameter('etype', $eventType);
        $event = $qb->getOneOrNullResult();
        if ($event != null) {
            $eventdate = Date::getDateTime($event['date']);
            $status = $date >= $eventdate;
        }
        else {
            $status = false;
        }
        return $status;
    }

    /* Tournament is archived - not shown by default */
    public static $TMNT_HIDE = 0;
    /* Tournament is open for team enrollment */
    public static $TMNT_ENROLL = 1;
    /* Tournament is in progress */
    public static $TMNT_GOING = 2;
    /* Tournament is over - hall of fame is visual */
    public static $TMNT_DONE = 3;
    /* Tournament is defined - ready to be announced */
    public static $TMNT_ANNOUNCE = 4;
    
    public function getTournamentStatus($tournamentid, $date) {
        // The order of checks is important - the checks are 
        if ($this->isTournamentArchived($tournamentid, $date)) {
            return TournamentSupport::$TMNT_HIDE;
        }
        if ($this->isTournamentComplete($tournamentid, $date)) {
            return TournamentSupport::$TMNT_DONE;
        }
        if ($this->isEnrollmentAllowed($tournamentid, $date)) {
            return TournamentSupport::$TMNT_ENROLL;
        }
        if ($this->isTournamentInProgress($tournamentid, $date)) {
            return TournamentSupport::$TMNT_GOING;
        }
        return TournamentSupport::$TMNT_ANNOUNCE;
    }

    public function listPlaygroundsByTournament($tournamentid) {
        $qb = $this->em->createQuery(
                "select p.id,p.name,s.name as site ".
                "from ".$this->entity->getRepositoryPath('Playground')." p, ".
                        $this->entity->getRepositoryPath('Site')." s ".
                "where s.tournament=:tournament and ".
                        "p.pid=s.id ".
                "order by p.no asc");
        $qb->setParameter('tournament', $tournamentid);
        return $qb->getResult();
    }

    public function listNewsByTournament($tournamentid) {
        $qb = $this->em->createQuery(
            "select n.id as nid,n.date,n.newstype,n.newsno,n.language,n.title,n.context,".
                   "t.id,t.name,t.division,c.name as club,c.country,".
                   "m.id as mid,m.matchno,m.date as matchdate,m.time as matchtime ".
            "from ".$this->entity->getRepositoryPath('News')." n ".
            "left outer join ".$this->entity->getRepositoryPath('Match')." m ".
            "with n.mid=m.id ".
            "left outer join ".$this->entity->getRepositoryPath('Team')." t ".
            "with n.cid=t.id ".
            "left outer join ".$this->entity->getRepositoryPath('Club')." c ".
            "with t.pid=c.id ".
            "where n.tournament=:tournament ".
            "order by n.newsno asc");
        $qb->setParameter('tournament', $tournamentid);
        $newsList = array();
        foreach ($qb->getResult() as $news) {
            $newsdate = Date::getDateTime($news['date']);
            $news['schedule'] = $newsdate;
            $newsList[] = $news;
        }
        return $newsList;
    }

    public function getNewsByNo($tournamentid, $newsno) {
        $qb = $this->em->createQuery(
            "select n " .
            "from " . $this->entity->getRepositoryPath('News') . " n " .
            "where n.tournament=:tournament and " .
            "n.newsno=:newsno");
        $qb->setParameter('tournament', $tournamentid);
        $qb->setParameter('newsno', $newsno);
        return $qb->getResult();
    }

    public function listTeamsByClub($tournamentid, $clubid) {
        $qb = $this->em->createQuery(
                "select t.id,t.name,t.division,c.id as catid,c.name as category,c.classification,c.age,c.gender,g.id as groupid,g.name as grp ".
                "from ".$this->entity->getRepositoryPath('Team')." t, ".
                        $this->entity->getRepositoryPath('GroupOrder')." o, ".
                        $this->entity->getRepositoryPath('Group')." g, ".
                        $this->entity->getRepositoryPath('Category')." c ".
                "where t.pid=:club and ".
                        "o.cid=t.id and ".
                        "o.pid=g.id and ".
                        "g.classification=0 and ".
                        "g.category=c.id and ".
                        "c.tournament=:tournament ".
                "order by c.gender asc, c.classification desc, c.age desc, t.division asc");
        $qb->setParameter('club', $clubid);
        $qb->setParameter('tournament', $tournamentid);
        return $qb->getResult();
    }

    public function listChampionsByTournament($tournamentid) {
        $qb = $this->em->createQuery(
                "select c.id as catid,c.name as category,c.gender,c.classification as class,c.age,g.id,g.classification ".
                "from ".$this->entity->getRepositoryPath('Category')." c, ".
                        $this->entity->getRepositoryPath('Group')." g ".
                "where c.tournament=:tournament and ".
                        "g.category=c.id and ".
                        "g.classification>=9 ".
                "order by c.gender asc, c.classification desc, c.age desc, g.classification desc");
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
                "where cat.tournament=:tournament and ".
                        "g.category=cat.id and ".
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
                "where s.tournament=:tournament and ".
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
                "where cat.tournament=:tournament and ".
                        "cat.gender='F' and ".
                        "g.category=cat.id and ".
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
                "where cat.tournament=:tournament and ".
                      "cat.classification='U' and ".
                      "cat.age<=18 and ".
                      "g.category=cat.id and ".
                      "o.pid=g.id and ".
                      "o.cid=t.id");
        $qb->setParameter('tournament', $tournamentid);
        return $qb->getResult();
    }
        
    public function getStatMatchCounts($tournamentid) {
        $qb = $this->em->createQuery(
                "select count(distinct m.id) as matches, ".
                        "sum(r.score) as goals, ".
                        "count(distinct m.date) as days ".
                "from ".$this->entity->getRepositoryPath('Category')." cat, ".
                        $this->entity->getRepositoryPath('Group')." g, ".
                        $this->entity->getRepositoryPath('MatchRelation')." r, ".
                        $this->entity->getRepositoryPath('Match')." m ".
                "where cat.tournament=:tournament and ".
                        "r.match=m.id and ".
                        "m.group=g.id and ".
                        "g.category=cat.id");
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
                "where cat.tournament=:tournament and ".
                        "g.category=cat.id and ".
                        "g.classification>=9 and ".
                        "o.pid=g.id and ".
                        "o.cid=t.id and ".
                        "t.pid=c.id and ".
                        "t.id in (select r.cid ".
                                 "from ".$this->entity->getRepositoryPath('MatchRelation')." r, ".
                                         $this->entity->getRepositoryPath('Match')." m ".
                                 "where r.match=m.id and ".
                                       "m.group=g.id and ".
                                       "r.scorevalid='Y') ".
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
        
    public function getTrophysByCountry($tournamentid) {
        $qb = $this->em->createQuery(
                "select c.country, count(t.id) as trophys ".
                "from ".$this->entity->getRepositoryPath('Category')." cat, ".
                        $this->entity->getRepositoryPath('Group')." g, ".
                        $this->entity->getRepositoryPath('GroupOrder')." o, ".
                        $this->entity->getRepositoryPath('Team')." t, ".
                        $this->entity->getRepositoryPath('Club')." c ".
                "where cat.tournament=:tournament and ".
                        "g.category=cat.id and ".
                        "g.classification>=9 and ".
                        "o.pid=g.id and ".
                        "o.cid=t.id and ".
                        "t.pid=c.id and ".
                        "t.id in (select r.cid ".
                                 "from ".$this->entity->getRepositoryPath('MatchRelation')." r, ".
                                         $this->entity->getRepositoryPath('Match')." m ".
                                 "where r.match=m.id and ".
                                       "m.group=g.id and ".
                                       "r.scorevalid='Y') ".
                "group by c.country ".
                "order by trophys desc");
        $qb->setParameter('tournament', $tournamentid);
        return $qb->getResult();
    }
        
    public function getTrophysByClub($tournamentid) {
        $qb = $this->em->createQuery(
                "select c.name as club, c.country, count(t.id) as trophys ".
                "from ".$this->entity->getRepositoryPath('Category')." cat, ".
                        $this->entity->getRepositoryPath('Group')." g, ".
                        $this->entity->getRepositoryPath('GroupOrder')." o, ".
                        $this->entity->getRepositoryPath('Team')." t, ".
                        $this->entity->getRepositoryPath('Club')." c ".
                "where cat.tournament=:tournament and ".
                        "g.category=cat.id and ".
                        "g.classification>=9 and ".
                        "o.pid=g.id and ".
                        "o.cid=t.id and ".
                        "t.pid=c.id and ".
                        "t.id in (select r.cid ".
                                 "from ".$this->entity->getRepositoryPath('MatchRelation')." r, ".
                                         $this->entity->getRepositoryPath('Match')." m ".
                                 "where r.match=m.id and ".
                                       "m.group=g.id and ".
                                       "r.scorevalid='Y') ".
                "group by c.name ".
                "order by trophys desc");
        $qb->setParameter('tournament', $tournamentid);
        return $qb->getResult();
    }
        
    public function getMostGoals($tournamentid) {
        $qb = $this->em->createQuery(
                "select t.id,c.name as club, c.country, cat.id as cid, max(r.score) as mostgoals ".
                "from ".$this->entity->getRepositoryPath('Category')." cat, ".
                        $this->entity->getRepositoryPath('Group')." g, ".
                        $this->entity->getRepositoryPath('MatchRelation')." r, ".
                        $this->entity->getRepositoryPath('Match')." m, ".
                        $this->entity->getRepositoryPath('Team')." t, ".
                        $this->entity->getRepositoryPath('Club')." c ".
                "where cat.tournament=:tournament and ".
                        "g.category=cat.id and ".
                        "m.group=g.id and ".
                        "r.match=m.id and ".
                        "t.id=r.cid and ".
                        "t.pid=c.id and ".
                        "r.scorevalid='Y' ".
                "group by t.id ".
                "order by mostgoals desc");
        $qb->setParameter('tournament', $tournamentid);
        return $qb->getResult();
    }
        
    public function getMostGoalsTotal($tournamentid) {
        $qb = $this->em->createQuery(
                "select t.id, c.name as club, c.country, cat.id as cid, sum(r.score) as mostgoals ".
                "from ".$this->entity->getRepositoryPath('Category')." cat, ".
                        $this->entity->getRepositoryPath('Group')." g, ".
                        $this->entity->getRepositoryPath('MatchRelation')." r, ".
                        $this->entity->getRepositoryPath('Match')." m, ".
                        $this->entity->getRepositoryPath('Team')." t, ".
                        $this->entity->getRepositoryPath('Club')." c ".
                "where cat.tournament=:tournament and ".
                        "g.category=cat.id and ".
                        "m.group=g.id and ".
                        "r.match=m.id and ".
                        "t.id=r.cid and ".
                        "t.pid=c.id and ".
                        "r.scorevalid='Y' ".
                "group by t.id ".
                "order by mostgoals desc");
        $qb->setParameter('tournament', $tournamentid);
        return $qb->getResult();
    }
        
    public function wipeTeams($tournamentid) {
        // wipe clubs
        $qbc = $this->em->createQuery(
                "delete from ".$this->entity->getRepositoryPath('Club')." clb ".
                "where clb.id in (select t.pid ".
                                "from ".$this->entity->getRepositoryPath('Category')." c, ".
                                        $this->entity->getRepositoryPath('Group')." g, ".
                                        $this->entity->getRepositoryPath('GroupOrder')." o, ".
                                        $this->entity->getRepositoryPath('Team')." t ".
                                "where c.tournament=:tournament and g.category=c.id and o.pid=g.id and o.cid=t.id) ".
                  "and clb.id not in (select tt.pid ".
                                "from ".$this->entity->getRepositoryPath('Category')." cc, ".
                                        $this->entity->getRepositoryPath('Group')." gg, ".
                                        $this->entity->getRepositoryPath('GroupOrder')." oo, ".
                                        $this->entity->getRepositoryPath('Team')." tt ".
                                "where cc.tournament<>:tournament and gg.category=cc.id and oo.pid=gg.id and oo.cid=tt.id)");
        $qbc->setParameter('tournament', $tournamentid);
        $qbc->getResult();
        // wipe teams
        $qbt = $this->em->createQuery(
                "delete from ".$this->entity->getRepositoryPath('Team')." t ".
                "where t.id in (select o.cid ".
                                "from ".$this->entity->getRepositoryPath('Category')." c, ".
                                        $this->entity->getRepositoryPath('Group')." g, ".
                                        $this->entity->getRepositoryPath('GroupOrder')." o ".
                                "where c.tournament=:tournament and g.category=c.id and o.pid=g.id)");
        $qbt->setParameter('tournament', $tournamentid);
        $qbt->getResult();
        // wipe group orders
        $qbo = $this->em->createQuery(
                "delete from ".$this->entity->getRepositoryPath('GroupOrder')." o ".
                "where o.pid in (select g.id ".
                                "from ".$this->entity->getRepositoryPath('Category')." c, ".
                                        $this->entity->getRepositoryPath('Group')." g ".
                                "where c.tournament=:tournament and g.category=c.id)");
        $qbo->setParameter('tournament', $tournamentid);
        $qbo->getResult();
        // wipe enrollments
        $qbe = $this->em->createQuery(
                "delete from ".$this->entity->getRepositoryPath('Enrollment')." e ".
                "where e.pid in (select c.id ".
                                "from ".$this->entity->getRepositoryPath('Category')." c ".
                                "where c.tournament=:tournament)");
        $qbe->setParameter('tournament', $tournamentid);
        $qbe->getResult();
    }
    
    public function wipeMatches($tournamentid) {
        // wipe match relations
        $qbr = $this->em->createQuery(
                "delete from ".$this->entity->getRepositoryPath('MatchRelation')." r ".
                "where r.match in (select m.id ".
                                "from ".$this->entity->getRepositoryPath('Category')." c, ".
                                        $this->entity->getRepositoryPath('Group')." g, ".
                                        $this->entity->getRepositoryPath('Match')." m ".
                                "where c.tournament=:tournament and g.category=c.id and m.group=g.id)");
        $qbr->setParameter('tournament', $tournamentid);
        $qbr->getResult();
        // wipe matches
        $qbm = $this->em->createQuery(
                "delete from ".$this->entity->getRepositoryPath('Match')." m ".
                "where m.group in (select g.id ".
                                "from ".$this->entity->getRepositoryPath('Category')." c, ".
                                        $this->entity->getRepositoryPath('Group')." g ".
                                "where c.tournament=:tournament and g.category=c.id)");
        $qbm->setParameter('tournament', $tournamentid);
        $qbm->getResult();
    }
    
    public function wipeQMatches($tournamentid) {
        // wipe qmatch relations
        $qbr = $this->em->createQuery(
                "delete from ".$this->entity->getRepositoryPath('QMatchRelation')." q ".
                "where q.match in (select m.id ".
                                "from ".$this->entity->getRepositoryPath('Category')." c, ".
                                        $this->entity->getRepositoryPath('Group')." g, ".
                                        $this->entity->getRepositoryPath('Match')." m ".
                                "where c.tournament=:tournament and g.category=c.id and m.group=g.id)");
        $qbr->setParameter('tournament', $tournamentid);
        $qbr->getResult();
        // wipe matches
        $qbm = $this->em->createQuery(
                "delete from ".$this->entity->getRepositoryPath('Match')." m ".
                "where m.group in (select g.id ".
                                "from ".$this->entity->getRepositoryPath('Category')." c, ".
                                        $this->entity->getRepositoryPath('Group')." g ".
                                "where c.tournament=:tournament and g.category=c.id) ".
                "and m.id not in (select r.match ".
                                 "from ".$this->entity->getRepositoryPath('MatchRelation')." r)");
        $qbm->setParameter('tournament', $tournamentid);
        $qbm->getResult();
    }
    
    public function wipeCategories($tournamentid) {
        // wipe groups
        $qbg = $this->em->createQuery(
                "delete from ".$this->entity->getRepositoryPath('Group')." g ".
                "where g.category in (select c.id ".
                                "from ".$this->entity->getRepositoryPath('Category')." c ".
                                "where c.tournament=:tournament)");
        $qbg->setParameter('tournament', $tournamentid);
        $qbg->getResult();
        // wipe playground attribute relations
        $qbp = $this->em->createQuery(
                "delete from ".$this->entity->getRepositoryPath('PARelation')." p ".
                "where p.cid in (select c.id ".
                                "from ".$this->entity->getRepositoryPath('Category')." c ".
                                "where c.tournament=:tournament)");
        $qbp->setParameter('tournament', $tournamentid);
        $qbp->getResult();
        // wipe categories
        $qbc = $this->em->createQuery(
                "delete from ".$this->entity->getRepositoryPath('Category')." c ".
                "where c.tournament=:tournament");
        $qbc->setParameter('tournament', $tournamentid);
        $qbc->getResult();
    }
    
    public function wipeSites($tournamentid) {
        // wipe playground attribute relations
        $qbr = $this->em->createQuery(
                "delete from ".$this->entity->getRepositoryPath('PARelation')." r ".
                "where r.pid in (select a.id ".
                                "from ".$this->entity->getRepositoryPath('Site')." s, ".
                                        $this->entity->getRepositoryPath('Playground')." p, ".
                                        $this->entity->getRepositoryPath('PlaygroundAttribute')." a ".
                                "where s.tournament=:tournament and p.pid=s.id and a.playground=p.id)");
        $qbr->setParameter('tournament', $tournamentid);
        $qbr->getResult();
        // wipe playground attributes
        $qbm = $this->em->createQuery(
                "delete from ".$this->entity->getRepositoryPath('PlaygroundAttribute')." a ".
                "where a.playground in (select p.id ".
                                "from ".$this->entity->getRepositoryPath('Site')." s, ".
                                        $this->entity->getRepositoryPath('Playground')." p ".
                                "where s.tournament=:tournament and p.pid=s.id)");
        $qbm->setParameter('tournament', $tournamentid);
        $qbm->getResult();
        // wipe playgrounds
        $qbg = $this->em->createQuery(
                "delete from ".$this->entity->getRepositoryPath('Playground')." p ".
                "where p.pid in (select s.id ".
                                "from ".$this->entity->getRepositoryPath('Site')." s ".
                                "where s.tournament=:tournament)");
        $qbg->setParameter('tournament', $tournamentid);
        $qbg->getResult();
        // wipe sites
        $qbc = $this->em->createQuery(
                "delete from ".$this->entity->getRepositoryPath('Site')." s ".
                "where s.tournament=:tournament");
        $qbc->setParameter('tournament', $tournamentid);
        $qbc->getResult();
    }
    
    public function wipeTournament($tournamentid) {
        $this->wipeQMatches($tournamentid);
        $this->wipeMatches($tournamentid);
        $this->wipeTeams($tournamentid);
        $this->wipeCategories($tournamentid);
        $this->wipeSites($tournamentid);
        // wipe timeslots
        $qbs = $this->em->createQuery(
                "delete from ".$this->entity->getRepositoryPath('Timeslot')." t ".
                "where t.tournament=:tournament");
        $qbs->setParameter('tournament', $tournamentid);
        $qbs->getResult();
        // wipe events
        $qbe = $this->em->createQuery(
                "delete from ".$this->entity->getRepositoryPath('Event')." e ".
                "where e.tournament=:tournament");
        $qbe->setParameter('tournament', $tournamentid);
        $qbe->getResult();
        // wipe news
        $qbn = $this->em->createQuery(
            "delete from ".$this->entity->getRepositoryPath('News')." n ".
            "where n.tournament=:tournament");
        $qbn->setParameter('tournament', $tournamentid);
        $qbn->getResult();
        // wipe tournament
        $qbt = $this->em->createQuery(
                "delete from ".$this->entity->getRepositoryPath('Tournament')." t ".
                "where t.id=:tournament");
        $qbt->setParameter('tournament', $tournamentid);
        $qbt->getResult();
    }
}

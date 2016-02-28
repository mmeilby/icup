<?php

namespace ICup\Bundle\PublicSiteBundle\Services\Doctrine;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Champion;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\QMatchRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
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
        return $this->isDateWithinInterval($tournamentid, $date, Event::$MATCH_START, Event::$MATCH_STOP, false);
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
                        "p.site=s.id ".
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
            "with n.match=m.id ".
            "left outer join ".$this->entity->getRepositoryPath('Team')." t ".
            "with n.team=t.id ".
            "left outer join ".$this->entity->getRepositoryPath('Club')." c ".
            "with t.club=c.id ".
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
                "where t.club=:club and ".
                        "o.team=t.id and ".
                        "o.group=g.id and ".
                        "g.classification=0 and ".
                        "g.category=c.id and ".
                        "c.tournament=:tournament ".
                "order by c.gender asc, c.classification desc, c.age desc, t.division asc");
        $qb->setParameter('club', $clubid);
        $qb->setParameter('tournament', $tournamentid);
        return $qb->getResult();
    }

    public function listChampionsByTournament(Tournament $tournament) {
        $sortedGroups = array();
        $teams = array();
        /* @var $category Category */
        foreach ($tournament->getCategories() as $category) {
            /* @var $champion Champion */
            foreach ($category->getChampions() as $champion) {
                $groupid = $champion->getGroup()->getId();
                if (!isset($sortedGroups[$groupid])) {
                    $sortedGroups[$groupid] = $this->container->get('orderTeams')->sortCompletedGroup($groupid);
                }
                $rankedTeams = $sortedGroups[$groupid];
                if ($rankedTeams && isset($rankedTeams[$champion->getRank()-1])) {
                    $team = $this->entity->getTeamById($rankedTeams[$champion->getRank()-1]->getId());
                    $teams[$category->getId()][$champion->getChampion()] = $team;
                }
            }
        }
        return $teams;
    }

    public function listQualifiedTeamsByTournament(Tournament $tournament) {
        $sortedGroups = array();
        $matches = array();
        /* @var $category Category */
        foreach ($tournament->getCategories() as $category) {
            /* @var $group Group */
            foreach ($category->getGroups() as $group) {
                // only inspect matches for groups in the elimination rounds
                if ($group->getClassification() > Group::$PRE) {
                    /* @var $match Match */
                    foreach ($group->getMatches() as $match) {
                        // search for matches with no teams qualified yet
                        if ($match->getMatchRelations()->count() == 0) {
                            $teams = array();
                            /* @var $qmatchrelation QMatchRelation */
                            foreach ($match->getQMatchRelations() as $qmatchrelation) {
                                // get current standing for the qualifying group if not yet found
                                $groupid = $qmatchrelation->getGroup()->getId();
                                if (!isset($sortedGroups[$groupid])) {
                                    $sortedGroups[$groupid] = $this->container->get('orderTeams')->sortCompletedGroup($groupid);
                                }
                                $rankedTeams = $sortedGroups[$groupid];
                                if ($rankedTeams && isset($rankedTeams[$qmatchrelation->getRank()-1])) {
                                    $team = $this->entity->getTeamById($rankedTeams[$qmatchrelation->getRank()-1]->getId());
                                    $teams[$qmatchrelation->getAwayteam()]= $team;
                                }
                            }
                            // if we got two teams ready for elimination then we got a match
                            if (count($teams) == 2) {
                                $matches[] = array(
                                    'match' => $match,
                                    'home' => $teams[MatchSupport::$HOME],
                                    'away' => $teams[MatchSupport::$AWAY],
                                );
                            }
                        }
                    }
                }
            }
        }
        return $matches;
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
                        "o.group=g.id and ".
                        "o.team=t.id and ".
                        "t.club=c.id and ".
                        "t.vacant<>'Y'");
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
                        "p.site=s.id");
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
                        "o.group=g.id and ".
                        "o.team=t.id and ".
                        "t.vacant<>'Y'");
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
                      "o.group=g.id and ".
                      "o.team=t.id and ".
                      "t.vacant<>'Y'");
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

    public function getTrophysByCountry(Tournament $tournament) {
        $order = array('first', 'second', 'third', 'forth');
        $championList = array();
        $teams = $this->listChampionsByTournament($tournament);
        foreach ($teams as $categoryid => $categoryChamps) {
            /* @var $team Team */
            foreach ($categoryChamps as $champion => $team) {
                if (!isset($championList[$team->getClub()->getCountry()])) {
                    $championList[$team->getClub()->getCountry()] =
                        array('first' => array(), 'second' => array(), 'third' => array(), 'forth' => array());
                }
                $championList[$team->getClub()->getCountry()][$order[$champion-1]][] = $team;
            }
        }
        uasort($championList,
            function (array $country1, array $country2) {
                $order = array('first', 'second', 'third', 'forth');
                foreach ($order as $i) {
                    $o = count($country1[$i]) - count($country2[$i]);
                    if ($o < 0) {
                        return 1;
                    }
                    else if ($o > 0) {
                        return -1;
                    }
                }
                return 0;
            }
        );
        foreach ($championList as $country => $trophys) {
            $totalTrophys = 0;
            foreach ($trophys as $champions) {
                $totalTrophys += count($champions);
            }
            return array(array('country' => $country, 'trophys' => $totalTrophys));
        }
        return array();
    }
        
    public function getTrophysByClub($tournament) {
        $order = array('first', 'second', 'third', 'forth');
        $championList = array();
        $teams = $this->listChampionsByTournament($tournament);
        foreach ($teams as $categoryid => $categoryChamps) {
            /* @var $team Team */
            foreach ($categoryChamps as $champion => $team) {
                if (!isset($championList[$team->getClub()->getId()])) {
                    $championList[$team->getClub()->getId()] =
                        array('first' => array(), 'second' => array(), 'third' => array(), 'forth' => array());
                }
                $championList[$team->getClub()->getId()][$order[$champion-1]][] = $team;
            }
        }
        uasort($championList,
            function (array $club1, array $club2) {
                $order = array('first', 'second', 'third', 'forth');
                foreach ($order as $i) {
                    $o = count($club1[$i]) - count($club2[$i]);
                    if ($o < 0) {
                        return 1;
                    }
                    else if ($o > 0) {
                        return -1;
                    }
                }
                return 0;
            }
        );
        foreach ($championList as $trophys) {
            $totalTrophys = 0;
            $club = null;
            foreach ($trophys as $champions) {
                if (count($champions) > 0) {
                    $totalTrophys += count($champions);
                    $club = $champions[0]->getClub();
                }
            }
            return array(array('club' => $club->getName(), 'country' => $club->getCountry(), 'trophys' => $totalTrophys));
        }
        return array();
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
                        "t.id=r.team and ".
                        "t.club=c.id and ".
                        "t.vacant<>'Y' and ".
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
                        "t.id=r.team and ".
                        "t.club=c.id and ".
                        "t.vacant<>'Y' and ".
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
                "where clb.id in (select identity(t.club) ".
                                "from ".$this->entity->getRepositoryPath('Category')." c, ".
                                        $this->entity->getRepositoryPath('Group')." g, ".
                                        $this->entity->getRepositoryPath('GroupOrder')." o, ".
                                        $this->entity->getRepositoryPath('Team')." t ".
                                "where c.tournament=:tournament and g.category=c.id and o.group=g.id and o.team=t.id) ".
                  "and clb.id not in (select identity(tt.club) ".
                                "from ".$this->entity->getRepositoryPath('Category')." cc, ".
                                        $this->entity->getRepositoryPath('Group')." gg, ".
                                        $this->entity->getRepositoryPath('GroupOrder')." oo, ".
                                        $this->entity->getRepositoryPath('Team')." tt ".
                                "where cc.tournament<>:tournament and gg.category=cc.id and oo.group=gg.id and oo.team=tt.id)");
        $qbc->setParameter('tournament', $tournamentid);
        $qbc->getResult();
        // wipe teams
        $qbt = $this->em->createQuery(
                "delete from ".$this->entity->getRepositoryPath('Team')." t ".
                "where t.id in (select identity(o.team) ".
                                "from ".$this->entity->getRepositoryPath('Category')." c, ".
                                        $this->entity->getRepositoryPath('Group')." g, ".
                                        $this->entity->getRepositoryPath('GroupOrder')." o ".
                                "where c.tournament=:tournament and g.category=c.id and o.group=g.id)");
        $qbt->setParameter('tournament', $tournamentid);
        $qbt->getResult();
        // wipe group orders
        $qbo = $this->em->createQuery(
                "delete from ".$this->entity->getRepositoryPath('GroupOrder')." o ".
                "where o.group in (select g.id ".
                                "from ".$this->entity->getRepositoryPath('Category')." c, ".
                                        $this->entity->getRepositoryPath('Group')." g ".
                                "where c.tournament=:tournament and g.category=c.id)");
        $qbo->setParameter('tournament', $tournamentid);
        $qbo->getResult();
        // wipe enrollments
        $qbe = $this->em->createQuery(
                "delete from ".$this->entity->getRepositoryPath('Enrollment')." e ".
                "where e.category in (select c.id ".
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
                "and m.id not in (select identity(r.match) ".
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
        // wipe categories
        $qbc = $this->em->createQuery(
                "delete from ".$this->entity->getRepositoryPath('Category')." c ".
                "where c.tournament=:tournament");
        $qbc->setParameter('tournament', $tournamentid);
        $qbc->getResult();
    }
    
    public function wipeSites($tournamentid) {
        // wipe playground attributes
        $qbm = $this->em->createQuery(
                "delete from ".$this->entity->getRepositoryPath('PlaygroundAttribute')." a ".
                "where a.playground in (select p.id ".
                                "from ".$this->entity->getRepositoryPath('Site')." s, ".
                                        $this->entity->getRepositoryPath('Playground')." p ".
                                "where s.tournament=:tournament and p.site=s.id)");
        $qbm->setParameter('tournament', $tournamentid);
        $qbm->getResult();
        // wipe playgrounds
        $qbg = $this->em->createQuery(
                "delete from ".$this->entity->getRepositoryPath('Playground')." p ".
                "where p.site in (select s.id ".
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
        $this->wipeChampions($tournamentid);
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

    public function wipeQualifyingGroups($tournamentid) {
        // wipe groups
        $qbg = $this->em->createQuery(
            "delete from ".$this->entity->getRepositoryPath('Group')." g ".
            "where g.classification>0 and g.category in (select c.id ".
                "from ".$this->entity->getRepositoryPath('Category')." c ".
                "where c.tournament=:tournament)");
        $qbg->setParameter('tournament', $tournamentid);
        $qbg->getResult();
    }

    public function wipeChampions($tournamentid) {
        // wipe champions
        $qbg = $this->em->createQuery(
            "delete from ".$this->entity->getRepositoryPath('Champion')." t ".
            "where t.category in (select c.id ".
            "from ".$this->entity->getRepositoryPath('Category')." c ".
            "where c.tournament=:tournament)");
        $qbg->setParameter('tournament', $tournamentid);
        $qbg->getResult();
    }
}

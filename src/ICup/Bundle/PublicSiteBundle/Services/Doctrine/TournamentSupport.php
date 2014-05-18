<?php

namespace ICup\Bundle\PublicSiteBundle\Services\Doctrine;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use ICup\Bundle\PublicSiteBundle\Services\Doctrine\Entity;
use ICup\Bundle\PublicSiteBundle\Services\Doctrine\BusinessLogic;
use ICup\Bundle\PublicSiteBundle\Entity\TeamInfo;
use DateTime;

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
    
    public function listMatchesByGroupPlayground($groupid, $playgroundid) {
        $qb = $this->em->createQuery(
                "select m.matchno,m.date,m.time,r.awayteam,r.scorevalid,r.score,r.points,t.id,t.name as team,t.division,c.country ".
                "from ".$this->entity->getRepositoryPath('MatchRelation')." r, ".
                        $this->entity->getRepositoryPath('Match')." m, ".
                        $this->entity->getRepositoryPath('Team')." t, ".
                        $this->entity->getRepositoryPath('Club')." c ".
                "where m.pid=:group and ".
                      "m.playground=:playground and ".
                      "r.pid=m.id and ".
                      "t.id=r.cid and ".
                      "c.id=t.pid ".
                "order by m.id");
        $qb->setParameter('group', $groupid);
        $qb->setParameter('playground', $playgroundid);
        return $qb->getResult();
    }

    public function isMatchResultValid($matchid) {
        $qb = $this->em->createQuery(
                "select count(r) as results ".
                "from ".$this->entity->getRepositoryPath('MatchRelation')." r ".
                "where r.pid=:match and r.scorevalid='Y'");
        $qb->setParameter('match', $matchid);
        $results = $qb->getOneOrNullResult();
        return $results != null ? $results['results'] == 2 : false;
    }
 
    public function getMatchRelationByMatch($matchid, $away) {
        $qb = $this->em->createQuery(
                "select r ".
                "from ".$this->entity->getRepositoryPath('MatchRelation')." r ".
                "where r.pid=:match and r.awayteam=:away");
        $qb->setParameter('match', $matchid);
        $qb->setParameter('away', $away ? 'Y' : 'N');
        return $qb->getOneOrNullResult();
    }
     
    public function getMatchHomeTeam($matchid) {
        $matchRel = $this->getMatchRelationByMatch($matchid, false);
        return $matchRel != null ? $matchRel->getCid() : 0;
    }
    
    public function getMatchAwayTeam($matchid) {
        $matchRel = $this->getMatchRelationByMatch($matchid, true);
        return $matchRel != null ? $matchRel->getCid() : 0;
    }

    public function listMatchCalendar($tournamentid) {
        $qb = $this->em->createQuery(
                "select distinct m.date ".
                "from ".$this->entity->getRepositoryPath('Category')." cat, ".
                        $this->entity->getRepositoryPath('Group')." g, ".
                        $this->entity->getRepositoryPath('MatchRelation')." r, ".
                        $this->entity->getRepositoryPath('Match')." m ".
                "where cat.pid=:tournament and ".
                        "r.pid=m.id and ".
                        "m.pid=g.id and ".
                        "g.pid=cat.id");
        $qb->setParameter('tournament', $tournamentid);
        $matchList = array();
        foreach ($qb->getResult() as $date) {
            $matchdate = date_create_from_format("d/m/Y", $date['date']);
            $matchList[] = $matchdate;
        }
        return $matchList;
    }
        
    public function listMatchesByPlaygroundDate($playgroundid, $date) {
        $matchdate = date_format($date, "d/m/Y");
        $qb = $this->em->createQuery(
                "select m.id as mid,m.matchno,m.date,m.time,".
                       "g.id as gid,g.name as grp,".
                       "cat.id as cid,cat.name as category,".
                       "r.id as rid,r.awayteam,r.scorevalid,r.score,r.points,".
                       "t.id,t.name as team,t.division,c.country ".
                "from ".$this->entity->getRepositoryPath('MatchRelation')." r, ".
                        $this->entity->getRepositoryPath('Match')." m, ".
                        $this->entity->getRepositoryPath('Group')." g, ".
                        $this->entity->getRepositoryPath('Category')." cat, ".
                        $this->entity->getRepositoryPath('Team')." t, ".
                        $this->entity->getRepositoryPath('Club')." c ".
                "where m.playground=:playground and ".
                      "m.pid=g.id and ".
                      "m.date=:date and ".
                      "g.pid=cat.id and ".
                      "r.pid=m.id and ".
                      "t.id=r.cid and ".
                      "c.id=t.pid ".
                "order by m.id");
        $qb->setParameter('playground', $playgroundid);
        $qb->setParameter('date', $matchdate);
        // Collect match relations as matches
        $matchList = array();
        foreach ($qb->getResult() as $match) {
            $matchList[$match['matchno']][$match['awayteam']=='Y'?'A':'H'] = $match;
        }
        // Prepare match list for output
        $matches = array();
        foreach ($matchList as $matchRelList) {
            // There must be two relations for each match - otherwise ignore the match
            if (count($matchRelList) == 2) {
                $matches[] = $this->prepareMatchListWithCategory($matchRelList);
            }
        }
        // Sort the matches based on schedule
        usort($matches, array("ICup\Bundle\PublicSiteBundle\Services\Doctrine\TournamentSupport", "reorderMatch"));
        return $matches;
    }
    
    public function listMatchesByGroup($groupid) {
        $qb = $this->em->createQuery(
                "select m.id as mid,m.matchno,m.date,m.time,".
                       "p.no,".
                       "r.id as rid,r.awayteam,r.scorevalid,r.score,r.points,".
                       "t.id,t.name as team,t.division,c.country ".
                "from ".$this->entity->getRepositoryPath('MatchRelation')." r, ".
                        $this->entity->getRepositoryPath('Match')." m, ".
                        $this->entity->getRepositoryPath('Playground')." p, ".
                        $this->entity->getRepositoryPath('Team')." t, ".
                        $this->entity->getRepositoryPath('Club')." c ".
                "where m.pid=:group and ".
                      "p.id=m.playground and ".
                      "r.pid=m.id and ".
                      "t.id=r.cid and ".
                      "c.id=t.pid ".
                "order by m.id");
        $qb->setParameter('group', $groupid);
        // Collect match relations as matches
        $matchList = array();
        foreach ($qb->getResult() as $match) {
            $matchList[$match['matchno']][$match['awayteam']=='Y'?'A':'H'] = $match;
        }
        // Prepare match list for output
        $matches = array();
        foreach ($matchList as $matchRelList) {
            // There must be two relations for each match - otherwise ignore the match
            if (count($matchRelList) == 2) {
                $matches[] = $this->prepareMatchList($matchRelList);
            }
        }
        // Sort the matches based on schedule
        usort($matches, array("ICup\Bundle\PublicSiteBundle\Services\Doctrine\TournamentSupport", "reorderMatch"));
        return $matches;
    }

    private function prepareMatchList($matchRelList) {
        $match = $matchRelList['H'];
        return array(
            'id' => $match['mid'],
            'matchno' => $match['matchno'],
            'schedule' => DateTime::createFromFormat('d/m/Y-H:i', $match['date'].'-'.str_replace(".", ":", $match['time'])),
            'playground' => $match['no'],
            'home' => $this->samplePart($matchRelList['H']),
            'away' => $this->samplePart($matchRelList['A'])
        );
    }

    private function prepareMatchListWithCategory($matchRelList) {
        $match = $matchRelList['H'];
        return array(
            'id' => $match['mid'],
            'matchno' => $match['matchno'],
            'schedule' => DateTime::createFromFormat('d/m/Y-H:i', $match['date'].'-'.str_replace(".", ":", $match['time'])),
            'category' => array('name' => $match['category'], 'id' => $match['cid']),
            'group' => array('name' => $match['grp'], 'id' => $match['gid']),
            'home' => $this->samplePart($matchRelList['H']),
            'away' => $this->samplePart($matchRelList['A'])
        );
    }
    
    private function samplePart($rel) {
        $name = $rel['team'];
        if ($rel['division'] != '') {
            $name.= ' "'.$rel['division'].'"';
        }
        $valid = $rel['scorevalid'] == 'Y';
        return array(
            'rid' => $rel['rid'],
            'id' => $rel['id'],
            'team' => $name,
            'country' => $rel['country'],
            'score' => $valid ? $rel['score'] : '',
            'points' => $valid ? $rel['points'] : '',
        );
    }
    
    static function reorderMatch($match1, $match2) {
        if ($match1['schedule'] == $match2['schedule']) {
            return 0;
        }
        elseif ($match1['schedule'] > $match2['schedule']) {
            return 1;
        }
        else {
            return -1;
        }
    }

    public function listMatchesByGroupTeam($groupid, $teamid) {
        $qb = $this->em->createQuery(
                "select m.matchno,m.date,m.time,p.id as playgroundid,p.no,p.name as playground,r.awayteam,r.scorevalid,r.score,r.points,t.id,t.name as team,t.division,c.country ".
                "from ".$this->entity->getRepositoryPath('MatchRelation')." r, ".
                        $this->entity->getRepositoryPath('Match')." m, ".
                        $this->entity->getRepositoryPath('Playground')." p, ".
                        $this->entity->getRepositoryPath('Team')." t, ".
                        $this->entity->getRepositoryPath('Club')." c ".
                "where m.pid=:group and ".
                        "m.id in (".
                            "select rx.pid ".
                            "from ".$this->entity->getRepositoryPath('MatchRelation')." rx ".
                            "where rx.cid=:team) and ".
                        "p.id=m.playground and ".
                        "r.pid=m.id and ".
                        "t.id=r.cid and ".
                        "c.id=t.pid ".
                "order by m.id");
        $qb->setParameter('group', $groupid);
        $qb->setParameter('team', $teamid);
        return $qb->getResult();
    }
    
    public function listMatchesByPlayground($playgroundid) {
        $qb = $this->em->createQuery(
                "select m.matchno,m.date,m.time,g.id as gid,g.name as grp,cat.name as category,r.awayteam,r.scorevalid,r.score,r.points,t.id,t.name as team,t.division,c.country ".
                "from ".$this->entity->getRepositoryPath('MatchRelation')." r, ".
                        $this->entity->getRepositoryPath('Match')." m, ".
                        $this->entity->getRepositoryPath('Group')." g, ".
                        $this->entity->getRepositoryPath('Category')." cat, ".
                        $this->entity->getRepositoryPath('Team')." t, ".
                        $this->entity->getRepositoryPath('Club')." c ".
                "where m.playground=:playground and ".
                      "m.pid=g.id and ".
                      "g.pid=cat.id and ".
                      "r.pid=m.id and ".
                      "t.id=r.cid and ".
                      "c.id=t.pid ".
                "order by m.id");
        $qb->setParameter('playground', $playgroundid);
        return $qb->getResult();
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

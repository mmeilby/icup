<?php

namespace ICup\Bundle\PublicSiteBundle\Services\Doctrine;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use ICup\Bundle\PublicSiteBundle\Services\Doctrine\Entity;
use ICup\Bundle\PublicSiteBundle\Services\Doctrine\BusinessLogic;
use DateTime;

class MatchSupport
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

    private function prepareAndSort($matchdata, $qmatchdata = array()) {
        // Collect match relations as matches
        $matchList = array();
        foreach ($matchdata as $match) {
            $matchList[$match['matchno']][$match['awayteam']=='Y'?'A':'H']['M'] = $match;
        }
        foreach ($qmatchdata as $qmatch) {
            $matchList[$qmatch['matchno']][$qmatch['awayteam']=='Y'?'A':'H']['Q'] = $qmatch;
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
        usort($matches, array("ICup\Bundle\PublicSiteBundle\Services\Doctrine\MatchSupport", "reorderMatch"));
        return $matches;
    }
    
    private function prepareMatchList($matchRelList) {
        $homeMatch = $this->flattenList($matchRelList['H']);
        $awayMatch = $this->flattenList($matchRelList['A']);
        return array(
            'id' => $this->getValue($homeMatch, 'mid'),
            'matchno' => $homeMatch['matchno'],
            'schedule' => DateTime::createFromFormat('d/m/Y-H:i', $homeMatch['date'].'-'.str_replace(".", ":", $homeMatch['time'])),
            'playground' => array('no' => $this->getValue($homeMatch, 'no'),
                                  'name' => $this->getValue($homeMatch, 'playground'),
                                  'id' => $this->getValue($homeMatch, 'pid')),
            'category' => array('name' => $this->getValue($homeMatch, 'category'),
                                'id' => $this->getValue($homeMatch, 'cid')),
            'group' => array('name' => $this->getValue($homeMatch, 'grp'),
                             'id' => $this->getValue($homeMatch, 'gid')),
            'home' => $this->samplePart($homeMatch),
            'away' => $this->samplePart($awayMatch)
        );
    }

    private function flattenList($matchList) {
        $matchRec = array();
        if (array_key_exists('Q', $matchList)) {
            $qmatch = $matchList['Q'];
            $matchRec = array_merge($matchRec, $qmatch); 
            $rankTxt = $this->container->get('translator')->
                            transChoice('RANK', $qmatch['rank'],
                                    array('%rank%' => $qmatch['rank'],
                                          '%group%' => $qmatch['gname']), 'tournament');
            $matchRec['rank'] = $rankTxt;
            $matchRec['team'] = '';
            $matchRec['division'] = '';
            $matchRec['scorevalid'] = 'N';
            $matchRec['id'] = -1;
            $matchRec['country'] = 'EUR';
//            $groupid = $qmatch['rgrp'];
//            $teamStatList = $this->container->get('orderTeams')->sortGroup($groupid);
        }
        if (array_key_exists('M', $matchList)) {
            $matchRec = array_merge($matchRec, $matchList['M']); 
        }
        return $matchRec;
    }
    
    private function getValue($ar, $key) {
        return array_key_exists($key, $ar) ? $ar[$key] : '';
    }
    
    private function samplePart($rel) {
        $name = $rel['team'];
        if ($rel['division'] != '') {
            $name.= ' "'.$rel['division'].'"';
        }
        $valid = $rel['scorevalid'] == 'Y';
        return array(
            'rid' => $this->getValue($rel, 'rid'),
            'id' => $rel['id'],
            'name' => $name,
            'country' => $rel['country'],
            'score' => $valid ? $rel['score'] : '',
            'points' => $valid ? $rel['points'] : '',
            'rank' => $this->getValue($rel, 'rank'),
        );
    }

    /*
     * PUBLIC FUNCTIONS
     */
    
    public function getMatchByNo($tournamentid, $matchno) {
        $qb = $this->em->createQuery(
                "select m ".
                "from ".$this->entity->getRepositoryPath('Match')." m, ".
                        $this->entity->getRepositoryPath('Group')." g, ".
                        $this->entity->getRepositoryPath('Category')." c ".
                "where m.pid=g.id and ".
                      "m.matchno=:matchno and ".
                      "g.pid=c.id and ".
                      "c.pid=:tournament");
        $qb->setParameter('tournament', $tournamentid);
        $qb->setParameter('matchno', $matchno);
        return $qb->getOneOrNullResult();
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
     
    public function getQMatchRelationByMatch($matchid, $away) {
        $qb = $this->em->createQuery(
                "select q ".
                "from ".$this->entity->getRepositoryPath('QMatchRelation')." q ".
                "where q.pid=:match and q.awayteam=:away");
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
                        $this->entity->getRepositoryPath('Match')." m ".
                "where cat.pid=:tournament and ".
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
    
    public function listMatchesByGroupPlayground($groupid, $playgroundid) {
        $matchList = $this->queryMatchListWithGroupPlayground($groupid, $playgroundid);
        $qmatchList = $this->queryQMatchListWithGroupPlayground($groupid, $playgroundid);
        return $this->prepareAndSort($matchList, $qmatchList);
    }

    private function queryMatchListWithGroupPlayground($groupid, $playgroundid) {
        $qb = $this->em->createQuery(
                "select m.id as mid,m.matchno,m.date,m.time,".
                       "r.id as rid,r.awayteam,r.scorevalid,r.score,r.points,".
                       "t.id,t.name as team,t.division,c.country ".
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
    
    private function queryQMatchListWithGroupPlayground($groupid, $playgroundid) {
        $qb = $this->em->createQuery(
                "select m.id as mid,m.matchno,m.date,m.time,".
                       "q.id as rid,q.awayteam,q.rank,g.id as rgrp,g.name as gname ".
                "from ".$this->entity->getRepositoryPath('QMatchRelation')." q, ".
                        $this->entity->getRepositoryPath('Match')." m, ".
                        $this->entity->getRepositoryPath('Group')." g ".
                "where m.pid=:group and ".
                      "m.playground=:playground and ".
                      "q.pid=m.id and ".
                      "q.cid=g.id ".
                "order by m.id");
        $qb->setParameter('group', $groupid);
        $qb->setParameter('playground', $playgroundid);
        return $qb->getResult();
    }
    
    public function listMatchesByGroup($groupid) {
        $matchList = $this->queryMatchList($groupid);
        $qmatchList = $this->queryQMatchList($groupid);
        return $this->prepareAndSort($matchList, $qmatchList);
    }

    private function queryMatchList($groupid) {
        $qb = $this->em->createQuery(
                "select m.id as mid,m.matchno,m.date,m.time,".
                       "p.id as pid,p.no,p.name as playground,".
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
        return $qb->getResult();
    }
    
    private function queryQMatchList($groupid) {
        $qb = $this->em->createQuery(
                "select m.id as mid,m.matchno,m.date,m.time,".
                       "p.id as pid,p.no,p.name as playground,".
                       "q.id as rid,q.awayteam,q.rank,g.id as rgrp,g.name as gname ".
                "from ".$this->entity->getRepositoryPath('QMatchRelation')." q, ".
                        $this->entity->getRepositoryPath('Match')." m, ".
                        $this->entity->getRepositoryPath('Playground')." p, ".
                        $this->entity->getRepositoryPath('Group')." g ".
                "where m.pid=:group and ".
                      "p.id=m.playground and ".
                      "q.pid=m.id and ".
                      "q.cid=g.id ".
                "order by m.id");
        $qb->setParameter('group', $groupid);
        return $qb->getResult();
    }
    
    public function listMatchesByPlaygroundDate($playgroundid, $date) {
        $matchdate = date_format($date, "d/m/Y");
        $matchList = $this->queryMatchListWithCategory($playgroundid, $matchdate);
        $qmatchList = $this->queryQMatchListWithCategory($playgroundid, $matchdate);
        return $this->prepareAndSort($matchList, $qmatchList);
    }
    
    private function queryMatchListWithCategory($playgroundid, $matchdate) {
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
        return $qb->getResult();
    }
    
    private function queryQMatchListWithCategory($playgroundid, $matchdate) {
        $qb = $this->em->createQuery(
                "select m.id as mid,m.matchno,m.date,m.time,".
                       "g.id as gid,g.name as grp,".
                       "cat.id as cid,cat.name as category,".
                       "q.id as rid,q.awayteam,q.rank,gq.id as rgrp,gq.name as gname ".
                "from ".$this->entity->getRepositoryPath('QMatchRelation')." q, ".
                        $this->entity->getRepositoryPath('Match')." m, ".
                        $this->entity->getRepositoryPath('Group')." g, ".
                        $this->entity->getRepositoryPath('Category')." cat, ".
                        $this->entity->getRepositoryPath('Group')." gq ".
                "where m.playground=:playground and ".
                      "m.pid=g.id and ".
                      "m.date=:date and ".
                      "g.pid=cat.id and ".
                      "q.pid=m.id and ".
                      "q.cid=gq.id ".
                "order by m.id");
        $qb->setParameter('playground', $playgroundid);
        $qb->setParameter('date', $matchdate);
        return $qb->getResult();
    }
    
    public function listMatchesByGroupTeam($groupid, $teamid) {
        $matchList = $this->queryMatchListWithGroupTeam($groupid, $teamid);
        // Normally a qualifying match can not be related to a team
        // However if the team actually has played the match this match will be returned
        $qmatchList = $this->queryQMatchListWithGroupTeam($groupid, $teamid);
        return $this->prepareAndSort($matchList, $qmatchList);
    }
    
    private function queryMatchListWithGroupTeam($groupid, $teamid) {
        $qb = $this->em->createQuery(
                "select m.id as mid,m.matchno,m.date,m.time,".
                       "p.id as pid,p.no,p.name as playground,".
                       "r.awayteam,r.scorevalid,r.score,r.points,".
                       "t.id,t.name as team,t.division,c.country ".
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
    
    private function queryQMatchListWithGroupTeam($groupid, $teamid) {
        $qb = $this->em->createQuery(
                "select m.id as mid,m.matchno,m.date,m.time,".
                       "p.id as pid,p.no,p.name as playground,".
                       "q.awayteam,q.rank,g.id as rgrp,g.name as gname ".
                "from ".$this->entity->getRepositoryPath('QMatchRelation')." q, ".
                        $this->entity->getRepositoryPath('Match')." m, ".
                        $this->entity->getRepositoryPath('Playground')." p, ".
                        $this->entity->getRepositoryPath('Group')." g ".
                "where m.pid=:group and ".
                      "m.id in (".
                            "select rx.pid ".
                            "from ".$this->entity->getRepositoryPath('MatchRelation')." rx ".
                            "where rx.cid=:team) and ".
                      "p.id=m.playground and ".
                      "q.pid=m.id and ".
                      "q.cid=g.id ".
                "order by m.id");
        $qb->setParameter('group', $groupid);
        $qb->setParameter('team', $teamid);
        return $qb->getResult();
    }
    
    public function listMatchesByPlayground($playgroundid) {
        $matchList = $this->queryMatchListWithPlayground($playgroundid);
        $qmatchList = $this->queryQMatchListWithPlayground($playgroundid);
        return $this->prepareAndSort($matchList, $qmatchList);
    }

    private function queryMatchListWithPlayground($playgroundid) {
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
                      "g.pid=cat.id and ".
                      "r.pid=m.id and ".
                      "t.id=r.cid and ".
                      "c.id=t.pid ".
                "order by m.id");
        $qb->setParameter('playground', $playgroundid);
        return $qb->getResult();
    }
    
    private function queryQMatchListWithPlayground($playgroundid) {
        $qb = $this->em->createQuery(
                "select m.id as mid,m.matchno,m.date,m.time,".
                       "g.id as gid,g.name as grp,".
                       "cat.id as cid,cat.name as category,".
                       "q.id as rid,q.awayteam,q.rank,gq.id as rgrp,gq.name as gname ".
                "from ".$this->entity->getRepositoryPath('QMatchRelation')." q, ".
                        $this->entity->getRepositoryPath('Match')." m, ".
                        $this->entity->getRepositoryPath('Group')." g, ".
                        $this->entity->getRepositoryPath('Category')." cat, ".
                        $this->entity->getRepositoryPath('Group')." gq ".
                "where m.playground=:playground and ".
                      "m.pid=g.id and ".
                      "g.pid=cat.id and ".
                      "q.pid=m.id and ".
                      "q.cid=gq.id ".
                "order by m.id");
        $qb->setParameter('playground', $playgroundid);
        return $qb->getResult();
    }
    
    public function listMatchesUnfinished($groupid) {
        $matchList = $this->queryUnfinishedMatchList($groupid);
        $matches = array();
        foreach ($matchList as $match) {
            if ($match['mrc'] == 0) {
                $matches[] = $this->prepareMatch($match);
            }
        }
        // Sort the matches based on schedule
        usort($matches, array("ICup\Bundle\PublicSiteBundle\Services\Doctrine\MatchSupport", "reorderMatch"));
        return $matches;
    }

    private function queryUnfinishedMatchList($groupid) {
        $qb = $this->em->createQuery(
                "select m.id as mid,m.matchno,m.date,m.time,".
                       "p.id as pid,p.no,p.name as playground,".
                       "count(r.id)+count(q.id) as mrc ".
                "from ".$this->entity->getRepositoryPath('Match')." m ".
                "join ".$this->entity->getRepositoryPath('Playground')." p ".
                "with m.playground=p.id ".
                "left outer join ".$this->entity->getRepositoryPath('MatchRelation')." r ".
                "with r.pid=m.id ".
                "left outer join ".$this->entity->getRepositoryPath('QMatchRelation')." q ".
                "with q.pid=m.id ".
                "where m.pid=:group ".
                "group by m.id");
        $qb->setParameter('group', $groupid);
        return $qb->getResult();
    }
    
    private function prepareMatch($match) {
        return array(
            'id' => $this->getValue($match, 'mid'),
            'matchno' => $match['matchno'],
            'schedule' => DateTime::createFromFormat('d/m/Y-H:i', $match['date'].'-'.str_replace(".", ":", $match['time'])),
            'playground' => array('no' => $this->getValue($match, 'no'),
                                  'name' => $this->getValue($match, 'playground'),
                                  'id' => $this->getValue($match, 'pid')),
            'category' => array('name' => $this->getValue($match, 'category'),
                                'id' => $this->getValue($match, 'cid')),
            'group' => array('name' => $this->getValue($match, 'grp'),
                             'id' => $this->getValue($match, 'gid')),
            'home' => array(
                'rid' => '',
                'id' => -1,
                'name' => '',
                'country' => 'EUR',
                'score' => '',
                'points' => '',
                'rank' => ''),
            'away' => array(
                'rid' => '',
                'id' => -1,
                'name' => '',
                'country' => 'EUR',
                'score' => '',
                'points' => '',
                'rank' => '')
        );
    }
}

<?php

namespace ICup\Bundle\PublicSiteBundle\Services\Doctrine;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\QMatchRelation;
use ICup\Bundle\PublicSiteBundle\Entity\MatchPlan;
use ICup\Bundle\PublicSiteBundle\Entity\QMatch;
use ICup\Bundle\PublicSiteBundle\Entity\TeamInfo;
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

    private $sort_matches;

    public function __construct(ContainerInterface $container, Logger $logger)
    {
        $this->container = $container;
        $this->entity = $container->get('entity');
        $this->logic = $container->get('logic');
        $this->em = $container->get('doctrine')->getManager();
        $this->logger = $logger;
        $this->sort_matches =
            function ($match1, $match2) {
                if ($match1['schedule'] == $match2['schedule']) {
                    return $match1['playground']['no'] - $match2['playground']['no'];
                }
                elseif ($match1['schedule'] > $match2['schedule']) {
                    return 1;
                }
                else {
                    return -1;
                }
            };
    }

    /**
     * @return callable
     */
    public function getSortMatches() {
        return $this->sort_matches;
    }
    
    private function prepareAndSort($matchdata, $qmatchdata = array(), $club_list = array()) {
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
                $match = $this->prepareMatchList($matchRelList);
                if ($this->validateTeams($club_list, $match)) {
                    $matches[] = $match;
                }
            }
        }
        // Sort the matches based on schedule
        usort($matches, $this->sort_matches);
        return $matches;
    }
    
    private function validateTeams($club_list, $match) {
        if (count($club_list) == 0) {
            return true;
        }
        return in_array($match['home']['clubid'], $club_list) || in_array($match['away']['clubid'], $club_list);
    }
    
    private function prepareMatchList($matchRelList) {
        $homeMatch = $this->flattenList($matchRelList['H']);
        $awayMatch = $this->flattenList($matchRelList['A']);
        return array(
            'id' => $this->getValue($homeMatch, 'mid'),
            'matchno' => $homeMatch['matchno'],
            'schedule' => DateTime::createFromFormat(
                    $this->container->getParameter('db_date_format').
                    '-'.
                    $this->container->getParameter('db_time_format'),
                    $homeMatch['date'].'-'.$homeMatch['time']),
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
            if ($qmatch['classification'] > 0) {
                $groupname = $this->container->get('translator')->trans('GROUPCLASS.'.$qmatch['classification'], array(), 'tournament');
            }
            else {
                $groupname = $this->container->get('translator')->trans('GROUP', array(), 'tournament');
            }
            $rankTxt = $this->container->get('translator')->
                            transChoice('RANK', $qmatch['rank'],
                                    array('%rank%' => $qmatch['rank'],
                                          '%group%' => strtolower($groupname).' '.$qmatch['gname']), 'tournament');
            $matchRec['rank'] = $rankTxt;
            $matchRec['team'] = '';
            $matchRec['division'] = '';
            $matchRec['scorevalid'] = 'N';
            $matchRec['id'] = -1;
            $matchRec['country'] = 'UNK';
            $matchRec['rgrp'] = $qmatch['rgrp'];
            $matchRec['qid'] = $qmatch['rgrp']*10 + $qmatch['rank'];
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
            'clubid' => $this->getValue($rel, 'clubid'),
            'id' => $rel['id'],
            'name' => $name,
            'country' => $rel['country'],
            'score' => $valid ? $rel['score'] : '',
            'points' => $valid ? $rel['points'] : '',
            'rank' => $this->getValue($rel, 'rank'),
            'qid' => $this->getValue($rel, 'qid'),
            'rgrp' => $this->getValue($rel, 'rgrp'),
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

    public function disqualify(&$relA, &$relB) {
        $relA->setScore(6);
        $relB->setScore(0);
        $this->updatePoints($relA, $relB);
    }

    public function updatePoints(&$relA, &$relB) {
        $looserPoints = 0;
        $tiePoints = 1;
        $winnerPoints = 3;
        if ($relA->getScore() > $relB->getScore()) {
            $relA->setPoints($winnerPoints);
            $relB->setPoints($looserPoints);
        }
        else if ($relA->getScore() < $relB->getScore()) {
            $relA->setPoints($looserPoints);
            $relB->setPoints($winnerPoints);
        }
        else {
            $relA->setPoints($tiePoints);
            $relB->setPoints($tiePoints);
        }
    }

    public function getMatchRelationDetails($matchid, $away) {
        $qb = $this->em->createQuery(
            "select r.id as rid,r.awayteam,r.scorevalid,r.score,r.points,".
            "t.id,t.name as team,t.division,c.country ".
            "from ".$this->entity->getRepositoryPath('MatchRelation')." r, ".
                    $this->entity->getRepositoryPath('Team')." t, ".
                    $this->entity->getRepositoryPath('Club')." c ".
            "where r.pid=:match and r.awayteam=:away and ".
            "t.id=r.cid and ".
            "c.id=t.pid ".
            "order by r.awayteam");
        $qb->setParameter('match', $matchid);
        $qb->setParameter('away', $away ? 'Y' : 'N');
        return $qb->getOneOrNullResult();
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

    public function getMatchDate($tournamentid, DateTime $date) {
        $qb = $this->em->createQuery(
            "select min(m.date) as date ".
            "from ".$this->entity->getRepositoryPath('Match')." m, ".
                    $this->entity->getRepositoryPath('Group')." g, ".
                    $this->entity->getRepositoryPath('Category')." c ".
            "where c.pid=:tournament and g.pid=c.id and m.pid=g.id and m.date>=:date");
        $qb->setParameter('tournament', $tournamentid);
        $qb->setParameter('date', Date::getDate($date));
        $mdate = $qb->getOneOrNullResult();
        if (!$mdate['date']) {
            $qb = $this->em->createQuery(
                "select max(m.date) as date ".
                "from ".$this->entity->getRepositoryPath('Match')." m, ".
                        $this->entity->getRepositoryPath('Group')." g, ".
                        $this->entity->getRepositoryPath('Category')." c ".
                "where c.pid=:tournament and g.pid=c.id and m.pid=g.id");
            $qb->setParameter('tournament', $tournamentid);
            $mdate = $qb->getOneOrNullResult();
        }
        return $mdate['date'] ? Date::getDateTime($mdate['date']) : null;
    }

    public function listMatchCalendar($tournamentid) {
        $qb = $this->em->createQuery(
                "select distinct a.date ".
                "from ".$this->entity->getRepositoryPath('PlaygroundAttribute')." a, ".
                        $this->entity->getRepositoryPath('Playground')." p, ".
                        $this->entity->getRepositoryPath('Site')." s ".
                "where s.pid=:tournament and p.pid=s.id and a.pid=p.id");
        $qb->setParameter('tournament', $tournamentid);
        $matchList = array();
        foreach ($qb->getResult() as $date) {
            $matchdate = Date::getDateTime($date['date']);
            $matchList[] = $matchdate;
        }
        return $matchList;
    }
    
    public function listMatchesUnresolved($tournamentid) {
        $qb = $this->em->createQuery(
                "select m ".
                "from ".$this->entity->getRepositoryPath('Category')." c, ".
                        $this->entity->getRepositoryPath('Group')." g, ".
                        $this->entity->getRepositoryPath('Match')." m ".
                "where c.pid=:tournament and ".
                      "g.pid=c.id and ".
                      "m.pid=g.id and ".
                      "m.id not in (select r.pid ".
                                   "from ".$this->entity->getRepositoryPath('MatchRelation')." r)");
        $qb->setParameter('tournament', $tournamentid);
        return $qb->getResult();
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
                       "q.id as rid,q.awayteam,q.rank,g.id as rgrp,g.name as gname,g.classification ".
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
                       "q.id as rid,q.awayteam,q.rank,g.id as rgrp,g.name as gname,g.classification ".
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
        $matchdate = date_format($date, $this->container->getParameter('db_date_format'));
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
                       "q.id as rid,q.awayteam,q.rank,gq.id as rgrp,gq.name as gname,gq.classification ".
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
                       "q.awayteam,q.rank,g.id as rgrp,g.name as gname,g.classification ".
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
    
    public function listMatchesByTeam($teamid) {
        $matchList = $this->queryMatchListWithTeam($teamid);
        // Normally a qualifying match can not be related to a team
        // However if the team actually has played the match this match will be returned
        $qmatchList = $this->queryQMatchListWithTeam($teamid);
        return $this->prepareAndSort($matchList, $qmatchList);
    }
    
    private function queryMatchListWithTeam($teamid) {
        $qb = $this->em->createQuery(
                "select m.id as mid,m.matchno,m.date,m.time,".
                       "p.id as pid,p.no,p.name as playground,".
                       "g.id as gid,g.name as grp,".
                       "cat.id as cid,cat.name as category,".
                       "r.awayteam,r.scorevalid,r.score,r.points,".
                       "t.id,t.name as team,t.division,c.country ".
                "from ".$this->entity->getRepositoryPath('MatchRelation')." r, ".
                        $this->entity->getRepositoryPath('Match')." m, ".
                        $this->entity->getRepositoryPath('Playground')." p, ".
                        $this->entity->getRepositoryPath('Group')." g, ".
                        $this->entity->getRepositoryPath('Category')." cat, ".
                        $this->entity->getRepositoryPath('Team')." t, ".
                        $this->entity->getRepositoryPath('Club')." c ".
                "where m.id in (".
                            "select rx.pid ".
                            "from ".$this->entity->getRepositoryPath('MatchRelation')." rx ".
                            "where rx.cid=:team) and ".
                      "p.id=m.playground and ".
                      "r.pid=m.id and ".
                      "t.id=r.cid and ".
                      "c.id=t.pid and ".
                      "g.id=m.pid and ".
                      "cat.id=g.pid ".
                "order by m.id");
        $qb->setParameter('team', $teamid);
        return $qb->getResult();
    }
    
    private function queryQMatchListWithTeam($teamid) {
        $qb = $this->em->createQuery(
                "select m.id as mid,m.matchno,m.date,m.time,".
                       "p.id as pid,p.no,p.name as playground,".
                       "g.id as gid,g.name as grp,".
                       "cat.id as cid,cat.name as category,".
                       "q.awayteam,q.rank,gx.id as rgrp,gx.name as gname,gx.classification ".
                "from ".$this->entity->getRepositoryPath('QMatchRelation')." q, ".
                        $this->entity->getRepositoryPath('Match')." m, ".
                        $this->entity->getRepositoryPath('Playground')." p, ".
                        $this->entity->getRepositoryPath('Group')." g, ".
                        $this->entity->getRepositoryPath('Category')." cat, ".
                        $this->entity->getRepositoryPath('Group')." gx ".
                "where m.id in (".
                            "select rx.pid ".
                            "from ".$this->entity->getRepositoryPath('MatchRelation')." rx ".
                            "where rx.cid=:team) and ".
                      "p.id=m.playground and ".
                      "q.pid=m.id and ".
                      "q.cid=gx.id and ".
                      "g.id=m.pid and ".
                      "cat.id=g.pid ".
                "order by m.id");
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
                       "q.id as rid,q.awayteam,q.rank,gq.id as rgrp,gq.name as gname,gq.classification ".
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
    
    public function listMatchesLimitedWithTournament($tournamentid, $date, $before = 10, $after = 3, $club_list = array()) {
        $matchList = $this->listMatchesByTournament($tournamentid, $club_list);
        $matchesMaster = array();
        $maxElements = $before;
        $maxElementsWithFuture = $before+$after;
        foreach ($matchList as $match) {
            $timeDiff = round(($match['schedule']->getTimestamp() - $date->getTimestamp()) / 60.2);
            if ($timeDiff <= 0) {
                array_push($matchesMaster, $match);
                if (count($matchesMaster) > $maxElements) {
                    array_shift($matchesMaster);
                }
            }
            else {
                if (count($matchesMaster) >= $maxElementsWithFuture) {
                    break;
                }
                array_push($matchesMaster, $match);
            }
        }
        return $matchesMaster;
    }
    
    public function listMatchesByTournament($tournamentid, $club_list = array()) {
        $matchList = $this->queryMatchListWithTournament($tournamentid);
        $qmatchList = $this->queryQMatchListWithTournament($tournamentid);
        return $this->prepareAndSort($matchList, $qmatchList, $club_list);
    }

    public function listMatchesByDate($tournamentid, $date, $club_list = array()) {
        $matchList = $this->queryMatchListWithTournament($tournamentid, Date::getDate($date));
        $qmatchList = $this->queryQMatchListWithTournament($tournamentid, Date::getDate($date));
        return $this->prepareAndSort($matchList, $qmatchList, $club_list);
    }

    /*
        private function queryMatchNearestDayWithTournament($tournamentid, $matchdate, $club_set = "") {
            $qb = $this->em->createQuery(
                    "select distinct m.date,m.time ".
                    "from ".$this->entity->getRepositoryPath('Match')." m, ".
                            $this->entity->getRepositoryPath('Group')." g, ".
                            $this->entity->getRepositoryPath('Category')." cat, ".
                            $this->entity->getRepositoryPath('MatchRelation')." r, ".
                            $this->entity->getRepositoryPath('Team')." t, ".
                            $this->entity->getRepositoryPath('Club')." c ".
                    "where cat.pid=:tournament and ".
                          "g.pid=cat.id and ".
                          "m.pid=g.id and ".
                          "r.pid=m.id and ".
                          "t.id=r.cid and ".
                          "c.id=t.pid ".
                          $club_set.
                    "order by m.date desc,m.time desc");
            $qb->setParameter('tournament', $tournamentid);
            $results = $qb->getResult();
            $date = $matchdate;
            foreach ($results as $rec) {
                $date = DateTime::createFromFormat(
                            $this->container->getParameter('db_date_format').
                            '-'.
                            $this->container->getParameter('db_time_format'),
                            $rec['date'].'-'.$rec['time']);
                if ($date <= $matchdate) break;
            }
            return $date;
        }
    */
    private function queryMatchListWithTournament($tournamentid, $date = null) {
        $qb = $this->em->createQuery(
                "select m.id as mid,m.matchno,m.date,m.time,".
                       "p.id as pid,p.no,p.name as playground,".
                       "g.id as gid,g.name as grp,".
                       "cat.id as cid,cat.name as category,".
                       "r.id as rid,r.awayteam,r.scorevalid,r.score,r.points,".
                       "t.id,t.name as team,t.division,c.country,c.id as clubid ".
                "from ".$this->entity->getRepositoryPath('MatchRelation')." r, ".
                        $this->entity->getRepositoryPath('Match')." m, ".
                        $this->entity->getRepositoryPath('Group')." g, ".
                        $this->entity->getRepositoryPath('Category')." cat, ".
                        $this->entity->getRepositoryPath('Playground')." p, ".
                        $this->entity->getRepositoryPath('Team')." t, ".
                        $this->entity->getRepositoryPath('Club')." c ".
                "where cat.pid=:tournament and ".
                      "g.pid=cat.id and ".
                      "m.pid=g.id and ".
                      "p.id=m.playground and ".
                      "r.pid=m.id and ".
                      "t.id=r.cid and ".
                      "c.id=t.pid ".
                      ($date ? "and m.date='".$date."' " : "").
                "order by m.id");
        $qb->setParameter('tournament', $tournamentid);
        return $qb->getResult();
    }
    
    private function queryQMatchListWithTournament($tournamentid, $date = null) {
        $qb = $this->em->createQuery(
                "select m.id as mid,m.matchno,m.date,m.time,".
                       "p.id as pid,p.no,p.name as playground,".
                       "g.id as gid,g.name as grp,".
                       "cat.id as cid,cat.name as category,".
                       "q.id as rid,q.awayteam,q.rank,gq.id as rgrp,gq.name as gname,gq.classification ".
                "from ".$this->entity->getRepositoryPath('QMatchRelation')." q, ".
                        $this->entity->getRepositoryPath('Match')." m, ".
                        $this->entity->getRepositoryPath('Group')." g, ".
                        $this->entity->getRepositoryPath('Category')." cat, ".
                        $this->entity->getRepositoryPath('Playground')." p, ".
                        $this->entity->getRepositoryPath('Group')." gq ".
                "where cat.pid=:tournament and ".
                      "g.pid=cat.id and ".
                      "m.pid=g.id and ".
                      "p.id=m.playground and ".
                      "q.pid=m.id and ".
                      "q.cid=gq.id ".
                      ($date ? "and m.date='".$date."' " : "").
                "order by m.id");
        $qb->setParameter('tournament', $tournamentid);
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
        usort($matches, $this->sort_matches);
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
            'schedule' => DateTime::createFromFormat(
                    $this->container->getParameter('db_date_format').
                    '-'.
                    $this->container->getParameter('db_time_format'),
                    $match['date'].'-'.$match['time']),
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

    public function listOpenQMatchesByTournament($tournamentid) {
        $qb = $this->em->createQuery(
            "select m.id as mid,m.matchno,m.date,m.time,".
            "p.id as pid,".
            "g.id as gid,".
            "q.id as rid,q.awayteam,q.rank,gq.id as rgrp ".
            "from ".$this->entity->getRepositoryPath('QMatchRelation')." q, ".
                    $this->entity->getRepositoryPath('MatchRelation')." r, ".
                    $this->entity->getRepositoryPath('Match')." m, ".
                    $this->entity->getRepositoryPath('Group')." g, ".
                    $this->entity->getRepositoryPath('Category')." cat, ".
                    $this->entity->getRepositoryPath('Playground')." p, ".
                    $this->entity->getRepositoryPath('Group')." gq ".
            "where cat.pid=:tournament and ".
            "g.pid=cat.id and ".
            "m.pid=g.id and ".
            "p.id=m.playground and ".
            "q.pid=m.id and ".
            "q.cid=gq.id and ".
            "(select count(r.pid) from matchrelations r where r.pid=m.id) = 0 ".
            "order by m.id");
        $qb->setParameter('tournament', $tournamentid);
        // Collect match relations as matches
        $matchList = array();
        foreach ($qb->getResult() as $qmatch) {
            $matchList[$qmatch['matchno']][$qmatch['awayteam']=='Y'?'A':'H'] = $qmatch;
        }
        // Prepare match list for output
        $matches = array();
        foreach ($matchList as $matchRelList) {
            // There must be two relations for each match - otherwise ignore the match
            if (count($matchRelList) == 2) {
                $match = new QMatch();
                $match->setId($matchRelList['H']['mid']);
                $match->setMatchno($matchRelList['H']['matchno']);
                $match->setPid($matchRelList['H']['gid']);
                $match->setPlayground($matchRelList['H']['pid']);
                $match->setDate($matchRelList['H']['date']);
                $match->setTime($matchRelList['H']['time']);
                $match->setGroupA($matchRelList['H']['rgrp']);
                $match->setGroupB($matchRelList['A']['rgrp']);
                $match->setRankA($matchRelList['H']['rank']);
                $match->setRankB($matchRelList['A']['rank']);
                $matches[] = $match;
            }
        }
        // Sort the matches based on schedule
        usort($matches, function (QMatch $match1, QMatch $match2) {
            $p1 = $match2->getPid() - $match1->getPid();
            $p2 = $match2->getDate() - $match1->getDate();
            $p3 = $match2->getPlayground() - $match1->getPlayground();
            $p4 = $match2->getTime() - $match1->getTime();
            if ($p1==0 && $p2==0 && $p3==0 && $p4==0) {
                return 0;
            }
            elseif ($p1 < 0 || ($p1==0 && $p2 < 0) || ($p1==0 && $p2==0 && $p3 < 0) || ($p1==0 && $p2==0 && $p3==0 && $p4 < 0)) {
                return 1;
            }
            else {
                return -1;
            }
        });
        return $matches;
    }
}

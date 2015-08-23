<?php

namespace ICup\Bundle\PublicSiteBundle\Services\Doctrine;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\QMatchRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
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

    public static $HOME = false;
    public static $AWAY = true;

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
            'id' => $homeMatch['mid'],
            'matchno' => $homeMatch['matchno'],
            'schedule' => Date::getDateTime($homeMatch['date'], $homeMatch['time']),
            'playground' => array('no' => $homeMatch['no'],
                                  'name' => $homeMatch['playground'],
                                  'id' => $homeMatch['pid']),
            'category' => array('name' => $homeMatch['category'],
                                'tip' =>  $this->container->get('translator')->trans('CATEGORY', array(), 'tournament')." ".
                                          $homeMatch['category']." - ".
                                          $this->container->get('translator')->transChoice(
                                                        'GENDER.'.$homeMatch['gender'].$homeMatch['cls'],
                                                        $homeMatch['age'],
                                                        array('%age%' => $homeMatch['age']), 'tournament'),
                                'id' => $homeMatch['cid']),
            'group' => array('name' => $homeMatch['grp'],
                             'id' => $homeMatch['gid']),
            'home' => $this->samplePart($homeMatch),
            'away' => $this->samplePart($awayMatch)
        );
    }

    private function getFieldList() {
        return
        // Match fields
            "m.id as mid,".
            "m.matchno,".
            "m.date,".
            "m.time,".
        // Playground fields
            "p.id as pid,".
            "p.no,".
            "p.name as playground,".
        // Group fields
            "g.id as gid,".
            "g.name as grp,".
        // Category fields
            "cat.id as cid,".
            "cat.name as category,".
            "cat.gender,".
            "cat.classification as cls,".
            "cat.age,".
        // MatchRelation fields
            "r.id as rid,".
            "r.awayteam,".
            "r.scorevalid,".
            "r.score,".
            "r.points,".
        // Team fields
            "t.id,".
            "t.name as team,".
            "t.division,".
        // Club fields
            "c.country,".
            "c.id as clubid ";
    }

    private function getQFieldList() {
        return
            // Match fields
            "m.id as mid,".
            "m.matchno,".
            "m.date,".
            "m.time,".
            // Playground fields
            "p.id as pid,".
            "p.no,".
            "p.name as playground,".
            // Group fields
            "g.id as gid,".
            "g.name as grp,".
            // Category fields
            "cat.id as cid,".
            "cat.name as category,".
            "cat.gender,".
            "cat.classification as cls,".
            "cat.age,".
            // QMatchRelation fields
            "q.id as qrid,".
            "q.awayteam,".
            "q.rank,".
            // Group (related) fields
            "gq.id as rgrp,".
            "gq.name as gname,".
            "gq.classification ";
    }

    private function getTableList() {
        return
            $this->entity->getRepositoryPath('MatchRelation')." r, ".
            $this->entity->getRepositoryPath('Match')." m, ".
            $this->entity->getRepositoryPath('Playground')." p, ".
            $this->entity->getRepositoryPath('Group')." g, ".
            $this->entity->getRepositoryPath('Category')." cat, ".
            $this->entity->getRepositoryPath('Team')." t, ".
            $this->entity->getRepositoryPath('Club')." c ";
    }

    private function getQTableList() {
        return
            $this->entity->getRepositoryPath('QMatchRelation')." q, ".
            $this->entity->getRepositoryPath('Match')." m, ".
            $this->entity->getRepositoryPath('Playground')." p, ".
            $this->entity->getRepositoryPath('Group')." g, ".
            $this->entity->getRepositoryPath('Category')." cat, ".
            $this->entity->getRepositoryPath('Group')." gq ";
    }

    private function getTableRelations() {
        return
            "g.category=cat.id and ".
            "m.group=g.id and ".
            "m.playground=p.id and ".
            "m.id=r.match and ".
            "t.id=r.team and ".
            "c.id=t.club ";
    }

    private function getQTableRelations() {
        return
            "g.category=cat.id and ".
            "m.group=g.id and ".
            "m.playground=p.id and ".
            "m.id=q.match and ".
            "gq.id=q.group ";
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
        $name = $this->logic->getTeamName($rel['team'], $rel['division']);
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
                "where m.group=g.id and ".
                      "m.matchno=:matchno and ".
                      "g.category=c.id and ".
                      "c.tournament=:tournament");
        $qb->setParameter('tournament', $tournamentid);
        $qb->setParameter('matchno', $matchno);
        return $qb->getOneOrNullResult();
    }
    
    public function isMatchResultValid($matchid) {
        $qb = $this->em->createQuery(
                "select count(r) as results ".
                "from ".$this->entity->getRepositoryPath('MatchRelation')." r ".
                "where r.match=:match and r.scorevalid='Y'");
        $qb->setParameter('match', $matchid);
        $results = $qb->getOneOrNullResult();
        return $results != null ? $results['results'] == 2 : false;
    }

    public function disqualify(Tournament $tournament, &$relA, &$relB) {
        $relA->setScore($tournament->getOption()->getDscore());
        $relB->setScore(0);
        $this->updatePoints($tournament, $relA, $relB);
    }

    public function updatePoints(Tournament $tournament, &$relA, &$relB) {
        $looserPoints = $tournament->getOption()->getLpoints();
        $tiePoints = $tournament->getOption()->getTpoints();
        $winnerPoints = $tournament->getOption()->getWpoints();
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
            "where r.match=:match and r.awayteam=:away and ".
            "t.id=r.team and ".
            "c.id=t.club ".
            "order by r.awayteam");
        $qb->setParameter('match', $matchid);
        $qb->setParameter('away', $away ? 'Y' : 'N');
        return $qb->getOneOrNullResult();
    }

    public function getMatchRelationByMatch($matchid, $away) {
        $qb = $this->em->createQuery(
                "select r ".
                "from ".$this->entity->getRepositoryPath('MatchRelation')." r ".
                "where r.match=:match and r.awayteam=:away");
        $qb->setParameter('match', $matchid);
        $qb->setParameter('away', $away ? 'Y' : 'N');
        return $qb->getOneOrNullResult();
    }
     
    public function getQMatchRelationByMatch($matchid, $away) {
        $qb = $this->em->createQuery(
                "select q ".
                "from ".$this->entity->getRepositoryPath('QMatchRelation')." q ".
                "where q.match=:match and q.awayteam=:away");
        $qb->setParameter('match', $matchid);
        $qb->setParameter('away', $away ? 'Y' : 'N');
        return $qb->getOneOrNullResult();
    }

    public function getMatchHomeTeam($matchid) {
        /* @var $matchRel MatchRelation */
        $matchRel = $this->getMatchRelationByMatch($matchid, false);
        return $matchRel != null ? $matchRel->getTeam() : 0;
    }
    
    public function getMatchAwayTeam($matchid) {
        /* @var $matchRel MatchRelation */
        $matchRel = $this->getMatchRelationByMatch($matchid, true);
        return $matchRel != null ? $matchRel->getTeam() : 0;
    }

    public function getMatchDate($tournamentid, DateTime $date) {
        $qb = $this->em->createQuery(
            "select min(m.date) as date ".
            "from ".$this->entity->getRepositoryPath('Match')." m, ".
                    $this->entity->getRepositoryPath('Group')." g, ".
                    $this->entity->getRepositoryPath('Category')." c ".
            "where c.tournament=:tournament and g.category=c.id and m.group=g.id and m.date>=:date");
        $qb->setParameter('tournament', $tournamentid);
        $qb->setParameter('date', Date::getDate($date));
        $mdate = $qb->getOneOrNullResult();
        if (!$mdate['date']) {
            $qb = $this->em->createQuery(
                "select max(m.date) as date ".
                "from ".$this->entity->getRepositoryPath('Match')." m, ".
                        $this->entity->getRepositoryPath('Group')." g, ".
                        $this->entity->getRepositoryPath('Category')." c ".
                "where c.tournament=:tournament and g.category=c.id and m.group=g.id");
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
                "where s.tournament=:tournament and p.site=s.id and a.playground=p.id");
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
                "where c.tournament=:tournament and ".
                      "g.category=c.id and ".
                      "m.group=g.id and ".
                      "m.id not in (select r.match ".
                                   "from ".$this->entity->getRepositoryPath('MatchRelation')." r)");
        $qb->setParameter('tournament', $tournamentid);
        return $qb->getResult();
    }

    /*
     * MATCH QUERY FUNCTIONS
     */

    public function listMatchByNo($tournamentid, $matchno) {
        $matchList = $this->queryMatchListWithMatchNo($tournamentid, $matchno);
        $qmatchList = $this->queryQMatchListWithMatchNo($tournamentid, $matchno);
        return $this->prepareAndSort($matchList, $qmatchList);
    }

    private function queryMatchListWithMatchNo($tournamentid, $matchno) {
        $qb = $this->em->createQuery(
            "select ".$this->getFieldList().
            "from ".$this->getTableList().
            "where ".$this->getTableRelations().
                   " and m.matchno=:matchno and cat.tournament=:tournament ".
            "order by m.id");
        $qb->setParameter('matchno', $matchno);
        $qb->setParameter('tournament', $tournamentid);
        return $qb->getResult();
    }

    private function queryQMatchListWithMatchNo($tournamentid, $matchno) {
        $qb = $this->em->createQuery(
            "select ".$this->getQFieldList().
            "from ".$this->getQTableList().
            "where ".$this->getQTableRelations().
                    " and m.matchno=:matchno and cat.tournament=:tournament ".
            "order by m.id");
        $qb->setParameter('matchno', $matchno);
        $qb->setParameter('tournament', $tournamentid);
        return $qb->getResult();
    }

    public function listMatchesByGroupPlayground($groupid, $playgroundid, DateTime $date = null) {
        $matchList = $this->queryMatchListWithGroupPlayground($groupid, $playgroundid, $date ? Date::getDate($date) : null);
        $qmatchList = $this->queryQMatchListWithGroupPlayground($groupid, $playgroundid, $date ? Date::getDate($date) : null);
        return $this->prepareAndSort($matchList, $qmatchList);
    }

    private function queryMatchListWithGroupPlayground($groupid, $playgroundid, $date) {
        $qb = $this->em->createQuery(
                "select ".$this->getFieldList().
                "from ".$this->getTableList().
                "where ".$this->getTableRelations().
                        " and m.group=:group and m.playground=:playground ".
                      ($date ? "and m.date='".$date."' " : "").
                "order by m.id");
        $qb->setParameter('group', $groupid);
        $qb->setParameter('playground', $playgroundid);
        return $qb->getResult();
    }
    
    private function queryQMatchListWithGroupPlayground($groupid, $playgroundid, $date) {
        $qb = $this->em->createQuery(
                "select ".$this->getQFieldList().
                "from ".$this->getQTableList().
                "where ".$this->getQTableRelations().
                        " and m.group=:group and m.playground=:playground ".
                      ($date ? "and m.date='".$date."' " : "").
                "order by m.id");
        $qb->setParameter('group', $groupid);
        $qb->setParameter('playground', $playgroundid);
        return $qb->getResult();
    }
    
    public function listMatchesByGroup($groupid, DateTime $date = null) {
        $matchList = $this->queryMatchList($groupid, $date ? Date::getDate($date) : null);
        $qmatchList = $this->queryQMatchList($groupid, $date ? Date::getDate($date) : null);
        return $this->prepareAndSort($matchList, $qmatchList);
    }

    private function queryMatchList($groupid, $date) {
        $qb = $this->em->createQuery(
                "select ".$this->getFieldList().
                "from ".$this->getTableList().
                "where ".$this->getTableRelations().
                        " and m.group=:group ".
                      ($date ? "and m.date='".$date."' " : "").
                "order by m.id");
        $qb->setParameter('group', $groupid);
        return $qb->getResult();
    }
    
    private function queryQMatchList($groupid, $date) {
        $qb = $this->em->createQuery(
                "select ".$this->getQFieldList().
                "from ".$this->getQTableList().
                "where ".$this->getQTableRelations().
                        " and m.group=:group ".
                      ($date ? "and m.date='".$date."' " : "").
                "order by m.id");
        $qb->setParameter('group', $groupid);
        return $qb->getResult();
    }
    
    public function listMatchesByPlaygroundDate($playgroundid, DateTime $date) {
        $matchList = $this->queryMatchListWithCategory($playgroundid, Date::getDate($date));
        $qmatchList = $this->queryQMatchListWithCategory($playgroundid, Date::getDate($date));
        return $this->prepareAndSort($matchList, $qmatchList);
    }
    
    private function queryMatchListWithCategory($playgroundid, $matchdate) {
        $qb = $this->em->createQuery(
                "select ".$this->getFieldList().
                "from ".$this->getTableList().
                "where ".$this->getTableRelations().
                        " and m.playground=:playground and m.date=:date ".
                "order by m.id");
        $qb->setParameter('playground', $playgroundid);
        $qb->setParameter('date', $matchdate);
        return $qb->getResult();
    }
    
    private function queryQMatchListWithCategory($playgroundid, $matchdate) {
        $qb = $this->em->createQuery(
                "select ".$this->getQFieldList().
                "from ".$this->getQTableList().
                "where ".$this->getQTableRelations().
                        " and m.playground=:playground and m.date=:date ".
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
                "select ".$this->getFieldList().
                "from ".$this->getTableList().
                "where ".$this->getTableRelations().
                        " and m.group=:group and ".
                          "m.id in (".
                                "select identity(rx.match) ".
                                "from ".$this->entity->getRepositoryPath('MatchRelation')." rx ".
                                "where rx.team=:team) ".
                "order by m.id");
        $qb->setParameter('group', $groupid);
        $qb->setParameter('team', $teamid);
        return $qb->getResult();
    }
    
    private function queryQMatchListWithGroupTeam($groupid, $teamid) {
        $qb = $this->em->createQuery(
                "select ".$this->getQFieldList().
                "from ".$this->getQTableList().
                "where ".$this->getQTableRelations().
                        " and m.group=:group and ".
                          "m.id in (".
                                "select identity(rx.match) ".
                                "from ".$this->entity->getRepositoryPath('MatchRelation')." rx ".
                                "where rx.team=:team) ".
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
                "select ".$this->getFieldList().
                "from ".$this->getTableList().
                "where ".$this->getTableRelations().
                        " and m.id in (".
                                "select identity(rx.match) ".
                                "from ".$this->entity->getRepositoryPath('MatchRelation')." rx ".
                                "where rx.team=:team) ".
                "order by m.id");
        $qb->setParameter('team', $teamid);
        return $qb->getResult();
    }
    
    private function queryQMatchListWithTeam($teamid) {
        $qb = $this->em->createQuery(
                "select ".$this->getQFieldList().
                "from ".$this->getQTableList().
                "where ".$this->getQTableRelations().
                        " and m.id in (".
                                "select identity(rx.match) ".
                                "from ".$this->entity->getRepositoryPath('MatchRelation')." rx ".
                                "where rx.team=:team) ".
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
                "select ".$this->getFieldList().
                "from ".$this->getTableList().
                "where ".$this->getTableRelations().
                        " and m.playground=:playground ".
                "order by m.id");
        $qb->setParameter('playground', $playgroundid);
        return $qb->getResult();
    }
    
    private function queryQMatchListWithPlayground($playgroundid) {
        $qb = $this->em->createQuery(
                "select ".$this->getQFieldList().
                "from ".$this->getQTableList().
                "where ".$this->getQTableRelations().
                        " and m.playground=:playground ".
                "order by m.id");
        $qb->setParameter('playground', $playgroundid);
        return $qb->getResult();
    }

    public function listMatchesByTournament($tournamentid, $club_list = array()) {
        $matchList = $this->queryMatchListWithTournament($tournamentid);
        $qmatchList = $this->queryQMatchListWithTournament($tournamentid);
        return $this->prepareAndSort($matchList, $qmatchList, $club_list);
    }

    public function listMatchesByDate($tournamentid, DateTime $date, $club_list = array()) {
        $matchList = $this->queryMatchListWithTournament($tournamentid, Date::getDate($date));
        $qmatchList = $this->queryQMatchListWithTournament($tournamentid, Date::getDate($date));
        return $this->prepareAndSort($matchList, $qmatchList, $club_list);
    }

    private function queryMatchListWithTournament($tournamentid, $date = null) {
        $qb = $this->em->createQuery(
                "select ".$this->getFieldList().
                "from ".$this->getTableList().
                "where ".$this->getTableRelations().
                        " and cat.tournament=:tournament ".
                      ($date ? "and m.date='".$date."' " : "").
                "order by m.id");
        $qb->setParameter('tournament', $tournamentid);
        return $qb->getResult();
    }
    
    private function queryQMatchListWithTournament($tournamentid, $date = null) {
        $qb = $this->em->createQuery(
                "select ".$this->getQFieldList().
                "from ".$this->getQTableList().
                "where ".$this->getQTableRelations().
                        " and cat.tournament=:tournament ".
                      ($date ? "and m.date='".$date."' " : "").
                "order by m.id");
        $qb->setParameter('tournament', $tournamentid);
        return $qb->getResult();
    }

    /*
     * OTHER..
     */

    public function listMatchesLimitedWithTournament($tournamentid, DateTime $date, $before = 10, $after = 3, $club_list = array()) {
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
                "with r.match=m.id ".
                "left outer join ".$this->entity->getRepositoryPath('QMatchRelation')." q ".
                "with q.match=m.id ".
                "where m.group=:group ".
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
                                'tip' => '',
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
            "where cat.tournament=:tournament and ".
            "g.category=cat.id and ".
            "m.group=g.id and ".
            "p.id=m.playground and ".
            "q.match=m.id and ".
            "q.group=gq.id and ".
            "(select count(r.match) from matchrelations r where r.match=m.id) = 0 ".
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

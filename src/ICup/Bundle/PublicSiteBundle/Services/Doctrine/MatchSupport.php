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
        $matchList = $this->queryMatchListSimple($groupid, $playgroundid);
        return $this->prepareAndSort($matchList);
    }

    private function queryMatchListSimple($groupid, $playgroundid) {
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
    
    private function prepareAndSort($matchdata) {
        // Collect match relations as matches
        $matchList = array();
        foreach ($matchdata as $match) {
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
        usort($matches, array("ICup\Bundle\PublicSiteBundle\Services\Doctrine\MatchSupport", "reorderMatch"));
        return $matches;
    }
    
    private function prepareMatchList($matchRelList) {
        $match = $matchRelList['H'];
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
            'home' => $this->samplePart($matchRelList['H']),
            'away' => $this->samplePart($matchRelList['A'])
        );
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

    public function listQMatchesByGroupPlayground($groupid, $playgroundid) {
        $qb = $this->em->createQuery(
                "select m.matchno,m.date,m.time,q.awayteam,q.rank,g.id as grp,g.name ".
                "from ".$this->entity->getRepositoryPath('QMatchRelation')." q, ".
                        $this->entity->getRepositoryPath('Match')." m, ".
                        $this->entity->getRepositoryPath('Group')." g ".
                "where m.pid=:group and ".
                      "m.playground=:playground and ".
                      "q.pid=m.id and ".
                      "q.assigned='N' and ".
                      "q.cid=g.id ".
                "order by m.id");
        $qb->setParameter('group', $groupid);
        $qb->setParameter('playground', $playgroundid);
        return $qb->getResult();
    }
    
    public function listMatchesByGroup($groupid) {
        $matchList = $this->queryMatchList($groupid);
        return $this->prepareAndSort($matchList);
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
    
    public function listMatchesByPlaygroundDate($playgroundid, $date) {
        $matchdate = date_format($date, "d/m/Y");
        $matchList = $this->queryMatchListWithCategory($playgroundid, $matchdate);
        return $this->prepareAndSort($matchList);
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
    
    public function listMatchesByGroupTeam($groupid, $teamid) {
        $matchList = $this->queryMatchListWithGroupTeam($groupid, $teamid);
        return $this->prepareAndSort($matchList);
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
    
    public function listMatchesByPlayground($playgroundid) {
        $matchList = $this->queryMatchListWithPlayground($playgroundid);
        return $this->prepareAndSort($matchList);
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
}

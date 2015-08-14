<?php

namespace ICup\Bundle\PublicSiteBundle\Services\Doctrine;

use DateTime;
use Doctrine\ORM\EntityManager;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Enrollment;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\GroupOrder;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchSchedule;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\PARelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Entity\TeamInfo;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;
use Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BusinessLogic
{
    /* @var $container ContainerInterface */
    protected $container;
    /* @var $em EntityManager */
    protected $em;
    /* @var $logger Logger */
    protected $logger;
   /* @var $entity Entity */
    protected $entity;

    public function __construct(ContainerInterface $container, Logger $logger)
    {
        $this->container = $container;
        $this->entity = $container->get('entity');
        $this->em = $container->get('doctrine')->getManager();
        $this->logger = $logger;
    }
    
    public function addEnrolled($categoryid, $clubid, $userid, $vacant = false) {
        $club = $this->entity->getClubById($clubid);
        $qb = $this->em->createQuery(
                "select e ".
                "from ".$this->entity->getRepositoryPath('Enrollment')." e, ".
                        $this->entity->getRepositoryPath('Team')." t ".
                "where e.pid=:category and e.cid=t.id and t.pid=:club ".
                "order by e.pid");
        $qb->setParameter('category', $categoryid);
        $qb->setParameter('club', $clubid);
        $enrolled = $qb->getResult();
 
        $noTeams = count($enrolled);
        if ($noTeams >= 26) {
            // Can not add more than 26 teams to same category - Team A -> Team Z
            throw new ValidationException("NOMORETEAMS", "More than 26 enrolled - club=".$clubid.", category=".$categoryid);
        }
        else if ($noTeams == 0) {
            $division = '';
        }
        else if ($noTeams == 1) {
            $division = 'B';
            $this->updateDivision(array_shift($enrolled), 'A');
        }
        else {
            $division = chr($noTeams + 65);
        }

        return $this->enrollTeam($categoryid, $userid,
                                 $clubid, $club->getName(), $division, $vacant);
    }
    
    public function deleteEnrolled($categoryid, $clubid) {
        $qb = $this->em->createQuery(
                "select e ".
                "from ".$this->entity->getRepositoryPath('Enrollment')." e, ".
                        $this->entity->getRepositoryPath('Team')." t ".
                "where e.pid=:category and e.cid=t.id and t.pid=:club ".
                "order by t.division");
        $qb->setParameter('category', $categoryid);
        $qb->setParameter('club', $clubid);
        $enrolled = $qb->getResult();
 
        $enroll = array_pop($enrolled);
        if ($enroll == null) {
            throw new ValidationException("NOTEAMS", "No teams enrolled - club=".$clubid.", category=".$categoryid);
        }
        // Verify that the team is not assigned to a group
        if ($this->isTeamAssigned($categoryid, $enroll->getCid())) {
            throw new ValidationException("TEAMASSIGNED", "Team was assigned previously - team=".$enroll->getCid().", category=".$categoryid);
        }
        // Remove the team entity enrolled in this category
        $team = $this->entity->getTeamById($enroll->getCid());
        $this->em->remove($team);
        // When clubs have more than one team enrolled in a category
        // the division must be defined for each team - A, B, C, ...
        // However for single teams the division must be blank.
        $noTeams = count($enrolled);
        if ($noTeams == 1) {
            // Only a single team is left enrolled for this club when change is committed
            // Get this lone team and change division to blank
            $this->updateDivision(array_shift($enrolled), '');
        }
        // Finally remove the enroll entity
        $this->em->remove($enroll);
        $this->em->flush();

        return $enroll;
    }

    public function removeEnrolled($teamid, $categoryid) {
        // Verify that the team is not assigned to a group
        if ($this->isTeamAssigned($categoryid, $teamid)) {
            throw new ValidationException("TEAMASSIGNED", "Team was assigned previously - team=".$teamid.", category=".$categoryid);
        }

        $qb = $this->em->createQuery(
            "select e ".
            "from ".$this->entity->getRepositoryPath('Enrollment')." e ".
            "where e.pid=:category and e.cid=:team");
        $qb->setParameter('category', $categoryid);
        $qb->setParameter('team', $teamid);
        $enroll = $qb->getOneOrNullResult();
        // Remove the team entity enrolled in this category
        $team = $this->entity->getTeamById($teamid);
        $clubid = $team->getPid();
        $this->em->remove($team);
        // Finally remove the enroll entity
        $this->em->remove($enroll);
        $this->em->flush();

        $enrolled = $this->listEnrolledTeamsByCategory($categoryid, $clubid);
        // When clubs have more than one team enrolled in a category
        // the division must be defined for each team - A, B, C, ...
        // However for single teams the division must be blank.
        $noTeams = count($enrolled);
        if ($noTeams == 1) {
            // Only a single team is left enrolled for this club when change is committed
            // Get this lone team and change division to blank
            $team = array_shift($enrolled);
            $team->setDivision('');
        }
        else {
            foreach ($enrolled as $idx => $team) {
                $division = chr($idx + 65);
                $team->setDivision($division);
            }
        }
        $this->em->flush();

        return $enrolled;
    }

    public function isTeamAssigned($categoryid, $teamid) {
        $qbt = $this->em->createQuery(
                "select count(o) as teams ".
                "from ".$this->entity->getRepositoryPath('Group')." g, ".
                        $this->entity->getRepositoryPath('GroupOrder')." o ".
                "where g.pid=:category and g.classification=0 and ".
                      "o.pid=g.id and ".
                      "o.cid=:team");
        $qbt->setParameter('category', $categoryid);
        $qbt->setParameter('team', $teamid);
        $teamsAssigned = $qbt->getOneOrNullResult();
        return $teamsAssigned != null ? $teamsAssigned['teams'] > 0 : false;
    }
    
    public function enrollTeam($categoryid, $userid, $clubid, $name, $division, $vacant = false) {
        $team = new Team();
        $team->setPid($clubid);
        $team->setName($name);
        $team->setColor('');
        $team->setDivision($division);
        $team->setVacant($vacant);
        $this->em->persist($team);
        $this->em->flush();

        $today = new DateTime();
        $enroll = new Enrollment();
        $enroll->setCid($team->getId());
        $enroll->setPid($categoryid);
        $enroll->setUid($userid);
        $enroll->setDate(Date::getDate($today));
        $this->em->persist($enroll);
        $this->em->flush();
        return $enroll;
    }
    
    private function updateDivision(Enrollment $enroll, $division) {
        $firstTeam = $this->entity->getTeamById($enroll->getCid());
        $firstTeam->setDivision($division);
        $this->em->flush();
    }

    public function assignCategory($categoryid, $playgroundattributeid, $matchtime, $finals) {
        // Verify that the category and playground share the same tournament
        $this->verifyRelation($categoryid, $playgroundattributeid);
        
        $parel = new PARelation();
        $parel->setPid($playgroundattributeid);
        $parel->setCid($categoryid);
        $parel->setFinals($finals);
        $parel->setMatchtime($matchtime);
        $this->em->persist($parel);
        $this->em->flush();
        return $parel;
    }
    
    public function removeAssignedCategory($categoryid, $playgroundattributeid) {
        // Verify that the category and playground share the same tournament
        $this->verifyRelation($categoryid, $playgroundattributeid);

        $parel = $this->entity->getPARelationRepo()->findOneBy(array('pid' => $playgroundattributeid, 'cid' => $categoryid));
        if ($parel == null) {
            throw new ValidationException("CATEGORYISNOTASSIGNED", "Category is not assigned - pattr=".$playgroundattributeid.", category=".$categoryid);
        }
        $this->em->remove($parel);
        $this->em->flush();
        return $parel;
    }

    private function verifyRelation($categoryid, $playgroundattributeid) {
        /* @var $category Category */
        $category = $this->entity->getCategoryById($categoryid);
        $pattr = $this->entity->getPlaygroundAttributeById($playgroundattributeid);
        $playground = $this->entity->getPlaygroundById($pattr->getPid());
        $site = $this->entity->getSiteById($playground->getPid());
        // Verify that the category and playground share the same tournament
        if ($site->getPid() != $category->getPid()) {
            throw new ValidationException("NOTTHESAMETOURNAMENT", "Category and playground does not share the same tournament - pattr=".$playgroundattributeid.", category=".$categoryid);
        }
    }    
    
    public function assignEnrolled($teamid, $groupid) {
        $group = $this->entity->getGroupById($groupid);
        // Verify that the team is not assigned to any group
        if ($this->isTeamAssigned($group->getPid(), $teamid)) {
            throw new ValidationException("TEAMASSIGNED", "Team was assigned previously - team=".$teamid.", category=".$group->getPid());
        }
        $groupOrder = $this->getFirstVacantAssigned($groupid);
        if (!$groupOrder) {
            $groupOrder = new GroupOrder();
            $groupOrder->setPid($groupid);
            $groupOrder->setCid($teamid);
            $this->em->persist($groupOrder);
        }
        else {
            $vacantTeamid = $groupOrder->getCid();
            $this->moveMatches($groupid, $vacantTeamid, $teamid);

            $groupOrder->setCid($teamid);
        }
        $this->em->flush();
        return $groupOrder;
    }

    private static $VACANT_CLUB_NAME = "VACANT";
    private static $VACANT_CLUB_COUNTRYCODE = "[V]";

    public function assignVacant($groupid, $userid) {
        $group = $this->entity->getGroupById($groupid);
        $club = $this->getClubByName(BusinessLogic::$VACANT_CLUB_NAME, BusinessLogic::$VACANT_CLUB_COUNTRYCODE);
        if ($club == null) {
            $club = new Club();
            $club->setName(BusinessLogic::$VACANT_CLUB_NAME);
            $club->setCountry(BusinessLogic::$VACANT_CLUB_COUNTRYCODE);
            $this->em->persist($club);
            $this->em->flush();
        }
        $enrolled = $this->addEnrolled($group->getPid(), $club->getId(), $userid, true);
        $groupOrder = new GroupOrder();
        $groupOrder->setCid($enrolled->getCid());
        $groupOrder->setPid($groupid);
        $this->em->persist($groupOrder);
        $this->em->flush();
        return $groupOrder;
    }

    public function getFirstVacantAssigned($groupid) {
        $qbt = $this->em->createQuery(
            "select o ".
            "from ".$this->entity->getRepositoryPath('GroupOrder')." o, ".
                    $this->entity->getRepositoryPath('Team')." t ".
            "where o.pid=:group and ".
            "o.cid=t.id and t.vacant='Y'");
        $qbt->setParameter('group', $groupid);
        $vacantTeams = $qbt->getResult();
        return array_shift($vacantTeams);
    }

    public function removeAssignment($teamid, $groupid) {
        // Verify that the team is assigned to the group
        $qb = $this->em->createQuery(
                "select o ".
                "from ".$this->entity->getRepositoryPath('Group')." g, ".
                        $this->entity->getRepositoryPath('GroupOrder')." o ".
                "where o.pid=:group and ".
                      "o.cid=:team");
        $qb->setParameter('group', $groupid);
        $qb->setParameter('team', $teamid);
        $groupOrder = $qb->getOneOrNullResult();
        if ($groupOrder == null) {
            throw new ValidationException("TEAMISNOTASSIGNED", "Team is not assigned - team=".$teamid.", group=".$groupid);
        }
        if ($this->isTeamActive($groupid, $teamid)) {
            throw new ValidationException("TEAMISACTIVE", "Team has matchresults - team=".$teamid.", group=".$groupid);
        }
        $this->em->remove($groupOrder);
        $this->em->flush();
        return $groupOrder;
    }

    public function moveMatches($groupid, $teamid, $target_teamid) {
        $qb = $this->em->createQuery(
            "update ".$this->entity->getRepositoryPath('MatchRelation')." r set r.cid=:target ".
            "where r.cid=:team and r.match in (".
            "select m.id ".
                "from ".$this->entity->getRepositoryPath('Match')." m ".
                "where m.group=:group)");
        $qb->setParameter('group', $groupid);
        $qb->setParameter('team', $teamid);
        $qb->setParameter('target', $target_teamid);
        $qb->getResult();
    }

    public function isTeamActive($groupid, $teamid) {
        $qb = $this->em->createQuery(
                "select count(r) as results ".
                "from ".$this->entity->getRepositoryPath('MatchRelation')." r, ".
                        $this->entity->getRepositoryPath('Match')." m ".
                "where r.match=m.id and m.group=:group and r.cid=:team ".
                "order by r.match");
        $qb->setParameter('group', $groupid);
        $qb->setParameter('team', $teamid);
        $results = $qb->getOneOrNullResult();
        return $results != null ? $results['results'] > 0 : false;
    }

    public function isTeamInGame($groupid, $teamid) {
        $qb = $this->em->createQuery(
            "select count(r) as results ".
            "from ".$this->entity->getRepositoryPath('MatchRelation')." r, ".
                    $this->entity->getRepositoryPath('Match')." m ".
            "where r.match=m.id and m.group=:group and r.cid=:team and r.scorevalid='Y'".
            "order by r.match");
        $qb->setParameter('group', $groupid);
        $qb->setParameter('team', $teamid);
        $results = $qb->getOneOrNullResult();
        return $results != null ? $results['results'] > 0 : false;
    }

    public function listAnyEnrolledByClub($clubid) {
        $qb = $this->em->createQuery(
                "select c.pid as tid,count(e) as enrolled ".
                "from ".$this->entity->getRepositoryPath('Enrollment')." e, ".
                        $this->entity->getRepositoryPath('Category')." c, ".
                        $this->entity->getRepositoryPath('Team')." t ".
                "where e.pid=c.id and e.cid=t.id and t.pid=:club ".
                "group by c.pid ".
                "order by c.pid asc");
        $qb->setParameter('club', $clubid);
        return $qb->getResult();
    }
        
    public function listEnrolled($tournamentid) {
        $qb = $this->em->createQuery(
                "select clb as club, count(e) as enrolled ".
                "from ".$this->entity->getRepositoryPath('Enrollment')." e, ".
                        $this->entity->getRepositoryPath('Category')." c, ".
                        $this->entity->getRepositoryPath('Team')." t, ".
                        $this->entity->getRepositoryPath('Club')." clb ".
                "where c.pid=:tournament and e.pid=c.id and e.cid=t.id and t.pid=clb.id ".
                "group by clb.id ".
                "order by clb.country, clb.name");
        $qb->setParameter('tournament', $tournamentid);
        return $qb->getResult();
    }

    public function listEnrolledByUser($userid) {
        $qb = $this->em->createQuery(
                "select e ".
                "from ".$this->entity->getRepositoryPath('Enrollment')." e ".
                "where e.uid=:user");
        $qb->setParameter('user', $userid);
        return $qb->getResult();
    }
    
    public function listEnrolledByCategory($categoryid) {
        return $this->entity->getEnrollmentRepo()->findBy(array('pid' => $categoryid));
    }
    
    public function listEnrolledByClub($tournamentid, $clubid) {
        $qb = $this->em->createQuery(
                "select e ".
                "from ".$this->entity->getRepositoryPath('Enrollment')." e, ".
                        $this->entity->getRepositoryPath('Category')." c, ".
                        $this->entity->getRepositoryPath('Team')." t ".
                "where c.pid=:tournament and e.pid=c.id and e.cid=t.id and t.pid=:club ".
                "order by e.pid");
        $qb->setParameter('tournament', $tournamentid);
        $qb->setParameter('club', $clubid);
        return $qb->getResult();
    }

    public function listEnrolledTeamsByCategory($categoryid, $clubid) {
        $qb = $this->em->createQuery(
                "select t ".
                "from ".$this->entity->getRepositoryPath('Enrollment')." e, ".
                        $this->entity->getRepositoryPath('Team')." t ".
                "where e.pid=:category and e.cid=t.id and t.pid=:club ".
                "order by t.division");
        $qb->setParameter('category', $categoryid);
        $qb->setParameter('club', $clubid);
        return $qb->getResult();
    }
    
    public function getEnrolledCategory($teamid) {
        $qb = $this->em->createQuery(
                "select c ".
                "from ".$this->entity->getRepositoryPath('Enrollment')." e, ".
                        $this->entity->getRepositoryPath('Category')." c ".
                "where e.pid=c.id and e.cid=:team");
        $qb->setParameter('team', $teamid);
        $category = $qb->getOneOrNullResult();
        if ($category == null) {
            throw new ValidationException("NOTEAMS", "Team is not enrolled in any category - team=".$teamid);
        }
        return $category;
    }

    public function getAssignedCategory($teamid) {
        $qb = $this->em->createQuery(
                "select distinct c ".
                "from ".$this->entity->getRepositoryPath('Group')." g, ".
                        $this->entity->getRepositoryPath('GroupOrder')." o, ".
                        $this->entity->getRepositoryPath('Category')." c ".
                "where o.pid=g.id and o.cid=:team and g.pid=c.id");
        $qb->setParameter('team', $teamid);
        $category = $qb->getOneOrNullResult();
        if ($category == null) {
            throw new ValidationException("NOTEAMS", "Team is not assigned in any category - team=".$teamid);
        }
        return $category;
    }

    public function listSites($tournamentid) {
        return $this->entity->getSiteRepo()->findBy(array('pid' => $tournamentid));
    }

    public function getPlaygroundByNo($tournamentid, $no) {
        $qb = $this->em->createQuery(
                "select p ".
                "from ".$this->entity->getRepositoryPath('Playground')." p, ".
                        $this->entity->getRepositoryPath('Site')." s ".
                "where s.pid=:tournament and p.pid=s.id and p.no=:no");
        $qb->setParameter('tournament', $tournamentid);
        $qb->setParameter('no', $no);
        return $qb->getOneOrNullResult();
    }

    public function listPlaygrounds($siteid) {
        return $this->entity->getPlaygroundRepo()->findBy(array('pid' => $siteid));
    }

    public function listPlaygroundsByTournament($tournamentid) {
        $qb = $this->em->createQuery(
                "select p ".
                "from ".$this->entity->getRepositoryPath('Playground')." p, ".
                        $this->entity->getRepositoryPath('Site')." s ".
                "where s.pid=:tournament and p.pid=s.id ".
                "order by p.no asc");
        $qb->setParameter('tournament', $tournamentid);
        return $qb->getResult();
    }
    
    public function getTournamentByKey($key) {
        return $this->entity->getTournamentRepo()->findOneBy(array('key' => $key));
    }

    public function listTimeslots($tournamentid) {
        return $this->entity->getTimeslotRepo()->findBy(array('pid' => $tournamentid), array('name' => 'asc'));
    }

    public function listPlaygroundAttributes($playgroundid) {
        return $this->entity->getPlaygroundAttributeRepo()->findBy(array('pid' => $playgroundid), array('date' => 'asc', 'start' => 'asc'));
    }

    public function listPlaygroundAttributesByTournament($tournamentid) {
        $qb = $this->em->createQuery(
                "select a ".
                "from ".$this->entity->getRepositoryPath('PlaygroundAttribute')." a, ".
                        $this->entity->getRepositoryPath('Playground')." p, ".
                        $this->entity->getRepositoryPath('Site')." s ".
                "where s.pid=:tournament and p.pid=s.id and a.pid=p.id ".
                "order by a.pid asc, a.date asc, a.start asc");
        $qb->setParameter('tournament', $tournamentid);
        return $qb->getResult();
    }

    public function getPlaygroundAttribute($playgroundid, $date, $start) {
        return $this->entity->getPlaygroundAttributeRepo()->findOneBy(array('pid' => $playgroundid, 'date' => $date, 'start' => $start));
    }

    public function listPARelations($playgroundattributeid) {
        return $this->entity->getPARelationRepo()->findBy(array('pid' => $playgroundattributeid), array('id' => 'asc'));
    }

    public function removePARelations($playgroundattributeid) {
        // wipe playground attribute relations
        $qbr = $this->em->createQuery(
            "delete from ".$this->entity->getRepositoryPath('PARelation')." p ".
            "where p.pid=:pattr");
        $qbr->setParameter('pattr', $playgroundattributeid);
        $qbr->getResult();
    }

    public function listPACategories($playgroundattributeid) {
        $qb = $this->em->createQuery(
                "select c ".
                "from ".$this->entity->getRepositoryPath('Category')." c, ".
                        $this->entity->getRepositoryPath('PARelation')." p ".
                "where p.cid=c.id and ".
                      "p.pid=:pattr ".
                "order by c.classification asc, c.age desc, c.gender asc");
        $qb->setParameter('pattr', $playgroundattributeid);
        return $qb->getResult();
    }

    public function listPARelationsByCategory($categoryid) {
        return $this->entity->getPARelationRepo()->findBy(array('cid' => $categoryid), array('id' => 'asc'));
    }

    public function listMatchSchedules($tournamentid) {
        return $this->entity->getMatchScheduleRepo()->findBy(array('tournament' => $tournamentid));
    }

    public function listMatchAlternatives($matchscheduleid) {
        return $this->entity->getMatchAlternativeRepo()->findBy(array('pid' => $matchscheduleid), array('paid' => 'asc'));
    }

    public function removeMatchSchedules($tournamentid) {
        // wipe matchalternatives
        $qba = $this->em->createQuery(
            "delete from ".$this->entity->getRepositoryPath('MatchAlternative')." m ".
            "where m.pid in (select ms.id ".
                            "from ".$this->entity->getRepositoryPath('MatchSchedule')." ms ".
                            "where ms.tournament=:tournament)");
        $qba->setParameter('tournament', $tournamentid);
        $qba->getResult();
        // wipe matchschedules
        foreach ($this->listMatchSchedules($tournamentid) as $ms) {
            $this->em->remove($ms);
        }
        $this->em->flush();
    }

    public function listHosts() {
        return $this->entity->getHostRepo()
                    ->findAll(array(),
                              array('name' => 'asc'));
    }
    
    public function listAvailableTournaments($hostid = 0) {
        if ($hostid > 0) {
            return $this->listTournaments($hostid);
        }
        else {
            return $this->entity->getTournamentRepo()
                ->findAll(array(),
                    array('name' => 'asc'));
        }
    }
    
    public function listTournaments($hostid) {
        return $this->entity->getTournamentRepo()
                    ->findBy(array('pid' => $hostid),
                             array('name' => 'asc'));
    }

    public function listCategories($tournamentid) {
        return $this->entity->getCategoryRepo()
                ->findBy(array('pid' => $tournamentid),
                         array('classification' => 'desc', 'age' => 'desc', 'gender' => 'asc'));
    }

    public function getCategoryByName($tournamentid, $category) {
        $qb = $this->em->createQuery(
                "select c ".
                "from ".$this->entity->getRepositoryPath('Category')." c ".
                "where c.pid=:tournament and ".
                      "c.name=:category");
        $qb->setParameter('tournament', $tournamentid);
        $qb->setParameter('category', $category);
        return $qb->getOneOrNullResult();
    }

    public function listGroupsByTournament($tournamentid) {
        $qb = $this->em->createQuery(
                "select g ".
                "from ".$this->entity->getRepositoryPath('Group')." g, ".
                        $this->entity->getRepositoryPath('Category')." c ".
                "where c.pid=:tournament and g.pid=c.id ".
                "order by g.classification asc, g.name asc");
        $qb->setParameter('tournament', $tournamentid);
        return $qb->getResult();
    }

    public function getGroupByCategory($tournamentid, $category, $group) {
        $qb = $this->em->createQuery(
                "select g ".
                "from ".$this->entity->getRepositoryPath('Group')." g, ".
                        $this->entity->getRepositoryPath('Category')." c ".
                "where c.pid=:tournament and ".
                      "c.name=:category and ".
                      "g.pid=c.id and ".
                      "g.name=:group");
        $qb->setParameter('tournament', $tournamentid);
        $qb->setParameter('category', $category);
        $qb->setParameter('group', $group);
        return $qb->getOneOrNullResult();
    }

    public function getGroup($groupfamily, $group) {
        $qb = $this->em->createQuery(
            "select g ".
            "from ".$this->entity->getRepositoryPath('Group')." g ".
            "where g.pid in (select gx.pid from ".$this->entity->getRepositoryPath('Group')." gx where gx.id=".$groupfamily.") and ".
                  "g.name=:group");
        $qb->setParameter('group', $group);
        return $qb->getOneOrNullResult();
    }

    public function listGroupsByCategory($categoryid) {
        return $this->entity->getGroupRepo()->findBy(array('pid' => $categoryid));
    }

    public function listGroups($categoryid, $classification = 0) {
        return $this->entity->getGroupRepo()
                    ->findBy(array('pid' => $categoryid, 'classification' => $classification),
                             array('name' => 'asc'));
    }
    
    public function listGroupsClassification($categoryid) {
        $qb = $this->em->createQuery(
                "select g ".
                "from ".$this->entity->getRepositoryPath('Group')." g ".
                "where g.pid=:category and g.classification > 0 and g.classification < 6 ".
                "order by g.classification asc, g.name asc");
        $qb->setParameter('category', $categoryid);
        return $qb->getResult();
    }
    
    public function listGroupsFinals($categoryid) {
        $qb = $this->em->createQuery(
                "select g ".
                "from ".$this->entity->getRepositoryPath('Group')." g ".
                "where g.pid=:category and g.classification > 5 ".
                "order by g.classification desc, g.name asc");
        $qb->setParameter('category', $categoryid);
        return $qb->getResult();
    }
    
    public function listGroupOrders($groupid) {
        return $this->entity->getGroupOrderRepo()->findBy(array('pid' => $groupid));
    }
    
    public function getTeamByCategory($categoryid, $name, $division) {
        $qb = $this->em->createQuery(
                "select t.id,t.name,t.division,c.name as club,c.country ".
                "from ".$this->entity->getRepositoryPath('Enrollment')." e, ".
                        $this->entity->getRepositoryPath('Team')." t, ".
                        $this->entity->getRepositoryPath('Club')." c ".
                "where e.pid=:category and ".
                      "e.cid=t.id and ".
                      "t.pid=c.id and ".
                      "t.name=:name and ".
                      "t.division=:division");
        $qb->setParameter('category', $categoryid);
        $qb->setParameter('name', $name);
        $qb->setParameter('division', $division);
        $teamsList = array();
        foreach ($qb->getResult() as $team) {
            $teamInfo = new TeamInfo();
            $teamInfo->id = $team['id'];
            $teamInfo->name = $this->getTeamName($team['name'], $team['division']);
            $teamInfo->club = $team['club'];
            $teamInfo->country = $team['country'];
            $teamsList[] = $teamInfo;
        }
        return $teamsList;
    }
    
    public function getTeamByGroup($groupid, $name, $division) {
        $qb = $this->em->createQuery(
                "select t.id,t.name,t.division,c.name as club,c.country ".
                "from ".$this->entity->getRepositoryPath('GroupOrder')." o, ".
                        $this->entity->getRepositoryPath('Team')." t, ".
                        $this->entity->getRepositoryPath('Club')." c ".
                "where o.pid=:group and ".
                      "o.cid=t.id and ".
                      "t.pid=c.id and ".
                      "t.name=:name and ".
                      "t.division=:division");
        $qb->setParameter('group', $groupid);
        $qb->setParameter('name', $name);
        $qb->setParameter('division', $division);
        $teamsList = array();
        foreach ($qb->getResult() as $team) {
            $teamInfo = new TeamInfo();
            $teamInfo->id = $team['id'];
            $teamInfo->name = $this->getTeamName($team['name'], $team['division']);
            $teamInfo->club = $team['club'];
            $teamInfo->country = $team['country'];
            $teamInfo->group = $groupid;
            $teamsList[] = $teamInfo;
        }
        return $teamsList;
    }
    
    public function listTeamsByGroup($groupid) {
        $qb = $this->em->createQuery(
                "select t.id,t.name,t.division,c.name as club,c.country ".
                "from ".$this->entity->getRepositoryPath('GroupOrder')." o, ".
                        $this->entity->getRepositoryPath('Team')." t, ".
                        $this->entity->getRepositoryPath('Club')." c ".
                "where o.pid=:group and ".
                      "o.cid=t.id and ".
                      "t.pid=c.id ".
                "order by o.id");
        $qb->setParameter('group', $groupid);
        $teamsList = array();
        foreach ($qb->getResult() as $team) {
            $teamInfo = new TeamInfo();
            $teamInfo->id = $team['id'];
            $teamInfo->name = $this->getTeamName($team['name'], $team['division']);
            $teamInfo->club = $team['club'];
            $teamInfo->country = $team['country'];
            $teamInfo->group = $groupid;
            $teamsList[] = $teamInfo;
        }
        return $teamsList;
    }

    public function listTeamsByGroupFinals($groupid) {
        $qb = $this->em->createQuery(
            "select distinct t.id,t.name,t.division,c.name as club,c.country ".
            "from ".$this->entity->getRepositoryPath('MatchRelation')." r, ".
                    $this->entity->getRepositoryPath('Match')." m, ".
                    $this->entity->getRepositoryPath('Team')." t, ".
                    $this->entity->getRepositoryPath('Club')." c ".
            "where m.group=:group and ".
            "r.match=m.id and ".
            "r.cid=t.id and ".
            "t.pid=c.id ".
            "order by t.id");
        $qb->setParameter('group', $groupid);
        $teamsList = array();
        foreach ($qb->getResult() as $team) {
            $teamInfo = new TeamInfo();
            $teamInfo->id = $team['id'];
            $teamInfo->name = $this->getTeamName($team['name'], $team['division']);
            $teamInfo->club = $team['club'];
            $teamInfo->country = $team['country'];
            $teamInfo->group = $groupid;
            $teamsList[] = $teamInfo;
        }
        $qb = $this->em->createQuery(
            "select q.id as rid,q.awayteam,q.rank,g.id as rgrp,g.name as gname,g.classification ".
            "from ".$this->entity->getRepositoryPath('QMatchRelation')." q, ".
            $this->entity->getRepositoryPath('Match')." m, ".
            $this->entity->getRepositoryPath('Group')." g ".
            "where m.group=:group and ".
            "q.match=m.id and ".
            "q.cid=g.id ".
            "order by m.id, q.awayteam");
        $qb->setParameter('group', $groupid);
        foreach ($qb->getResult() as $qmatch) {
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

            $teamInfo = new TeamInfo();
            $teamInfo->id = $qmatch['rgrp'].'-'.$qmatch['rank'];
            $teamInfo->name = $rankTxt;
            $teamInfo->club = $rankTxt;
            $teamInfo->country = "EUR";
            $teamInfo->group = $groupid;
            $teamsList[] = $teamInfo;
        }
        return $teamsList;
    }

    public function listTeamsByClub($clubid) {
        $qb = $this->em->createQuery(
                "select t ".
                "from ".$this->entity->getRepositoryPath('Team')." t ".
                "where t.pid=:club");
        $qb->setParameter('club', $clubid);
        return $qb->getResult();
    }
    
    public function listTeamsEnrolledUnassigned($categoryid, $classification = 0) {
        $qb = $this->em->createQuery(
                "select t.id,t.name,t.division,c.name as club,c.country ".
                "from ".$this->entity->getRepositoryPath('Enrollment')." e, ".
                        $this->entity->getRepositoryPath('Team')." t, ".
                        $this->entity->getRepositoryPath('Club')." c ".
                "where e.pid=:category and e.cid=t.id and t.pid=c.id and ".
                      "t.id not in (".
                            "select o.cid ".
                            "from ".$this->entity->getRepositoryPath('Group')." g, ".
                                    $this->entity->getRepositoryPath('GroupOrder')." o ".
                            "where g.pid=:category and g.classification=:class and ".
                                  "o.pid=g.id".
                            ") ".
                "order by c.country, c.name, t.division");
        $qb->setParameter('category', $categoryid);
        $qb->setParameter('class', $classification);
        $teamsList = array();
        foreach ($qb->getResult() as $team) {
            $teamInfo = new TeamInfo();
            $teamInfo->id = $team['id'];
            $teamInfo->name = $this->getTeamName($team['name'], $team['division']);
            $teamInfo->club = $team['club'];
            $teamInfo->country = $team['country'];
            $teamsList[] = $teamInfo;
        }
        return $teamsList;
    }
    
    public function getTeamResultsByGroup($groupid) {
        $qb = $this->em->createQuery(
                "select r ".
                "from ".$this->entity->getRepositoryPath('MatchRelation')." r, ".
                        $this->entity->getRepositoryPath('Match')." m ".
                "where r.match=m.id and m.group=:group ".
                "order by r.match");
        $qb->setParameter('group', $groupid);
        return $qb->getResult();
    }
    
    public function getTeamName($clubName, $division) {
        $teamName = $this->container->get('translator')->trans($clubName, array(), 'teamname');
        if ($division != '') {
            $teamName.= ' "'.$division.'"';
        }
        return $teamName;
    }
    
    public function listClubs() {
        return $this->entity->getClubRepo()->findBy(array(), array('country' => 'asc', 'name' => 'asc'));
    }
    
    public function listClubsByTournament($tournamentid) {
        $qb = $this->em->createQuery(
                "select c ".
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
                        "t.pid=c.id ".
                "order by c.country asc, c.name asc");
        $qb->setParameter('tournament', $tournamentid);
        return $qb->getResult();
    }

    public function listClubsByPattern($pattern, $countryCode) {
        $qb = $this->em->createQuery(
                "select c ".
                "from ".$this->entity->getRepositoryPath('Club')." c ".
                "where c.name like :pattern and c.country=:country ".
                "order by c.name");
        $qb->setParameter('pattern', $pattern);
        $qb->setParameter('country', $countryCode);
        return $qb->getResult();
    }
    
    public function getClubByName($name, $countryCode) {
        return $this->entity->getClubRepo()->findOneBy(array('name' => $name, 'country' => $countryCode));
    }

    public function listUsersByClub($clubid) {
        $qb = $this->em->createQuery(
                "select u ".
                "from ".$this->entity->getRepositoryPath('User')." u ".
                "where u.cid=:club and ".
                      "u.role in (".User::$CLUB.",".User::$CLUB_ADMIN.") and ".
                      "u.status in (".User::$PRO.",".User::$ATT.",".User::$INF.") ".
                "order by u.status, u.role desc, u.name");
        $qb->setParameter('club', $clubid);
        return $qb->getResult();
    }

    public function listUsersByHost($hostid) {
        $qb = $this->em->createQuery(
                "select u ".
                "from ".$this->entity->getRepositoryPath('User')." u ".
                "where u.pid=:host and ".
                      "u.role in (".User::$EDITOR.",".User::$EDITOR_ADMIN.") ".
                "order by u.role desc, u.name");
        $qb->setParameter('host', $hostid);
        return $qb->getResult();
    }
    
    public function getUserByName($username) {
        return $this->entity->getUserRepo()->findOneBy(array('username' => $username));
    }
    
    public function listAdminUsers() {
        $qb = $this->em->createQuery(
                "select u ".
                "from ".$this->entity->getRepositoryPath('User')." u ".
                "where u.role in (".User::$ADMIN.") ".
                "order by u.name");
        return $qb->getResult();
    }
    
    public function isUserKnown($username) {
        return $this->getUserByName($username) != null;
    }
}

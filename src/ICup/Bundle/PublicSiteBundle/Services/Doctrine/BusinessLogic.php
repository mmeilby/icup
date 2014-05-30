<?php

namespace ICup\Bundle\PublicSiteBundle\Services\Doctrine;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Enrollment;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\GroupOrder;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Host;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Playground;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Site;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Template;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Entity\TeamInfo;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use DateTime;

class BusinessLogic
{
   /* @var $entity Entity */
    protected $entity;
    /* @var $em EntityManager */
    protected $em;
     /* @var $logger Logger */
    protected $logger;

    public function __construct(Entity $entity, EntityManager $em, Logger $logger)
    {
        $this->entity = $entity;
        $this->em = $em;
        $this->logger = $logger;
    }

    public function addEnrolled(Category $category, Club $club, User $user) {
        $qb = $this->em->createQuery(
                "select e ".
                "from ".$this->entity->getRepositoryPath('Enrollment')." e, ".
                        $this->entity->getRepositoryPath('Team')." t ".
                "where e.pid=:category and e.cid=t.id and t.pid=:club ".
                "order by e.pid");
        $qb->setParameter('category', $category->getId());
        $qb->setParameter('club', $club->getId());
        $enrolled = $qb->getResult();
 
        $noTeams = count($enrolled);
        if ($noTeams >= 26) {
            // Can not add more than 26 teams to same category - Team A -> Team Z
            throw new ValidationException("NOMORETEAMS", "More than 26 enrolled - club=".$club->getId().", category=".$category->getId());
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
        
        $team = new Team();
        $team->setPid($club->getId());
        $team->setName($club->getName());
        $team->setColor('');
        $team->setDivision($division);
        $this->em->persist($team);
        $this->em->flush();
        
        $today = new DateTime();
        $enroll = new Enrollment();
        $enroll->setCid($team->getId());
        $enroll->setPid($category->getId());
        $enroll->setUid($user->getId());
        $enroll->setDate($today->format('d/m/Y'));
        $this->em->persist($enroll);
        $this->em->flush();

        return $enroll;
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
    
    private function isTeamAssigned($categoryid, $teamid) {
        $qbt = $this->em->createQuery(
                "select count(o) as teams ".
                "from ".$this->entity->getRepositoryPath('Group')." g, ".
                        $this->entity->getRepositoryPath('GroupOrder')." o ".
                "where g.pid=:category and ".
                      "o.pid=g.id and ".
                      "o.cid=:team");
        $qbt->setParameter('category', $categoryid);
        $qbt->setParameter('team', $teamid);
        $teamsAssigned = $qbt->getOneOrNullResult();
        return $teamsAssigned != null ? $teamsAssigned['teams'] > 0 : false;
    }
    
    private function updateDivision(Enrollment $enroll, $division) {
        $firstTeam = $this->entity->getTeamById($enroll->getCid());
        $firstTeam->setDivision($division);
        $this->em->persist($firstTeam);
    }

    public function assignEnrolled($teamid, $groupid) {
        $group = $this->entity->getGroupById($groupid);
        // Verify that the team is not assigned to any group
        if ($this->isTeamAssigned($group->getPid(), $teamid)) {
            throw new ValidationException("TEAMASSIGNED", "Team was assigned previously - team=".$teamid.", category=".$group->getPid());
        }
        $groupOrder = new GroupOrder();
        $groupOrder->setCid($teamid);
        $groupOrder->setPid($groupid);
        $this->em->persist($groupOrder);
        $this->em->flush();
        return $groupOrder;
    }
    
    public function removeEnrolled($teamid, $groupid) {
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

    public function isTeamActive($groupid, $teamid) {
        $qb = $this->em->createQuery(
                "select count(r) as results ".
                "from ".$this->entity->getRepositoryPath('MatchRelation')." r, ".
                        $this->entity->getRepositoryPath('Match')." m ".
                "where r.pid=m.id and m.pid=:group and r.cid=:team ".
                "order by r.pid");
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

    /* TODO: this function must restrict til available tournaments */
    public function listAvailableTournaments() {
        return $this->entity->getTournamentRepo()
                    ->findAll(array(),
                              array('name' => 'asc'));
    }
    
    public function listTournaments($hostid) {
        return $this->entity->getTournamentRepo()
                    ->findBy(array('pid' => $hostid),
                             array('name' => 'asc'));
    }

    public function listCategories($tournamentid) {
        return $this->entity->getCategoryRepo()
                ->findBy(array('pid' => $tournamentid),
                         array('classification' => 'asc', 'gender' => 'asc'));
    }

    public function listGroupsByTournament($tournamentid) {
        $qb = $this->em->createQuery(
                "select g ".
                "from ".$this->entity->getRepositoryPath('Group')." g, ".
                        $this->entity->getRepositoryPath('Category')." c ".
                "where c.pid=:tournament and g.pid=c.id ".
                "order by g.name asc");
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
                "where g.pid=:category and g.classification > 0 and g.classification < 8 ".
                "order by g.classification asc, g.name asc");
        $qb->setParameter('category', $categoryid);
        return $qb->getResult();
    }
    
    public function listGroupsFinals($categoryid) {
        $qb = $this->em->createQuery(
                "select g ".
                "from ".$this->entity->getRepositoryPath('Group')." g ".
                "where g.pid=:category and g.classification > 7 ".
                "order by g.classification desc, g.name asc");
        $qb->setParameter('category', $categoryid);
        return $qb->getResult();
    }
    
    public function listGroupOrders($groupid) {
        return $this->entity->getGroupOrderRepo()->findBy(array('pid' => $groupid));
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
                "where r.pid=m.id and m.pid=:group ".
                "order by r.pid");
        $qb->setParameter('group', $groupid);
        return $qb->getResult();
    }
    
    public function getTeamName($clubName, $division) {
        $teamName = $clubName;
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
                      "u.status in (".User::$PRO.",".User::$ATT.") ".
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
    
    public function isUserKnown($username) {
        return $this->getUserByName($username) != null;
    }
}

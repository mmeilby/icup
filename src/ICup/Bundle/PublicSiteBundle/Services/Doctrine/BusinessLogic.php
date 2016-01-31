<?php

namespace ICup\Bundle\PublicSiteBundle\Services\Doctrine;

use DateTime;
use Doctrine\ORM\EntityManager;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Enrollment;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\GroupOrder;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchSchedule;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\PlaygroundAttribute;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\QMatchSchedule;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
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
    
    public function addEnrolled(Category $category, Club $club, User $user, $vacant = false) {
        $qb = $this->em->createQuery(
                "select e ".
                "from ".$this->entity->getRepositoryPath('Enrollment')." e, ".
                        $this->entity->getRepositoryPath('Team')." t ".
                "where e.category=:category and e.team=t.id and t.club=:club ".
                "order by e.category");
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

        return $this->enrollTeam($category, $user,
                                 $club, $club->getName(), $division, $vacant);
    }
    
    public function deleteEnrolled($categoryid, $clubid) {
        $qb = $this->em->createQuery(
                "select e ".
                "from ".$this->entity->getRepositoryPath('Enrollment')." e, ".
                        $this->entity->getRepositoryPath('Team')." t ".
                "where e.category=:category and e.team=t.id and t.club=:club ".
                "order by t.division");
        $qb->setParameter('category', $categoryid);
        $qb->setParameter('club', $clubid);
        $enrolled = $qb->getResult();

        /* @var $enroll Enrollment */
        $enroll = array_pop($enrolled);
        if ($enroll == null) {
            throw new ValidationException("NOTEAMS", "No teams enrolled - club=".$clubid.", category=".$categoryid);
        }
        $team = $enroll->getTeam();
        // Verify that the team is not assigned to a group
        if ($this->isTeamAssigned($categoryid, $team->getId())) {
            throw new ValidationException("TEAMASSIGNED", "Team was assigned previously - team=".$team->getId().", category=".$categoryid);
        }
        // Remove the team entity enrolled in this category
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
            "where e.category=:category and e.team=:team");
        $qb->setParameter('category', $categoryid);
        $qb->setParameter('team', $teamid);
        $enroll = $qb->getOneOrNullResult();
        // Remove the team entity enrolled in this category
        $team = $this->entity->getTeamById($teamid);
        $club = $team->getClub();
        $this->em->remove($team);
        // Finally remove the enroll entity
        $this->em->remove($enroll);
        $this->em->flush();

        $enrolled = $this->listEnrolledTeamsByCategory($categoryid, $club->getId());
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
                "where g.category=:category and g.classification=0 and ".
                      "o.group=g.id and ".
                      "o.team=:team");
        $qbt->setParameter('category', $categoryid);
        $qbt->setParameter('team', $teamid);
        $teamsAssigned = $qbt->getOneOrNullResult();
        return $teamsAssigned != null ? $teamsAssigned['teams'] > 0 : false;
    }
    
    public function enrollTeam(Category $category, User $user, Club $club, $name, $division, $vacant = false) {
        $team = new Team();
        $team->setClub($club);
        $team->setName($name);
        $team->setColor('');
        $team->setDivision($division);
        $team->setVacant($vacant);
        $this->em->persist($team);
        $this->em->flush();

        $today = new DateTime();
        $enroll = new Enrollment();
        $enroll->setTeam($team);
        $enroll->setCategory($category);
        $enroll->setUser($user);
        $enroll->setDate(Date::getDate($today));
        $this->em->persist($enroll);
        $this->em->flush();
        return $enroll;
    }
    
    private function updateDivision(Enrollment $enroll, $division) {
        $firstTeam = $enroll->getTeam();
        $firstTeam->setDivision($division);
        $this->em->flush();
    }

    public function assignCategory($categoryid, $playgroundattributeid) {
        // Verify that the category and playground share the same tournament
        $objects = $this->verifyRelation($categoryid, $playgroundattributeid);
        /* @var $pattr PlaygroundAttribute */
        $pattr = $objects['pattr'];
        $pattr->getCategories()->add($objects['category']);
        $this->em->flush();
    }
    
    public function removeAssignedCategory($categoryid, $playgroundattributeid) {
        // Verify that the category and playground share the same tournament
        $objects = $this->verifyRelation($categoryid, $playgroundattributeid);
        /* @var $pattr PlaygroundAttribute */
        $pattr = $objects['pattr'];
        if ($pattr->getCategories()->removeElement($objects['category']) === false) {
            throw new ValidationException("CATEGORYISNOTASSIGNED", "Category is not assigned - pattr=".$playgroundattributeid.", category=".$categoryid);
        }
        $this->em->flush();
    }

    private function verifyRelation($categoryid, $playgroundattributeid) {
        /* @var $category Category */
        $category = $this->entity->getCategoryById($categoryid);
        /* @var $pattr PlaygroundAttribute */
        $pattr = $this->entity->getPlaygroundAttributeById($playgroundattributeid);
        $playground = $pattr->getPlayground();
        $site = $playground->getSite();
        // Verify that the category and playground share the same tournament
        if ($site->getTournament()->getId() != $category->getTournament()->getId()) {
            throw new ValidationException("NOTTHESAMETOURNAMENT", "Category and playground does not share the same tournament - pattr=".$playgroundattributeid.", category=".$categoryid);
        }
        return array('category' => $category, 'pattr' => $pattr);
    }    
    
    public function assignEnrolled($teamid, $groupid) {
        /* @var $group Group */
        $group = $this->entity->getGroupById($groupid);
        $team = $this->entity->getTeamById($teamid);
        // Verify that the team is not assigned to any group
        if ($this->isTeamAssigned($group->getCategory()->getId(), $teamid)) {
            throw new ValidationException("TEAMASSIGNED", "Team was assigned previously - team=".$teamid.", category=".$group->getCategory()->getId());
        }
        $groupOrder = $this->getFirstVacantAssigned($groupid);
        if (!$groupOrder) {
            $groupOrder = new GroupOrder();
            $groupOrder->setGroup($group);
            $groupOrder->setTeam($team);
            $this->em->persist($groupOrder);
        }
        else {
            $vacantTeam = $groupOrder->getTeam();
            $this->moveMatches($groupid, $vacantTeam->getId(), $teamid);
            $groupOrder->setTeam($team);
        }
        $this->em->flush();
        return $groupOrder;
    }

    private static $VACANT_CLUB_NAME = "VACANT";
    private static $VACANT_CLUB_COUNTRYCODE = "[V]";

    public function assignVacant($groupid, User $user) {
        /* @var $group Group */
        $group = $this->entity->getGroupById($groupid);
        $club = $this->getClubByName(BusinessLogic::$VACANT_CLUB_NAME, BusinessLogic::$VACANT_CLUB_COUNTRYCODE);
        if ($club == null) {
            $club = new Club();
            $club->setName(BusinessLogic::$VACANT_CLUB_NAME);
            $club->setCountry(BusinessLogic::$VACANT_CLUB_COUNTRYCODE);
            $this->em->persist($club);
            $this->em->flush();
        }
        $enrolled = $this->addEnrolled($group->getCategory(), $club, $user, true);
        $groupOrder = new GroupOrder();
        $groupOrder->setTeam($enrolled->getTeam());
        $groupOrder->setGroup($group);
        $this->em->persist($groupOrder);
        $this->em->flush();
        return $groupOrder;
    }

    public function getFirstVacantAssigned($groupid) {
        $qbt = $this->em->createQuery(
            "select o ".
            "from ".$this->entity->getRepositoryPath('GroupOrder')." o, ".
                    $this->entity->getRepositoryPath('Team')." t ".
            "where o.group=:group and ".
            "o.team=t.id and t.vacant='Y'");
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
                "where o.group=:group and ".
                      "o.team=:team");
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
            "update ".$this->entity->getRepositoryPath('MatchRelation')." r set r.team=:target ".
            "where r.team=:team and r.match in (".
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
                "where r.match=m.id and m.group=:group and r.team=:team ".
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
            "where r.match=m.id and m.group=:group and r.team=:team and r.scorevalid='Y'".
            "order by r.match");
        $qb->setParameter('group', $groupid);
        $qb->setParameter('team', $teamid);
        $results = $qb->getOneOrNullResult();
        return $results != null ? $results['results'] > 0 : false;
    }

    public function listAnyEnrolledByClub($clubid) {
        $qb = $this->em->createQuery(
                "select c.tournament as tid,count(e) as enrolled ".
                "from ".$this->entity->getRepositoryPath('Enrollment')." e, ".
                        $this->entity->getRepositoryPath('Category')." c, ".
                        $this->entity->getRepositoryPath('Team')." t ".
                "where e.category=c.id and e.team=t.id and t.club=:club ".
                "group by c.tournament ".
                "order by c.tournament asc");
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
                "where c.tournament=:tournament and e.category=c.id and e.team=t.id and t.club=clb.id ".
                "group by clb.id ".
                "order by clb.country, clb.name");
        $qb->setParameter('tournament', $tournamentid);
        return $qb->getResult();
    }

    public function listEnrolledByClub($tournamentid, $clubid) {
        $qb = $this->em->createQuery(
                "select e ".
                "from ".$this->entity->getRepositoryPath('Enrollment')." e, ".
                        $this->entity->getRepositoryPath('Category')." c, ".
                        $this->entity->getRepositoryPath('Team')." t ".
                "where c.tournament=:tournament and e.category=c.id and e.team=t.id and t.club=:club ".
                "order by e.category");
        $qb->setParameter('tournament', $tournamentid);
        $qb->setParameter('club', $clubid);
        return $qb->getResult();
    }

    public function listEnrolledTeamsByCategory($categoryid, $clubid) {
        $qb = $this->em->createQuery(
                "select t ".
                "from ".$this->entity->getRepositoryPath('Enrollment')." e, ".
                        $this->entity->getRepositoryPath('Team')." t ".
                "where e.category=:category and e.team=t.id and t.club=:club ".
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
                "where e.category=c.id and e.team=:team");
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
                "where o.group=g.id and o.team=:team and g.category=c.id");
        $qb->setParameter('team', $teamid);
        $category = $qb->getOneOrNullResult();
        if ($category == null) {
            throw new ValidationException("NOTEAMS", "Team is not assigned in any category - team=".$teamid);
        }
        return $category;
    }

    public function getPlaygroundByNo($tournamentid, $no) {
        $qb = $this->em->createQuery(
                "select p ".
                "from ".$this->entity->getRepositoryPath('Playground')." p, ".
                        $this->entity->getRepositoryPath('Site')." s ".
                "where s.tournament=:tournament and p.site=s.id and p.no=:no");
        $qb->setParameter('tournament', $tournamentid);
        $qb->setParameter('no', $no);
        return $qb->getOneOrNullResult();
    }

    public function listPlaygroundsByTournament($tournamentid) {
        $qb = $this->em->createQuery(
                "select p ".
                "from ".$this->entity->getRepositoryPath('Playground')." p, ".
                        $this->entity->getRepositoryPath('Site')." s ".
                "where s.tournament=:tournament and p.site=s.id ".
                "order by p.no asc");
        $qb->setParameter('tournament', $tournamentid);
        return $qb->getResult();
    }
    
    public function getTournamentByKey($key) {
        return $this->entity->getTournamentRepo()->findOneBy(array('key' => $key));
    }

    public function listPlaygroundAttributesByTournament($tournamentid) {
        $qb = $this->em->createQuery(
                "select a ".
                "from ".$this->entity->getRepositoryPath('PlaygroundAttribute')." a, ".
                        $this->entity->getRepositoryPath('Playground')." p, ".
                        $this->entity->getRepositoryPath('Site')." s ".
                "where s.tournament=:tournament and p.site=s.id and a.playground=p.id ".
                "order by p.no asc, a.date asc, a.start asc");
        $qb->setParameter('tournament', $tournamentid);
        return $qb->getResult();
    }

    public function getPlaygroundAttribute($playgroundid, $date, $start) {
        return $this->entity->getPlaygroundAttributeRepo()->findOneBy(array('playground' => $playgroundid, 'date' => $date, 'start' => $start));
    }

    /**
     * List match schedules ordered by match time ascending
     * Unassigned match schedules are sorted at the end of the list
     * @param Tournament $tournament
     * @return array sorted match schedule list
     */
    public function listMatchSchedules(Tournament $tournament) {
        $matchschedules = $this->entity->getMatchScheduleRepo()->findBy(array('tournament' => $tournament->getId()));
        usort($matchschedules, function (MatchSchedule $ms1, MatchSchedule $ms2) {
            if ($ms1->getPlan() && $ms2->getPlan()) {
                $schedule1 = Date::getDateTime($ms1->getPlan()->getPlaygroundAttribute()->getDate(), $ms1->getPlan()->getMatchstart());
                $schedule2 = Date::getDateTime($ms2->getPlan()->getPlaygroundAttribute()->getDate(), $ms2->getPlan()->getMatchstart());
                return min(1, max(-1, $schedule1->getTimestamp() - $schedule2->getTimestamp()));
            }
            return (!$ms2->getPlan() ? 0 : 1) - (!$ms1->getPlan() ? 0 : 1);
        });
        return $matchschedules;
    }

    /**
     * List qualifying match schedules ordered by match time ascending
     * Unassigned match schedules are sorted at the end of the list
     * @param Tournament $tournament
     * @return array sorted match schedule list
     */
    public function listQMatchSchedules(Tournament $tournament) {
        $qmatchschedules = $this->entity->getQMatchScheduleRepo()->findBy(array('tournament' => $tournament->getId()));
        usort($qmatchschedules, function (QMatchSchedule $ms1, QMatchSchedule $ms2) {
            if ($ms1->getPlan() && $ms2->getPlan()) {
                $schedule1 = Date::getDateTime($ms1->getPlan()->getPlaygroundAttribute()->getDate(), $ms1->getPlan()->getMatchstart());
                $schedule2 = Date::getDateTime($ms2->getPlan()->getPlaygroundAttribute()->getDate(), $ms2->getPlan()->getMatchstart());
                return min(1, max(-1, $schedule1->getTimestamp() - $schedule2->getTimestamp()));
            }
            return (!$ms2->getPlan() ? 0 : 1) - (!$ms1->getPlan() ? 0 : 1);
        });
        return $qmatchschedules;
    }

    public function listMatchAlternatives($matchscheduleid) {
        return $this->entity->getMatchAlternativeRepo()->findBy(array('matchschedule' => $matchscheduleid));
    }

    public function removeMatchSchedules(Tournament $tournament) {
        // wipe matchalternatives
        $qba = $this->em->createQuery(
            "delete from ".$this->entity->getRepositoryPath('MatchAlternative')." m ".
            "where m.matchschedule in (select ms.id ".
                            "from ".$this->entity->getRepositoryPath('MatchSchedule')." ms ".
                            "where ms.tournament=:tournament)");
        $qba->setParameter('tournament', $tournament->getId());
        $qba->getResult();
        // wipe matchschedules
        foreach ($this->listMatchSchedules($tournament) as $ms) {
            $this->em->remove($ms);
        }
        $this->em->flush();
    }

    public function removeQMatchSchedules(Tournament $tournament) {
        // wipe qmatchschedules
        foreach ($this->listQMatchSchedules($tournament) as $ms) {
            $this->em->remove($ms);
        }
        $this->em->flush();
    }

    public function listHosts() {
        return $this->entity->getHostRepo()
                    ->findAll(array(),
                              array('name' => 'asc'));
    }
    
    public function listAvailableTournaments() {
        $tournaments = array();
        foreach ($this->entity->getTournamentRepo()->findAll(array(), array('host' => 'asc', 'name' => 'asc')) as $tournament) {
            $status = $this->container->get('tmnt')->getTournamentStatus($tournament->getId(), new DateTime());
            if ($status === TournamentSupport::$TMNT_ENROLL || $status === TournamentSupport::$TMNT_GOING || $status === TournamentSupport::$TMNT_DONE) {
                $tournaments[] = $tournament;
            }
        }
        return $tournaments;
    }
    
    public function getCategoryByName($tournamentid, $category) {
        $qb = $this->em->createQuery(
                "select c ".
                "from ".$this->entity->getRepositoryPath('Category')." c ".
                "where c.tournament=:tournament and ".
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
                "where c.tournament=:tournament and g.category=c.id ".
                "order by g.classification asc, g.name asc");
        $qb->setParameter('tournament', $tournamentid);
        return $qb->getResult();
    }

    public function getGroupByCategory($tournamentid, $category, $group) {
        $qb = $this->em->createQuery(
                "select g ".
                "from ".$this->entity->getRepositoryPath('Group')." g, ".
                        $this->entity->getRepositoryPath('Category')." c ".
                "where c.tournament=:tournament and ".
                      "c.name=:category and ".
                      "g.category=c.id and ".
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
            "where g.category in (select identity(gx.category) from ".$this->entity->getRepositoryPath('Group')." gx where gx.id=".$groupfamily.") and ".
                  "g.name=:group");
        $qb->setParameter('group', $group);
        return $qb->getOneOrNullResult();
    }

    public function getTeamByCategory($categoryid, $name, $division) {
        $qb = $this->em->createQuery(
                "select t ".
                "from ".$this->entity->getRepositoryPath('Enrollment')." e, ".
                        $this->entity->getRepositoryPath('Team')." t ".
                "where e.category=:category and ".
                      "e.team=t.id and ".
                      "t.name=:name and ".
                      "t.division=:division");
        $qb->setParameter('category', $categoryid);
        $qb->setParameter('name', $name);
        $qb->setParameter('division', $division);
        $teamsList = array();
        /* @var $team Team */
        foreach ($qb->getResult() as $team) {
            $teamInfo = new TeamInfo();
            $teamInfo->id = $team->getId();
            $teamInfo->name = $team->getTeamName();
            $teamInfo->club = $team->getClub()->getName();
            $teamInfo->country = $team->getClub()->getCountry();
            $teamsList[] = $teamInfo;
        }
        return $teamsList;
    }

    public function findTeamByCategory(Category $category, $name, $division) {
        $qb = $this->em->createQuery(
            "select t ".
            "from ".$this->entity->getRepositoryPath('Enrollment')." e, ".
                    $this->entity->getRepositoryPath('Team')." t ".
            "where e.category=:category and ".
                  "e.team=t.id and ".
                  "t.name=:name and ".
                  "t.division=:division");
        $qb->setParameter('category', $category->getId());
        $qb->setParameter('name', $name);
        $qb->setParameter('division', $division);
        return $qb->getResult();
    }

    public function getTeamByGroup($groupid, $name, $division) {
        $qb = $this->em->createQuery(
                "select t ".
                "from ".$this->entity->getRepositoryPath('GroupOrder')." o, ".
                        $this->entity->getRepositoryPath('Team')." t ".
                "where o.group=:group and ".
                      "o.team=t.id and ".
                      "t.name=:name and ".
                      "t.division=:division");
        $qb->setParameter('group', $groupid);
        $qb->setParameter('name', $name);
        $qb->setParameter('division', $division);
        $teamsList = array();
        /* @var $team Team */
        foreach ($qb->getResult() as $team) {
            $teamInfo = new TeamInfo();
            $teamInfo->id = $team->getId();
            $teamInfo->name = $team->getTeamName();
            $teamInfo->club = $team->getClub()->getName();
            $teamInfo->country = $team->getClub()->getCountry();
            $teamInfo->group = $groupid;
            $teamsList[] = $teamInfo;
        }
        return $teamsList;
    }

    public function findTeamByGroup(Group $group, $name, $division, $country) {
        $qb = $this->em->createQuery(
            "select t ".
            "from ".$this->entity->getRepositoryPath('GroupOrder')." o, ".
                    $this->entity->getRepositoryPath('Team')." t, ".
                    $this->entity->getRepositoryPath('Club')." c ".
            "where o.group=:group and ".
                  "o.team=t.id and ".
                  "c.country=:country and ".
                  "t.club=c.id and ".
                  "t.name=:name and ".
                  "t.division=:division");
        $qb->setParameter('group', $group->getId());
        $qb->setParameter('name', $name);
        $qb->setParameter('division', $division);
        $qb->setParameter('country', $country);
        return $qb->getOneOrNullResult();
    }

    public function listTeamsByGroup($groupid) {
        $qb = $this->em->createQuery(
                "select t.id,t.name,t.division,c.name as club,c.country ".
                "from ".$this->entity->getRepositoryPath('GroupOrder')." o, ".
                        $this->entity->getRepositoryPath('Team')." t, ".
                        $this->entity->getRepositoryPath('Club')." c ".
                "where o.group=:group and ".
                      "o.team=t.id and ".
                      "t.club=c.id ".
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
            "select distinct t ".
            "from ".$this->entity->getRepositoryPath('MatchRelation')." r, ".
                    $this->entity->getRepositoryPath('Match')." m, ".
                    $this->entity->getRepositoryPath('Team')." t ".
            "where m.group=:group and ".
            "r.match=m.id and ".
            "r.team=t.id and ".
            "order by t.id");
        $qb->setParameter('group', $groupid);
        $teamsList = array();
        /* @var $team Team */
        foreach ($qb->getResult() as $team) {
            $teamInfo = new TeamInfo();
            $teamInfo->id = $team->getId();
            $teamInfo->name = $team->getTeamName();
            $teamInfo->club = $team->getClub()->getName();
            $teamInfo->country = $team->getClub()->getCountry();
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
                  "q.group=g.id ".
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

    public function listTeamsEnrolledUnassigned($categoryid, $classification = 0) {
        $qb = $this->em->createQuery(
                "select t.id,t.name,t.division,c.name as club,c.country ".
                "from ".$this->entity->getRepositoryPath('Enrollment')." e, ".
                        $this->entity->getRepositoryPath('Team')." t, ".
                        $this->entity->getRepositoryPath('Club')." c ".
                "where e.category=:category and e.team=t.id and t.club=c.id and ".
                      "t.id not in (".
                            "select identity(o.team) ".
                            "from ".$this->entity->getRepositoryPath('Group')." g, ".
                                    $this->entity->getRepositoryPath('GroupOrder')." o ".
                            "where g.category=:category and g.classification=:class and ".
                                  "o.group=g.id".
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
                "where cat.tournament=:tournament and ".
                        "g.category=cat.id and ".
                        "g.classification=0 and ".
                        "o.group=g.id and ".
                        "o.team=t.id and ".
                        "t.club=c.id ".
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

    public function getUserByName($username) {
        return $this->entity->getUserRepo()->findOneBy(array('username' => $username));
    }
    
    public function listAdminUsers() {
        $admins = array();
        $users = $this->entity->getUserRepo()->findAll();
        /* @var $user User */
        foreach ($users as $user) {
            if ($user->hasRole(User::ROLE_ADMIN) || $user->hasRole(User::ROLE_SUPER_ADMIN)) {
                $admins[] = $user;
            }
        }
        return $admins;
    }
    
    public function isUserKnown($username) {
        return $this->getUserByName($username) != null;
    }
}

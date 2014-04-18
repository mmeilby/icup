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
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;

class Entity
{
    /* @var $em EntityManager */
    protected $em;
    /* @var $logger Logger */
    protected $logger;
    // Path to the doctrine entity classes
    protected $doctrinePath;

    public function __construct(EntityManager $em, $path, Logger $logger)
    {
        $this->em = $em;
        $this->doctrinePath = $path;
        $this->logger = $logger;
    }

    /**
     * Get the entity repository from the key
     * @param $repository
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getRepository($repository) {
        return $this->em->getRepository($this->getRepositoryPath($repository));
    }

    /**
     * Get the entity repository path from the key
     * @param $repository
     * @return String
     */
    public function getRepositoryPath($repository) {
        return $this->doctrinePath.$repository;
    }

    /**
     * Get the Category entity repository
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getCategoryRepo() {
        return $this->getRepository('Category');
    }
    
    /**
     * Get the Club entity repository
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getClubRepo() {
        return $this->getRepository('Club');
    }
    
    /**
     * Get the Enrollment entity repository
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getEnrollmentRepo() {
        return $this->getRepository('Enrollment');
    }
    
    /**
     * Get the Group entity repository
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getGroupRepo() {
        return $this->getRepository('Group');
    }
    
    /**
     * Get the GroupOrder entity repository
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getGroupOrderRepo() {
        return $this->getRepository('GroupOrder');
    }
    
    /**
     * Get the Host entity repository
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getHostRepo() {
        return $this->getRepository('Host');
    }
    
    /**
     * Get the Match entity repository
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getMatchRepo() {
        return $this->getRepository('Match');
    }
    
    /**
     * Get the MatchRelation entity repository
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getMatchRelationRepo() {
        return $this->getRepository('MatchRelation');
    }
    
    /**
     * Get the Playground entity repository
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getPlaygroundRepo() {
        return $this->getRepository('Playground');
    }
    
    /**
     * Get the Site entity repository
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getSiteRepo() {
        return $this->getRepository('Site');
    }
    
    /**
     * Get the Team entity repository
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getTeamRepo() {
        return $this->getRepository('Team');
    }
    
    /**
     * Get the Tournament entity repository
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getTournamentRepo() {
        return $this->getRepository('Tournament');
    }
    
    /**
     * Get the User entity repository
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getUserRepo() {
        return $this->getRepository('User');
    }
    
    /**
     * Test if user is referring to the local admin - default user object
     * @param $user User object
     * @return boolean True if user is local admin - of type Symfony\Component\Security\Core\User\User. 
     * False if user is a Entity\Doctrine\User entity object
     */
    public function isLocalAdmin($user) {
        return !($user instanceof User);
    }
    
    /**
     * Get the host from the host id
     * @param $hostid
     * @return Host
     * @throws ValidationException
     */
    public function getHostById($hostid) {
        /* @var $host Host */
        $host = $this->getHostRepo()->find($hostid);
        if ($host == null) {
            // That host id is pointing to nowhere....
            throw new ValidationException("BADHOST", "Unknown hostid=".$hostid);
        }
        return $host;
    }
    
    /**
     * Get the user from the user id
     * @param $userid
     * @return User
     * @throws ValidationException
     */
    public function getUserById($userid) {
        /* @var $user User */
        $user = $this->getUserRepo()->find($userid);
        if ($user == null) {
            throw new ValidationException("BADUSER", "Unknown userid=".$userid);
        }
        return $user;
    }

    /**
     * Get the club from the club id
     * @param $clubid
     * @return Club
     * @throws ValidationException
     */
    public function getClubById($clubid) {
        /* @var $club Club */
        $club = $this->getClubRepo()->find($clubid);
        if ($club == null) {
            // User was related to a missing club
            throw new ValidationException("BADCLUB", "Unknown clubid=".$clubid);
        }
        return $club;
    }
    
    /**
     * Get the tournament from the tournament id
     * @param $tournamentid
     * @return Tournament
     * @throws ValidationException
     */
    public function getTournamentById($tournamentid) {
        /* @var $tournament Tournament */
        $tournament = $this->getTournamentRepo()->find($tournamentid);
        if ($tournament == null) {
            throw new ValidationException("BADTOURNAMENT", "Unknown tournamentid=".$tournamentid);
        }
        return $tournament;
    }
    
    /**
     * Get the category from the category id
     * @param $categoryid
     * @return Category
     * @throws ValidationException
     */
    public function getCategoryById($categoryid) {
        /* @var $category Category */
        $category = $this->getCategoryRepo()->find($categoryid);
        if ($category == null) {
            throw new ValidationException("BADCATEGORY", "Unknown categoryid=".$categoryid);
        }
        return $category;
    }
    
    /**
     * Get the group from the group id
     * @param $groupid
     * @return Group
     * @throws ValidationException
     */
    public function getGroupById($groupid) {
        /* @var $group Group */
        $group = $this->getGroupRepo()->find($groupid);
        if ($group == null) {
            throw new ValidationException("BADGROUP", "Unknown groupid=".$groupid);
        }
        return $group;
    }
    
    /**
     * Get the group order from the group order id
     * @param $grouporderid
     * @return GroupOrder
     * @throws ValidationException
     */
    public function getGroupOrderById($grouporderid) {
        /* @var $grouporder GroupOrder */
        $grouporder = $this->getGroupOrderRepo()->find($grouporderid);
        if ($grouporder == null) {
            throw new ValidationException("BADGROUPORDER", "Unknown grouporderid=".$grouporderid);
        }
        return $grouporder;
    }
    
    /**
     * Get the match from the match id
     * @param $matchid
     * @return Match
     * @throws ValidationException
     */
    public function getMatchById($matchid) {
        /* @var $match Match */
        $match = $this->getMatchRepo()->find($matchid);
        if ($match == null) {
            throw new ValidationException("BADMATCH", "Unknown matchid=".$matchid);
        }
        return $match;
    }
    
    /**
     * Get the match relation from the match relation id
     * @param $matchrelationid
     * @return MatchRelation
     * @throws ValidationException
     */
    public function getMatchRelationById($matchrelationid) {
        /* @var $matchrelation MatchRelation */
        $matchrelation = $this->getMatchRelationRepo()->find($matchrelationid);
        if ($matchrelation == null) {
            throw new ValidationException("BADMATCHRELATION", "Unknown matchreleationid=".$matchrelationid);
        }
        return $matchrelation;
    }
    
    /**
     * Get the playground from the playground id
     * @param $playgroundid
     * @return Playground
     * @throws ValidationException
     */
    public function getPlaygroundById($playgroundid) {
        /* @var $category Category */
        $playground = $this->getPlaygroundRepo()->find($playgroundid);
        if ($playground == null) {
            throw new ValidationException("BADPLAYGROUND", "Unknown playgroundid=".$playgroundid);
        }
        return $playground;
    }
    
    /**
     * Get the site from the site id
     * @param $siteid
     * @return Site
     * @throws ValidationException
     */
    public function getSiteById($siteid) {
        /* @var $site Site */
        $site = $this->getSiteRepo()->find($siteid);
        if ($site == null) {
            throw new ValidationException("BADSITE", "Unknown siteid=".$siteid);
        }
        return $site;
    }
    
    /**
     * Get the team from the team id
     * @param $teamid
     * @return Team
     * @throws ValidationException
     */
    public function getTeamById($teamid) {
        /* @var $team Team */
        $team = $this->getTeamRepo()->find($teamid);
        if ($team == null) {
            throw new ValidationException("BADTEAM", "Unknown teamid=".$teamid);
        }
        return $team;
    }
    
    /**
     * Get the enrollment from the enrollment id
     * @param $enrollmentid
     * @return Enrollment
     * @throws ValidationException
     */
    public function getEnrollmentById($enrollmentid) {
        /* @var $enrollment Enrollment */
        $enrollment = $this->getEnrollmentRepo()->find($enrollmentid);
        if ($enrollment == null) {
            throw new ValidationException("BADENROLLMENT", "Unknown enrollmentid=".$enrollmentid);
        }
        return $enrollment;
    }
}

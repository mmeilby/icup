<?php

namespace ICup\Bundle\PublicSiteBundle\Services\Doctrine;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Enrollment;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Event;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Group;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\GroupOrder;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Host;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Match;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchAlternative;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\MatchSchedule;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\News;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Playground;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\PlaygroundAttribute;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\QMatchSchedule;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Timeslot;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Site;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team;
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
     * Get the entity class metadata from the key
     * @param $repository
     * @return \Doctrine\ORM\Mapping\ClassMetadata
     */
    public function getClassMetadata($repository) {
        return $this->em->getClassMetadata($this->getRepositoryPath($repository));
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
     * Get the Country entity repository
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getCountryRepo() {
        return $this->getRepository('Country');
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
     * Get the PlaygroundAttribute entity repository
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getPlaygroundAttributeRepo() {
        return $this->getRepository('PlaygroundAttribute');
    }
    
    /**
     * Get the Timeslot entity repository
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getTimeslotRepo() {
        return $this->getRepository('Timeslot');
    }

    /**
     * Get the MatchSchedule entity repository
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getMatchScheduleRepo() {
        return $this->getRepository('MatchSchedule');
    }

    /**
     * Get the QMatchSchedule entity repository
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getQMatchScheduleRepo() {
        return $this->getRepository('QMatchSchedule');
    }

    /**
     * Get the MatchAlternatives entity repository
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getMatchAlternativeRepo() {
        return $this->getRepository('MatchAlternative');
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
     * Get the Event entity repository
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getEventRepo() {
        return $this->getRepository('Event');
    }

    /**
     * Get the News entity repository
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getNewsRepo() {
        return $this->getRepository('News');
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
        /* @var $playground Playground */
        $playground = $this->getPlaygroundRepo()->find($playgroundid);
        if ($playground == null) {
            throw new ValidationException("BADPLAYGROUND", "Unknown playgroundid=".$playgroundid);
        }
        return $playground;
    }
    
    /**
     * Get the playground attribute from the playground attribute id
     * @param $playgroundattributeid
     * @return PlaygroundAttribute
     * @throws ValidationException
     */
    public function getPlaygroundAttributeById($playgroundattributeid) {
        /* @var $playgroundattribute PlaygroundAttribute */
        $playgroundattribute = $this->getPlaygroundAttributeRepo()->find($playgroundattributeid);
        if ($playgroundattribute == null) {
            throw new ValidationException("BADPLAYGROUNDATTRIBUTE", "Unknown playgroundattributeid=".$playgroundattributeid);
        }
        return $playgroundattribute;
    }
    
    /**
     * Get the timeslot from the timeslot id
     * @param $timeslotid
     * @return Timeslot
     * @throws ValidationException
     */
    public function getTimeslotById($timeslotid) {
        /* @var $timeslot Timeslot */
        $timeslot = $this->getTimeslotRepo()->find($timeslotid);
        if ($timeslot == null) {
            throw new ValidationException("BADTIMESLOT", "Unknown timeslotid=".$timeslotid);
        }
        return $timeslot;
    }

    /**
     * Get the match schedule from the matchschedule id
     * @param $matchscheduleid
     * @return MatchSchedule
     * @throws ValidationException
     */
    public function getMatchScheduleById($matchscheduleid) {
        /* @var $matchschedule MatchSchedule */
        $matchschedule = $this->getMatchScheduleRepo()->find($matchscheduleid);
        if ($matchschedule == null) {
            throw new ValidationException("BADMATCHSCHEDULE", "Unknown matchscheduleid=".$matchscheduleid);
        }
        return $matchschedule;
    }

    /**
     * Get the qualifying match schedule from the qmatchschedule id
     * @param $qmatchscheduleid
     * @return QMatchSchedule
     * @throws ValidationException
     */
    public function getQMatchScheduleById($qmatchscheduleid) {
        /* @var $qmatchschedule QMatchSchedule */
        $qmatchschedule = $this->getQMatchScheduleRepo()->find($qmatchscheduleid);
        if ($qmatchschedule == null) {
            throw new ValidationException("BADMATCHSCHEDULE", "Unknown qmatchscheduleid=".$qmatchscheduleid);
        }
        return $qmatchschedule;
    }

    /**
     * Get the match alternatives from the matchalternative id
     * @param $matchalternativeid
     * @return MatchAlternative
     * @throws ValidationException
     */
    public function getMatchAlternativeById($matchalternativeid) {
        /* @var $matchalternative MatchAlternative */
        $matchalternative = $this->getMatchAlternativeRepo()->find($matchalternativeid);
        if ($matchalternative == null) {
            throw new ValidationException("BADMATCHALTERNATIVE", "Unknown matchalternativeid=".$matchalternativeid);
        }
        return $matchalternative;
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
    
    /**
     * Get the event from the event id
     * @param $eventid
     * @return Event
     * @throws ValidationException
     */
    public function getEventById($eventid) {
        /* @var $event Event */
        $event = $this->getEventRepo()->find($eventid);
        if ($event == null) {
            throw new ValidationException("BADEVENT", "Unknown eventid=".$eventid);
        }
        return $event;
    }

    /**
     * Get the news from the news id
     * @param $newsid
     * @return News
     * @throws ValidationException
     */
    public function getNewsById($newsid) {
        /* @var $news News */
        $news = $this->getNewsRepo()->find($newsid);
        if ($news == null) {
            throw new ValidationException("BADNEWS", "Unknown newsid=".$newsid);
        }
        return $news;
    }

    /**
     * @param $key
     * @return null|object
     */
    public function getEntityByExternalKey($entity, $key) {
        $valid_entities = array(
            "TOURNAMENT" => "Tournament",
            "CATEGORY" => "Category",
            "GROUP" => "Group",
            "VENUE" => "Playground",
            "CLUB" => "Club",
            "TEAM" => "Team",
            "MATCH" => "Match"
        );
        if (isset($valid_entities[strtoupper($entity)])) {
            return $this->getRepository($valid_entities[strtoupper($entity)])->findOneBy(array("key" => $key));
        }
        return null;
    }
}

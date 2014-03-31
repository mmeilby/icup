<?php

namespace ICup\Bundle\PublicSiteBundle\Services\Doctrine;

use DateTime;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Enrollment;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Host;
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
    private function getRepository($repository) {
        return $this->em->getRepository($this->doctrinePath.$repository);
    }

    /**
     * Get the Host entity repository
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getHostRepo() {
        return $this->getRepository('Host');
    }
    
    /**
     * Get the User entity repository
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getUserRepo() {
        return $this->getRepository('User');
    }
    
    /**
     * Get the Tournament entity repository
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getTournamentRepo() {
        return $this->getRepository('Tournament');
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
     * Test if user is referring to the local admin - default user object
     * @param $user User object
     * @return boolean True if user is local admin - of type Symfony\Component\Security\Core\User\User. 
     * False if user is a Entity\Doctrine\User entity object
     */
    public function isLocalAdmin($user) {
        return !is_a($user, $this->doctrinePath.'User');
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
            throw new ValidationException("badhost.html.twig");
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
            throw new ValidationException("baduser.html.twig");
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
            throw new ValidationException("badclub.html.twig");
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
            throw new ValidationException("badtournament.html.twig");
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
            throw new ValidationException("badcategory.html.twig");
        }
        return $category;
    }
}

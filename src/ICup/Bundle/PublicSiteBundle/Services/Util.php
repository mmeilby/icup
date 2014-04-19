<?php

namespace ICup\Bundle\PublicSiteBundle\Services;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Services\Doctrine\Entity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session;
use Symfony\Component\Yaml\Exception\ParseException;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;

class Util
{
    /* @var $container ContainerInterface */
    protected $container;
    /* @var $em EntityManager */
    protected $em;
    /* @var $logger Logger */
    protected $logger;
   /* @var $entity Entity */
    protected $entity;

    public function __construct(ContainerInterface $container, Entity $entity, EntityManager $em, Logger $logger)
    {
        $this->container = $container;
        $this->entity = $entity;
        $this->em = $em;
        $this->logger = $logger;
    }

    public function setupController($tournament = '_')
    {
        /* @var $request Request */
        $request = $this->container->get('request');
        /* @var $session Session */
        $session = $request->getSession();
        if ($tournament == '_') {
            $tournament = $session->get('Tournament', '_');
        }
        $session->set('Tournament', $tournament);

        $this->switchLanguage();
        if ($session->get('Countries') == null) {
            $session->set('Countries', $this->getCountries());
        }
    }
    
    public function switchLanguage()
    {
        // List of supported locales - first locale is preferred default if user requests unsupported locale
        $supported_locales = array('en', 'da', 'it', 'fr', 'de', 'es', 'po');
        /* @var $request Request */
        $request = $this->container->get('request');
        /* @var $session Session */
        $session = $request->getSession();
        $language = $session->get('locale', $request->getPreferredLanguage($supported_locales));
        if (!array_search($language, $supported_locales)) {
            $request->setLocale($supported_locales[0]);
        }
        else {
            $request->setLocale($language);
        }
    }

    public function getCountries()
    {
        try {
            $dbConfig = file_get_contents(dirname(__DIR__) . '/Services/countries.xml');
        } catch (ParseException $e) {
            throw new ParseException('Could not parse the query form config file: ' . $e->getMessage());
        }
        $xml = simplexml_load_string($dbConfig, null, LIBXML_NOWARNING);
        $countries = array();
        foreach ($xml as $country) {
            $countries[(String)$country->ccode] = (String)$country->cflag;
        }
        return $countries;
    }
    
    public function getTournamentKey() {
        /* @var $request Request */
        $request = $this->container->get('request');
        /* @var $session Session */
        $session = $request->getSession();
        return $session->get('Tournament', '_');
    }

    /**
     * Get the current selcted tournament
     * @return Tournament
     */
    public function getTournament() {
        $tournamentKey = $this->getTournamentKey();
        return $this->entity->getTournamentRepo()->findOneBy(array('key' => $tournamentKey));
    }

    public function getTournamentId() {
        $tournament = $this->getTournament();
        return $tournament != null ? $tournament->getId() : 0;
    }
    
    public function generatePassword(User $user, $secret = null) {
        if ($secret == null) {
            $secret = $this->generateSecret();
        }
        $factory = $this->container->get('security.encoder_factory');
        $encoder = $factory->getEncoder($user);
        $password = $encoder->encodePassword($secret, $user->getSalt());
        $user->setPassword($password);
        $pwValid = $encoder->isPasswordValid($password, $secret, $user->getSalt());
        if (!$pwValid) {
            $this->logger->addNotice("Password is not valid: " . $user->getName() . ": " . $secret . " -> " . $password);
        }
        return $pwValid ? $secret : FALSE;
    }
    
    public function generateSecret() {
        return uniqid();
    }
    
    /**
     * Get the current logged in user
     * @return User
     * @throws RuntimeException - if no user is logged in
     */
    public function getCurrentUser() {
        /* @var $thisuser User */
        $thisuser = $this->container->get('security.context')->getToken()->getUser();
        if ($thisuser == null) {
            throw new RuntimeException("This controller is not available for anonymous users");
        }
        return $thisuser;
    }

    /**
     * Check that user is an admin
     * @param User $user
     */
    public function isAdminUser(User $user) {
        return $this->entity->isLocalAdmin($user) || $user->isAdmin();
    }

    public function validateEditorAdminUser(User $user, $hostid) {
        // If user is admin anything is allowed...
        if (!$this->isAdminUser($user)) {
            // Since this is not the admin - validate for editor
            if (!$user->isEditor()) {
                // Controller is called by admin user - switch to my page
                throw new ValidationException("NOTEDITORADMIN", "userid=".$user->getId().", role=".$user->getRole());
            }
            if ($user->getPid() != $hostid) {
                throw new ValidationException("NOTEDITORADMIN", "userid=".$user->getId().", hostid=".$hostid);
            }
        }
    }
    
    /**
     * Check that user is a true editor admin and is allowed to access the host
     * @param User $user
     * @param Mixed $hostid The host this user wants to access
     * @throws ValidationException
     */
    public function validateEditorUser($user, $hostid) {
        $this->validateHostUser($user);
        if ($user->getPid() != $hostid) {
                throw new ValidationException("NOTEDITORADMIN", "userid=".$user->getId().", hostid=".$hostid);
        }
    }
    
    /**
     * Check that user is a true editor (pid is referring to a valid host)
     * @param User $user
     * @throws ValidationException
     */
    public function validateHostUser(User $user) {
        // Validate the user - must be an editor
        if ($this->entity->isLocalAdmin($user) || !$user->isEditor()) {
            // Controller is called by admin user - switch to my page
            throw new ValidationException("NEEDTOBEEDITOR", $this->entity->isLocalAdmin($user) ?
                    "Local admin" : "userid=".$user->getId().", role=".$user->getRole());
        }
    }

    /**
     * Check that user is a true club user (cid is referring to a valid club)
     * @param User $user
     * @throws ValidationException
     */
    public function validateClubUser(User $user) {
        // Validate the user - must be a club user
        if ($this->entity->isLocalAdmin($user) || !$user->isClub()) {
            // Controller is called by editor or admin user - switch to my page
            throw new ValidationException("NEEDTOBERELATED", $this->entity->isLocalAdmin($user) ?
                    "Local admin" : "userid=".$user->getId().", role=".$user->getRole());
        }
    }
    
    public function validateCurrentUser($clubid) {
        /* @var $thisuser User */
        $thisuser = $this->getCurrentUser();
        // User must have CLUB_ADMIN role to change user properties
        if (!$this->container->get('security.context')->isGranted('ROLE_CLUB_ADMIN')) {
            throw new ValidationException("NEEDTOBERELATED", $this->entity->isLocalAdmin($thisuser) ?
                    "Local admin" : "userid=".$thisuser->getId().", role=".$thisuser->getRole());
        }
        // If controller is not called by default admin then validate the user
        if (!$this->entity->isLocalAdmin($thisuser)) {
            // If user is a club administrator then validate relation to the club
            if ($thisuser->isClub() && !$thisuser->isRelatedTo($clubid)) {
                // Even though this is a club admin - the admin does not administer this club
                throw new ValidationException("NOTCLUBADMIN", "userid=".$thisuser->getId().", role=".$thisuser->getRole());
            }
        }
        return $thisuser;
    }
    
    public function getUserById($userid) {
        /* @var $user User */
        $user = $this->entity->getUserById($userid);
        if (!$user->isClub() || !$user->isRelated()) {
            // The user has no relation?
            throw new ValidationException("NEEDTOBERELATED", "userid=".$user->getId().", role=".$user->getRole());
        }
        return $user;
    }
}

<?php

namespace ICup\Bundle\PublicSiteBundle\Services;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Services\Doctrine\Entity;
use ICup\Bundle\PublicSiteBundle\Services\Doctrine\BusinessLogic;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session;
use Symfony\Component\Yaml\Exception\ParseException;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;
use ICup\Bundle\PublicSiteBundle\Exceptions\RedirectException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\RedirectResponse;

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

    public function setupController($tournament = '_')
    {
        /* @var $request Request */
        $request = $this->container->get('request');
        /* @var $session Session */
        $session = $request->getSession();
        if ($tournament == '_') {
            $tournament = $session->get('Tournament', '_');
        }
        else {
            $session->set('Tournament', $tournament);
        }

        $this->switchLanguage();
    }
    
    private function switchLanguage()
    {
        $globals = $this->container->get('twig')->getGlobals();
        // Get list of supported locales - first locale is preferred default if user requests unsupported locale
        $supported_locales = array_keys($globals['supported_locales']);
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

    /**
     * Get list of country codes available for the application
     * @return array
     */
    public function getCountries()
    {
        $globals = $this->container->get('twig')->getGlobals();
        return array_keys($globals['countries']);
    }
    
    public function getReferer() {
        /* @var $request Request */
        $request = $this->container->get('request');
        if ($request->isMethod('GET')) {
            $returnUrl = $request->headers->get('referer');
            $session = $request->getSession();
            $session->set('icup.referer', $returnUrl);
        }
        else {
            $session = $request->getSession();
            $returnUrl = $session->get('icup.referer');
        }
        return $returnUrl;
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
        if ($tournamentKey == '_') {
            $rexp = new RedirectException();
            $url = $this->container->get('router')->generate('_tournament_select');
            $rexp->setResponse(new RedirectResponse($url));
            throw $rexp;
        }
        return $this->logic->getTournamentByKey($tournamentKey);
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
            $this->logger->addInfo("Password is not valid: " . $user->getName() . ": " . $secret . " -> " . $password);
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
        if (!($thisuser instanceof User)) {
            // Logged in with default admin - prepare an admin user
            $username = $thisuser->getUsername();
            $admin = $this->logic->getUserByName($username);
            if ($admin == null) {
                $admin = new User();
                $admin->setName($username);
                $admin->setUsername($username);
                $admin->setRole(User::$ADMIN);
                $admin->setStatus(User::$SYSTEM);
                $admin->setEmail('');
                $admin->setPid(0);
                $admin->setCid(0);
                $this->generatePassword($admin, $username);
                $this->em->persist($admin);
                $this->em->flush();
                $this->logger->addNotice("Default admin created: " . $admin->getUsername() . ":" . $admin->getId());
            }
            $thisuser = $admin;
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

    /**
     * Verify that user is admin or an editor allowed to access the host specified by hostid.
     * This function does not ensure that user->pid is referring to a valid host - if user is an admin.
     * @param User $user
     * @param Mixed $hostid
     * @throws ValidationException
     */
    public function validateEditorAdminUser(User $user, $hostid) {
        // If user is admin anything is allowed...
        if (!$this->isAdminUser($user)) {
            // Since this is not the admin - validate for editor
            if (!$user->isEditor()) {
                // Controller is called by club user user
                throw new ValidationException("NOTEDITORADMIN", "userid=".$user->getId().", role=".$user->getRole());
            }
            if (!$user->isEditorFor($hostid)) {
                // Controller is called by editor - however editor is not allowed to access this host
                throw new ValidationException("NOTEDITORADMIN", "userid=".$user->getId().", hostid=".$hostid);
            }
        }
    }
    
    /**
     * Verify that user is admin or a club user allowed to administer the club specified by clubid
     * This function does not ensure that user->cid is referring to a valid club - if user is an admin.
     * @param User $user
     * @param Mixed $clubid
     * @throws ValidationException
     */
    public function validateClubAdminUser(User $user, $clubid) {
        // If user is admin anything is allowed...
        if (!$this->isAdminUser($user)) {
            // Since this is not the admin - validate for club admin
            if (!$user->isClub()) {
                // Controller is called by club user user
                throw new ValidationException("NOTCLUBADMIN", "userid=".$user->getId().", role=".$user->getRole());
            }
            if (!$user->isRelatedTo($clubid)) {
                // Even though this is a club admin - the admin does not administer this club
                throw new ValidationException("NOTCLUBADMIN", "userid=".$user->getId().", clubid=".$clubid);
            }
        }
    }
    
    /**
     * Verify that user is a true editor (pid is referring to a valid host)
     * @param User $user
     * @throws ValidationException
     */
    public function validateEditorUser(User $user) {
        // Validate the user - must be an editor
        if ($this->entity->isLocalAdmin($user) || !$user->isEditor()) {
            // Controller is called by admin user - switch to my page
            throw new ValidationException("NEEDTOBEEDITOR", $this->entity->isLocalAdmin($user) ?
                    "Local admin" : "userid=".$user->getId().", role=".$user->getRole());
        }
    }

    /**
     * Verify that user is a true club user (cid is referring to a valid club)
     * @param User $user
     * @throws ValidationException
     */
    public function validateClubUser(User $user) {
        // Validate the user - must be a club user
        if ($this->entity->isLocalAdmin($user) || !$user->isClub() || !$user->isRelated()) {
            // Controller is called by editor or admin user - switch to my page
            throw new ValidationException("NEEDTOBERELATED", $this->entity->isLocalAdmin($user) ?
                    "Local admin" : "userid=".$user->getId().", role=".$user->getRole());
        }
    }
}

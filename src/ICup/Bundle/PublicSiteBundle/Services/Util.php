<?php

namespace ICup\Bundle\PublicSiteBundle\Services;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Services\Doctrine\Entity;
use ICup\Bundle\PublicSiteBundle\Services\Doctrine\BusinessLogic;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;
use ICup\Bundle\PublicSiteBundle\Exceptions\RedirectException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\RedirectResponse;
use ICup\Bundle\PublicSiteBundle\Controller\User\SelectClubController;
use RuntimeException;

class Util
{
    /* @var $container ContainerInterface */
    protected $container;
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
        $this->logger = $logger;
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

    public function getFlag($country) {
        $globals = $this->container->get('twig')->getGlobals();
        return array_key_exists($country, $globals['countries']) ? $globals['countries'][$country]['flag'] : null;
    }

    public function getCountryByLocale($locale) {
        $globals = $this->container->get('twig')->getGlobals();
        return $globals['supported_locales'][$locale];
    }

    public function getSupportedLocales() {
        $globals = $this->container->get('twig')->getGlobals();
        return array_keys($globals['supported_locales']);
    }

    public function getReferer() {
        /* @var $request Request */
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $route = $request->get('_route');
        if ($route) {
            $key = 'icup.referer.'.$route;
        }
        else {
            $key = 'icup.referer';
        }
        if ($request->isMethod('GET')) {
            $returnUrl = $request->headers->get('referer');
            $session = $request->getSession();
            if (!$session->has($key)) {
                $session->set($key, $returnUrl);
            }
        }
        else {
            $session = $request->getSession();
            $returnUrl = $session->remove($key);
        }
        return $returnUrl ? $returnUrl : $this->container->get('router')->generate('_icup');
    }

    public function getClubList() {
        $clubs = array();
        /* @var $request Request */
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $club_list = $request->cookies->get(SelectClubController::$ENV_CLUB_LIST, '');
        foreach (explode(':', $club_list) as $club_ident) {
            $club_ident_array = explode('|', $club_ident);
            $name = $club_ident_array[0];
            if (count($club_ident_array) > 1) {
                $countryCode = $club_ident_array[1];
            }
            else {
                $countryCode = 'EUR';
            }
            $club = $this->logic->getClubByName($name, $countryCode);
            if ($club) {
                $clubs[$club->getId()] = $club;
            }
        }
        return $clubs;
    }
    
    public function getTournamentKey() {
        /* @var $request Request */
        $request = $this->container->get('request_stack')->getCurrentRequest();
        /* @var $session Session */
        $session = $request->getSession();
        return $session->get('Tournament', '_');
    }

    public function setTournamentKey($tournament)
    {
        /* @var $request Request */
        $request = $this->container->get('request_stack')->getCurrentRequest();
        /* @var $session Session */
        $session = $request->getSession();
        $session->set('Tournament', $tournament);
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
            // Logged in with default admin - not allowed
            throw new RuntimeException("This controller is not available for default admins");
        }
        return $thisuser;
    }

    /**
     * Check that user is an admin
     * @param User $user
     */
    public function isAdminUser(User $user) {
        return $user->isAdmin();
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

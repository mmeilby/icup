<?php

namespace ICup\Bundle\PublicSiteBundle\Services;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\ClubRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Host;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Services\Doctrine\Entity;
use ICup\Bundle\PublicSiteBundle\Services\Doctrine\BusinessLogic;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
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

    public function parseHostDomain(Request $request) {
        $domain = "";
        $scoretable = array();
        $url = explode(".", $request->getHost());
        foreach ($this->logic->listHosts() as $host) {
            /* @var $host Host */
            $domain_url = explode(".", $host->getDomain());
            $i = count($url)-1;
            $j = count($domain_url)-1;
            $score = 0;
            while ($i >= 0 && $j >= 0) {
                if (strcmp($url[$i], $domain_url[$j]) == 0) {
                    $score++;
                }
                else {
                    $score = 0;
                    break;
                }
                $i--; $j--;
            }
            $scoretable[] = array("score" => $score, "alias" => $host->getAlias());
        }
        usort($scoretable, function ($item1, $item2) {
           return $item1["score"] > $item2["score"] ? -1 : 1;
        });
        return count($scoretable) > 0 ? $scoretable[0]["alias"] : "";
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
        try {
            /* @var $user User */
            $user = $this->getCurrentUser();
            /* @var $rel ClubRelation */
            foreach ($user->getClubRelations() as $rel) {
                if ($rel->getStatus() == ClubRelation::$APP || $rel->getStatus() == ClubRelation::$MEM) {
                    $clubs[$rel->getClub()->getId()] = $rel->getClub();
                }
            }
        }
        catch (RuntimeException $e) {}
        if (count($clubs) == 0) {
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
        $length = 10;
        $words = 'Dog,Cat,Sheep,Sun,Sky,Red,Ball,Happy,Ice,';
        $words .= 'Green,Blue,Music,Movies,Radio,Green,Turbo,';
        $words .= 'Mouse,Computer,Paper,Water,Fire,Storm,Chicken,';
        $words .= 'Boot,Freedom,White,Nice,Player,Small,Eyes,';
        $words .= 'Path,Kid,Box,Black,Flower,Ping,Pong,Smile,';
        $words .= 'Coffee,Colors,Rainbow,Pplus,King,TV,Ring';

        // Split by ",":
        $words = explode(',', $words);

        // Add words while password is smaller than the given length
        $pwd = '';
        while (strlen($pwd) < $length){
            $r = mt_rand(0, count($words)-1);
            $pwd .= $words[$r];
        }

        // append a number at the end if length > 2 and
        // reduce the password size to $length
        $num = mt_rand(1, 99);
        if ($length > 2){
            $pwd = substr($pwd,0,$length-strlen($num)).$num;
        } else {
            $pwd = substr($pwd, 0, $length);
        }

        return $pwd;
    }

    public function generateUsername(User $user) {
        $user->setUsername(uniqid());
    }

    /**
     * @return Club
     */
    public function getClub(User $user) {
        /* @var $rel ClubRelation */
        foreach ($user->getClubRelations()->toArray() as $rel) {
            if ($rel->getStatus() == ClubRelation::$MEM) {
                return $rel->getClub();         // TODO: fix return of many club relations
            }
        }
        return null;
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
     * @param Host $host
     * @throws ValidationException
     */
    public function validateEditorAdminUser(User $user, Host $host) {
        // If user is admin anything is allowed...
        if (!$this->isAdminUser($user)) {
            // Since this is not the admin - validate for editor
            if (!$user->isEditor()) {
                // Controller is called by club user user
                throw new ValidationException("NOTEDITORADMIN", "user=".$user->__toString());
            }
            if ($user->getHost() == null || $user->getHost()->getId() != $host->getId()) {
                // Controller is called by editor - however editor is not allowed to access this host
                throw new ValidationException("NOTEDITORADMIN", "user=".$user->__toString().", hostid=".$host->getId());
            }
        }
    }
    
    /**
     * Verify that user is admin or a club user allowed to administer the club specified by clubid
     * This function does not ensure that user->cid is referring to a valid club - if user is an admin.
     * @param User $user
     * @param Club $club
     * @throws ValidationException
     */
    public function validateClubAdminUser(User $user, Club $club) {
        // If user is admin anything is allowed...
        if (!$this->isAdminUser($user)) {
            // Since this is not the admin - validate for club admin
            if (!$user->isClubUser()) {
                // Controller is called by club user user
                throw new ValidationException("NOTCLUBADMIN", "user=".$user->__toString());
            }
            if (!$user->getClubRelations()->exists(
                function ($idx, ClubRelation $rel) use ($club) {
                    return $rel->getClub()->getId() == $club->getId() && $rel->getRole() == ClubRelation::$MANAGER && $rel->getStatus() == ClubRelation::$MEM;
                }
                )) {
                // Even though this is a club admin - the admin does not administer this club
                throw new ValidationException("NOTCLUBADMIN", "user=".$user->__toString().", clubid=".$club->getId());
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
                    "Local admin" : "user=".$user->__toString());
        }
    }

    /**
     * Verify that user is a true club user (cid is referring to a valid club)
     * @param User $user
     * @throws ValidationException
     */
    public function validateClubUser(User $user) {
        // Validate the user - must be a club user
        if ($this->entity->isLocalAdmin($user) || !$user->isClubUser() || !$user->getClubRelations()->exists(
            function ($idx, ClubRelation $rel) {
                    return $rel->getStatus() == ClubRelation::$MEM;
            }
            )) {
            // Controller is called by editor or admin user - switch to my page
            throw new ValidationException("NEEDTOBERELATED", $this->entity->isLocalAdmin($user) ?
                    "Local admin" : "user=".$user->__toString());
        }
    }
}

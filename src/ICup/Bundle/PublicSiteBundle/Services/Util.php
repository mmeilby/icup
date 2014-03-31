<?php

namespace ICup\Bundle\PublicSiteBundle\Services;

use DateTime;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Enrollment;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session;
use Symfony\Component\Yaml\Exception\ParseException;
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

    public function __construct(ContainerInterface $container, EntityManager $em, Logger $logger)
    {
        $this->container = $container;
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

    public function getTournament() {
        $tournamentKey = $this->getTournamentKey();
        return $this->em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')
                        ->findOneBy(array('key' => $tournamentKey));
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
     * Check that user is a true editor (pid is referring to a valid host)
     * @param \ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User $user
     * @throws \ICup\Bundle\PublicSiteBundle\Controller\Host\RedirectException
     */
    public function validateHostUser(User $user) {
        // Validate the user - must be an editor
        if ($this->container->get('entity')->isLocalAdmin($user) || !$user->isEditor()) {
            // Controller is called by admin user - switch to my page
            $rexp = new RedirectException();
            $rexp->setResponse($this->container->redirect($this->container->generateUrl('_user_my_page')));
            throw $rexp;
        }
    }

    public function validateClubUser(User $user) {
        // Validate the user - must be a club user
        if ($this->container->get('entity')->isLocalAdmin($user) || !$user->isClub()) {
            // Controller is called by editor or admin user - switch to my page
            $rexp = new RedirectException();
            $rexp->setResponse($this->container->redirect($this->container->generateUrl('_user_my_page')));
            throw $rexp;
        }
    }
    
    public function validateCurrentUser($clubid) {
        /* @var $thisuser User */
        $thisuser = $this->getCurrentUser();
        // User must have CLUB_ADMIN role to change user properties
        if (!$this->container->get('security.context')->isGranted('ROLE_CLUB_ADMIN')) {
            throw new ValidationException("notclubadmin.html.twig");
        }
        // If controller is not called by default admin then validate the user
        if (!$this->container->get('entity')->isLocalAdmin($thisuser)) {
            // If user is a club administrator then validate relation to the club
            if ($thisuser->isClub() && !$thisuser->isRelatedTo($clubid)) {
                // Even though this is a club admin - the admin does not administer this club
                throw new ValidationException("notclubadmin.html.twig");
            }
        }
        return $thisuser;
    }
    
    public function getUserById($userid) {
        /* @var $user User */
        $user = $this->container->get('entity')->getUserById($userid);
        if (!$user->isClub() || !$user->isRelated()) {
            // The user to be disconnected has no relation?
            throw new ValidationException("baduser.html.twig");
        }
        return $user;
    }

    public function addEnrolled(Category $category, Club $club, User $user) {
        $qb = $this->em->createQuery("select e ".
                               "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Enrollment e, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team t ".
                               "where e.pid=:category and e.cid=t.id and t.pid=:club ".
                               "order by e.pid");
        $qb->setParameter('category', $category->getId());
        $qb->setParameter('club', $club->getId());
        $enrolled = $qb->getResult();
 
        $noTeams = count($enrolled);
        if ($noTeams >= 26) {
            // Can not add more than 26 teams to same category - Team A -> Team Z
            throw new ValidationException("nomoreteams.html.twig");
        }
        
        $team = new Team();
        $team->setPid($club->getId());
        $team->setName($club->getName());
        $team->setColor('');
        $team->setDivision(chr($noTeams + 65));
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
        $qb = $this->em->createQuery("select e ".
                               "from ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Enrollment e, ".
                                    "ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team t ".
                               "where e.pid=:category and e.cid=t.id and t.pid=:club ".
                               "order by t.division");
        $qb->setParameter('category', $categoryid);
        $qb->setParameter('club', $clubid);
        $enrolled = $qb->getResult();
 
        $enroll = array_pop($enrolled);
        if ($enroll == null) {
            throw new ValidationException("noteams.html.twig");
        }
                
        $team = $this->em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team')->find($enroll->getCid());
        $this->em->remove($team);
        
        $this->em->remove($enroll);
        $this->em->flush();

        return $enroll;
    }
}

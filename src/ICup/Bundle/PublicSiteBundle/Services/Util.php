<?php

namespace ICup\Bundle\PublicSiteBundle\Services;

use DateTime;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Enrollment;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session;
use Symfony\Component\Yaml\Exception\ParseException;

class Util
{
    public function setupController(Controller $container, $tournament = '_')
    {
        /* @var $request Request */
        /* @var $session Session */
        $request = $container->getRequest();
        $session = $request->getSession();
        if ($tournament == '_') {
            $tournament = $session->get('Tournament', '_');
        }
        $session->set('Tournament', $tournament);

        $this->switchLanguage($container);
        if ($session->get('Countries') == null) {
            $session->set('Countries', $this->getCountries());
        }
    }
    
    public function switchLanguage(Controller $container)
    {
        // List of supported locales - first locale is preferred default if user requests unsupported locale
        $supported_locales = array('en', 'da', 'it', 'fr', 'de', 'es', 'po');
        /* @var $request Request */
        $request = $container->getRequest();
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
    
    public function getTournamentKey(Controller $container) {
        /* @var $request Request */
        $request = $container->getRequest();
        /* @var $session Session */
        $session = $request->getSession();
        return $session->get('Tournament', '_');
    }

    public function getTournament(Controller $container) {
        $tournamentKey = $this->getTournamentKey($container);
        return $container->getDoctrine()->getManager()
                ->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')
                ->findOneBy(array('key' => $tournamentKey));
    }

    public function getTournamentId(Controller $container) {
        $tournament = $this->getTournament($container);
        return $tournament != null ? $tournament->getId() : 0;
    }
    
    public function generatePassword(Controller $container, User $user, $secret = null) {
        if ($secret == null) {
            $secret = $this->generateSecret();
        }
        $factory = $container->get('security.encoder_factory');
        $encoder = $factory->getEncoder($user);
        $password = $encoder->encodePassword($secret, $user->getSalt());
        $user->setPassword($password);
        $pwValid = $encoder->isPasswordValid($password, $secret, $user->getSalt());
        if (!$pwValid)
            $container->get('logger')->addNotice("Password is not valid: " . $user->getName() . ": " . $secret . " -> " . $password);
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
    public function getCurrentUser(Controller $container) {
        /* @var $thisuser User */
        $thisuser = $container->getUser();
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
    public function validateHostUser(Controller $container, User $user) {
        // Validate the user - must be an editor
        if (!is_a($user, 'ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User') || !$user->isEditor()) {
            // Controller is called by admin user - switch to my page
            $rexp = new RedirectException();
            $rexp->setResponse($container->redirect($container->generateUrl('_user_my_page')));
            throw $rexp;
        }
    }

    public function validateClubUser(Controller $container, User $user) {
        // Validate the user - must be a club user
        if (!is_a($user, 'ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User') || !$user->isClub()) {
            // Controller is called by editor or admin user - switch to my page
            $rexp = new RedirectException();
            $rexp->setResponse($container->redirect($container->generateUrl('_user_my_page')));
            throw $rexp;
        }
    }
    
    public function validateCurrentUser(Controller $container, $clubid) {
        /* @var $thisuser User */
        $thisuser = $this->getCurrentUser($container);
        // User must have CLUB_ADMIN role to change user properties
        if (!$container->get('security.context')->isGranted('ROLE_CLUB_ADMIN')) {
            throw new ValidationException("notclubadmin.html.twig");
        }
        // If controller is not called by default admin then validate the user
        if (is_a($thisuser, 'ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User')) {
            // If user is a club administrator then validate relation to the club
            if ($thisuser->isClub() && !$thisuser->isRelatedTo($clubid)) {
                // Even though this is a club admin - the admin does not administer this club
                throw new ValidationException("notclubadmin.html.twig");
            }
        }
        return $thisuser;
    }

    /**
     * Get the host from the host id
     * @param $hostid
     * @return Host
     * @throws ValidationException
     */
    public function getHostById(Controller $container, $hostid) {
        $em = $container->getDoctrine()->getManager();
        /* @var $host Host */
        $host = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Host')->find($hostid);
        if ($host == null) {
            // That host id is pointing to nowhere....
            throw new ValidationException("badhost.html.twig");
        }
        return $host;
    }
    
    public function getUserById(Controller $container, $userid) {
        $em = $container->getDoctrine()->getManager();
        /* @var $user User */
        $user = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User')->find($userid);
        if ($user == null) {
            throw new ValidationException("baduser.html.twig");
        }
        if (!$user->isClub() || !$user->isRelated()) {
            // The user to be disconnected has no relation?
            throw new ValidationException("baduser.html.twig");
        }
        return $user;
    }

    public function getClubById(Controller $container, $clubid) {
        $em = $container->getDoctrine()->getManager();
        /* @var $club Club */
        $club = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club')->find($clubid);
        if ($club == null) {
            // User was related to a missing club
            throw new ValidationException("badclub.html.twig");
        }
        return $club;
    }
    
    public function addEnrolled(Controller $container, Category $category, Club $club, User $user) {
        $em = $container->getDoctrine()->getManager();

        $qb = $em->createQuery("select e ".
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
        $em->persist($team);
        $em->flush();
        
        $today = new DateTime();
        $enroll = new Enrollment();
        $enroll->setCid($team->getId());
        $enroll->setPid($category->getId());
        $enroll->setUid($user->getId());
        $enroll->setDate($today->format('d/m/Y'));
        $em->persist($enroll);
        $em->flush();

        return $enroll;
    }
    
    public function deleteEnrolled(Controller $container, $categoryid, $clubid) {
        $em = $container->getDoctrine()->getManager();

        $qb = $em->createQuery("select e ".
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
                
        $team = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team')->find($enroll->getCid());
        $em->remove($team);
        
        $em->remove($enroll);
        $em->flush();

        return $enroll;
    }
}

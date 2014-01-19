<?php

namespace ICup\Bundle\PublicSiteBundle\Services;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
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
}

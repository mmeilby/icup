<?php

namespace ICup\Bundle\PublicSiteBundle\Services;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session;
use Symfony\Component\Security\Core\User\User;
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
            $tournament = $session->get('Tournament', 'IWC2013');
        }
        $session->set('Tournament', $tournament);

        $this->switchLanguage($container);
        if ($session->get('ImagePath') == null) {
            $session->set('ImagePath', $this->getImagePath($container));
        }
        if ($session->get('Countries') == null) {
            $session->set('Countries', $this->getCountries());
        }

        $headerMenu = array(
                'MENU.TOURNAMENT.OVERVIEW' => $container->generateUrl('_tournament_overview', array('tournament' => $tournament)),
                'MENU.INFO.ABOUT' => $container->generateUrl('_tournament_overview', array('tournament' => $tournament)),
                'MENU.INFO.FAQ' => $container->generateUrl('_tournament_overview', array('tournament' => $tournament))
        );
        $session->set('HeaderMenu', $headerMenu);

        $footerMenu = array(
            'MENU.TOURNAMENT.TITLE' => array(
                'MENU.TOURNAMENT.OVERVIEW' => $container->generateUrl('_tournament_overview', array('tournament' => $tournament)),
                'MENU.TOURNAMENT.GROUPS' => $container->generateUrl('_tournament_categories', array('tournament' => $tournament)),
                'MENU.TOURNAMENT.PLAYGROUNDS' => $container->generateUrl('_tournament_playgrounds', array('tournament' => $tournament)),
                'MENU.TOURNAMENT.TEAMS' => $container->generateUrl('_tournament_clubs', array('tournament' => $tournament)),
                'MENU.TOURNAMENT.WINNERS' => $container->generateUrl('_tournament_winners', array('tournament' => $tournament)),
                'MENU.TOURNAMENT.STATISTICS' => $container->generateUrl('_tournament_statistics', array('tournament' => $tournament))),
            'MENU.ENROLLMENT.TITLE' => array(
                'MENU.ENROLLMENT.CLUBS' => $container->generateUrl('_tournament_overview', array('tournament' => $tournament)),
                'MENU.ENROLLMENT.TEAMS' => $container->generateUrl('_tournament_overview', array('tournament' => $tournament)),
                'MENU.ENROLLMENT.REFEREES' => $container->generateUrl('_tournament_overview', array('tournament' => $tournament))),
            'MENU.ADMIN.TITLE' => array(
                'MENU.ADMIN.RESULTS' => $container->generateUrl('_tournament_playgrounds', array('tournament' => $tournament)),
                'MENU.ADMIN.TOURNAMENT' => $container->generateUrl('_edit_host_list'),
                'MENU.ADMIN.TEAMS' => $container->generateUrl('_edit_host_list', array('tournament' => $tournament)),
                'MENU.ADMIN.PLAYERS' => $container->generateUrl('_edit_host_list', array('tournament' => $tournament)),
                'MENU.ADMIN.REFEREES' => $container->generateUrl('_edit_host_list', array('tournament' => $tournament)))
        );
        $session->set('FooterMenu', $footerMenu);

        /* @var $user User */
        $user = $container->getUser();
        if ($user == null) {
            $session->set('User', '-');
        }
        else {
            $session->set('User', $user->getUsername());
        }
    }
    
    public function switchLanguage(Controller $container)
    {
        /* @var $request Request */
        /* @var $session Session */
        $request = $container->getRequest();
        $session = $request->getSession();
        $language = $session->get('locale', $request->getPreferredLanguage());
        if (!array_key_exists($language, array('en', 'da', 'it', 'fr', 'de', 'es', 'po'))) $language = 'en';
        $request->setLocale($session->get('locale', $request->getPreferredLanguage()));
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
    
    public function getTournament(Controller $container) {
        /* @var $request Request */
        /* @var $session Session */
        $request = $container->getRequest();
        $session = $request->getSession();
        $em = $container->getDoctrine()->getManager();
        $tournamentKey = $session->get('Tournament', '_');
        $tournament = $em->getRepository('ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament')
                                ->findOneBy(array('key' => $tournamentKey));
        return $tournament != null ? $tournament->getId() : 0;
    }
    
    public function getImagePath(Controller $container) {
        return '/icup/web/bundles/icuppublicsite/images';
    }
}

<?php

namespace ICup\Bundle\PublicSiteBundle\Services;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class Util
{
    public function setupController(Controller $container, $tournament = '_')
    {
        /* @var $request \Symfony\Component\HttpFoundation\Request */
        /* @var $session \Symfony\Component\HttpFoundation\Session */
        $request = $container->getRequest();
        $session = $request->getSession();
        if ($tournament == '_') {
            $tournament = $session->get('Tournament', 'IWC2013');
        }

        $this->switchLanguage($container);
        $session->set('ImagePath', $this->getImagePath($container));
        $session->set('Tournament', $tournament);
        $session->set('Countries', $this->getCountries());
        $headerMenu = array(
                'MENU.TOURNAMENT.OVERVIEW' => $container->generateUrl('_tournament_overview', array('tournament' => $tournament)),
                'MENU.TOURNAMENT.GROUPS' => $container->generateUrl('_tournament_categories', array('tournament' => $tournament)),
                'MENU.TOURNAMENT.PLAYGROUNDS' => $container->generateUrl('_tournament_playgrounds', array('tournament' => $tournament)),
                'MENU.TOURNAMENT.TEAMS' => $container->generateUrl('_tournament_clubs', array('tournament' => $tournament))
        );
        $footerMenu = array(
            'MENU.TOURNAMENT.TITLE' => array(
                'MENU.TOURNAMENT.OVERVIEW' => $container->generateUrl('_tournament_overview', array('tournament' => $tournament)),
                'MENU.TOURNAMENT.GROUPS' => $container->generateUrl('_tournament_categories', array('tournament' => $tournament)),
                'MENU.TOURNAMENT.PLAYGROUNDS' => $container->generateUrl('_tournament_playgrounds', array('tournament' => $tournament)),
                'MENU.TOURNAMENT.TEAMS' => $container->generateUrl('_tournament_clubs', array('tournament' => $tournament))),
            'MENU.ENROLLMENT.TITLE' => array(
                'MENU.ENROLLMENT.CLUBS' => $container->generateUrl('_tournament_overview', array('tournament' => $tournament)),
                'MENU.ENROLLMENT.TEAMS' => $container->generateUrl('_tournament_overview', array('tournament' => $tournament)),
                'MENU.ENROLLMENT.REFEREES' => $container->generateUrl('_tournament_overview', array('tournament' => $tournament))),
            'MENU.ADMIN.TITLE' => array(
                'MENU.ADMIN.RESULTS' => $container->generateUrl('_tournament_overview', array('tournament' => $tournament)),
                'MENU.ADMIN.TOURNAMENT' => $container->generateUrl('_tournament_overview', array('tournament' => $tournament)),
                'MENU.ADMIN.TEAMS' => $container->generateUrl('_tournament_overview', array('tournament' => $tournament)),
                'MENU.ADMIN.PLAYERS' => $container->generateUrl('_tournament_overview', array('tournament' => $tournament)),
                'MENU.ADMIN.REFEREES' => $container->generateUrl('_tournament_overview', array('tournament' => $tournament)))
        );
/*        
                        <h3>{% trans %}MENU.INFO.TITLE{% endtrans %}</h3>
                                <a href="{{ path('_showtournament', { 'tournament': tournamentkey }) }}">{% trans %}MENU.INFO.ABOUT{% endtrans %}</a>
                                <a href="{{ path('_showtournament', { 'tournament': tournamentkey }) }}">{% trans %}MENU.INFO.PRICES{% endtrans %}</a>
                                <a href="{{ path('_showtournament') }}">{% trans %}MENU.INFO.FAQ{% endtrans %}</a>
*/
        $session->set('HeaderMenu', $headerMenu);
        $session->set('FooterMenu', $footerMenu);
    }
    
    public function switchLanguage(Controller $container)
    {
        /* @var $request \Symfony\Component\HttpFoundation\Request */
        /* @var $session \Symfony\Component\HttpFoundation\Session */
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
        /* @var $request \Symfony\Component\HttpFoundation\Request */
        /* @var $session \Symfony\Component\HttpFoundation\Session */
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

<?php

namespace ICup\Bundle\PublicSiteBundle\Controller\Util;

use Symfony\Component\Yaml\Exception\ParseException;

class Util
{
    private static $countries = null;
    
    public static function setupController($container, $tournament)
    {
        Util::switchLanguage($container);
        /* @var $request \Symfony\Component\HttpFoundation\Request */
        /* @var $session \Symfony\Component\HttpFoundation\Session */
        $request = $container->getRequest();
        $session = $request->getSession();
        $session->set('ImagePath', Util::getImagePath($container));
        $session->set('Tournament', $tournament);
    }

    
    public static function switchLanguage($container)
    {
        /* @var $request \Symfony\Component\HttpFoundation\Request */
        /* @var $session \Symfony\Component\HttpFoundation\Session */
        $request = $container->getRequest();
        $session = $request->getSession();
        $language = $session->get('locale', $request->getPreferredLanguage());
        if (!array_key_exists($language, array('en', 'da', 'it'))) $language = 'en';
        $request->setLocale($session->get('locale', $request->getPreferredLanguage()));
    }

    public static function getCountries()
    {
        if (self::$countries == null) {
            try {
                $dbConfig = file_get_contents(dirname(__DIR__) . '../../Resources/config/countries.xml');
            } catch (ParseException $e) {
                throw new ParseException('Could not parse the query form config file: ' . $e->getMessage());
            }
            $xml = simplexml_load_string($dbConfig, null, LIBXML_NOWARNING);
            self::$countries = array();
            foreach ($xml as $country) {
                self::$countries[(String)$country->ccode] = (String)$country->cflag;
            }
        }
        return self::$countries;
    }
    
    public static function getTournament($container) {
        $em = $container->getDoctrine()->getManager();
        $query = $em->createQuery('SELECT max(t.id) FROM ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament t');
        $idmax = $query->getResult();
        return $idmax[0][1];
    }
    
    public static function getImagePath($container) {
        return '/icup/web/bundles/icuppublicsite/images';
    }
}

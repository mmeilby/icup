<?php

namespace ICup\Bundle\PublicSiteBundle\Services;

use Symfony\Component\Yaml\Exception\ParseException;

class Util
{
    public function setupController($container, $tournament)
    {
        if ($tournament == '_') {
            $tournament = 'IWC2013';
        }

        $this->switchLanguage($container);
        /* @var $request \Symfony\Component\HttpFoundation\Request */
        /* @var $session \Symfony\Component\HttpFoundation\Session */
        $request = $container->getRequest();
        $session = $request->getSession();
        $session->set('ImagePath', $this->getImagePath($container));
        $session->set('Tournament', $tournament);
        $session->set('Countries', $this->getCountries());
    }
    
    public function switchLanguage($container)
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
    
    public function getTournament($container) {
        $em = $container->getDoctrine()->getManager();
        $query = $em->createQuery('SELECT max(t.id) FROM ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament t');
        $idmax = $query->getResult();
        return $idmax[0][1];
    }
    
    public function getImagePath($container) {
        return '/icup/web/bundles/icuppublicsite/images';
    }
}

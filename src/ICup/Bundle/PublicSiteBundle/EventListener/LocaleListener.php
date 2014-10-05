<?php

namespace ICup\Bundle\PublicSiteBundle\EventListener;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Monolog\Logger;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LocaleListener implements EventSubscriberInterface
{
    private $defaultLocale;

    protected $supported_locales;
    /* @var $logger Logger */
    protected $logger;

    public function __construct($templating, Logger $logger, $defaultLocale = 'en')
    {
        $globals = $templating->getGlobals();
        // Get list of supported locales - first locale is preferred default if user requests unsupported locale
        $this->supported_locales = array_keys($globals['supported_locales']);
        $this->logger = $logger;
        $this->defaultLocale = $defaultLocale;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        /* @var $request Request */
        $request = $event->getRequest();
        if (!$request->hasPreviousSession()) {
            return;
        }

        /* @var $session Session */
        $session = $request->getSession();
        
        // try to see if the locale has been set as a _locale routing parameter
        $locale = $request->attributes->get('_locale');
        if ($locale) {
            $session->set('_locale', $locale);
        } else {
            // if no explicit locale has been set on this request, use one from the session
            $language = $session->get('_locale', $request->getPreferredLanguage($this->supported_locales));
            if (!array_search($language, $this->supported_locales)) {
                $request->setLocale($this->defaultLocale);
            }
            else {
                $request->setLocale($language);
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            // must be registered before the default Locale listener
            KernelEvents::REQUEST => array(array('onKernelRequest', 17)),
        );
    }
}
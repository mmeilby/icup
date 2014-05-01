<?php

namespace ICup\Bundle\PublicSiteBundle\EventListener;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Monolog\Logger;

class LanguageListener
{
    protected $supported_locales;
    /* @var $logger Logger */
    protected $logger;

    public function __construct($templating, Logger $logger)
    {
        $globals = $templating->getGlobals();
        // Get list of supported locales - first locale is preferred default if user requests unsupported locale
        $this->supported_locales = array_keys($globals['supported_locales']);
        $this->logger = $logger;
    }

    /**
     * kernel.request event. If a guest user doesn't have an opened session, locale is equal to
     * "undefined" as configured by default in parameters.ini. If so, set as a locale the user's
     * preferred language.
     *
     * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
     */
    public function setLocaleForUnauthenticatedUser(GetResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        /* @var $request Request */
        $request = $event->getRequest();
        /* @var $session Session */
        $session = $request->getSession();
        $language = $session->get('locale', $request->getPreferredLanguage($this->supported_locales));
        if (!array_search($language, $this->supported_locales)) {
            $request->setLocale($this->supported_locales[0]);
        }
        else {
            $request->setLocale($language);
        }
    }

    /**
     * security.interactive_login event. If a user chose a language in preferences, it would be set,
     * if not, a locale that was set by setLocaleForUnauthenticatedUser remains.
     *
     * @param \Symfony\Component\Security\Http\Event\InteractiveLoginEvent $event
     */
    public function setLocaleForAuthenticatedUser(InteractiveLoginEvent $event)
    {
        $user = $event->getAuthenticationToken()->getUser();

        if ($lang = $user->getLanguage()) {
            $this->session->set('_locale', $lang);
        }
    }
}
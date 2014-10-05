<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICup\Bundle\PublicSiteBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Synchronizes the locale between the request and the translator.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class TranslatorListener implements EventSubscriberInterface
{
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $this->translator->setLocale($event->getRequest()->getLocale());
    }

    public static function getSubscribedEvents()
    {
        return array(
            // must be registered after the Locale listener
            KernelEvents::REQUEST => array(array('onKernelRequest', 10))
        );
    }
}

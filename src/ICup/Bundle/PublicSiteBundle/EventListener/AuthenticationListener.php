<?php

namespace ICup\Bundle\PublicSiteBundle\EventListener;

use Doctrine\ORM\EntityManager;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;

class AuthenticationListener implements EventSubscriberInterface
{
    /* @var $container ContainerInterface */
    protected $container;
    /* @var $em EntityManager */
    protected $em;
    /* @var $logger Logger */
    protected $logger;

    public function __construct(ContainerInterface $container, Logger $logger)
    {
        $this->container = $container;
        $this->em = $container->get('doctrine')->getManager();
        $this->logger = $logger;
    }

    public static function getSubscribedEvents()
    {
        return array(
            AuthenticationEvents::AUTHENTICATION_FAILURE => 'onAuthenticationFailure',
            AuthenticationEvents::AUTHENTICATION_SUCCESS => 'onAuthenticationSuccess',
        );
    }

    public function onAuthenticationFailure(AuthenticationFailureEvent $event) {
        $token = $event->getAuthenticationToken();
        /* @var $user User */
        $user = $this->container->get('logic')->getUserByName($token->getUsername());
        if ($user) {
            $user->loginFailed();
            $this->em->flush();
            if (!$user->isAccountNonLocked()) {
                $this->logger->addWarning("User account ".$user->getUsername()." is locked.");
            }
        }
    }

    public function onAuthenticationSuccess(AuthenticationEvent $event) {
        $token = $event->getAuthenticationToken();
        /* @var $user User */
        $user = $this->container->get('logic')->getUserByName($token->getUsername());
        if ($user) {
            $user->loginSucceeded();
            $this->em->flush();
        }
    }
}
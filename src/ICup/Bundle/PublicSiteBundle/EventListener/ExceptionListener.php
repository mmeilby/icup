<?php
namespace ICup\Bundle\PublicSiteBundle\EventListener;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;
use ICup\Bundle\PublicSiteBundle\Exceptions\RedirectException;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\Routing\Router;
use Monolog\Logger;

/**
 * Description of ExceptionListener
 *
 * @author mm
 */
class ExceptionListener extends ContainerAware
{

    /* @var $templating TwigEngine */
    protected $templating;
    /* @var $router Router */
    protected $router;
    /* @var $logger Logger */
    protected $logger;
    
    public function __construct(TwigEngine $templating, Router $router, Logger $logger)
    {
        $this->templating = $templating;
        $this->router = $router;
        $this->logger = $logger;
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();
        if ($exception instanceof ValidationException) {
            $event->setResponse(
                        $this->templating->renderResponse(
                            'ICupPublicSiteBundle:Errors:'.strtolower($exception->getMessage()).'.html.twig',
                            array('redirect' => $this->router->generate('_icup'))));
            $this->logger->addError("ValidationException ".$exception->getMessage().": ".$exception->getDebugInfo().' - '.$exception->getFile().':'.$exception->getLine());
        }
        elseif ($exception instanceof RedirectException) {
            $this->logger->addDebug("Handling RedirectException");
            $response = $exception->getResponse();
            $response->setStatusCode(200);
            $event->setResponse($response);
        }
    }
}

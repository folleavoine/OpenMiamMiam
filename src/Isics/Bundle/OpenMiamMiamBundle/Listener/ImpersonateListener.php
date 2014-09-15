<?php

namespace Isics\Bundle\OpenMiamMiamBundle\Listener;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

/**
 * Class ImpersonateListener
 * @package Isics\Bundle\OpenMiamMiamBundle\Listener
 */
class ImpersonateListener
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Templating\EngineInterface
     */
    protected $engine;

    /**
     * @param EngineInterface $engine
     */
    public function __construct(EngineInterface $engine) {
        $this->engine = $engine;
    }

    /**
     * Catch the error 500 page about someone you want to impersonate but already impersonate
     *
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event) {
        $exception = $event->getException();

        if(preg_match('/You are already switched to ".*" user./', $exception->getMessage())) {
            $response = new Response();
            $response->setContent($this->engine->render('IsicsOpenMiamMiamBundle:Admin:impersonate.html.twig'));
            $event->setResponse($response);
        }
    }
}
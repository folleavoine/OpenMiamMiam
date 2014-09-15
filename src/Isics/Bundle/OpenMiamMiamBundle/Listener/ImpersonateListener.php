<?php

namespace Isics\Bundle\OpenMiamMiamBundle\Listener;

use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class ImpersonateListener
{
    public function onKernelException(GetResponseForExceptionEvent $event) {
        $exception = $event->getException();

        ldd($exception);

        if($exception->getCode() == 500 && preg_match('/You are already switched to ".*" user./', $exception->getMessage())) {

        }
    }
}
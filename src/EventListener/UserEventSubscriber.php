<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class UserEventSubscriber implements EventSubscriberInterface
{
    public function onKernelRequest()
    {
        dd('it\'s alive');
    }

    public static function getSubscribedEvents(){
        return [
            RequestEvent::class => 'onKernelRequest',
        ];
    }
}
<?php

namespace App\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class UserEventSubscriber implements EventSubscriberInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger){

        $this->logger = $logger;
    }
    public function onKernelRequest(RequestEvent $event)
    {
        dd($event);
        $this->logger->info('I\'m logging SUPER early on the request');
    }

    public static function getSubscribedEvents(){
        return [
            RequestEvent::class => 'onKernelRequest',
        ];
    }
}
<?php

namespace App\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class UserEventSubscriber implements EventSubscriberInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger){

        $this->logger = $logger;
    }
    public function onKernelRequest(RequestEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest ();
        $userAgent = $request->attributes->get('isMac');
        $this->logger->info(sprintf('The User-Agent is "%s"', $userAgent));
        $request->attributes->set('_isMac', $this->isMac($request));
    }

    public static function getSubscribedEvents(){
        return [
            RequestEvent::class => 'onKernelRequest',
        ];
    }

    private function isMac(Request $request): bool
    {
        if ($request->query->has('mac')) {
            return $request->query->getBoolean('mac');
        }
        $userAgent = $request->headers->get('User-Agent');

        return stripos($userAgent, 'Mac') !== false;
    }
}
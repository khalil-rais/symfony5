<?php

namespace App\Controller;

use App\Message\Query\GetTotalImageCount;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

class MainController extends AbstractController
{
    /**
     * @Route("/")
     */
    public function homepage(MessageBusInterface $queryBus)
    {
        /*
            We can get the main command.bus
            by using the MessageBusInterface type-hint with any argument name.
            To get the query bus, we need to use that type-hint and name the argument: $queryBus.
            Do that: MessageBusInterface $queryBus.
            Inside the function, say $envelope = $queryBus->dispatch(new GetTotalImageCount()).
         */
        /*
              We haven't used it too much,
              but the dispatch() method returns the final Envelope object,
              which will have a number of different stamps on it.
              One of the properties of a query bus
              is that every query will always be handled synchronously.
              Why? Simple: we need the answer to our query right now!
              And so, our handler must be run immediately.
              In Messenger, there's nothing that enforces this on a query bus,
              it's just that we won't ever route our queries to a transport,
              so they'll always be handled right now.
              Anyways, once a message is handled,
              Messenger automatically adds a stamp called HandledStamp.
              Let's get that: $handled = $envelope->last() with HandledStamp::class.
              I'll add some inline documentation above
              that to tell my editor that this will be a HandledStamp instance.
         */
        $envelope = $queryBus->dispatch(new GetTotalImageCount());
        /** @var HandledStamp $handled */
        $handled = $envelope->last(HandledStamp::class);
        /*
            So why did we get this stamp?
            Well, we need to know what the return value of our handler was.
            And, conveniently, Messenger stores that on this stamp!
            Get it with $imageCount = $handled->getResult().
         */
        $imageCount = $handled->getResult();
        /*
            Let's pass that into the template as an imageCount variable.
        */
        return $this->render('main/homepage.html.twig', [
            'imageCount' => $imageCount
        ]);
    }
}
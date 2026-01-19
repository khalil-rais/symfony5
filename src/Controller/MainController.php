<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Messenger\MessageBusInterface;

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
        return $this->render('main/homepage.html.twig');
    }
}
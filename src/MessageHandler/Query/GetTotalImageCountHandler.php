<?php

namespace App\MessageHandler\Query;

use App\Message\Query\GetTotalImageCount;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class GetTotalImageCountHandler implements MessageHandlerInterface
{
    /*
        Next, inside of MessageHandler/,
        do the same thing:
        add a Query/ subdirectory and then a new class called GetTotalImageCountHandler.
        And like with everything else,
        make this implement MessageHandlerInterface
        and create public function __invoke() with an argument type-hinted with the message class: GetTotalImageCount $getTotalImageCount.
     */
    public function __invoke(GetTotalImageCount $getTotalImageCount){
        /*
            What do we do inside of here?
            Find the image count!
            Probably by injecting the ImagePostRepository,
            executing a query and then returning that value.
            I'll leave the querying part to you and just return 50.
         */
        return 50;
        /*
            Because we just did something totally new!
            We're returning a value from our handler!
            This is not something that we've done anywhere else.
            Commands do work but don't return any value.
            A query doesn't really do any work, its only point is to return a value.
         */
    }

}
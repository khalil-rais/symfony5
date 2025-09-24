<?php

namespace App\MessageHandler;

use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use App\Message\AddPonkaToImage;

class AddPonkaToImageHandler implements MessageHandlerInterface
{
    public function __invoke(AddPonkaToImage $addPonkaToImage)
    {
        dump($addPonkaToImage);
    }
}
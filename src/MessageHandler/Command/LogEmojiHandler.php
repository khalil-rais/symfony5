<?php

namespace App\MessageHandler\Command;

use App\Message\Command\LogEmoji;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

/*
    Step two: in the MessageHandler/Command/ directory,
    create a new LogEmojiHandler class.
    Make this implement our normal MessageHandlerInterface
 */
class LogEmojiHandler implements MessageHandlerInterface
{
    /*
        I'll paste an emoji list on top:
        here are the five that the outside system can choose from: cookie, dinosaur, cheese, robot, and of course, poop.
     */
    private static $emojis = [
        '😉',
        '😎',
        '😏',
        '😜',
        '😢'
    ];

    private $logger;

    /*
        And then, because we're going to be logging something,
        add an __construct() method with the LoggerInterface type hint.
        Hit Alt + Enter and select "Initialize Fields" one more time to create that property and set it.
     */
    public function __construct(LoggerInterface $logger){
        $this->logger = $logger;
    }

    /*
        and add public function __invoke() with the type-hint for the message:
        LogEmoji $logEmoji.
    */
    public function __invoke(LogEmoji $logEmoji){
        /*
            Inside __invoke(), our job is pretty simple:
            To get the emoji,
            set an $index variable to $logEmoji->getEmojiIndex().
            Then $emoji = self::$emojis - to reference that static property -
            self::$emojis[$index] ?? self::emojis[0].
            In other words, if the index exists, use it.
            Otherwise, fallback to logging a cookie cause everyone loves cookies.
            Log with $this->logger->info('Important message! ')
            and then $emoji.
            The big takeaway from this new message and message handler is
            that it is, well, absolutely no different from any other message and message handler!
            Messenger does not care whether the LogEmoji object will be dispatched manually from our own app or if a worker will receive a message from an outside system
            that will get mapped to this class.
         */
        $index = $logEmoji->getEmojiIndex();
        $emoji = self::$emojis[$index] ?? self::$emojis[0];
        $this->logger->info('Important message! '.$emoji);
    }

}
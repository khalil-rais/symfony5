<?php

namespace App\MessageHandler\Event;

use App\Message\Event\ImagePostDeletedEvent;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

/*
Creating an event "handler" also looks identical to command handlers.
In the MessageHandler directory, let's create another subdirectory called Event/ for organization.
Then add a new PHP class.
Let's call this RemoveFileWhenImagePostDeleted.
 */
/*
This also follows a different naming convention.
For commands, if a command was named AddPonkaToImage,
we called the handler AddPonkaToImageHandler.
The big difference between commands and events is that,
while each command has exactly one handler -
so using the "command name Handler" convention makes sense -
each event could have multiple handlers.
But the inside of a handler looks the same: implement MessageHandlerInterface
and then create our beloved public function __invoke()
with the type-hint for the event class: ImagePostDeletedEvent $event.
 */
use App\Photo\PhotoFileManager;
/*
    It is possible to dispatch an event with no handlers.
    Open RemoveFileWhenImagePostDeleted
    and take off the implements MessageHandleInterface part.
 */
/*
    I'm doing this temporarily to see what happens if Symfony sees zero handlers for an event.
    Restart the worker, it works then it logs:
    CRITICAL error!
    15:54:07 INFO      [messenger] Received message App\Message\Event\ImagePostDeletedEvent ["message" => App\Message\Event\ImagePostDeletedEvent^ { …},"class" => "App\Message\Event\ImagePostDeletedEvent"]
    15:54:07 CRITICAL  [messenger] Error thrown while handling message App\Message\Event\ImagePostDeletedEvent. Removing from transport after 3 retries. Error: "No handler for message "App\Message\Event\ImagePostDeletedEvent"." ["message" => App\Message\Event\ImagePostDeletedEvent^ { …},"class" => "App\Message\Event\ImagePostDeletedEvent","retryCount" => 3,"error" => "No handler for message "App\Message\Event\ImagePostDeletedEvent".","exception" => Symfony\Component\Messenger\Exception\NoHandlerForMessageException^ { …}]

    By default, Messenger requires each message to have at least one handler.
    That's to help us avoid silly mistakes.
    But for an event bus we do want to allow zero handlers.
    Again this is more of a philosophical problem than a real one:
    it's unlikely you'll decide to dispatch events that have no handlers.
    But, let's see how to fix it!
 */
class RemoveFileWhenImagePostDeleted
{
    private $photoFileManager;
    public function __construct(PhotoFileManager $photoFileManager){
        $this->photoFileManager = $photoFileManager;
    }
/*
Now we'll do the work,
and this will be identical to the handler we just deleted.
Add a constructor with the one service we need to delete files: PhotoFileManager.
I'll initialize fields to create that property then,
down below, finish things with $this->photoFileManager->deleteImage()
passing that $event->getFilename().
 */


    public function __invoke(ImagePostDeletedEvent $event)
    {
        $this->photoFileManager->deleteImage($event->getFilename());
    }

}
/*
We deleted a command and command handler and
replaced them with an event and an event handler
that are other than the name... identical!
 */
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
class RemoveFileWhenImagePostDeleted implements MessageHandlerInterface
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
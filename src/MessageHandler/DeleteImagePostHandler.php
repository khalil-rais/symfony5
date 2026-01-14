<?php

namespace App\MessageHandler;

use App\Message\DeleteImagePost;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use App\Message\Event\ImagePostDeletedEvent;

class DeleteImagePostHandler implements MessageHandlerInterface
{

    /*
     Inside DeleteImagePostHandler, change the argument to $eventBus.
     I don't have to, but I'm also going to rename the property to $eventBus for clarity.
     */
    private $eventBus;
    private $entityManager;
    public function __construct(MessageBusInterface $eventBus, EntityManagerInterface $entityManager){
        $this->eventBus = $eventBus;
        $this->entityManager = $entityManager;
    }


    public function __invoke(DeleteImagePost $deleteImagePost)
    {
        $imagePost  = $deleteImagePost->getImagePost();
        $filename = $imagePost->getFilename();

        $this->entityManager->remove($imagePost);
        $this->entityManager->flush();
/*
    We already have a situation like this!
    Look at DeleteImagePost and then DeleteImagePostHandler.
    The "main" job for this handler is to remove this ImagePost from the database.
    But it also has a second task: deleting the underlying file from the filesystem.
    To do that, well, we're dispatching a second command - DeletePhotoFile -
    and its handler deletes the file.
    This is the event pattern!
    Well, it's almost the event pattern.
    The only difference is the naming: DeletePhotoFile sounds like a "command".
    Instead of "commanding" the system to do something,
    an event is more of an "announcement" that something did happen.
    To fully understand this, let's back up and re-implement all of this fresh.
    Comment out the $messageBus->dispatch() call
    and then remove the DeletePhotoFile use statement on top.
 */
//        $this->messageBus->dispatch(new DeletePhotoFile($filename));
        /*
         Inside __invoke(), it's really the same as before:
        $this->eventBus->dispatch() with new ImagePostDeletedEvent() passing that $filename.
         */
        $this->eventBus->dispatch(new ImagePostDeletedEvent($filename));
        /*
            That's it! The end result of all of this work
            was to do the same thing as before,
            but with some renaming to match the "event bus" pattern.
            The handler performs its primary task
            - deleting the record from the database -
            then dispatches an event that says:
            “An image post was just deleted! If anyone cares... do something!”
         */

    }
}
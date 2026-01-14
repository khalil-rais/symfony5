<?php

namespace App\MessageHandler;

use App\Message\DeleteImagePost;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class DeleteImagePostHandler implements MessageHandlerInterface
{
    private $messageBus;
    private $entityManager;

    public function __construct(MessageBusInterface $messageBus, EntityManagerInterface $entityManager){
        $this->messageBus = $messageBus;
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

    }
}
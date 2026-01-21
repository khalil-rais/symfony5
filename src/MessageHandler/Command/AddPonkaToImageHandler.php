<?php

namespace App\MessageHandler\Command;

use App\Message\Command\AddPonkaToImage;
use App\Photo\PhotoFileManager;
use App\Photo\PhotoPonkaficator;
use App\Repository\ImagePostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

/*
    And finally, in the handlers, we have the same thing:
    each handler has a use statements for the command class it handles.
    Add the Command\ namespace on both.
 */

class AddPonkaToImageHandler implements MessageHandlerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private $ponkaficator;
    private $photoManager;
    private $entityManager;

    private $imagePostRepository;

    public function __construct(PhotoPonkaficator $ponkaficator, PhotoFileManager $photoManager, EntityManagerInterface $entityManager, ImagePostRepository $imagePostRepository){
        $this->ponkaficator = $ponkaficator;
        $this->photoManager = $photoManager;
        $this->entityManager = $entityManager;
        $this->imagePostRepository = $imagePostRepository;
    }
    # Anyways, this system is quick to use but there are a few things that you can't change.
    # For example, the method in your handler must be called __invoke(),
    # that's just what Symfony looks for.
    # And because a class can only have one method named __invoke(),
    # this means that you can't have a single handler that handles multiple different message classes.
    # I don't usually like to do this anyways,
    # I prefer one message class per handler,
    # but it is a technical limitation.
    public function __invoke(AddPonkaToImage $addPonkaToImage)
    {
        $imagePostId = $addPonkaToImage->getImagePostId();
        $imagePost = $this->imagePostRepository->find($imagePostId);
        if (!$imagePost) {
            if ($this->logger) {
                $this->logger->alert(sprintf('Image post %d was missing!',$imagePostId));
            }
            return;
        }
        /*
         * Start Ponkafication!
         */

        $updatedContents = $this->ponkaficator->ponkafy(
            $this->photoManager->read($imagePost->getFilename())
        );
        $this->photoManager->update($imagePost->getFilename(), $updatedContents);
        $imagePost->markAsPonkaAdded();
        $this->entityManager->flush();
        /*
         * You've been Ponkafied!
         */
    }
}
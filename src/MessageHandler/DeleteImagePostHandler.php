<?php

namespace App\MessageHandler;

use App\Message\DeleteImagePost;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use App\Photo\PhotoFileManager;
use Doctrine\ORM\EntityManagerInterface;

class DeleteImagePostHandler implements MessageHandlerInterface
{
    public function __invoke(DeleteImagePost $deleteImagePost)
    {
        $imagePost  = $deleteImagePost->getImagePost();
        $this->photoManager->deleteImage($imagePost->getFilename());

        $this->entityManager->remove($imagePost);
        $this->entityManager->flush();
    }

    private $photoManager;
    private $entityManager;

    public function __construct(PhotoFileManager $photoManager, EntityManagerInterface $entityManager)
    {
        $this->photoManager = $photoManager;
        $this->entityManager = $entityManager;
    }
}
<?php

namespace App\Photo;

use Doctrine\ORM\EntityManagerInterface;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\ImageManager;
use Symfony\Component\Finder\Finder;

class PhotoPonkaficator
{
    private $entityManager;
    private $imageManager;

    public function __construct(EntityManagerInterface $entityManager, ImageManager $imageManager)
    {
        $this->entityManager = $entityManager;
        $this->imageManager = $imageManager;
    }

    public function ponkafy(string $imageContents): string
    {
        $targetPhoto = $this->imageManager->read($imageContents);

        $ponkaFilename = $this->getRandomPonkaFilename();
        $ponkaPhoto = $this->imageManager->read($ponkaFilename);

        $targetWidth = $targetPhoto->width() * .3;
        $targetHeight = $targetPhoto->height() * .4;

        $ponkaPhoto->resize($targetWidth, $targetHeight);

        $targetPhoto->place($ponkaPhoto, 'bottom-right');

        // for dramatic effect, make this *really* slow
        sleep(2);

        return $targetPhoto->encode()->toString();
    }

    private function getRandomPonkaFilename(): string
    {
        $finder = new Finder();
        $finder->in(__DIR__.'/../../assets/ponka')
            ->files();

        // array keys are the absolute file paths
        $ponkaFiles = iterator_to_array($finder->getIterator());

        return array_rand($ponkaFiles);
    }
}

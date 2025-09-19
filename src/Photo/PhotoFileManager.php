<?php

namespace App\Photo;

use App\Entity\ImagePost;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PhotoFileManager
{
    private $uploadDirectory;
    private $publicAssetBaseUrl;

    public function __construct(string $uploadDirectory, string $publicAssetBaseUrl)
    {
        $this->uploadDirectory = $uploadDirectory;
        $this->publicAssetBaseUrl = $publicAssetBaseUrl;
    }

    public function uploadImage(File $file)
    {
        if ($file instanceof UploadedFile) {
            $originalFilename = $file->getClientOriginalName();
        } else {
            $originalFilename = $file->getFilename();
        }

        $newFilename = pathinfo($originalFilename, PATHINFO_FILENAME).'-'.uniqid().'.'.$file->guessExtension();
        $targetPath = $this->uploadDirectory.'/'.$newFilename;

        // Ensure upload directory exists
        if (!is_dir($this->uploadDirectory)) {
            mkdir($this->uploadDirectory, 0755, true);
        }

        if (!copy($file->getPathname(), $targetPath)) {
            throw new \Exception(sprintf('Could not write uploaded file "%s"', $newFilename));
        }

        return $newFilename;
    }

    public function deleteImage(string $filename): void
    {
        // make it a bit slow
        sleep(3);

        $filePath = $this->uploadDirectory.'/'.$filename;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    public function getPublicPath(ImagePost $imagePost): string
    {
        return $this->publicAssetBaseUrl.'/'.$imagePost->getFilename();
    }

    public function read(string $filename): string
    {
        $filePath = $this->uploadDirectory.'/'.$filename;
        if (!file_exists($filePath)) {
            throw new \Exception(sprintf('File "%s" does not exist', $filename));
        }
        return file_get_contents($filePath);
    }

    public function update(string $filename, string $updatedContents): void
    {
        $filePath = $this->uploadDirectory.'/'.$filename;
        if (file_put_contents($filePath, $updatedContents) === false) {
            throw new \Exception(sprintf('Could not update file "%s"', $filename));
        }
    }
}

<?php

namespace App\Service;

/*
    That's why I like to isolate my upload logic into a service class.
    In the Service/ directory - or really anywhere - create a new class:
    how about UploaderHelper?
 */

use Behat\Transliterator\Transliterator;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploaderHelper
{
    /*
        Perfect! Well... not perfect, because the $this->getParameter() method is a shortcut
        that only works in the controller.
        If you need a parameter - or any configuration - from inside a service,
        you need to add it via dependency injection.
        Add the public function __construct() with, how about, a string $uploadsPath argument.
        Instead of just injecting the kernel.project_dir parameter,
        we'll pass in the whole string to where uploads should be stored.
     */
    private $uploadsPath;

    public function __construct(string $uploadsPath)
    {
        $this->uploadsPath = $uploadsPath;
    }
    /*
        This class will handle all things related to uploading files.
        Create a public function uploadArticleImage():
        it will take the UploadedFile as an argument,
        remember the one from HttpFoundation - and return a string.
        That will be the string filename that was ultimately saved.
     */
    public function uploadArticleImage(UploadedFile $uploadedFile): string
    {
        /*
            Ok! Let's go steal some code for this.
            In fact, we're going to steal pretty much all the logic here...
            and paste it in.
            Make sure to retype the r on Urlizer to get the use statement on top.
        */
        /*
            I'll put my cursor on that argument name,
            hit Alt + Enter and select initialize fields to create that property and set it.
            Now, below, we can say $this->uploadsPath and then /article_image.
        */
        $destination = $this->uploadsPath.'/article_image';;
        $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $newFilename = Transliterator::urlize($originalFilename).'-' .uniqid().'.'.$uploadedFile->guessExtension();
        $uploadedFile->move(
            $destination,
            $newFilename
        );
        /*
            And at the bottom, return $newFilename.
         */
        return $newFilename;
    }
}
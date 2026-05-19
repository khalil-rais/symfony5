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
        Great first step.
        Now, let's get organized!
        One problem is that we have the directory name -article_image - in Article and also in UploaderHelper where we move the file around.
        That's not too bad - but as we start adding more file uploads to the system, we're going to have more duplication.
        I don't like having these important strings in multiple places.
        So, in UploaderHelper, why not create a constant for this? Call it ARTICLE_IMAGE and set it to the directory name: article_image.
     */
    const ARTICLE_IMAGE = 'article_image';

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
        /*
            Down below, use that: self::ARTICLE_IMAGE.
         */
        $destination = $this->uploadsPath.'/'.self::ARTICLE_IMAGE;
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
    /*
        Now getImagePath() returns the path to the image relative to wherever our app decides to store uploads.
        In UploaderHelper, add a new public function getPublicPath().
        This will take a string $path - that will be something like article_image/astronaut.jpeg -
        and it will return a string, which will be the actual public path to the file.
        Inside, return 'uploads/'.$path;.
     */
    public function getPublicPath(string $path): string
    {
        return 'uploads/'.$path;
    }
    /*
        That may feel like a micro improvement, but it's awesome! Thanks to this,
        we can call getPublicPath() from anywhere in our app to get the URL to an uploaded asset.
        If we move to the cloud, we only need to change the URL here! Awesome!
     */

}
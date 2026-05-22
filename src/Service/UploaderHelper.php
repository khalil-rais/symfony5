<?php

namespace App\Service;

/*
    That's why I like to isolate my upload logic into a service class.
    In the Service/ directory - or really anywhere - create a new class:
    how about UploaderHelper?
 */

use Behat\Transliterator\Transliterator;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Asset\Context\RequestStackContext;
use Symfony\Component\HttpFoundation\File\File;
use League\Flysystem\FilesystemInterface;

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
    private $requestStackContext;
    private $filesystem;

    /*
        Config done!
        Let's get to work in UploaderHelper.
        Instead of passing the $uploadsPath, which we were using to store things,
        change this to FilesystemInterface - the one from Flysystem - $filesystem.
        Use that below, and rename the property to $filesystem.
        Tip: If you're using version 4 of oneup/flysystem-bundle (so, flysystem v2),
        autowire Filesystem instead of FilesystemInterface from League\Flysystem.
     */
    public function __construct(FilesystemInterface $filesystem, RequestStackContext $requestStackContext)
    {
        $this->filesystem = $filesystem;
        $this->requestStackContext = $requestStackContext;
    }
    /*
        This class will handle all things related to uploading files.
        Create a public function uploadArticleImage():
        it will take the UploadedFile as an argument,
        remember the one from HttpFoundation - and return a string.
        That will be the string filename that was ultimately saved.
     */
    /*
        That's beautiful! In UploaderHelper, we need to make this work not with an UploadedFile object, but with the parent File.
        Change the type-hint to File - again, make sure you get the one from HttpFoundation
        or you will have no fun.
        To keep things clear, I'll Refactor -> Rename this variable to $file.
     */
    public function uploadArticleImage(File $file): string
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
        /*
            Let's see: everything looks happy, ah - except for getClientOriginalName():
            that method does not exist in File - it only exists in UploadedFile.
            Ok, let's get fancy then:
            if $file is an instanceof UploadedFile,
            we can say $originalFilename = $file->getClientOriginalName().
            Else, set $originalFilename to $file->getFilename() -
            that's just the name of the file on the filesytem.
         */
        if ($file instanceof UploadedFile) {
            $originalFilename = $file->getClientOriginalName();
        } else {
            $originalFilename = $file->getFilename();
        }
        /*
            After this, delete the pathinfo() stuff -
            we can move that to the next line.
            Inside urlize(), re-add the pathinfo()
            and pass the same second argument: PATHINFO_FILENAME.
         */
        $newFilename = Transliterator::urlize(pathinfo($originalFilename,
                PATHINFO_FILENAME)).'-'
            .uniqid().'.'.$file->guessExtension();
        /*
            I think that's all we need!
            Let's completely clear out the uploads/ directory.
            Now, find your terminal and run:
            php bin/console doctrine:fixtures:load
             Careful, database "main" will be purged. Do you want to continue? (yes/no) [no]:
             > yes

               > purging database
               > loading App\DataFixtures\TagFixture
               > loading App\DataFixtures\UserFixture
               > loading App\DataFixtures\ArticleFixtures

            In File.php line 36:

              The file "/Users/khalil.rais/cauldron_overflow/src/DataFixtures/images/asteroid.jpeg" does not exist
         */
        /*
            That's beautiful! In UploaderHelper, we need to make this work not with an UploadedFile object, but with the parent File.
            Change the type-hint to File - again, make sure you get the one from HttpFoundation
            or you will have no fun.
            To keep things clear, I'll Refactor -> Rename this variable to $file.
         */
        /*
            Now, in the method, instead of $file->move(), we can say
            $this->filesystem->write(), which is used to create new files. Pass this
            self::ARTICLE_IMAGE.'/'.$newFilename and then the contents of the file:
            file_get_contents() with $file->getPathname().
         */
        $this->filesystem->write(
            self::ARTICLE_IMAGE.'/'.$newFilename,
            file_get_contents($file->getPathname())
        );
        /*
            That's it!
            This File object has a ton of different methods for getting the filename, the full path,
            the file without the extension and more.
            Honestly, I get them all confused and have to Google them.
            getPathname() gives us the absolute file path on the filesystem.
            Above, we can get rid of the unused $destination variable.
            Because the filesystem's root is public/uploads/,
            the only thing we need to pass to write() is the path relative to that:
            article_image/ and then $newFilename.
            I think we're ready! Let's clear out the uploads/ directory again.
            And then try our fixtures:
            php bin/console doctrine:fixtures:load
            In DefinitionErrorExceptionPass.php line 49:
            Cannot autowire service "App\Service\UploaderHelper": argument "$filesystem" of method "__construct()" references interface "League\Flysystem\FilesystemInterface" but no such service exists. Available autowiring aliases for this interface are: "$publicUploadsFilesystem".
            Oh! It does not work!
         */
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
    /*
        Down in getPublicPath(), return $this->requestStackContext->getBasePath() and then '/uploads/'.$path.
    */
    public function getPublicPath(string $path): string
    {
        return $this->requestStackContext
                ->getBasePath().'/uploads/'.$path;
    }
    /*
        If our app lives at the root of the domain - like it does right now -
        this will just return and empty string.
        But if it lives at a subdirectory like thespacebar, it'll return /thespacebar.
     */
    /*
        That may feel like a micro improvement, but it's awesome! Thanks to this,
        we can call getPublicPath() from anywhere in our app to get the URL to an uploaded asset.
        If we move to the cloud, we only need to change the URL here! Awesome!
     */

}
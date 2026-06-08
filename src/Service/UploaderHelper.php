<?php

namespace App\Service;

/*
    That's why I like to isolate my upload logic into a service class.
    In the Service/ directory - or really anywhere - create a new class:
    how about UploaderHelper?
 */

use App\Entity\Article;
use Behat\Transliterator\Transliterator;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Asset\Context\RequestStackContext;
use Symfony\Component\HttpFoundation\File\File;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\FileNotFoundException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

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
    /*
        Now we can do the same thing down in uploadArticleReference.
        Oh, but first, we need to create another constant for the directory
        const ARTICLE_REFERENCE = 'article_reference.
     */
    const ARTICLE_REFERENCE = 'article_reference';
    private $requestStackContext;
    private $publicUploadsFilesystem;
    private $logger;
    private $publicAssetBaseUrl;
    private $privateFilesystem;


    /*
        Config done!
        Let's get to work in UploaderHelper.
        Instead of passing the $uploadsPath, which we were using to store things,
        change this to FilesystemInterface - the one from Flysystem - $filesystem.
        Use that below, and rename the property to $filesystem.
        Tip: If you're using version 4 of oneup/flysystem-bundle (so, flysystem v2),
        autowire Filesystem instead of FilesystemInterface from League\Flysystem.
     */
    /*
        First, rename the argument to be more descriptive, how about $publicUploadFilesystem:
     */
    /*
        Tip: If you're using version 4 of oneup/flysystem-bundle (so, flysystem v2),
        autowire Filesystem instead of FilesystemInterface from League\Flysystem.

        Now, copy that argument name and, in UploaderHelper,
        add a second argument: FilesystemInterface $privateUploadFilesystem.
        Create a new property on top called $privateFilesystem and set it below:
        $this->privateFilesystem = $privateUploadFilesystem

     */
    public function __construct(FilesystemInterface $publicUploadsFilesystem, FilesystemInterface $privateUploadsFilesystem, RequestStackContext $requestStackContext, LoggerInterface $logger, string $uploadedAssetsBaseUrl)
    {
        /*
            The last place is in UploaderHelper. The getBasePath() call will give us the directory
            where the site is installed - usually an empty string. Then we need to pass in the
            uploads_base_url parameter.
            Add a new argument to the constructor: string $uploadedAssetsBaseUrl. I'll create the
            property by hand and give it a slightly different name: $publicAssetBaseUrl, not for any
            particular reason. Set that in the constructor:
         */
        $this->publicUploadsFilesystem = $publicUploadsFilesystem;
        $this->requestStackContext = $requestStackContext;
        $this->logger = $logger;
        $this->publicAssetBaseUrl = $uploadedAssetsBaseUrl;
        $this->privateFilesystem = $privateUploadsFilesystem;
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
    /*
        Ok, for problem number two, go back to /admin/article.
        Log back in with password engage,
        edit an article, and go select an image - how about astronaut.jpg.
        Hit update and it works!
        So what's the problem?
        Well, we just replaced an existing image with this new one.
        Does the old file still exist in our uploads directory?
        Absolutely! But it probably shouldn't.
        When an article image is updated, let's delete the old file.
        In UploaderHelper, add a second argument - a nullable string argument called $existingFilename.
     */
    /*
        This is nullable because sometimes there may not be an existing file to delete.
     */
    public function uploadArticleImage(File $file, ?string $existingFilename): string
    {
        /*
            All done! Back up in uploadArticleImage(),
            re-select all that code we just copied, delete it,
            do a happy dance and replace it with $newFilename = $this->uploadFile() passing the $file,
            the directory - self::ARTICLE_IMAGE - and whether or not this file should be public, which is true.
         */
        $newFilename = $this->uploadFile($file, self::ARTICLE_IMAGE, true);
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
        /*
            At the bottom, it's beautifully simple:
            if an $existingFilename was passed, then $this->filesystem->delete()
            and pass that the full path,
            which will be self::ARTICLE_IMAGE.'/'.$existingFilename.
         */
        /*
            In a perfect system, the existing file will always exist, right?
            I mean, how could a filename get set on the entity without being uploaded?
            Well, what if we're developing locally and maybe
            we clear out the uploads directory to test something -
            or we clear out the uploads directory in our automated tests.
            What would happen?
            Let's find it!
            Empty uploads/.
            Back in our browser, the image preview still shows up because
            this is rendering a thumbnail file -
            which we didn't delete -
            but the original image is totally gone.
            Select earth.jpeg, update and it fails!
            It fails on $this->filesystem->delete().
            This may be the behavior you want:
            if something weird happens and the old file is gone,
            please explode so that I know.
            But, I'm going to propose something slightly less hardcore.
            If the old file doesn't exist for some reason,
            I don't want the entire process to fail, it really doesn't need to.
            The error from Flysystem is a FileNotFoundException from League\Flysystem.
            In UploaderHelper wrap that line in a try-catch.
            Let's catch that FileNotFoundException -
            the one from League\Flysystem
         */
        if ($existingFilename) {
            try {
                $result = $this->publicUploadsFilesystem->delete(self::ARTICLE_IMAGE.'/'.$existingFilename);
                /*
                    Copy that and do the same for delete:
                    “Could not delete old uploaded file "%s"” with $existingFilename.
                 */
                if ($result === false) {
                    throw new \Exception(sprintf('Could not delete old uploaded file "%s"', $existingFilename));
                }
                /*
                    I'm throwing this error instead of just logging something because
                    this would truly be an exceptional case -
                    we shouldn't let things continue.
                    But, it's your call.
                    Let's make sure this all works:
                    move over and select the stars file -
                    or actually the "Earth from Moon" photo.
                    Update and got it!
                    Next: let's teach LiipImagineBundle to play nice with Flysytem.
                    After all, if we move Flysystem to S3,
                    but LiipImagineBundle is still looking for the source files locally,
                    well we're not going to have a great time.
                 */
            }
            catch (FileNotFoundException $e) {
                /*
                    Now, down in the catch, say $this->logger->alert() -
                    alert is one of the highest log levels
                    and I usually send all logs that are this level or higher to a Slack channel.
                    Inside, how about: "Old uploaded file %s was missing when trying to delete" -
                    and pass $existingFilename.
                 */
                $this->logger->alert(sprintf('Old uploaded file "%s" was missing when trying to delete', $existingFilename));
                /*
                    Thanks to this, the user gets a smooth experience,
                    but we get notified so we can figure out how the heck the old file disappeared.
                    Move over and re-POST the form.
                    Now it works. And to prove the log worked, check out the terminal tab
                    where we're running the Symfony web server:
                    it's streaming all of our logs here.
                    Scroll up and there it is!
                    “Old uploaded file "rocket..." was missing when trying to delete”
                 */
            }

        }
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
        /*
            Back in getPublicPath(), use this: getBasePath() then
            $this->publicAssetsBaseUrl, which will contain the / at the beginning.
         */
        return $this->requestStackContext
                ->getBasePath().$this->publicAssetBaseUrl.$path;
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

    /*
        But, we'll start in very similar way:
        by opening our favorite service, and all-around nice class, UploaderHelper.
        Down here, add a new public function uploadArticleReference()
        that will have a File argument and return a string,
        pretty much the same as the other method, except that we won't need an $existingFilename
        because we won't let ArticleReference objects be updated.
        If you want to upload a modified file - cool!
        Delete the old ArticleReference and upload a new one.
        You'll see what I mean as we keep building this out.
     */
    public function uploadArticleReference (File $file) : string
    {
        /*
            Back down, all we need is return $this->uploadFile(),
            with $file, self::ARTICLE_REFERENCE and false so that it uses the private filesystem.
         */
        return $this->uploadFile($file, self::ARTICLE_REFERENCE, false);
        /*
            I think that's it!
            Let's test this puppy out!
            Move over and refresh to re-POST the form.
            No error... but I have no idea if that worked...
            because we're not rendering anything yet.
            Check out the var/ directory... var/uploads/article_reference/symfony-best-practices...,
            we got it!
            Of course, there's absolutely no way for anyone to access this file...
            but we'll fix that up soon enough.
            Next: unless we really, really, trust our authors,
            we probably shouldn't let them upload any file type.
            Let's tighten up validation.
         */
    }

    /*
        Ok, we're ready!
        Most of the logic in uploadArticleImage() should be reusable:
        we're basically going to do the same thing, just through the private filesystem:
        we need to figure out the filename and stream it through Flysystem.
        The only part of this method that we don't need is the $existingFilename.
        We don't need to delete an existing file
        because we're not going to allow files to be "updated" for a specific ArticleReference -
        we'll just have the user delete them and re-upload the new file.
        Refactoring time!
        Copy all of this code down through the fclose()
        and, at the bottom, create a new private function called uploadFile().
        This will take in the File object that we're uploading...
        and we also need to pass the directory name -
        you'll see what that is in a moment.
        Then add a bool $isPublic flag so that this method knows
        whether to store things in the public or private filesystem.
     */
    public function uploadFile(File $file,  string $directory, bool $isPublic): string
    {
        /*
            To start, paste that exact logic
         */
        if ($file instanceof UploadedFile) {
            $originalFilename = $file->getClientOriginalName();
        } else {
            $originalFilename = $file->getFilename();
        }

        $newFilename = Transliterator::urlize(pathinfo($originalFilename,
                PATHINFO_FILENAME)).'-'
            .uniqid().'.'.$file->guessExtension();

        /*
            Let's see... the first thing we need to do is handle this $isPublic argument.
            So Let's say $filesystem = $isPublic ? and, if it is public,
            use $this->filesystem, otherwise use $this->privateFilesystem.
            Below, replace $this->filesystem with $filesystem.
         */
        $filesystem = $isPublic ? $this->publicUploadsFilesystem : $this->privateFilesystem;
        $stream = fopen($file->getPathname(), 'r');
        /*
            The other thing we need to update is the directory:
            it's hardcoded to ARTICLE_IMAGE.
            Replace that with $directory:
            this is the directory inside the filesystem where the file will be stored.
         */
        $result = $filesystem->writeStream(
            $directory.'/'.$newFilename,
            $stream
        );
        if ($result === false) {
            throw new \Exception(sprintf('Could not write uploaded file "%s"', $newFilename));
        }
        if (is_resource($stream)) {
            fclose($stream);
        }
        /*
            and, at the bottom, return $newFilename.
            Oh, and I should also probably add a return type.
         */
        return $newFilename;
    }

    /*
        In some ways, our job in the controller is really simple:
        read the contents of the file and send it to the user.
        But we don't actually want to read the contents of the file into a string
        and then put it in a Response.
        Because if it's a large file,
        that will eat up PHP memory.
        This is already why, in UploaderHelper, we're using a stream to write the file.
        And now, we'll use a stream to read it.
        To keep all this streaming logic centralized in this class,
        add a new public function readStream() with a string $path argument and bool $isPublic
        so we know which of these two filesystems to read from.
     */
    /*
        Above the method, advertise that this will return a resource -
        PHP doesn't have a resource return type yet.
        Inside, step 1 is to get the right filesystem using the $isPublic argument.
     */
    /**
     * @return resource
     */
    public function readStream(string $path, bool $isPublic)
    {
        $filesystem = $isPublic ? $this->publicUploadsFilesystem : $this->privateFilesystem;
        /*
            Then, $resource = $filesystem->readStream($path).
            Tip: If you're using version 4 of oneup/flysystem-bundle (so, flysystem v2),
            you don't need to code defensively anymore!
            All methods will throw an exception automatically if the operation fails.
         */
        $resource = $filesystem->readStream($path);
        /*
            That's pretty much it!
            But hold Cmd or Ctrl and click to see the readStream() method.
            Ah yes, if this fails, Flysystem will return false.
            So let's code defensively:
            if ($resource === false),
            throw a new \Exception() with a nice message:
            “Error opening stream for %s” and pass $path.
            At the bottom, return $resource.
         */
        if ($resource === false) {
            throw new \Exception(sprintf('Error opening stream for "%s"', $path));
        }
        return $resource;
        /*
            This is great!
            We now have an easy way to get a stream to read any file in our filesystems
            which will work if the file is stored locally or somewhere else.
         */
    }
}
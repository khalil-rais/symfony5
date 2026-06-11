<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\File\File;
use App\Entity\Article;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use App\Service\UploaderHelper;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\ArticleReference;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints\File as FileConstraints;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;

class ArticleReferenceAdminController extends BaseController
{
    /*
        Unlike the main form on this page,
        this form will submit to a different endpoint.
        And instead of continuing to put more things into ArticleAdminController,
        let's create a new controller for everything related to article references: ArticleReferenceAdminController.
        Extend BaseController - that's just a small base controller we created in our Symfony series:
        it extends the normal AbstractController. So nothing magic happening there.
     */
    /*
        Back in the new class,
        create public function uploadArticleReference() and, above, @Route:
        make sure to get the one from Symfony/Component.
        Set the URL to, how about, /admin/article/{id}/references - where the {id} is the Article id
        that we want to attach the reference to.
        Add name="admin_article_add_reference".
        Oh, and let's also set methods={"POST"}.
     */
    /*
        That's optional, but it'll let us create another endpoint later with the same URL
        that can be used to fetch all the references for a single article.
     */
    /*
        Let's keep going!
        Because the article {id} is in the URL,
        add an Article $article argument.
        Oh, and we need security!
        You can only upload a file if you have access to edit this article.
        In our app, we check that with this @IsGranted("MANAGE", subject="article") annotation,
        which leverages a custom voter that we created in our Symfony series.
        It basically makes sure that you are the author of this article or a super admin.
     */
    /*
        But because we're not inside a form,
        we need to validate directly...
        which is totally fine!
        Add another argument: ValidatorInterface $validator.
        This is the service that the form system uses internally for validation.
     */
    /**
     * @Route("/admin/article/{id}/references",name="admin_article_add_reference", methods={"POST"})
     * @IsGranted("MANAGE", subject="article")
     */
    public function uploadArticleReference (Article $article, Request $request, UploaderHelper $uploaderHelper, EntityManagerInterface
    $entityManager, ValidatorInterface $validator)
    {
        /*
            Back in the controller, let's finish this whole darn thing.
            Set the file to an $uploadedFile object
            and I'll add the same inline documentation
            that says that this is an UploadedFile object - the one from HttpFoundation.
         */
        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $request->files->get('reference');
        /*
            When you select a file with Dropzone,
            it's smart enough to upload to the action URL on our form.
            So in theory it should just sort of work.
            Back in the controller, scroll up to the upload endpoint and dump($uploadedFile).
            I'm not using dd() - dump and die -
            because this will submit via AJAX -
            and by using dump() without die'ing,
            we'll be able to see it in the profiler.
         */
        dump($uploadedFile);
        /*
            Ok: select a file.
            The first cool thing is that the file upload AJAX request showed up down on the web debug toolbar!
            I'll click the hash and open that up in a new tab.
            This is awesome!
            We're now looking at all the profiler data for that AJAX request!
            Actually that's not true.
            Look closely: it says that we were redirected from a POST request to the admin_article_add_reference route.

            302 Redirect from POST @admin_article_add_reference (084a77)
            We're looking at the profiler for the article edit page!
            This is a bit confusing.
            Click the "Last 10" link to see a list of the last 10 requests made into our app.
10 results found
Status 	IP 	Method 	URL 	Time 	Token
200 	127.0.0.1   GET https://127.0.0.1:8000/admin/article/91/edit    08-Jun-2026 15:35:14 	deb9e4
302 	127.0.0.1   POST    https://127.0.0.1:8000/admin/article/91/references  08-Jun-2026 15:35:14 	084a77
        ...
            Now it's more obvious:
            Dropzone made a POST request to /admin/article/41/references - that's our upload endpoint.
            But, for some reason, that redirected us to the edit page.
            Click the token link to see the profiler for the POST request.

            Check out the Debug tab.
            There it is: this is the dump from our controller and it's null:
            Dumped Contents
            In ArticleReferenceAdminController.php line 86:

            null

            Where's our upload?
            The problem is that, by default, Dropzone uploads a field called file:
Uploaded Files
Key 	Value
file

Symfony\Component\HttpFoundation\File\UploadedFile {#16 ▼
  -test: false
  -originalName: "plektrum-desktop-variant3.png"
  -mimeType: "image/png"
  -error: 0
  path: "/private/var/folders/7k/dmlmkxps5259w7n8h4q4p4b00000gn/T"
  filename: "phpm7rd58nen6egbOeuCrP"
  basename: "phpm7rd58nen6egbOeuCrP"
  pathname: "/private/var/folders/7k/dmlmkxps5259w7n8h4q4p4b00000gn/T/phpm7rd58nen6egbOeuCrP"
  extension: ""
  realPath: "/private/var/folders/7k/dmlmkxps5259w7n8h4q4p4b00000gn/T/phpm7rd58nen6egbOeuCrP"
  aTime: 2026-06-08 15:35:14
  mTime: 2026-06-08 15:35:14
  cTime: 2026-06-08 15:35:14
  inode: 100437607
  size: 8031
  perms: 0100600
  owner: 501
  group: 20
  type: "file"
  writable: true
  readable: true
  executable: false
  file: true
  dir: false
  link: false
}
            But in the controller, we're expecting it to be called reference:
Key 	Value
reference ""
         */
        $violations = $validator->validate(
            $uploadedFile,
            [
                new NotBlank(),
                /*
                    Refresh one more time.
                    The huge error is replaced by a much more pleasant validation message.
                    Next: the author can upload a file reference...
                    but it is literally impossible for them to download it.
                    How can we make these private files accessible,
                    but still check security first?
                 */
                new FileConstraints([
                    'maxSize' => '5M',
                    'mimeTypes' => [
                        'image/*',
                        'application/pdf',
                        'application/msword',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                        'text/plain',
                    ],
                ]),
            ],
        );
        /*
            Let's try it out! Go back, select the Best Practices PDF, Upload and... no error!
            Try it again - but with this earth.zip file - that's a zip of two photos.
            Submit and... error!
            But wow is that a wordy error.
            You can change that message with the mimeTypesMessage option.
         */
        if ($violations->count() > 0) {
            /*
                The AJAX upload finishes successfully
                but the response is a redirect
                which doesn't break anything technically
                but it's weird.
                Our endpoint isn't setup to be an API endpoint -
                it's 100% traditional:
                we're redirecting on error and success.
                But now that we are using this as an API endpoint, let's fix that!
                And this kinda simplifies things.
                For the validation error, we can say return $this->json($violations, 400).
             */
            return $this->json($violations, 400);
        }
        /*
            Cool! Move over, select the Best Practices PDF - that's definitely more than 1kb - and upload!
            Say hello to the ConstraintViolationList:
            a glorified array of ConstraintViolation error objects.
            And there's the message: the file is too large.
            If you want, you can customize that message by passing the maxSizeMessage option...
            because it is kind of a nerdy message.
         */
        /*
            ArticleReferenceAdminController.php on line 109:
            Symfony\Component\Validator\ConstraintViolationList {#360 ▼
              -violations: array:1 [▼
                0 => Symfony\Component\Validator\ConstraintViolation {#354 ▼
                  -message: "The file is too large (47 kB). Allowed maximum size is 1 kB."
                  -messageTemplate: "The file is too large ({{ size }} {{ suffix }}). Allowed maximum size is {{ limit }} {{ suffix }}."
                  -parameters: array:5 [▼
                    "{{ file }}" => ""/private/var/folders/7k/dmlmkxps5259w7n8h4q4p4b00000gn/T/phpvsia7iq05ftielIkFHM""
                    "{{ size }}" => "47"
                    "{{ limit }}" => "1"
                    "{{ suffix }}" => "kB"
                    "{{ name }}" => ""tbt_9_16.png""
                  ]
                  -plural: null
                  -root: Symfony\Component\HttpFoundation\File\UploadedFile {#16 ▶}
                  -propertyPath: ""
                  -invalidValue: Symfony\Component\HttpFoundation\File\UploadedFile {#16 ▶}
                  -constraint: Symfony\Component\Validator\Constraints\File {#534 ▶}
                  -code: "df8637af-d466-48c6-a59d-e7126250a654"
                  -cause: null
                }
              ]
            }
         */
        /*
            Then say $filename =... oh -
            we don't have the UploaderHelper service yet!
            Add that argument: UploaderHelper $uploaderHelper.
            Then $filename = $uploaderHelper->uploadArticleReference($uploadedFile).
         */
        $filename = $uploaderHelper->uploadArticleReference($uploadedFile);
        /*
            We know that won't work yet...
            but if we use our imagination,
            we know that... someday, it should
            return the new filename that was stored on the filesystem.
            To put this value into the database,
            we need to create a new ArticleReference object and persist it.
         */
        /*
            Back up in our controller, say $articleReference = new ArticleReference()
            and pass $article.
            Call $article->setFilename($filename) to store the unique filename
            where this file was stored on the filesystem.
         */
        $articleReference = new ArticleReference($article);
        $articleReference->setFilename($filename);
        /*
            But remember! There are a couple of new pieces of info
            that we can set on ArticleReference- like the original filename.
            Set that to $uploadedFile->getClientOriginalName().
            Now, technically this method can return null, though,
            I'm not actually sure if that's something that can happen in any realistic scenario.
            But, just in case, add ?? $filename.
            So, if the client original name is missing for some reason, fall back to $filename.
         */
        $articleReference->setOriginalFilename($uploadedFile->getClientOriginalName() ?? $filename);
        /*
            Finally, just in case we ever want to know what type of file this is,
            we'll store the file's mime type.
            Set this to $uploadedFile->getMimeType().
            This can also return null -
            so default it to application/octet-stream,
            which is sort of a common way to say
            "I have no idea what this file is".
         */
        $articleReference->setMimeType($uploadedFile->getMimeType() ?? 'application/octet-stream');
        /*
            With that done, save this: add the EntityManagerInterface $entityManager argument,
            then $entityManager->persist($articleReference) and $entityManager->flush().
         */
        $entityManager->persist($articleReference);
        $entityManager->flush();
        /*
            How nice is that?
            And at the bottom, we don't really need to return anything yet,
            but it's pretty standard to return the JSON of a resource after creating it.
            So, return $this->json($articleReference).
         */
        /*
            return $this->json($articleReference);
            Let's try it!
            Move over, refresh
            even though we don't need to
            and select astronaut.jpg.
            This time it fails!
            Let's see what the error looks like.
            Hmm, actually, better: click to open the
            profiler - you can always see the error there. Oh:
            “A circular reference has been detected when serializing the object of class "App\Entity\Article" (configured limit: 1).”
            This is a super common problem with the serializer,
            and we saw it earlier.

            We're serializing ArticleReference.
            And, by default, that will serialize all the properties that have getter methods including the article property.
            Then when it serializes the Article,
            it finds the $articleReferences property
            and tries to serialize the ArticleReference objects in an endless loop.
         */
        /*
            Back in the controller, let's break this onto multiple lines.
            The second argument is the status code and we should actually use 201 -
            that's the proper status code when you've created a resource.
            Next is headers - we don't need anything custom,
            and, for context, add an array with groups set to ['main'].
         */
        return $this->json(
            $articleReference,
            201,
            [],
            [
                'groups' => ['main']
            ]
        );
        /*
            Let's see if that fixed things.
            Close the profiler and select "stars".
            Duh - I totally forgot - the stars file is too big -
            you can see it failed.
            But when you hover over it object Object?
            That's not a great error message.
            We'll fix that in a minute.
            Select Earth from the Moon.jpg and nice!
            It works and the JSON response looks awesome!
            {"id":7,"filename":"plektrum-desktop-6a2a75f967162.png","originalFilename":"plektrum-desktop.png","mimeType":"image\/png"}
         */
    }

    /*
        To add a download link,
        we know that we can't just link to the file directly:
        it's not public.
        Instead, we're going to link to a Symfony route and controller
        and that controller will check security and return the file to the user.
        Let's do this in ArticleReferenceAdminController.
        Add a new public function, how about, downloadArticleReference().
     */
    /*
        Add the @Route() above this with /admin/article/references/{id}/download -
        where the {id} this time is the id of the ArticleReference object.
        Then, name="admin_article_download_reference" and methods={"GET"},
        just to be extra cool.
     */
    /**
     * @Route("/admin/article/references/{id}/download", name="admin_article_download_reference", methods={"GET"})
     */
    public function downloadArticleReference(ArticleReference $reference, UploaderHelper $uploaderHelper)
    {
        /*
            In the controller add the UploaderHelper argument.
            Oh, but before we use this,
            I forgot to check security!
            That was the whole point!
            The goal is to allow these files to be downloaded by anyone
            who has access to edit the article.
            We've been checking that via the @IsGranted('MANAGE') annotation -
            which leverages a custom voter we created in the Symfony series.
            We can use this annotation here
            because the article in the annotation refers to the $article argument to the controller.
            But in this new controller, we don't have an article argument,
            so we can't use the annotation in the same way.
            No problem: add $article = $reference->getArticle()
            and then run the security check manually: $this->denyAccessUnlessGranted()
            with that same 'MANAGE' string and $article.
         */
        $article = $reference->getArticle();
        $this->denyAccessUnlessGranted('MANAGE', $article);
        /*
            We have a method that will allow us to open a stream of the file's contents.
            But how can we send that to the user?
            We're used to returning a Response object or a JsonResponse object
            where we already have the response as a string or array.
            But if you want to stream something to the user
            without reading it all into memory,
            you need a special class called StreamedResponse.
            Add $response = new StreamedResponse().
            This takes one argument - a callback. At the bottom, return this.
         */
        $response = new StreamedResponse(function() use ($reference,$uploaderHelper) {
            /*
                Here's the idea: we can't just start streaming the response or echo'ing content right now inside the controller:
                Symfony's just not ready for that yet,
                it has more work to do, more headers to set, etc.
                That's why we normally create a Response object and later,
                when it's ready, Symfony echo's the response's content for us.
                With a StreamedResponse, when Symfony is ready to finally send the data,
                it executes our callback and then we can do whatever we want.
                Heck, we can echo 'foo' and that's what the user would see.

                Add a use statement and bring $reference and $uploaderHelper into the callback's scope
                so we can use them.
                To send a file stream to the user,
                it looks a little strange.
                Start with $outputStream set to fopen('php://output') and wb.
             */
            $outputStream = fopen('php://output', 'wb');
            /*
                We usually use fopen to write to a file.
                But this special php://output allows us to write to the "output" stream -
                a fancy way of saying that anything we write to this stream will just get "echo'ed" out.
                Next, set $fileStream to $uploaderHelper->readStream()
                and pass this the path to the file -
                something like article_reference/symfony-best-practices-blah-blah.pdf.
             */
            /*
                Great! Back in the controller, pass $reference->getFilePath()
                and then false for the $isPublic argument.
             */
            $fileStream = $uploaderHelper->readStream($reference->getFilePath(), false);
            /*
                Finally, now that we have a "write" stream
                and a "read" stream, we can use a function called stream_copy_to_stream() to do exactly that!
                Copy $fileStream to $outputStream.
             */
            stream_copy_to_stream($fileStream, $outputStream);
            /*
                There ya go! The fanciest way of echo'ing content that you've probably ever seen,
                but it avoids eating memory.
             */
        });
        /*
            Try it out!
            Refresh and it works sort of.
            We are sending the file contents
            but the browser is clearly not handling it well.
            The reasons is that we haven't told the browser what type of file this is,
            so it's just treating it like the world's ugliest web page.
            And... hey! Remember when we stored the $mimeType of the file in the database?
            that's about to come in handy big time!
            Add $response->headers->set() with Content-Type set to $reference->getMimeType().
         */
        $response->headers->set('Content-Type', $reference->getMimeType());
        /*
            Another thing you might want to do is force the browser to download the file.
            It's really up to you.
            By default, based on the Content-Type,
            the browser may try to open the file - like it is here -
            or have the user download it.
            To force the browser to always download the file,
            we can leverage a header called Content-Disposition.
            This header has a very specific format,
            so Symfony comes with a helper to create it.
            Say $disposition = HeaderUtils::makeDisposition().
            For the first argument, we'll tell it
            whether we want the user to download the file,
            or open it in the browser by passing HeaderUtils::DISPOSITION_ATTACHMENT or DISPOSITION_INLINE.
         */
        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            /*
                Next, pass it the filename.
                This is especially cool because,
                without this, the browser would probably try to call the file just "download" -
                because that's the last part of the URL.
                Now it will use $reference->getOriginalFilename().
                Tip: If your original filename is not in ASCII characters,
                add a 3rd argument to HeaderUtils::makeDisposition to provide a "fallback" filename.
             */
            $reference->getOriginalFilename()
        );
        /*
            Before we set this header,
            I just want you to see what it looks like.
            So, dd($disposition)
         */
        //dd($disposition);
        /*
            ArticleReferenceAdminController.php on line 398:
            "attachment; filename=CV_Rais_de_260602.pdf"
         */
        /*
            move over, refresh and there it is.
            It's just a string, like any other header -
            but it has this specific format,
            which is why Symfony has a helper method.
         */
        /*
            Set this on the actual response with $response->headers->set('Content-Disposition', $disposition).
         */
        $response->headers->set('Content-Disposition', $disposition);
        /*
            Try it one more time.
            Yes! It downloads and uses the original filename.
            Next: let's make this all way cooler by uploading instantly via AJAX.
         */

        return $response;
    }

    /*
        To power the frontend,
        we need a new API endpoint
        that will return all of the references for a specific Article.
        We got this: go into ArticleReferenceAdminController
        and create a new public function called getArticleReferences().
     */
    /*
        Add the @Route() above this with /admin/article/{id}/references.
        This time, the id is the article id.
        URLs aren't technically important, but this is on purpose:
        in an API, /admin/article/{id} would be the URL
        to get info about a specific article.
        Adding /references onto that is a nice way to read its references.
        Now add the methods="GET" - yes you can leave off the curly braces
        when there's just one method - and name="admin_article_list_references".
     */
    /*
        Down in the method, add the Article argument
        and don't forget the security check:
        @IsGranted("MANAGE").
        We can use the annotation this time
        because we do have an article argument.
        Then, oh, it's beautiful:
        return $this->json($article->getArticleReferences());.
     */
    /*
        How nice is it!?
        Let's check it out: in the browser,
        take off the /edit and replace it with /references.
        And... oh boy, it explodes!
        “Semantical error: Couldn't find constant article... make sure annotations are installed and enabled.”
     */
    /*
        Well, they are - this is a total rookie mistake I made with my annotations.
        On the @IsGranted annotation, it should be subject="article".
        @IsGranted("MANAGE", subject="article").
        Try it again.
    */
    /**
     * @Route("/admin/article/{id}/references", methods="GET", name="admin_article_list_references")
     * @IsGranted("MANAGE", subject="article")
     */
    public function getArticleReferences(Article $article)
    {
        /*
            return $this->json($article->getArticleReferences());

            Here we go - that's the error I was expecting:
            our favorite circular reference has been detected.
            This is the exact same thing we saw a second ago
            when we tried to serialize a single ArticleReference.
            And the fix is the same:
            we need to use the main serialization group.
            Pass 200 as the status code, no custom headers,
            but one custom groups option set to main.
         */
        return $this->json(
            $article->getArticleReferences(),
            200,
            [],
            [
                'groups' => ['main']
            ]
        );
        /*
            Try it again. Gorgeous!
            That contains everything we need to render the list in JavaScript.
            https://127.0.0.1:8000/admin/article/91/references
            [{"id":1,"filename":"tbt-9-16-6a22daf304c5e.png","originalFilename":"tbt_9_16.png","mimeType":"image\/png"},{"id":2,"filename":"cv-rais-de-260602-6a22edbbe35ad.pdf","originalFilename":"CV_Rais_de_260602.pdf","mimeType":"application\/pdf"},{"id":3,"filename":"cv-rais-de-260602-6a22edc59ceac.pdf","originalFilename":"CV_Rais_de_260602.pdf","mimeType":"application\/pdf"},...]
         */


    }

    /*
        The next thing our file gallery needs is the ability to delete files.
        I know this tutorial is all about uploading
        but in these chapters, we're sorta, accidentally creating a nice API for our Article references.
        We already have the ability to get all references for a specific article,
        create a new reference and download a reference's file.
        Now we need an endpoint to delete a reference.
        Add a new function at the bottom called deleteArticleReference().
        Put the @Route() above this with /admin/article/references/{id}, name="admin_article_delete_reference" and - this will be important - methods={"DELETE"}.
        We do not want to make it possible
        to make a GET request to this endpoint.
        First, because that's crazy-dangerous.
        And second, because if we kept building out the API,
        we would want to have a different endpoint for making a GET request to /admin/article/references/{id}
        that would return the JSON for that one reference.
     */
    /**
     * @Route("/admin/article/references/{id}", name="admin_article_delete_reference", methods={"DELETE"})
     */
    public function deleteArticleReference(ArticleReference $reference)
    {
        /*
            Inside, add the ArticleReference $reference argument and then we'll add our normal security check.
            In fact, copy it from above and put it here.
         */
        $article = $reference->getArticle();
        $this->denyAccessUnlessGranted('MANAGE', $article);
    }

}
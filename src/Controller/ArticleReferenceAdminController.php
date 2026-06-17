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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Serializer\SerializerInterface;
use Aws\S3\S3Client;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Api\ArticleReferenceUploadApiModel;

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
    /*
        Let me show you what I mean.
        I'm going to use Postman to interact with our endpoint
        as if it were truly meant to be an API endpoint used by API clients.
        For the URL, copy the URL in the browser,
        paste, and change /edit to /references.
        Yep, that'll hit our controller.
        Make this a POST request.
        What about the body of the request?
        What should that look like?
        Well, because we wrote our endpoint to basically handle a traditional form-submit,
        the format will be form-data.
        For the key, remember that we're expecting the file data on a field called reference.
        Change the field type to "file" and select earth.jpeg.
        That's it! Before trying this, our site is being served over https thanks to the Symfony local web server
        and some certificate magic it does behind the scenes.
        But Postman doesn't know to use that magic,
        so the certificate won't work.
        In the Postman preferences - I've already done it - turn SSL verification off.
        Or you can run the Symfony web server with the --allow-http flag if you want to avoid this.
        Ok, send the request! Oh... what's this?
        Check out the preview.
        The login page, of course!
        Uploading requires a valid user.
        Just to play around, let's remove the @IsGranted() temporarily.
     */
    /**
     * @Route("/admin/article/{id}/references",name="admin_article_add_reference", methods={"POST"})
     */
    public function uploadArticleReference (Article $article, Request $request, UploaderHelper $uploaderHelper, EntityManagerInterface
    $entityManager, ValidatorInterface $validator, SerializerInterface $serializer)
    {
        /*
            1- Love it! Let's get to work.
            Back in our controller, to see what it looks like,
            let's make this endpoint capable of handling both ways of uploading files: form-data and JSON.
            We can figure out which situation we're in by looking at the Content-Type header.
            So, if $request->headers->get('Content-Type') === 'application/json',
            we'll do our new thing, else, run the normal code.
            And... this is pretty cool... the only part that'll really be different is the $uploadedFile part.
            Move that into the else.
         */
        if ($request->headers->get('Content-Type') === 'application/json')
        {
            /*
                4- We're ready! Back in the controller add a new argument at the end: SerializerInterface $serializer.
                Then, it's beautiful, really $uploadApiModel = $serializer->deserialize().
                This takes three arguments: the raw JSON - $request->getContent() - the type of object it should be turned into -
                ArticleReferenceUploadApiModel::class - and the input format, json.
             */
            $uploadApiModel = $serializer->deserialize(
                $request->getContent(),
                ArticleReferenceUploadApiModel::class,
                'json'
            );
            /*
                We don't need a context this time, because we're not deserializing into an existing object
                and we don't need to use groups.
                And because this object has some constraints,
                we'll need to check validation up here:
                $violations = $validator->validate($uploadApiModel).
                And if $violations->count() > 0, return the normal, $this->json($violations, 400).
             */
            $violations = $validator->validate($uploadApiModel);
            if ($violations->count() > 0) {
                return $this->json($violations, 400);
            }
            /*
                At the bottom, let's dd($uploadApiModel) so we can see if this crazy idea is working.
             */
            dd($uploadApiModel);
            /*
                You ready to try this?
                Spin back over to Postman, high-five someone near you and... send! Hey!
                Check out that beautiful dump!
                The text is still encoded, but that's a killer first step.
                Leave the filename blank to check validation. Looks great.
                Let's finish this next:
                we still need to base64 decode that data and push it into our normal file upload system.
                Let's do that in a clean way that we can love.
             */
        }
        else
        {
            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $request->files->get('reference');
        }
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
        /*
            Try it again. Beautiful! It works!
            So, the first way to build an upload endpoint for an API is like this!
            An endpoint that requires the multipart form data format
            that we checked out at the beginning of this tutorial.
            Any API client will be able to work with this and a lot of API's are built this way.
         */
    }

    /*
        Head back to downloadArticleReference().
        Remove the UploaderHelper argument -
        we won't need that anymore -
        and add S3Client $s3client.
        Also add string $s3BucketName.
     */
    /**
     * @Route("/admin/article/references/{id}/download", name="admin_article_download_reference", methods={"GET"})
     */
    public function downloadArticleReference(ArticleReference $reference, S3Client $s3Client, string $s3BucketName)
    {
        /*
            Cool! Back in the controller,
            copy the $disposition line -
            we're going to put this back in a minute.
            Then, delete everything after the security check,
            paste the $disposition line,
            but comment it out for now.
            Ok, let's go steal some code from the docs!
            We already have the S3Client object,
            so just grab the rest.
            Paste that then... let's see... replace my-bucket with the $s3BucketName variable.
            For Key, that's the file path: $reference->getFilePath().
            And, for $request = $s3Client->createPresignedRequest(),
            you can use whatever lifetime you want.
            These files are pretty small,
            so we don't need too much time -
            but let's make the URLs live for 30 minutes.
         */

        $article = $reference->getArticle();
        $this->denyAccessUnlessGranted('MANAGE', $article);
        /*
            2- And ResponseContentDisposition.
            Move the $disposition code up above,
            then use that value down here.
         */
        $disposition = HeaderUtils::makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $reference->getOriginalFilename()
        );
        /*
            3- Cool, right? Go download the file one more time.
            Ha! It downloads and uses the original filename.
            This is probably the best way to allow users to download private files.
            Oh, and if you need even faster downloads,
            because S3 isn't that fast for large files,
            you can do the same thing with Cloudfront.
            Cloudfront is another service that gives users faster access to S3 files,
            and has a similar process for creating signed URLs.
            Ok friends, only one thing left, and it's a fun one!
            Let's talk about how our file upload endpoint might look different if we were building a pure API.
         */
        /*
            1- But we did lose one thing:
            our Content-Disposition header!
            This gave us two nice things:
            it forced the user to download the file instead of loading it "inline",
            and it controlled the download filename.
            Hmm, this is tricky.
            Now that the user is no longer downloading the file directly from us,
            we don't really have a way to set custom headers on the response.
            Well, actually, that's a big ol' lie!
            There are two ways to do that.
            First, you can set custom headers on each object in S3.
            Or you can hint to S3 that you want it to set custom headers on your behalf
            when the user goes to the signed URL.
            How? Add another option to getCommand(): ResponseContentType set to $reference->getMimeType().
            That'll hint to S3 that we want it to set a Content-Type header on the download response.
         */
        $command = $s3Client->getCommand('GetObject', [
            'Bucket' => $s3BucketName,
            'Key' => $reference->getFilePath(),
            'ResponseContentType' => $reference->getMimeType(),
            'ResponseContentDisposition' => $disposition,
        ]);
        $request = $s3Client->createPresignedRequest($command, '+30 minutes');
        /*
            Now that we have this "request" thing...
            how can we get the URL?
            Back on their docs, scroll down...
            here it is: $request->getUri().
            When the user hits our endpoint,
            what we want to do is redirect them to the URL.
            Do that with return new RedirectResponse(), (string) -
            they mentioned that in the docs,
            it turns the URI into a string - then $request->getUri().
         */
        return new RedirectResponse((string) $request->getUri());
        /*
            Let's try it! Refresh! And... download!
            Ha! It works!
            We're loading this directly from S3.
            This long URL contains a signature that proves to S3
            that the request was pre-authenticated and should last for 30 minutes.
         */
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
    public function deleteArticleReference(
        ArticleReference $reference,
        UploaderHelper $uploaderHelper,
        EntityManagerInterface $entityManager
    )
    {
        /*
            Inside, add the ArticleReference $reference argument and then we'll add our normal security check.
            In fact, copy it from above and put it here.
         */
        $article = $reference->getArticle();
        $this->denyAccessUnlessGranted('MANAGE', $article);
        /*
            That's nice!
            Back in the controller, add an UploaderHelper argument,
            oh and we're also going to need the EntityManagerInterface service as well.
            Remove the reference from the database with $entityManager->remove($reference) and $entityManager->flush().
            Then $uploaderHelper->deleteFile() passing that $reference->getFilePath() and false
            so it uses the private filesystem.
         */
        $entityManager->remove($reference);
        $entityManager->flush();
        /*
            Then, search for "delete", and remove the second argument from deleteFile() as well.
         */
        $uploaderHelper->deleteFile($reference->getFilePath());
        /*
            Quick note: in the real world, if there was a problem deleting the file from Flysystem -
            which is definitely possible when you're storing in the cloud -
            then you could end up with a situation where the row is deleted in the database,
            but the file still exists!
            If you changed the order, you'd have the opposite problem:
            the file might get deleted,
            but then the row stays because of a temporary connection error to the database.
            If you're worried about this,
            use a Doctrine transaction to wrap all of this logic.
            If the file was successfully deleted, commit the transaction.
            If not, roll it back so both the file and row stay.
            Anyways, what should this endpoint return?
            Well... how about... nothing!
            Return a new Response() - the one from HttpFoundation -
            with null as the content and a 204 status code.
            204 means: the operation was successful but I have nothing else to say!
         */
        return new Response(null, 204);
    }

    /*
        Let's keep thinking about our ArticleReference routes as a set of nice, RESTful API endpoints.
        We already have an endpoint to create and delete an ArticleReference.
        This will be an endpoint to edit a reference
        except that the only field the user will be allowed to edit will be the originalFilename.
        Copy the beginning of our delete endpoint, paste, close it up
        and we'll call this updateArticleReference().
        Keep the same URL, but change the route name to admin_article_update_reference -
        it should be reference, not references, let's fix that in both places -
        I don't think I'm referencing that route name anywhere.
        And instead of methods={"DELETE"}, use methods={"PUT"}.
     */
    /*
        So far, we've been using $this->json() to turn an object or multiple objects into JSON.
        This uses Symfony's serializer behind the scenes.
        Now we're going to use the serializer to do the opposite:
        to turn JSON back into an ArticleReference object.
        That's called deserialization.
        Let's add a few more arguments: SerializerInterface $serializer and Request -
        the one from HttpFoundation -
        so we can read the raw JSON body.
     */
    /**
     * @Route("/admin/article/references/{id}",name="admin_article_update_reference", methods={"PUT"})
     */
    public function updateArticleReference(
        ArticleReference $reference,
        UploaderHelper $uploaderHelper,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        Request $request,
        ValidatorInterface $validator)
    {
        /*
            To automagically turn the JSON into an ArticleReference object,
            say $serializer->deserialize().
            The serializer only has these two methods: serialize() and deserialize().
         */
        /*
            Ok, let's see if I've finally got everything right.
            Refresh, add a dash to the filename,
            click off and... 500 error! That's progress!
            Open the profiler for that request in a new tab.
            Ok: a "Syntax Error" coming from a JsonDecode class.
            Oh, and look at the data that's passed to the deserialize() function!
            That's not JSON!
            Silly mistake.

            Serializer->deserialize('id=3&filename=cv-rais-de-260602-6a22edc59ceac.pdf&originalFilename=CV_Rais.pdf&mimeType=application%2Fpdf', 'App\\Entity\\ArticleReference', 'json', array('object_to_populate' => object(ArticleReference), 'groups' => array('input'))) in src/Controller/ArticleReferenceAdminController.php (line 678)
         */
        $serializer->deserialize(
        /*
            This method needs the raw JSON from the request -
            that's $request->getContent(),
            what type of object to turn this into - ArticleReference::class - and the format of the data: json,
            because the serializer can also handle XML or any crazy format you dream up.
         */
            $request->getContent(),
            ArticleReference::class,
            'json',
            [
                /*
                    Finally, we can pass some options - called "context".
                    By default, deserialize() will always create a new object
                    but we want it to update an existing object.
                    To do that, pass an option called object_to_populate set to $reference.
                 */
                'object_to_populate' => $reference,
                /*
                    In the controller, way back down here, set groups to input.
                    So if any other fields or passed, they'll just be ignored.
                 */
                'groups' => ['input']
            ]
        );
        /*
            Then, inside our endpoint,
            there's no form here, but that's fine.
            Add the ValidatorInterface $validator argument.
            And right after we update the object with the serializer,
            add $violations = $validator->validate()
            and pass it the $reference object.
            Then if $violations->count() > 0,
            return $this->json($violations, 400).
         */
        $violations = $validator->validate($reference);
        if ($violations->count() > 0) {
            return $this->json($violations, 400);
        }
        /*
            We're actually not going to handle that in JavaScript -
            I'll leave rendering the errors up to you -
            you could highlight the element in red
            and print the error below whatever you want.
            But let's at least make sure it works.
            Clear out the filename, hit tab to blur and there it is!
            A 400 error with our beautiful error response.
            To handle this in JavaScript,
            you'll chain a .catch() onto the end of the AJAX call
            and then do whatever you want.
            Ok, what else can we add to our upload widget?
            How about the ability to reorder the list.
            That's next.
            {"type":"https:\/\/symfony.com\/errors\/validation","title":"Validation Failed","detail":"originalFilename: This value should not be blank.","violations":[{"propertyPath":"originalFilename","title":"This value should not be blank.","parameters":{"{{ value }}":"\u0022\u0022"},"type":"urn:uuid:c1051bb4-d103-4f74-8988-acbcafc7fdc3"}]}
         */
        $article = $reference->getArticle();
        $this->denyAccessUnlessGranted('MANAGE', $article);
        /*
            And... yea, that's it!
            We do need to think about validation - but, pff, we'll handle that later.
            Right now we can celebrate with $entityManager->persist($reference),
            which we technically don't need because this isn't a new object,
            but I usually add it, and $entityManager->flush().
         */
        $entityManager->persist($reference);
        $entityManager->flush();

        /*
            What should we return?
            Typically after you edit a resource in an API,
            we return that resource again.
            Scroll all the way up to our upload endpoint and steal the JSON logic.
            We could also refactor this into a private method if we wanted to avoid duplication.
            Back down in our method, paste, rename the variable to $reference
            and use 200 as the status code: we're not creating a resource in this case.
         */
        return $this->json(
            $reference,
            200,
            [],
            [
                'groups' => ['main']
            ]
        );
        /*
            Ok, that endpoint should be good!
            Or at least, we're ready to hook up our JavaScript
            so we can find out if it explodes when we use it! That's next.
         */
    }
    /*
        Cool! Let's think about how we want this endpoint to work.
        First, our JavaScript will send a request with a JSON body that contains the data
        that should be updated on the ArticleReference.
        In this case, the data will have only one field: originalFilename.
     */

    /*
        1- This is amazing!
        This is the exact data we need to send to the server!
        Open up ArticleReferenceAdminController and find downloadArticleReference().
        If you look closely, about half of the methods in this controller have an {id} route wildcard
        where the id is for an ArticleReference.
        Those endpoints are actions that operating on a single item.
        The other half of the endpoints, the ones on top, also have an {id} wildcard,
        but these are for the Article.
        What about our new endpoint?
        We'll be reordering all of the references for one article...
        so it's a bit more like these ones on top.
        Copy this entire action for getting article references,
        change the name to reorderArticleReferences and put /reorder on the URL.
        Make this a method="POST" and name it admin_article_reorder_references.
     */
    /**
     * @Route("/admin/article/{id}/references/reorder", methods="POST", name="admin_article_reorder_references")
     * @IsGranted("MANAGE", subject="article")
     */
    public function reorderArticleReferences(
        Article $article,
        Request $request,
        EntityManagerInterface $entityManager
    )
    /*
        public function reorderArticleReferences(Article $article)
        2.2- Inside the method, here's the plan:
        our JavaScript will send a JSON body containing an array of the ids in the right order.
        This array exactly.
        Add the Request argument so we can get read
        that data and the EntityManagerInterface so we can save stuff.
     */
    {
        /*
            3-To decode the JSON this time,
            it's so simple!
            I'm going to skip using Symfony's serializer.
            Say $orderedIds = json_decode() passing that $request->getContent() and true
            so it gives us an associative array.
         */
        $orderedIds = json_decode($request->getContent(), true);
        /*
            4- Then, if orderedIds === false, something went wrong.
            Let's return this->json() and,
            to at least somewhat match the validation responses we've had so far,
            let's set a detail key to,
            how about, Invalid body with 400 for the status code.
         */
        if ($orderedIds === null) {
            return $this->json(['detail' => 'Invalid body'], 400);
        }

        /*
            1- Ok, cool: we've got the array of ids in the new order we want.
            Use this to say $orderedIds = array_flip($orderedIds).
            This deserves some explanation.
            The original array is a map from the position to the id -
            the keys are 0, 1, 2, 3 and so on.
            After the flip, we have a very handy array:
            the key is the id and the value is its new position.
         */
        // from (position)=>(id) to (id)=>(position)
        $orderedIds = array_flip($orderedIds);

        /*
            2- To use this, foreach over $article->getArticleReferences() as $reference.
            And inside, $reference->setPosition() passing this $orderedIds[$reference->getId()]
            to look up the new position.
         */
        foreach ($article->getArticleReferences() as $reference) {
            $reference->setPosition($orderedIds[$reference->getId()]);
        }
        /*
            3- And yes, we could code more defensively -
            like checking to make sure each array key was actually sent.
            And I would do that if this were a public API that other people used,
            or if invalid data could cause some harm.
            Anyways, at the bottom, save: $entityManager->flush().
         */
        $entityManager->flush();

        /*
            2- If you're wondering about the URL or the method POST,
            well, this endpoint isn't very RESTful,
            it doesn't fit into the nice create-read-update-delete model
            and that's ok.
            Usually when I have a weird endpoint like this,
            I use POST.
         */
        return $this->json(
            $article->getArticleReferences(),
            200,
            [],
            [
                'groups' => ['main']
            ]
        );
    }

}
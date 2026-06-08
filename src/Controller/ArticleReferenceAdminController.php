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
            Then, before we do anything with that uploaded file,
            say $violations = $validator->validate().
            Pass this the object that you want to validate.
            For us, it's the $uploadedFile object itself.
            If we stopped here, it would read any validation annotations off of that class
            and apply those rules...
            which would be zero rules!
            This is a core class!
            There's no validation rules,
            and we can't just open up that file and add them.
            No worries: pass a second argument: the constraint to validate against.
         */
        /*
            Oh! There's one last case we need to validate for.
            Hit enter on the URL to refresh the form.
            Do nothing and hit upload.
            Ah!!! Whoops! Everything explodes inside UploaderHelper...
            because there is no uploaded file! The horror!
            Back in the controller, the second argument to validate() can accept an array of validation constraints.
            Put the new File into an array.
            Then add: new NotBlank() with a custom message: please select a file to upload.
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
                So, in theory, you can have multiple validation rules and multiple errors.
                To keep things simple, let's show the first error if there is one.
                Use $violation = $violations[0] to get it.
                The ConstraintViolationList class implements ArrayAccess,
                which is why we can use this syntax.
                Oh, and let's help out my editor by telling it that this is a ConstraintViolation object.
             */
            /** @var ConstraintViolation $violation */
            $violation = $violations[0];
            /*
                And now... hmm... how should we show this error to the user?
                This controller will eventually turn into an AJAX,
                or API endpoint that communicates via JSON. But because this is still a normal form submit,
                the easiest option is to put the error into a flash message and display it on the next page.
                Say $this->addFlash(), pass it an "error" type, and then $violation->getMessage().
             */
            $this->addFlash('error', $violation->getMessage());
            /*
                Finish by stealing the redirect code from the bottom to send us back to the edit page.
             */
            return $this->redirectToRoute('admin_article_edit', [
                'id' => $article->getId(),
            ]);
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
            Finish with return redirectToRoute() and send the user back to the edit page:
            admin_article_edit passing this id set to $article->getId().
         */
        return $this->redirectToRoute('admin_article_edit', [
            'id' => $article->getId(),
        ]);
        /*
            Yep - that's the route on the edit endpoint.
            Alright! With any luck, it should hit our dd() statement.
            Go back to your browser:
            I already have the Symfony Best Practices PDF selected.
            Hit update... yea! UploadedFile coming from UploaderHelper.
            Next: let's move the uploaded file... except that...
            we can't move it using the filesystem service object we have now...
            because we can't store these private files in the public/ directory. Hmm...
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
            Try it again. Hello PDF!
         */

        return $response;
    }

}
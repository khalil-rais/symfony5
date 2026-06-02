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
    /**
     * @Route("/admin/article/{id}/references",name="admin_article_add_reference", methods={"POST"})
     * @IsGranted("MANAGE", subject="article")
     */
    public function uploadArticleReferenceArticle (Article $article, Request
    $request, UploaderHelper $uploaderHelper, EntityManagerInterface
    $entityManager)
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

    }

}
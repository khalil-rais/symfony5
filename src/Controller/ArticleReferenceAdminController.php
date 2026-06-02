<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\File\File;
use App\Entity\Article;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

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
    public function uploadArticleReferenceArticle (Article $article, Request $request)
    {
        /*
            Finally, we're ready to fetch the file:
            add the Request argument - the one from HttpFoundation -
            and let's dd($request->files->get())
            and then the name from the input field: reference.
         */
        dd($request->files->get('reference'));
        /*
            Solid start. Copy the route name and head back to the template.
         */
        /*
            ArticleReferenceAdminController.php on line 56:
            Symfony\Component\HttpFoundation\File\UploadedFile {#16 ▼
              -test: false
              -originalName: "plektrum-desktop.png"
              -mimeType: "image/png"
              -error: 0
              path: "/private/var/folders/7k/dmlmkxps5259w7n8h4q4p4b00000gn/T"
              filename: "php4d1u9cl9nd9f18jy5hn"
              basename: "php4d1u9cl9nd9f18jy5hn"
              pathname: "/private/var/folders/7k/dmlmkxps5259w7n8h4q4p4b00000gn/T/php4d1u9cl9nd9f18jy5hn"
              extension: ""
              realPath: "/private/var/folders/7k/dmlmkxps5259w7n8h4q4p4b00000gn/T/php4d1u9cl9nd9f18jy5hn"
              aTime: 2026-06-02 15:19:42
              mTime: 2026-06-02 15:19:42
              cTime: 2026-06-02 15:19:42
              inode: 99533777
              size: 6635
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
         */
    }

}
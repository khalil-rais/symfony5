<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleFormType;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ArticleAdminController extends BaseController
{
    /**
     * @Route("/admin/article/new", name="admin_article_new")
     * @IsGranted("ROLE_ADMIN_ARTICLE")
     */
    public function new(EntityManagerInterface $em, Request $request)
    {
        $form = $this->createForm(ArticleFormType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Article $article */
            $article = $form->getData();

            $em->persist($article);
            $em->flush();

            $this->addFlash('success', 'Article Created! Knowledge is power!');

            return $this->redirectToRoute('admin_article_list');
        }

        return $this->render('article_admin/new.html.twig', [
            'articleForm' => $form->createView()
        ]);
    }

    /**
     * @Route("/admin/article/{id}/edit", name="admin_article_edit")
     * @IsGranted("MANAGE", subject="article")
     */
    public function edit(Article $article, Request $request, EntityManagerInterface $em)
    {
        $form = $this->createForm(ArticleFormType::class, $article, [
            'include_published_at' => true
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($article);
            $em->flush();

            $this->addFlash('success', 'Article Updated! Inaccuracies squashed!');

            return $this->redirectToRoute('admin_article_edit', [
                'id' => $article->getId(),
            ]);
        }

        return $this->render('article_admin/edit.html.twig', [
            'articleForm' => $form->createView()
        ]);
    }

    /**
     * @Route("/admin/upload/test", name="upload_test")
     */
    public function temporaryUploadAction(Request $request)
    {
        /*
            Let's get to work inside of our controller to move the file.
            First, set the uploaded file to a new $uploadedFile variable.
            And, unfortunately, the phpdoc on this get() method is a bit generic,
            so it doesn't tell our editor that this will be an UploadedFile object.
            Because I'm obsessed with auto-completion,
            let's add inline doc about this:
            this will be an UploadedFile object - but not the one from Guzzle - the one from HttpFoundation in Symfony.
         */
        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $request->files->get('image');

        /*
            This page uses a Symfony form.
            And we will learn how to add a file upload field to a form object.
            But let's start simpler - with a good old-fashioned HTML form.
            The controller behind this page live at src/Controller/ArticleAdminController.php,
            and we're on the edit() action.
            Create a totally new, temporary endpoint:
            public function temporaryUploadAction().
            We're going to create an HTML form in our template,
            put an input file field inside,
            and make it submit to this action.
            Add the @Route() with,
            how about, /admin/upload/test and name="upload_test".
            But don't do anything else yet.
        */
        /*
            In some ways, uploading a file is really no different than any other form field:
            you're always just sending data to the server
            where each data has a key equal to its name attribute.
            So, the same as any form, to read the submitted data,
            we'll need the request object.
            Add a new argument with a Request type-hint - the one from HttpFoundation - $request.
            Then say: dd() - that's dump & die - $request->files->get('image').
            I'm using image because that's the name attribute used on the field.
         */
        /*
            And guess what? This UploadedFile object has a super useful method on it: move()!
            Give it the destination directory and it'll take care of the rest.
            To get that directory, say $destination =
            and we need to get the path to our uploads/ directory.
            The best way is to read a parameter: $this->getParameter('kernel.project_dir'),
            to get the absolute path to the root of the app - then /public/uploads.
            Then add $uploadedFile->move() and pass it $destination.
            Hold Command or Ctrl and click this method.
            Ah, it returns a File object that represents the new file.
            Let's see what this looks like: surround this entire call with dd().
         */
        $destination = $this->getParameter('kernel.project_dir').'/public/uploads';
        dd($uploadedFile->move($destination));
        /*
            ArticleAdminController.php on line 126:
            Symfony\Component\HttpFoundation\File\File {#693 ▼
              path: "/Users/khalil.rais/cauldron_overflow/public/uploads"
              filename: "phplg3v8h2gio9fd3gGH54"
              basename: "phplg3v8h2gio9fd3gGH54"
              pathname: "/Users/khalil.rais/cauldron_overflow/public/uploads/phplg3v8h2gio9fd3gGH54"
              extension: ""
              realPath: "/Users/khalil.rais/cauldron_overflow/public/uploads/phplg3v8h2gio9fd3gGH54"
              aTime: 2026-05-13 15:18:57
              mTime: 2026-05-13 15:18:57
              cTime: 2026-05-13 15:18:57
              inode: 95770732
              size: 110234
              perms: 0100666
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

        /*
            Alright team!
            Find your browser, refresh and re-post that upload.
            I think it worked!
            The dumped file object tells me that there is a new file in our public/uploads/ directory.
            Let's go check it out! There it is!
            Well, I think that's it but sheesh - the filename is terrible.
            Let's check its file size:
            ls -la public/uploads/
            total 224
            drwxr-xr-x@ 4 khalil.rais  staff     128 13 Mai  17:18 .
            drwxr-xr-x  8 khalil.rais  staff     256 13 Mai  16:58 ..
            -rw-r--r--@ 1 khalil.rais  staff    1131 13 Mai  17:02 .gitignore
            -rw-rw-rw-@ 1 khalil.rais  staff  110234 13 Mai  17:18 phplg3v8h2gio9fd3gGH54
         */
        /*
            ArticleAdminController.php on line 100:
            Symfony\Component\HttpFoundation\File\UploadedFile {#16 ▼
              -test: false
              -originalName: "image (2).png"
              -mimeType: "image/png"
              -error: 0
              path: "/private/var/folders/7k/dmlmkxps5259w7n8h4q4p4b00000gn/T"
              filename: "php9hokarb4di64a3ZaKFK"
              basename: "php9hokarb4di64a3ZaKFK"
              pathname: "/private/var/folders/7k/dmlmkxps5259w7n8h4q4p4b00000gn/T/php9hokarb4di64a3ZaKFK"
              extension: ""
              realPath: "/private/var/folders/7k/dmlmkxps5259w7n8h4q4p4b00000gn/T/php9hokarb4di64a3ZaKFK"
              aTime: 2026-05-13 07:18:49
              mTime: 2026-05-13 07:18:49
              cTime: 2026-05-13 07:18:49
              inode: 95313761
              size: 120507
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

        /*
            Refresh the form so the new attribute is rendered.
            Let's choose the astronaut again.
            And before hitting Upload,
            open up your developer tools and go to the Network tab:
            I want to see what this request looks like.
            Hit upload!
            Nice! This time we get an UploadedFile object full of useful data.
            But before we dive into that,
            look down at the network tools and find the POST request we just made.

            Accept
            	text/html,application/xhtml+xml,application/xml;q=0.9,*;q=0.8
            Accept-Encoding
            	gzip, deflate, br, zstd
            Accept-Language
            	en-US,en;q=0.5
            Cache-Control
            	no-cache
            Connection
            	keep-alive
            Content-Length
            	120724
            Content-Type
            	multipart/form-data; boundary=----geckoformboundary2461054baf588057a19dfbf058ec8444
            Cookie
            	PHPSESSID=d8d3fdd9a91b39ff2ae3e17825e56150
            Host
            	127.0.0.1:8000
            Origin
            	https://127.0.0.1:8000
            Pragma
            	no-cache
            Priority
            	u=0, i
            Referer
            	https://127.0.0.1:8000/admin/article/41/edit
            Sec-Fetch-Dest
            	document
            Sec-Fetch-Mode
            	navigate
            Sec-Fetch-Site
            	same-origin
            Sec-Fetch-User
                ?1
                TE
            	trailers
            Upgrade-Insecure-Requests
            	1
            User-Agent
            	Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:144.0) Gecko/20100101 Firefox/144.0
    
            If you look at the request headers, here it is:
            our browser sent a
            Content-Type: multipart/form-data header.
            This is because of the enctype attribute.
            It also added this weird boundary=----WebkitFormBoundary thing.
            Ok: this stuff is super-nerdy-cool.
            Normally, when you do not have that enctype attribute,
            when you submit a form,
            all of the data is sent in the body of the request in a big string full of
            what looks like query parameters.
            That's kind of invisible to us, because PHP parses all of that and makes
            the data available.
            But when you add the multipart/form-data attribute,
            it tells our browser to send the data in a different format.
            It's actually kind of hard to see what the body of these requests look like - Chrome hides it.
            No worries! Through the magic of TV boom!
            This is what the body of that request looks like.
            Weird, right! Each field is separated by this mysterious WebkitFormBoundary thing,
            which is the string that we saw in the Content-Type header!
            Our form only has one field,
            but if we had multiple, this separator would be between every field.
            Our browsers invents this string,
            separates each piece of data with it,
            then sends this separator up with the request
            so that the server knows how to parse everything.
            Why is this cool?
            Because we can now send up multiple pieces of information about our name="image" field,
            like the original filename on our system and what type of file it is,
            which by the way, can be totally faked by the user.
            More on that later. After all that, we've got the data itself!
            If you look all the way at the bottom,
            it has another WebKitFormBoundary line.
            If there were more fields on this form,
            you'd see their data below - all separated by another "boundary".
            So that's it! It literally tells our browser to send the data in a different format,
            and PHP understands both formats just fine.
            We need this format when doing file uploads because a file upload is more than just its contents:
            we also want to send some metadata.
            And also, due to how the data is encoded,
            if you were able to send binary data on a normal request,
            without the multipart/form-data encoding,
            it would increase the amount of data you need to upload by as much as three times!
            Not great for uploads!
         */
    }

    /**
     * @Route("/admin/article/location-select", name="admin_article_location_select")
     * @IsGranted("ROLE_USER")
     */
    public function getSpecificLocationSelect(Request $request)
    {
        // a custom security check
        if (!$this->isGranted('ROLE_ADMIN_ARTICLE') && $this->getUser()->getArticles()->isEmpty()) {
            throw $this->createAccessDeniedException();
        }

        $article = new Article();
        $article->setLocation($request->query->get('location'));
        $form = $this->createForm(ArticleFormType::class, $article);

        // no field? Return an empty response
        if (!$form->has('specificLocationName')) {
            return new Response(null, 204);
        }

        return $this->render('article_admin/_specific_location_name.html.twig', [
            'articleForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/article", name="admin_article_list")
     * @IsGranted("ROLE_ADMIN_ARTICLE")
     */
    public function list(ArticleRepository $articleRepo)
    {
        $articles = $articleRepo->findAll();

        return $this->render('article_admin/list.html.twig', [
            'articles' => $articles,
        ]);
    }
}

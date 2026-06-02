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
use Behat\Transliterator\Transliterator;
use App\Service\UploaderHelper;

class ArticleAdminController extends BaseController
{
    /**
     * @Route("/admin/article/new", name="admin_article_new")
     * @IsGranted("ROLE_ADMIN_ARTICLE")
     */
    public function new(EntityManagerInterface $em, Request $request, UploaderHelper $uploaderHelper)
    {
        $form = $this->createForm(ArticleFormType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Article $article */
            $article = $form->getData();
            /*
                Now that all of our logic is isolated,
                we can easily repeat this in the new() action.
                We do need to copy these 5 lines or so, but I'm happy with that.
                Up in new(), add the argument - UploaderHelper $uploaderHelper -
                and inside the isValid() block, paste!
            */
            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $form['imageFile']->getData();
            if ($uploadedFile) {
                /*
                    In new(), you can really just pass null - there will not be an article image. But I'll pass
                    getImageFilename() to be consistent.
                 */
                $newFilename = $uploaderHelper->uploadArticleImage($uploadedFile, $article->getImageFilename());
                $article->setImageFilename($newFilename);
            }
            /*
                This uses the same form, with the same unmapped field, so it'll all just work.
                Next: let's talk about validation.
             */
            $em->persist($article);
            $em->flush();

            $this->addFlash('success', 'Article Created! Knowledge is power!');

            return $this->redirectToRoute('admin_article_list');
        }

        /*
            To do that, we need the Article object.
            Copy the image path logic from the homepage
            and then go find the controller for the admin section: ArticleAdminController.
            When we render the template - this is in the new() action -
            we're only passing the form variable.
            In edit(), we're doing the same thing.
            We could add an article variable here - that's a fine option.
            But, we don't need to.
         */
        return $this->render('article_admin/new.html.twig', [
            'articleForm' => $form->createView()
        ]);
    }

    /**
     * @Route("/admin/article/{id}/edit", name="admin_article_edit")
     * @IsGranted("MANAGE", subject="article")
     */
    public function edit(Article $article, Request $request, EntityManagerInterface $em, UploaderHelper $uploaderHelper)
    {
        $form = $this->createForm(ArticleFormType::class, $article, [
            'include_published_at' => true
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /*
                Time to finish this.
                Let's upload a different file - earth.jpeg.
                And there's the dump.
                We have two jobs in our controller:
                move this file to the final location and store the new filename on the $imageFilename property.
                Back in the controller, scroll down to temporaryUploadAction(),
                steal all its code, and delete it.
                Up in edit(), remove the dd() and set this to an $uploadedFile variable.
                Add the same inline phpdoc as last time
             */
            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $form['imageFile']->getData();
            /*
                Of course!
                We're not uploading a file!
                So the $uploadedFile variable is null! That's ok!
                If the user didn't upload a file,
                we don't need to do any of this logic.
                In other words, if ($uploadedFile), then do all of that. Otherwise, skip it!
             */
            if ($uploadedFile) {
                /*
                    Cool! Let's worry about configuring the $uploadsPath argument to our service in a minute.
                    After all, Symfony's service system is so awesome,
                    it'll tell me exactly what I need to configure once we try this.
                    For now, go back into ArticleAdminController and use this.
                    Start by adding another argument: UploaderHelper $uploaderHelper.
                    And celebrate by removing all of the logic below and replacing it with
                    $newFilename = $uploaderHelper->uploadArticleImage($uploadedFile).
                */
                /*
                    Done! You can see the astronaut file that we're using right now.
                    Oh, but first, head over to ArticleAdminController:
                    we need to pass this new argument.
                    Let's see - this is the edit() action - so pass $article->getImageFilename().
                 */
                $newFilename = $uploaderHelper->uploadArticleImage($uploadedFile, $article->getImageFilename());
                $article->setImageFilename($newFilename);
                /*
                    Dang - that is nice!
                    There is still a little bit of logic here:
                    the form logic and the logic that sets the filename on the Article -
                    but I'm comfortable with that.
                    And we now have this great new method:
                    pass it an UploadedFile object,
                    and it'll move it into the correct directory and give it a unique filename.
                 */
            }
            /*
                Refresh now. Got it!
                Next: This is looking good! Except
                that we need this exact same logic in the new() action.
                To make a truly killer upload system,
                we need to refactor the upload logic into a reusable service.
             */
            /*
                and let Doctrine save the entity, just like it already was.
                Beautiful! I do want to point out that the $newFilename string
                that we're storing in the database is just the filename:
                it doesn't contain the directory or the word uploads:
                it's the filename.
                Oh, for my personal sanity, let's upload things into an article_image sub-directory:
                that'll be cleaner when we start uploading multiple types of things.
                Remove the old files.
             */

            $em->persist($article);
            $em->flush();

            $this->addFlash('success', 'Article Updated! Inaccuracies squashed!');

            return $this->redirectToRoute('admin_article_edit', [
                'id' => $article->getId(),
            ]);
        }

        return $this->render('article_admin/edit.html.twig', [
            'articleForm' => $form->createView(),
            /*
                Find the edit() action of ArticleAdminController and pass an article variable. Now
                we can say article.id.
             */
            'article' => $article,
        ]);
        /*
            Phew! Ok, let's check this out:
            refresh and inspect element on the form.
            Yep, the URL looks right and the enctype attribute is there.
            Ok, try it: select the Symfony Best Practices doc and upload!
            Yes! It's our favorite UploadedFile object!
            These article references are special
            because we need to keep them private:
            they should only be accessible to the author or a super admin.
            The process for uploading & downloading private files is, a bit different.
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

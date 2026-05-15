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
                Moment of truth! Find your browser, roll up your sleeves, and refresh!
                Um it probably worked?
                In the uploads/ directory yea!
                There's our Earth file! Let's see what the database looks like.
                Find your terminal and run:
                php bin/console doctrine:query:sql 'SELECT * FROM article WHERE id = 41'
                Let's see, the id of this article is 1.
                Yes! the image_filename column is totally set!
                Fist-pumping time!
             */
            $destination = $this->getParameter('kernel.project_dir').'/public/uploads/article_image';
            /*
                then paste the code.
                Yep! We'll move the file to public/uploads and give it a unique filename.
                Take off the dd() around move().
             */
            $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
            $newFilename = Transliterator::urlize($originalFilename).'-' .uniqid().'.'.$uploadedFile->guessExtension();
            $uploadedFile->move($destination, $newFilename);
            /*
                Now, call $article->setImageFilename($newFilename)
             */
            $article->setImageFilename($newFilename);
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
            'articleForm' => $form->createView()
        ]);
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

<?php

namespace App\DataFixtures;

use App\Entity\Article;
use App\Entity\Comment;
use App\Entity\Tag;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use App\Service\UploaderHelper;
use Symfony\Component\HttpFoundation\File\File;

class ArticleFixtures extends BaseFixture implements DependentFixtureInterface
{
    private static $articleTitles = [
        'Why Asteroids Taste Like Bacon',
        'Life on Planet Mercury: Tan, Relaxing and Fabulous',
        'Light Speed Travel: Fountain of Youth or Fallacy',
    ];

    private static $articleImages = [
        'asteroid.jpeg',
        'mercury.jpeg',
        'lightspeed.png',
    ];

    /*
        How? By faking the file upload inside the fixtures.
        It's kinda beautiful!
        Our UploaderHelper service is already really good at moving things into the right spot
        - why not reuse it here?
        Inside ArticleFixtures, create a public function __construct().
        Add an UploaderHelper $uploaderHelper argument and I'll hit ALT + Enter
        and select initialize fields to create that property and set it.
     */
    private $uploaderHelper;

    public function __construct(UploaderHelper $uploaderHelper)
    {
        $this->uploaderHelper = $uploaderHelper;
    }

    protected function loadData(ObjectManager $manager): void
    {
        $this->createMany(10, 'main_articles', function($count) use ($manager) {
            $article = new Article();
            $article->setTitle($this->faker->randomElement(self::$articleTitles))
                ->setContent(<<<EOF
Spicy **jalapeno bacon** ipsum dolor amet veniam shank in dolore. Ham hock nisi landjaeger cow,
lorem proident [beef ribs](https://baconipsum.com/) aute enim veniam ut cillum pork chuck picanha. Dolore reprehenderit
labore minim pork belly spare ribs cupim short loin in. Elit exercitation eiusmod dolore cow
**turkey** shank eu pork belly meatball non cupim.

Laboris beef ribs fatback fugiat eiusmod jowl kielbasa alcatra dolore velit ea ball tip. Pariatur
laboris sunt venison, et laborum dolore minim non meatball. Shankle eu flank aliqua shoulder,
capicola biltong frankfurter boudin cupim officia. Exercitation fugiat consectetur ham. Adipisicing
picanha shank et filet mignon pork belly ut ullamco. Irure velit turducken ground round doner incididunt
occaecat lorem meatball prosciutto quis strip steak.

Meatball adipisicing ribeye bacon strip steak eu. Consectetur ham hock pork hamburger enim strip steak
mollit quis officia meatloaf tri-tip swine. Cow ut reprehenderit, buffalo incididunt in filet mignon
strip steak pork belly aliquip capicola officia. Labore deserunt esse chicken lorem shoulder tail consectetur
cow est ribeye adipisicing. Pig hamburger pork belly enim. Do porchetta minim capicola irure pancetta chuck
fugiat.
EOF
            );

            // publish most articles
            if ($this->faker->boolean(70)) {
                $article->setPublishedAt($this->faker->dateTimeBetween('-100 days', '-1 days'));
            }
            /*
                Here's the idea: we'll use the UploaderHelper down here,
                point it at one of these 3 files,
                and have it, sort of, "fake" upload it.
                Start with $randomImage =, copy the faker code, and paste.
                This is now one of the three random image filenames.
             */
            $randomImage = $this->faker->randomElement(self::$articleImages);
            /*
                That's ok! It just means we need to dig deeper!
                Go back into UploaderHelper.
                Hold Command or Ctrl and click to open the UploadedFile class.
                This lives in the Symfony\HttpFoundation\File namespace
                and extends a class called File that lives in the same directory.
                The File class is awesome: it simply represents any file on your filesystem,
                regardless of whether it's an uploaded file or just a normal file.
                And, if you look closely, the vast majority of the methods we've been using come from this class - not from UploadedFile.
                And we can create a File object outside of an upload context.
                So back in ArticleFixtures, instead of creating a new UploadedFile(),
                say new File() - the one from HttpFoundation.
                Pass this the path to the random image: __DIR__.'/images/' and then $randomImage,
                which will be one of these image filenames.
             */
            $imageFilename = $this->uploaderHelper->uploadArticleImage(new
            File(__DIR__.'/images/'.$randomImage));
            /*
                Now, take $imageFilename - that'll be whatever the final filename is on the system after moving it,
                and set that onto the entity.
             */
            /*
                Next, in UploaderHelper, what I'd like to do is call uploadArticleImage()
                and basically say:
                “Hey! Pretend like asteroid.jpeg is a file that was just uploaded.
                And... ya know... do all your normal stuff and move it into the uploads/ directory.”
                This is easier than you think:
                in the fixtures class, set $imageFilename to $this->uploaderHelper->uploadArticleImage().
                What I want to do is now say new UploadedFile()
                and point it at one of the images.
                The problem is that you can't really create a fake UploadedFile object.
                Internally, it's bound to the PHP uploading process
                - weird stuff will happen if you try to create one outside of that context.
             */

            $article->setAuthor($this->getRandomReference('main_users'))
                ->setHeartCount($this->faker->numberBetween(5, 100))
                ->setImageFilename($this->faker->randomElement(self::$articleImages))
            ;

            $tags = $this->getRandomReferences('main_tags', $this->faker->numberBetween(0, 5));
            foreach ($tags as $tag) {
                $article->addTag($tag);
            }

            return $article;
        });

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            TagFixture::class,
            UserFixture::class,
        ];
    }
}

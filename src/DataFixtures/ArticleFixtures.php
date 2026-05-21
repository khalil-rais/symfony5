<?php

namespace App\DataFixtures;

use App\Entity\Article;
use App\Entity\Comment;
use App\Entity\Tag;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use App\Service\UploaderHelper;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Filesystem\Filesystem;

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
                Back up top, delete all this stuff and say $imageFilename = $this->fakeUploadImage().
             */
            $imageFilename = $this->fakeUploadImage();
            /*
                Let's run those fixtures one more time!
                php bin/console doctrine:fixtures:load
                 Careful, database "main" will be purged. Do you want to continue? (yes/no) [no]:
                 > yes

                   > purging database
                   > loading App\DataFixtures\TagFixture
                   > loading App\DataFixtures\UserFixture
                   > loading App\DataFixtures\ArticleFixtures
                   > loading App\DataFixtures\CommentFixture
                When it finishes, we have some new files and the homepage is shiny!
                That's a solid fixture system.
                Next: we'll take our first step towards storing uploaded files in the cloud by integrating the
                gorgeous Flysystem library.
             */

            $article->setAuthor($this->getRandomReference('main_users'))
                ->setHeartCount($this->faker->numberBetween(5, 100))
                ->setImageFilename($imageFilename)
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

    /*
        Though, I don't love having all of this logic right in the middle of this already-long function:
        it's not super obvious what it does.
        Let's do some cleanup: copy all of this.
        And at the bottom, create a new private function fakeUploadImage() that will return a string.
     */
    private function fakeUploadImage(): string
    {
        /*
            Paste all that logic and return the $this->uploaderHelper line.
            It selects a random image, uploads it and returns the path.
         */
        $randomImage = $this->faker->randomElement(self::$articleImages);
        $fs = new Filesystem();
        $targetPath = sys_get_temp_dir().'/'.$randomImage;
        $fs->copy(__DIR__.'/images/'.$randomImage, $targetPath, true);
        return $this->uploaderHelper
            ->uploadArticleImage(new File($targetPath));
    }
}

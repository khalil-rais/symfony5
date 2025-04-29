<?php

namespace App\DataFixtures;

use App\Entity\Answer;
use App\Entity\Question;
use App\Factory\AnswerFactory;
use App\Factory\QuestionFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);
        QuestionFactory::new()->createMany(20);

        QuestionFactory::new()
            ->unpublished()
            ->createMany(5);

        AnswerFactory::createMany(100,
        [
            'question' => QuestionFactory::random(),
        ]);

        $manager->flush();

    }
}

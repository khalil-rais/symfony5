<?php

namespace App\DataFixtures;

use App\Entity\Answer;
use App\Entity\Question;
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

        $answer = new Answer();
        $answer->setContent('This question is the best! I whsih I knew the answer');
        $answer->setUsername ('weaverryan') ;

        $question = new Question();
        $question->setName('How to up-disappear you wallet.');
        $question->setQuestion('... I should not have done this...');

        $answer->setQuestion($question);

        $manager->persist($answer);
        $manager->persist($question);

        $manager->flush();

    }
}

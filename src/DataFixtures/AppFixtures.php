<?php

namespace App\DataFixtures;

use App\Entity\Question;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);

        $question = new Question();
        $question->setName('Missing dress')
            ->setSlug("missing-dress-" . rand(0, 1000))
            ->setQuestion(<<<EOF
Hi! So... I'm having a *weird* day. Yesterday, I cast a spell 
to make my dishes wash themselves. But while I was casting it, 
I slipped a little and I think `I also hit my dress with the spell`.

When I woke up this morning, I caught a quick glimpse of my dresses 
opening the front door and walking out! I've been out all afternoon 
(with no dresses mind you) searching for them.

Does anyone have a spell to call your dresses back?
EOF
            );
        if(rand(1,10) > 2) {
            $question->setAskedAt(new \DateTimeImmutable(sprintf('-%d days', rand(1, 100))));
        }

        $question->setVotes(rand(-20, 50));

        $manager->persist($question);
        $manager->flush();

        $manager->flush();
    }
}

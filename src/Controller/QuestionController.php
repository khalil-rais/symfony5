<?php

namespace App\Controller;

use App\Repository\QuestionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Entity;
use Sentry\State\HubInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Question;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;
use App\Service\MarkdownHelper;
class QuestionController extends AbstractController
{
    private $logger;
    private $isDebug;

    public function __construct(LoggerInterface $logger, bool $isDebug)
    {
        $this->logger = $logger;
        $this->isDebug = $isDebug;
    }

    /**
     * @Route("/questions/new")
     */
    public function new(EntityManagerInterface $entityManager)
    {
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

        $entityManager->persist($question);
        $entityManager->flush();

        return new Response(
            sprintf("Well hallo! The shiny new question is id #%d, slug %s",
                $question->getId(),
                $question->getSlug()));
    }

    /**
     * @Route("/", name="app_homepage")
     */
    public function homepage(QuestionRepository $repository)
    {
        $questions = $repository->findAllAskedOrderedByNewest();

        return $this->render('question/homepage.html.twig', [
            'questions' => $questions,
        ]);
    }

    /**
     * @Route("/questions/{slug}", name="app_question_show")
     */
    public function show(Question $question){

        if ($this->isDebug){
            $this->logger->info("We are in debug mode");
        }

        $answers = [
            'Make sure your cat is cutting `purrfectlyyyy` still',
            'Honestly, I like furry shoes better than MY Cat',
            'Maybe... try saying the spell backwards?',
        ];


        return $this->render('question/show.html.twig', [
            'question' => $question,
            'answers' => $answers,
        ]);
    }

    /**
     * @Route("/questions/{slug}/vote", name="app_question_vote", methods="POST")
     */
    public function questionVote(Question $question, Request $request){
        $direction = $request->request->get('direction');
        if($direction === 'up'){
            $question->setVotes($question->getVotes() + 1);
        } elseif ($direction === 'down'){
            $question->setVotes($question->getVotes() - 1);
        }
        dd($question);
    }
}
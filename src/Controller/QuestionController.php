<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\MarkdownHelper;
class QuestionController extends AbstractController
{
    /**
     * @Route("/", name="app_homepage")
     */
    public function homepage(){
        return $this->render('question/homepage.html.twig');
    }

    /**
     * @Route("/questions/{slug}", name="app_question_show")
     */
    public function show($slug, MarkdownHelper $markdownHelper, bool $isDebug){
        dump($isDebug);
        $answers = [
            'Make sure your cat is cutting `purrfectlyyyy` still',
            'Honestly, I like furry shoes better than MY Cat',
            'Maybe... try saying the spell backwards?',
        ];
        $question_text = 'I\'ve been turned into a cat, any thoughts on how to turn back? While I\'m **adorable**, I don\'t really care for cat food.';

        $parsedQuestionText = $markdownHelper->parse($question_text);
        //dd($slug, $this);
        //dump($slug, $this);
        //dump($cache);

        return $this->render('question/show.html.twig', [
            'question' => ucwords(str_replace('-',' ',$slug)),
            'questionText' => $parsedQuestionText,
            'answers' => $answers,
        ]);
    }
}
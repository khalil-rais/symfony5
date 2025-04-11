<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class QuestionController extends AbstractController
{
    /**
     * @Route("/")
     */
    public function homepage(){
        return new Response('Hello World!');
    }

    /**
     * @Route("/questions/{slug}")
     */
    public function show($slug){
        $answers = [
            'Make sure your cat is cutting purrfectly still',
            'Honestly, I like furry shoes better than MY Cat',
            'Maybe... try saying the spell backwards?',
        ];
        return $this->render('question/show.html.twig', [
            'question' => ucwords(str_replace('-',' ',$slug)),
            'answers' => $answers,
        ]);
    }
}
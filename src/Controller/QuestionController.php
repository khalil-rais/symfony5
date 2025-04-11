<?php

namespace App\Controller;

use Symfony\Component\Console\Question\Question;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class QuestionController
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
        //return new Response('Future page in preparation!');
        return new Response(sprintf('Hello "%s"!',
            ucwords(str_replace('-',' ',$slug))));
    }
}
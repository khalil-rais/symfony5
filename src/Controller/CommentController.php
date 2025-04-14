<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CommentController extends AbstractController
{

    /**
     * @Route("/comments/{id}/vote/{direction}")
     */
    public function commentVote($id, $direction): Response
    {
        // todo use id ti query database

        // use real logic here to save this to the database
        if ($direction === 'up') {
            $currentVoteCount = rand(7, 100);
        }
        else {
            $currentVoteCount = rand(0, 5);
        }

        return $this->json(['votes' => $currentVoteCount]);
    }
}

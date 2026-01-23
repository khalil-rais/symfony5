<?php

namespace App\Controller;

use App\Entity\ImagePost;
/*
    Next, in ImagePostController, all the way on top,
    we're referencing both commands.
    Update the namespace on each one.
 */
use App\Message\Command\AddPonkaToImage;
use App\Message\Command\DeleteImagePost;
use App\Photo\PhotoFileManager;
use App\Repository\ImagePostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ImagePostController extends AbstractController
{
    /**
     * @Route("/api/images", methods="GET")
     */
    public function list(ImagePostRepository $repository)
    {
        $posts = $repository->findBy([], ['createdAt' => 'DESC']);

        return $this->toJson([
            'items' => $posts
        ]);
    }

    /**
     * @Route("/api/images", methods="POST")
     */
    public function create(Request $request, ValidatorInterface $validator, PhotoFileManager $photoManager, EntityManagerInterface $entityManager, MessageBusInterface $messageBus)
/*
    So far, whenever we needed the message bus - like in ImagePostController -
    we autowired it by using the MessageBusInterface type-hint.
    The question now is:
    how can we get access to the new message bus service?
 */
    {
        // Debug: Check all files received
        $allFiles = $request->files->all();
        
        /** @var UploadedFile $imageFile */
        $imageFile = $request->files->get('file');

        if (!$imageFile) {
            return $this->json([
                'error' => 'No file uploaded', 
                'debug_all_files' => array_keys($allFiles),
                'debug_file_structure' => gettype($imageFile)
            ], 400);
        }

        // Handle different file upload structures
        if (is_array($imageFile)) {
            if (empty($imageFile)) {
                return $this->json(['error' => 'Empty file array received'], 400);
            }
            
            // Check if it's a raw PHP $_FILES array structure
            if (isset($imageFile['tmp_name']) && isset($imageFile['name'])) {
                // Create UploadedFile from array
                $imageFile = new UploadedFile(
                    $imageFile['tmp_name'],
                    $imageFile['name'],
                    $imageFile['type'] ?? null,
                    $imageFile['error'] ?? null,
                    true // test mode
                );
            } else {
                // For arrays, find the first UploadedFile object
                foreach ($imageFile as $file) {
                    if ($file instanceof UploadedFile) {
                        $imageFile = $file;
                        break;
                    }
                }
            }
        }

        if (!($imageFile instanceof UploadedFile)) {
            return $this->json([
                'error' => 'Invalid file upload format', 
                'debug' => 'Type: ' . gettype($imageFile) . ', Class: ' . (is_object($imageFile) ? get_class($imageFile) : 'N/A'),
                'all_files_debug' => $allFiles
            ], 400);
        }

        if (!$imageFile->isValid()) {
            return $this->json(['error' => 'Invalid file upload: ' . $imageFile->getErrorMessage()], 400);
        }

        // Basic image type validation
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($imageFile->getMimeType(), $allowedMimeTypes)) {
            return $this->json(['error' => 'Invalid image type. Only JPEG, PNG, and GIF are allowed.'], 400);
        }

        $newFilename = $photoManager->uploadImage($imageFile);
        $imagePost = new ImagePost();
        $imagePost->setFilename($newFilename);
        $imagePost->setOriginalFilename($imageFile->getClientOriginalName());

        $entityManager->persist($imagePost);
        $entityManager->flush();

        $message = new AddPonkaToImage($imagePost->getId());
        /*
            Before we try this, I want to make one other change.
            Open up src/Controller/ImagePostController.php and find the create() method.
            This is the controller that's executed whenever we upload a photo
            and it's responsible for dispatching the AddPonkaToImage command.
            It also adds a 500 millisecond delay via this stamp.
            Comment that out for now,
            I'll show you why we're doing this a bit later.
         */
        $envelope = new Envelope($message, [
            //new DelayStamp(500)
        ]);
        $messageBus->dispatch($envelope);

        return $this->toJson($imagePost, 201);
    }

    /**
     * @Route("/api/images/{id}", methods="DELETE")
     */
    public function delete(ImagePost $imagePost, MessageBusInterface $messageBus)
    {
        $messageBus->dispatch(new DeleteImagePost($imagePost));

        return new Response(null, 204);
    }

    /**
     * @Route("/api/images/{id}", methods="GET", name="get_image_post_item")
     */
    public function getItem(ImagePost $imagePost)
    {
        return $this->toJson($imagePost);
    }

    private function toJson($data, int $status = 200, array $headers = [], array $context = []): JsonResponse
    {
        // add the image:output group by default
        if (!isset($context['groups'])) {
            $context['groups'] = ['image:output'];
        }

        return $this->json($data, $status, $headers, $context);
    }
}

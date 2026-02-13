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
use App\Message\Command\LogEmoji;
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
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
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
            # When we started working with AMQP,
            # I told you to go into ImagePostController and remove the DelayStamp.
            # This stamp is a way to tell the transport system to wait at least 500 milliseconds
            # before allowing a worker to receive the message.
            # Let's change this to 10 seconds - so 10000 milliseconds.
            # At this moment, the delays exchange has no bindings,
            # but that will change when we send a delayed message.
            # To be able to really see what's happening, let's increase the delay to 60 seconds.
            /*Let's change this delay back to one second,
            so we're not waiting all day for our photos to be processed.*/
            new DelayStamp(1000),
            /*
                So, let's back up and look at the whole flow.
                When we dispatch an AddPonkaToImage object,
                our Messenger routing config always routes this to the async_priority_high transport.
                This causes the message to be sent to the messages exchange
                with a routing key set to high
                and the binding logic means that it will ultimately be delivered to the messages_high queue.
                Due to the way that Messenger's routing works,
                the fact that you route a class to a transport,
                every message class will always be delivered to the same queue.
                But what if you did want to control this dynamically?
                What if, at the moment you dispatch a message,
                you needed to send that message to a different transport than normal?
                Maybe you decide that this particular AddPonkaToImage message
                is not important and should be routed to async.
                Well... that's just not possible with Messenger:
                each class is always routed to a specific transport.
                But this end-result is possible,
                if you know how to leverage routing keys.
                Here's the trick: what if we could publish an AddPonkaToImage object,
                but tell Messenger that when it sends it to the exchange,
                it should use the normal routing key instead of high?
                Yea, the message would technically still be routed to the async_priority_high transport,
                but it would ultimately end up in the messages_normal queue.
                That would do it!
                Is that possible? Totally!
                Open up ImagePostController
                and find where we dispatch the message.
                After the DelayStamp, add a new AmqpStamp
                - but be careful not to choose AmqpReceivedStamp -
                that's something different
                and isn't useful for us.
                This stamp accepts a few arguments and the first one - gasp! -
                is the routing key to use!
                Pass this normal.
                Let's try it!
             */
            new AmqpStamp('normal')
        ]);
        /*
            Forget about asynchronous messages and external transports and all that stuff.
            Open up ImagePostController.
            As a reminder, when you dispatch a message,
            you actually dispatch an Envelope object,
            which is a simple "wrapper"
            that contains the message itself
            and may also contain some stamps
            which add extra info.
            If you dispatch the message object directly,
            the message bus creates an Envelope for you and puts your message inside.
            The point is, internally, Messenger is always working with an Envelope.
            And when you call $messageBus->dispatch(),
            it also returns an Envelope:
            the final Envelope after Messenger has done all its work.
            Let's see what that looks like:
            dump() that whole $messageBus->dispatch() line.
            Now, move over and upload a photo.
            Once that's done,
            find that request on the web debug toolbar,
            and open the profiler.
         */
        dump($messageBus->dispatch($envelope));
        /*
            To prove it, go up to ImagePostController,
            find the create() method and,
            just to see make sure this is working, add:
         */
        /*
            We've just created a new message class & handler,
            then instantiated it and dispatched it directly into the message bus.
            Yep, we just did something totally classic!
            But it's actually pretty similar to our real goal!
            Our real goal is to pretend that an outside system
            is putting messages into a RabbitMQ queue, probably formatted as JSON,
            and we will read those messages,
            transform that JSON into a LogEmoji object
            and basically dispatch that through the message bus.
            It's really the same basic flow:
            in both cases, we create a LogEmoji object and pass it to Messenger.
         */
        //$messageBus->dispatch(new LogEmoji(2));
        /*
            If this is working,
            we should see a message in our logs each time we upload a photo.
         */

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

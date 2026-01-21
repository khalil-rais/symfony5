<?php

namespace App\MessageHandler\Command;

/*
    Let's do the same thing for the handlers:
    create a new subdirectory called Command/,
    move those inside
    then add the \Command namespace to each one.
 */
use App\Message\Command\DeleteImagePost;
use App\Message\Event\ImagePostDeletedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;
/*
    Now that we've reviewed all of that,
    it turns out that this is only part of the story.
    If we want to, we can take more control of
    how a message class is linked to its handler, including some extra config.
    How? Instead of implementing MessageHandlerInterface,
    implement MessageSubscriberInterface.
 */
use Symfony\Component\Messenger\MessageBusInterface;

/*
    Open up DeleteImagePostHandler.
    The main thing that a message bus needs to know
    is the link between the DeleteImagePost message class and its handler.
    It needs to know that when we dispatch a DeleteImagePost object,
    it should call DeleteImagePostHandler.
    How does Messenger know these two classes are connected?
    It knows because our handler implements MessageHandlerInterface
    - this "marks" it as a message handler -
    and because its __invoke() method is type-hinted with DeleteImagePost.
    If you follow these two rules
    - implement that interface & create an __invoke() method with an argument type-hinted with the message class -
    then you're done!
 */
class DeleteImagePostHandler implements MessageSubscriberInterface
{

    /*
     Inside DeleteImagePostHandler, change the argument to $eventBus.
     I don't have to, but I'm also going to rename the property to $eventBus for clarity.
     */
    private $eventBus;
    private $entityManager;
    public function __construct(MessageBusInterface $eventBus, EntityManagerInterface $entityManager){
        $this->eventBus = $eventBus;
        $this->entityManager = $entityManager;
    }


    /*
        But technically, this type-hint isn't needed anymore.
     */
    public function __invoke($deleteImagePost)
    {
        $imagePost  = $deleteImagePost->getImagePost();
        $filename = $imagePost->getFilename();

        $this->entityManager->remove($imagePost);
        $this->entityManager->flush();
/*
    We already have a situation like this!
    Look at DeleteImagePost and then DeleteImagePostHandler.
    The "main" job for this handler is to remove this ImagePost from the database.
    But it also has a second task: deleting the underlying file from the filesystem.
    To do that, well, we're dispatching a second command - DeletePhotoFile -
    and its handler deletes the file.
    This is the event pattern!
    Well, it's almost the event pattern.
    The only difference is the naming: DeletePhotoFile sounds like a "command".
    Instead of "commanding" the system to do something,
    an event is more of an "announcement" that something did happen.
    To fully understand this, let's back up and re-implement all of this fresh.
    Comment out the $messageBus->dispatch() call
    and then remove the DeletePhotoFile use statement on top.
 */
//        $this->messageBus->dispatch(new DeletePhotoFile($filename));
        /*
         Inside __invoke(), it's really the same as before:
        $this->eventBus->dispatch() with new ImagePostDeletedEvent() passing that $filename.
         */
        $this->eventBus->dispatch(new ImagePostDeletedEvent($filename));
        /*
            That's it! The end result of all of this work
            was to do the same thing as before,
            but with some renaming to match the "event bus" pattern.
            The handler performs its primary task
            - deleting the record from the database -
            then dispatches an event that says:
            “An image post was just deleted! If anyone cares... do something!”
         */

    }

    /*
        This is less of a huge change than it may seem.
        If you open up MessageSubscriberInterface, it extends MessageHandlerInterface.
        So, we're still effectively implementing the same interface,
        but now we're forced to have one new method: getHandledMessages().
        At the bottom of my class, I'll go to Code -> Generate - or Command + N on a Mac - and select "Implement Methods".
        As soon as we implement this interface,
        instead of magically looking for the __invoke() method and checking the type-hint on the argument for which message class this should handle,
        Symfony will call this method.
        Our job here? Tell it exactly which classes we handle,
        which method to call.
     */
    public static function getHandledMessages(): iterable
    {
        /*
            The easiest thing you can put here is yield DeleteImagePost::class.
            Don't over-think that yield, it's just syntax sugar.
            You could also return an array with a DeleteImagePost::class string inside.
         */
        /*
            Ok but since we probably should use type-hints, this isn't that interesting yet.
            What else can we do?
            Well, by assigning this to an array, we can add some config.
            For example, we can say: 'method' => '__invoke'.
            Yep, we can now control which method Messenger will call.
            That's especially useful if you decide that you want to add another yield to handle a second message
            and want Messenger to call a different method.
         */
        yield DeleteImagePost::class => [
            'method' => '__invoke',
            /*
                What else can we put here?
                One option is priority - let's set it to... 10.
                This option is much less interesting than it might look like at first.
                We talked earlier about priority transports:
             */
            /*
                The priority option here is less powerful.
                If you send a message to a transport with a priority 0
                and then you send another message to that same transport with priority 10,
                what do you think will happen?
                Which message will be handled first?
                The answer: the first message that was sent - the one with the lower priority.
                Basically, Messenger will always read messages in a first-in-first-out basis:
                it will always read the oldest messages first.
                The priority does not influence this.
                So what does it do?
                Well, if DeleteImagePost had two handlers and one had the default priority of zero and another had 10,
                the handler with priority 10 would be called first.
                That's not usually important,
                but could be if you had two event handlers and really needed them to happen in a certain order.
             */
            'priority' => 10,
        ];
    }
}
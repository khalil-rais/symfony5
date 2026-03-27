<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

/*
    In the src/ directory, create a new directory called EventListener.
    And inside, a new PHP class called SetFromListener.
    Make this implement EventSubscriberInterface: the interface for all subscribers.
    I'll go to the "Code -> Generate" menu - or Command + N on a Mac
    - and hit "Implement Methods" to add the one method required by this interface: getSubscribedEvents().
 */
class SetFromListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        /*
            Inside, return an array: we want to listen to MessageEvent. So: MessageEvent::class => 'onMessage'.
            When this event occurs,
            call the onMessage method which we need to create!
         */
        return [
            MessageEvent::class => 'onMessage',
        ];
    }

    public function onMessage(MessageEvent $event)
    {
        /*
            On top, add public function onMessage().
            Because we're listening to MessageEvent,
            that will be the first argument: MessageEvent $event.

            So what's inside of this event object anyways?
            Surprise! The original Email!
            Ok, maybe that's not too surprising.
            Add $email = $event->getMessage().
         */
        $email = $event->getMessage();
        /*
            But is that truly our original Email object or is it something else?
            Hold Command or Ctrl and click the getMessage() method to jump inside.
            Hmm, this returns something called a
            RawMessage. What's that?
            We have been working with Email objects or TemplatedEmail objects.
            Open up TemplatedEmail and let's dig!
            TemplatedEmail extends Email extends Message
            and Message extends ah ha! RawMessage!
            Oooook. We typically work with TemplatedEmail or Email,
            but on a really, really low level, all Mailer really needs is an instance of RawMessage.
            Let's close a few files.
            The point is: when we call $event->getMessage(),
            this will return whatever object was actually passed
            to the send() method... which in our case is always going to be a TemplatedEmail object.
            But just to be safe, let's add if !
            $email instanceof Email - make sure you get the one from the Mime component - just return.
            This shouldn't happen... but could in theory if a third-party bundle sends emails.
            If you want to be safe, you could also throw an exception here so you know if this happens.
         */
        if (!$email instanceof Email) {
            return;
        }
        /*
            Anyways, now that we're sure this is an Email object,
            we can say $email->from()... go steal the from() inside Mailer... and paste here.
            Re-type the "S" on NamedAddress and hit tab to add its use statement on top.
            Tip: In Symfony 4.4 and higher, use new Address() -
         */
        $email->from(new Address('alienmailcarrier@example.com', 'The
Space Bar'));
        /*
            it works the same way as the old NamedAddress.
            That's it! We just globally set the from!
            Back in Mailer, delete it from sendWelcomeMessage()...
            and also from the weekly report email.
            Testing time! Register with any email -
            because we know that all emails are being delivered to ryan@symfonycasts.com in the development environment - any password, hit register and...
            run over to the inbox!
            There it is! Welcome to The Space Bar from alienmailer@example.com.
            Next, sending an email requires a network call... so it's a heavy operation.
            We can speed up the user experience by sending emails asynchronously via Messenger.
         */
    }
}
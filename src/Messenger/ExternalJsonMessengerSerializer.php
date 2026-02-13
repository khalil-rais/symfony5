<?php

namespace App\Messenger;

use App\Message\Command\LogEmoji;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Stamp\BusNameStamp;

/*
    Inside of our src/Messenger/ directory...,
    though this class could live anywhere,
    let's create a new PHP class called ExternalJsonMessengerSerializer.
    The only rule is that this needs to implement SerializerInterface.
    But, careful!
    There are two SerializerInterface: one is from the Serializer component.
    We want the other one: the one from the Messenger component.
    I'll go to the "Code Generate" menu,
    or Command + N on a Mac,
    and select "Implement Methods"
    to add the two that this interface requires: decode() and encode().
 */
class ExternalJsonMessengerSerializer implements SerializerInterface
{

    public function decode(array $encodedEnvelope): Envelope
    {
        /*
            The method that we need to focus on is decode().
            When a worker consumes a message from a transport,
            the transport calls decode() on its serializer.
            Our job is to read the message from the queue
            and turn that into an Envelope object with the message object inside.
            If you check out the SerializerInterface one more time,
            you'll see that the argument we're passed - $encodedEnvelope -
            is really just an array with the same two keys we saw a moment ago:
            body and headers.
            Let's separate the pieces first:
            $body = $encodedEnvelope['body'] and
            $headers = $encodedEnvelope['headers'].
            The $body will be the raw JSON in the message.
            We'll talk about the headers later:
            it's empty right now.
         */
        $body = $encodedEnvelope['body'];
        $headers = $encodedEnvelope['headers'];

        /*
            Ok, remember our goal here:
            to turn this JSON into a LogEmoji object
            and then put that into an Envelope object.
            How? Let's keep it simple!
            Start with
            $data = json_decode($body, true)
            to turn the JSON into an associative array.
         */
        $data = json_decode($body, true);
        /*
            This is looking great!
            But there are two improvements I want to make.
            First, we haven't been coding very defensively.
            For example, what if, for some reason,
            the message contains invalid JSON?
            Let's check for that: if null === $data,
         */
        if (null === $data) {
            throw new MessageDecodingFailedException('Invalid JSON');
        }
        /*
            I'll show you why we're using this exact exception class in a minute.
            But let's try this with some invalid JSON
            and see what happens.
            Go restart the worker so it sees our new code:
         */
        /*
            I'm not doing any error-checking yet,
            like to check that this is valid JSON,
            we'll do that a bit later.
            Now say: $message = new LogEmoji($data['emoji'])
            because emoji is the key in the JSON
            that we've decided will hold the $emojiIndex.
         */
        $message = new LogEmoji($data['emoji']);

        // in case of redelivery, unserialize any stamps
        $stamps = [];
        if (isset($headers['stamps'])) {
            $stamps = unserialize($headers['stamps']);
        }
        /*
            Finally, we need to return an Envelope object.
            Remember: an Envelope is just a small wrapper around the message itself
            and it might also hold some stamps.
            At the bottom, return new Envelope() and put $message inside.
         */
        /*
            But the BusNameStamp is one that you might want to add.
            Sure, Messenger used the correct bus in this case by accident,
            but we can be more explicit!
            Head into ExternalJsonMessengerSerializer.
            Change this to $envelope = new Envelope()
            and, at the bottom, return $envelope.
            Add the stamp with $envelope = $envelope->with(),
            this is how you add a stamp, new BusNameStamp().
            Then because our transport & serializer only handle this one message
            and because this one message is a command,
            we'll want to put the command bus here.
            Copy the command.bus bus name and paste.
            I'll add a comment that says
            that this is technically only needed if you need the message to be sent through a non-default bus.
         */
        /*
            Next, our serializer is great,
            but we didn't code very defensively.
            What would happen if the message contained invalid JSON or was missing the emoji field?
            Would our app fail gracefully or explode?
         */
        $envelope = new Envelope($message, $stamps);
        // needed only if you need this to be sent through the non-default bus
        $envelope = $envelope->with(new BusNameStamp('command.bus'));
        return $envelope;
    }

    /*
        The idea is beautifully simple:
        when we send a message through a transport that uses this serializer,
        the transport will call the encode() method
        and pass us the Envelope object that contains the message.
        Our job is to turn that into a string format
        that can be sent to the transport.
        Oh, well, notice that this returns an array.
        But if you look at the SerializerInterface,
        this method should return an array with two keys:
        body - the body of the message -
        and headers - any headers that should be sent.
        Nice, right?
        But we're actually never going to send any messages through our external transport...
        so we don't need this method.
        To prove that it will never be called,
        throw a new Exception with:
        “Transport & serializer not meant for sending messages”
     */
    /*
        That'll give me a gentle reminder,
        in case I do something silly and route a message to a transport
        that uses this serializer by accident.
        Actually, if you want your messages to be redelivered,
        you do need to implement the encode() method.
        See the code-block on this page for an example,
        which includes a small update to decode().
     */
    public function encode(Envelope $envelope): array
    {
        // this is called if a message is redelivered for "retry"
        $message = $envelope->getMessage();
        // expand this logic later if you handle more than
        // just one message class
        if ($message instanceof LogEmoji) {
            // recreate what the data originally looked like
            $data = ['emoji' => $message->getEmojiIndex()];
        } else {
            throw new \Exception('Unsupported message class');
        }
        $allStamps = [];
        foreach ($envelope->all() as $stamps) {
            $allStamps = array_merge($allStamps, $stamps);
        }
        return [
            'body' => json_encode($data),
            'headers' => [
                // store stamps as a header - to be read in decode()
                'stamps' => serialize($allStamps)
            ],
        ];
    }

}
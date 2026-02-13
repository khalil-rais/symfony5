<?php

namespace App\Messenger;

use App\Message\Command\LogEmoji;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

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
        $body = $encodedEnvelope['body'];
        $headers = $encodedEnvelope['headers'];

        $data = json_decode($body, true);
        $message = new LogEmoji($data['emoji']);

        // in case of redelivery, unserialize any stamps
        $stamps = [];
        if (isset($headers['stamps'])) {
            $stamps = unserialize($headers['stamps']);
        }
        return new Envelope($message, $stamps);
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

        throw new \Exception('Transport & serializer not meant for sending
messages');
    }

}
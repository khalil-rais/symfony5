<?php

namespace App\Messenger;

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
        // TODO: Implement decode() method.
    }

    public function encode(Envelope $envelope): array
    {
        // TODO: Implement encode() method.
    }

}
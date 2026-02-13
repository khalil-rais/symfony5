<?php

namespace App\Messenger;

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Messenger\Stamp\SentStamp;

/*
this middleware makes sure that every Envelope has exactly one UniqueIdStamp.
Then, anyone can use the unique id string on that stamp
to track this exact message through the whole process.
Wait so if this is normally added when
we originally dispatch a message,
should we manually add the stamp inside of our serializer
so that the Envelope has one?
Look at it this way: a normal message that's sent from our app would
already have this stamp by the time it's published to RabbitMQ.
When a worker receives it, it'll be there.
But in this case, as you can clearly see,
after receiving the external message,
we are not adding that stamp.
So, is that something we should add here so this "acts" like other messages?
Great question!
The answer is no!
Check out the log messages:
you can already see some messages with this 5d7bc string.
That is the unique id.
Our message does have a UniqueIdStamp!
How? Remember, after our serializer returns the Envelope,
the worker dispatches it back through the bus.
And so, our AuditMiddleware is called,
it adds that stamp and then logs some messages about it.
 */
class AuditMiddleware implements MiddlewareInterface
{
    private $logger;
    public function __construct(LoggerInterface $messengerAuditLogger)
    {
        $this->logger = $messengerAuditLogger;
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        if (null === $envelope->last(UniqueIdStamp::class)) {
            $envelope = $envelope->with(new UniqueIdStamp());
        }

        /** @var UniqueIdStamp $stamp */
        $stamp = $envelope->last(UniqueIdStamp::class);
        $context = [
            'id' => $stamp->getUniqueId(),
            'class' => get_class($envelope->getMessage())
        ];

        $envelope = $stack->next()->handle($envelope, $stack);

        if ($envelope->last(ReceivedStamp::class)) {
            $this->logger->info(
                '[{id}] Received & handling {class}',
                $context
            );
        }
        elseif ($envelope->last(SentStamp::class)) {
            $this->logger->info(
                '[{id}] Sent {class}',
                $context
            );
        }
        else {
            $this->logger->info(
                '[{id}] Handling or sending {class}',
                $context
            );
        }

        return $envelope;
    }
}
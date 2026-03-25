<?php

namespace App\Tests\Service;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\WebpackEncoreBundle\Asset\EntrypointLookupInterface;
use Twig\Environment;
use App\Entity\User;
use App\Service\Mailer;


class MailerTest extends TestCase
{
    /*
        The idea is that this will test the Mailer class,
        which lives in the Service/ directory.
        Inside tests/, create a new Service/ directory
        to match that and move MailerTest inside.
        You typically want your test directory structure to match your src/ structure.
        Inside the file, don't forget to add \Service to the namespace
        to match the new location.
     */
    /*
        Let's get to work!
        So what are we going to test?
        Well, we probably want to test that the mail was actually sent
        and maybe we'll assert a few things about the Email object itself.
        Unit tests always start the same way:
        by instantiating the class you want to test.
        Back in MailerTest,
        rename the method to testSendWelcomeMessage().
     */
    public function testSendWelcomeMessage()
    {
        /*
            Then add $mailer = new Mailer().
            For this to work, we need to pass the 4 dependencies:
            objects of the types MailerInterface,
            Twig,
            Pdf and
            EntrypointLookupInterface.
            In a unit test, instead of using real objects that really do send emails
            or render Twig templates, we use mocks.
            For the first, say $symfonyMailer = this->createMock()
            and because the first argument needs to be an instance of MailerInterface,
            that's what we'll mock: MailerInterface::class.
         */
        $symfonyMailer = $this->createMock(MailerInterface::class);
        /*
            To make sure we don't forget to actually send the email,
            we can add an assertion to this mock:
            we can tell PHPUnit that the send method must be called exactly one time.
            Do that with $symfonyMailer->expects($this->once())
            that the ->method('send') is called.
         */
        $symfonyMailer->expects($this->once())
            ->method('send');
        /*
            Let's create the 3 other mocks:
            $pdf = this->createMock(Pdf::class)
            and the other two are for Environment and EntrypointLookupInterface:
            $twig = $this->createMock(Environment::class) and
            $entrypointLookup = $this->createMock(EntrypointLookupInterface::class).
        */
        $twig = $this->createMock(Environment::class);
        $entrypointLookup = $this->createMock(EntrypointLookupInterface::class);
        /*
            These three objects aren't even used in this method
            so we don't need to add any assertions to them or configure any behavior.
            Finish the new Mailer() line by passing $symfonyMailer, $twig, $pdf and $entrypointLookup.
            Then, call the method:
            $mailer->sendWelcomeMessage().
            Oh, to do this, we need a User object.
         */
        $mailer = new Mailer($symfonyMailer, $twig, $entrypointLookup);
        /*
            Should we mock the User object?
            We could, but as a general rule,
            I like to mock services but manually instantiate simple "data" objects, like Doctrine entities.
            The reason is that these classes don't have dependencies
            and it's usually dead-simple to put whatever data you need on them.
            Basically, it's easier to create the real object, than create a mock.
            Start with $user = new User().
            And... let's see... the only information
            that we use from User is the email and first name.
            For $user->setFirstName(), let's pass the name of my brave co-author for this tutorial: Victor!
            And for $user->setEmail(), him again victor@symfonycasts.com.
            Give this $user variable to the sendWelcomeMessage() method.
         */
        $user = new User();
        $user->setFirstName('Victor');
        $user->setEmail('victor@symfonycasts.com');
        $mailer->sendWelcomeMessage($user);


    }

}
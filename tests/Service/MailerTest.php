<?php

namespace App\Tests\Service;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\WebpackEncoreBundle\Asset\EntrypointLookupInterface;
use Twig\Environment;
use App\Entity\User;
use App\Service\Mailer;
use Symfony\Component\Mime\Address;
use App\Entity\Article;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/*
    To make this test able to use real objects,
    we need to change extends from TestCase to KernelTestCase.
 */
class MailerTest extends KernelTestCase
/*
    That class extends the normal TestCase
    but gives us the ability to boot Symfony's service container in the background.
    Specifically, it gives us the ability, down in the method, to say: self::bootKernel().
 */
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

        /*
            You don't have to do this,
            but it'll make our unit test more useful and keep it simple.
            Now we can say $email = $mailer->sendWelcomeMessage()
            and we can check pretty much anything on that email.
            I'll paste in some asserts:
         */
        $email = $mailer->sendWelcomeMessage($user);
        $this->assertSame('Welcome to the Space Bar!', $email->getSubject());
        $this->assertCount(1, $email->getTo());
        /** @var Address[] $namedAddresses */
        $namedAddresses = $email->getTo();
        $this->assertInstanceOf(Address::class, $namedAddresses[0]);
        $this->assertSame('Victor', $namedAddresses[0]->getName());
        $this->assertSame('victor@symfonycasts.com', $namedAddresses[0]->getAddress());

    }

    /*
        I also want to test the method that sends the weekly update email.
        But because the real complexity of this method is centered around generating the PDF,
        instead of a unit test,
        let's write an integration test.
        In MailerTest, add a second method: testIntegrationSendAuthorWeeklyReportMessage().
     */
    public function testIntegrationSendAuthorWeeklyReportMessage()
    {
        /*
            Let's start the same way as the first method:
            copy all of its code except for the asserts,
            paste them down here and change the method to sendAuthorWeeklyReportMessage().
         */
        /*
            That will give us the ability to fetch real service objects and use them.
         */
        self::bootKernel();
        /*
            So we'll leave $symfonyMailer mocked,
            leave the $entrypointLookup mocked,
            but for the Pdf,
            get the real Pdf service.
            How? In the test environment,
            we can fetch things out of the container using the same type-hints as normal.
            So, $pdf = self::$container,
            bootKernel() set that property.
            ->get() passing this Pdf::class.
            Do the same for Twig: self::$container->get(Environment::class).
            Tip: Starting in Symfony 5.3, instead of self::$container,
            use static::getContainer() to get the container from inside a test.
            Also, calling bootKernel() is no longer needed.
         */
        //$pdf = self::$container->get(Pdf::class);
        $twig = static::getContainer()->get(Environment::class);
        /*
            I love that!
            Again, the downside is that you really do need to have wkhtmltopdf installed correctly anywhere you run your tests.
            That's the cost of doing this.
         */
        $symfonyMailer = $this->createMock(MailerInterface::class);
        $symfonyMailer->expects($this->once())
            ->method('send');
        $entrypointLookup = $this->createMock(EntrypointLookupInterface::class);

        /*
            This needs a User object,
            but it also needs an array of articles.
            Let's create one: $article = new Article().
            These articles are passed to the template where we print their title.
            So let's at least populate that property: $article->setTitle():
            “Black Holes: Ultimate Party Pooper”
         */
        $user = new User();
        $user->setFirstName('Victor');
        $user->setEmail('victor@symfonycasts.com');
        $article = new Article();
        $article->setTitle('Black Holes: Ultimate Party Pooper');
        /*
            Use this for the 2nd argument of sendAuthorWeeklyReportMessage():
            an array with just this inside.
        */
        $mailer = new Mailer($symfonyMailer, $twig, $entrypointLookup);
        $email = $mailer->sendAuthorWeeklyReportMessage($user, [$article]);
        /*
            Before we try it, at the bottom,
            we don't have any asserts yet.
            Let's add at least one:
            $this->assertCount() that 1 is the count of $email->getAttachments().
         */
        $this->assertCount(0, $email->getAttachments());
        /*
            We could go further and look closer at the attachment
            maybe make sure that it looks like it's in a PDF format
            but this is a good start.
            Now let's try this.
            Find your terminal and run our normal:
            php bin/phpunit
            It is slower this time  and then.. ah!
            What just happened?
            Two things. First, because this booted up a lot more code,
            we're seeing a ton of deprecation warnings.
            These are annoying, but we can ignore them.
         */
    }


}
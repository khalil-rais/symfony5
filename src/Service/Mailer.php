<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\ExceptionInterface as MailerException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\WebpackEncoreBundle\Asset\EntrypointLookupInterface;
use Twig\Environment;

/*
So what we're going to do is, in the Service/ directory,
create a new class called FileThatWillSendAllTheEmails, or, maybe just Mailer it's shorter.
 */
class Mailer
{
    /*
        The idea is that this class will have one method for each email that our app sends.
        Now, if your app sends a lot of emails,
        instead of having just one Mailer class,
        you could instead create a Mailer/ directory with a bunch of service classes inside -  like one per email.
        In both cases, you're either organizing your email logic into a single service or multiple services in one directory.
        Start by adding an __construct() method.
        The one service that we know we're going to need is MailerInterface $mailer
        because we're going to send emails.
        I'll hit Alt + Enter and go to "Initialize fields" to create that property and set it.
     */
    private $mailer;
    private $twig;
    private $entrypointLookup;

    public function __construct(MailerInterface $mailer, Environment $twig, EntrypointLookupInterface $entrypointLookup)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->entrypointLookup = $entrypointLookup;
        /*
            This time, we need to inject a few more services
            - for entrypointLookup, twig and pdf.
            Let's add those on top: Environment $twig, Pdf $pdf and EntrypointLookupInterface $entrypointLookup.
            I'll do my Alt + Enter shortcut and go to "Initialize fields" to create those three properties
            and set them.
         */
    }

    /*
        Ok, let's start with the registration email inside of SecurityController.
        Ok to send this email,
        the only info we need is the User object.
        Create a new public function sendWelcomeMessage() with a User $user argument.
     */
    public function sendWelcomeMessage(User $user): TemplatedEmail
    {
        /*
            Then, grab the logic from the controller
            everything from $email = to the sending part
            and paste that here.
            It looks like this class is missing a few use statements
            so I'll re-type the "L" on TemplatedEmail and hit tab,
            then re-type the S on NamedAddress and hit tab once more
            to add those use statements to the top of this file.
            Then change $mailer to $this->mailer.
            Tip: In Symfony 4.4 and higher, use new Address() -
            it works the same way as the old NamedAddress.
         */
        $email = (new TemplatedEmail())
            ->from(new Address('alienmailcarrier@example.com', 'The Space Bar'))
            ->to(new Address($user->getEmail(), $user->getFirstName()))
            ->subject('Welcome to the Space Bar!')
            ->htmlTemplate('email/welcome.html.twig')
            ->html("<h1>Nice to meet you {$user->getFirstName()}! ❤</h1>")
            ->context([
                /*
                 * To prove it, let's get crazy and comment-out the user variable in context.
                 */
                //'user' => $user,
            ]);
        $this->mailer->send($email);
        /*
            The tricky thing is that the majority of this method is about creating the Email
            and we're not testing what that object looks like at all.
            And maybe we don't need to?
            I tend to unit test logic that scares me and manually test other things -
            like the wording inside an email.
            But let's at least assert a few basic things.
            How? An easy way is to return the email from each method:
            return $email and then advertise that this method returns a TemplatedEmail.
            I'll do the same for the other method:
            return $email and add the TemplatedEmail return type.
         */
        return $email;

    }

    /*
        That looks really nice!
        Our controller is now more readable.
        Let's repeat the same thing for our weekly report email.
        In this case, the two things we need are the $author
        that we're going to send to - which is a User object - and the array of articles.
        Ok, over in our new Mailer class, add a public function sendAuthorWeeklyReportMessage() with a User object argument called $author and an array of Article objects.
     */
    public function sendAuthorWeeklyReportMessage( User $author, array $articles): TemplatedEmail
    {
        /*
            Time to steal some code!
            Back in the command, copy everything related to sending the email,
            which in this case includes the entrypoint reset, Twig render, PDF code and the actual email logic.
            Paste that into Mailer.
         */
        $this->entrypointLookup->reset();
        /*
            Back in the method!
            We're already using the properties
            and everything looks happy!
            Oh, and it's minor, but I'm going to move the "entrypoint reset" code below the render.
            This is subtle but it makes sure that the Encore stuff is reset after we render our template.
            If some other part of our app calls this methods and then renders its own template,
            Encore will now be ready to do work correctly for them.
         */
        $this->entrypointLookup->reset();

        $email = (new TemplatedEmail())
            ->from(new Address('alienmailcarrier@example.com',
                'The Space Bar'))
            ->to(new Address($author->getEmail(), $author->getFirstName()))
            ->subject('Your weekly report on the Space Bar!')
            ->htmlTemplate('email/author-weekly-report.html.twig')
            ->context([
                'author' => $author,
                'articles' => $articles,
            ]
        );
        $this->mailer->send($email);

        return $email;
    }

}

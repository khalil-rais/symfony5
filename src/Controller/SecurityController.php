<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\Model\UserRegistrationFormModel;
use App\Form\UserRegistrationFormType;
use App\Security\LoginFormAuthenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Exception\ExceptionInterface as MailerException;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils)
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error'         => $error,
        ]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
        throw new \Exception('Will be intercepted before getting here');
    }

    /**
     * @Route("/register", name="app_register")
     */
    public function register(MailerInterface $mailer, Request $request, UserPasswordHasherInterface $passwordHasher, GuardAuthenticatorHandler $guardHandler, LoginFormAuthenticator $formAuthenticator)
    /*
    Ok how do we send this email?
    As soon as we installed the Mailer component,
    Symfony configured a new mailer service for us that we can autowire by using the MailerInterface type-hint.
    Let's add that as one of the arguments to our controller method:
    MailerInterface $mailer.
    */
    {
        $form = $this->createForm(UserRegistrationFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UserRegistrationFormModel $userModel */
            $userModel = $form->getData();

            $user = new User();
            $user->setFirstName($userModel->firstName);
            $user->setEmail($userModel->email);
            $user->setPassword($passwordHasher->hashPassword(
                $user,
                $userModel->plainPassword
            ));
            // be absolutely sure they agree
            if (true === $userModel->agreeTerms) {
                $user->agreeToTerms();
            }
            $user->setSubscribeToNewsletter($userModel->subscribeToNewsletter);

            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();
/*
Time to send an email!
After a user registers for a new account,
we should probably send them a welcome email.
The controller for this page lives at src/Controller/SecurityController.php,
find the register() method.
This is a very traditional controller:
it creates a Symfony form,
processes it,
saves a new User object to the database
and ultimately redirects when it finishes.
Let's send an email right here:
right after the user is saved, but before the redirect.
Start with $email = (new Email()) - the one from the Mime namespace.
 */
            /*
            I've put the new Email object in parentheses on purpose:
            it allows us to immediately chain off of this to configure the message.
            Pretty much all the methods on the Email class are delightfully boring & familiar.
            Let's set the ->from() address to alienmailer@example.com,
            the ->to() to the address of the user that just registered, so $user->getEmail(),
            and this email needs a snazzy subject: “Welcome to the Space Bar!”!
             */

            /*
            Say hello to our fancy new templates/email/welcome.html.twig file.
            This is a full HTML page with embedded styling via a <style> tag
            and nothing else interesting: it's 100% static.
            This %name% thing I added here isn't a variable:
            it's just a reminder of something that we need to make dynamic later.
            But first, let's use this!
            As soon as your email needs to leverage a Twig template,
            you need to change from the Email class to TemplatedEmail.
            Hold Command or Ctrl and click that class to jump into it.
            Ah, this TemplatedEmail class extends the normal Email:
            we're really still using the same class as before,
            but with a few extra methods related to templates.
            Let's use one of these.
            Remove both the html() and text() calls - you'll see why in a minute -
            and replace them with ->htmlTemplate() and then the normal path to the template: email/welcome.html.twig.
            And that's it!
            Before we try this, let's make a few things in the template dynamic,
            like the URLs and the image path.
            But, there's an important thing to remember with emails: paths must always be absolute.
            That's next.
            */
            /*
                When you set the HTML part of an email,
                Mailer helps out by creating the "text" version for us!
                If you did want to control this manually, in SecurityController,
                you could set this the text by calling either the text() method or textTemplate()
                to render a template that would only contain text.
             */
            $email = (new TemplatedEmail())
                ->from(new Address('alienmailcarrier@example.com', 'The Space Bar'))
                ->to(new Address($user->getEmail(), $user->getFirstName()))
                ->subject('Welcome to the Space Bar!')
                ->htmlTemplate('email/welcome.html.twig')
                ->text("Nice to meet you {$user->getFirstName()}! ❤")
                /*
                    Every email can contain content in two formats, or "parts": a "text" part and an HTML part.
                    And an email can contain just the text part, just the HTML part or both.
                    Of course, these days, most email clients support HTML,
                    so that's the format you really need to focus on.
                    But there are still some situations where having a text version is useful,
                    so we won't completely forget about text.
                    You'll see what I mean.
                    The email we just sent did not contain the HTML "part" - only the text version.
                    How do we also include an HTML version of the content?
                    Back in the controller, you can almost guess how:
                    copy the ->text(...) line,
                    delete the semicolon,
                    paste and change the method to html().
                    It's that simple! To make it fancier, put an <h1> around this.

                    This email now has two "parts": a text part and an HTML part.
                    The user's email client will choose which to show, usually HTML.
                    Let's see what this looks like in Mailtrap.
                    Click back to get to the registration form again,
                    change the email address,
                    add a password and register!
                    No errors!
                    Check out Mailtrap.
                    Yeah! This time we have an HTML version!
                    One of the things I love about Mailtrap is how easily we can see the original HTML source, the text or the rendered HTML.
                 */
                ->html("<h1>Nice to meet you {$user->getFirstName()}! ❤</h1>");;
                /*
                There are a bunch more methods on this class, like cc(), addCc(), bcc() and more
                but most of these are dead-easy to understand.
                And because it's such a simple class,
                you can look inside to see what else is possible, like replyTo().
                We'll talk about many of these - like attaching files - later.
                That's what it looks like to create an email.
                 */
            /*
                And what methods does this object have on it?
                Oh, just one: $mailer->send() and pass this $email.
             */
            try {
                $mailer->send($email);
                $this->addFlash('success', 'Welcome email sent! Check your mailbox (or Mailtrap inbox for the SMTP credentials in APP_MAILER_DSN).');
            } catch (MailerException $e) {
                $this->addFlash('warning', 'Your account was created but the welcome email could not be sent: ' . $e->getMessage());
            }

            return $guardHandler->authenticateUserAndHandleSuccess(
                $user,
                $request,
                $formAuthenticator,
                'main'
            );
        }

        return $this->render('security/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}

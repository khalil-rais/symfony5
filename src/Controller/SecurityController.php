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
            $email = (new Email())
                ->from(new Address('alienmailcarrier@example.com', 'The Space Bar'))
                ->to(new Address($user->getEmail(), $user->getFirstName()))
                ->subject('Welcome to the Space Bar!')
                ->text("Nice to meet you {$user->getFirstName()}! ❤");
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

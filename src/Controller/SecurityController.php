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
use Symfony\Component\Mime\Email;

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
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, GuardAuthenticatorHandler $guardHandler, LoginFormAuthenticator $formAuthenticator)
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
            $email = (new Email());

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

<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/*
    We don't have to, but to make this match the src/Controller directory structure,
    create a new Controller/ folder inside of tests/...
    and move the test file there.
    Don't forget to add \Controller to the end of its namespace.
 */
class SecurityControllerTest extends WebTestCase
{
    /*
        And, again, to stay somewhat conventional,
        let's rename the method to testRegister().
     */
    public function testRegister(): void
    {
        /*
            We won't go too deep into the details of how to write functional tests,
            but it's a pretty simple idea.
            First, we create a $client object,
            which is almost like a "browser":
            it helps us make requests to our app.
            In this case, we want to make a GET request to /register to load the form.
         */
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        /*
            The assertResponseIsSuccessful() method is a helper assertion from Symfony
            that will make sure the response wasn't an error or a redirect.
         */
        $this->assertResponseIsSuccessful();
        /*
            Now I'll remove the assertSelectorTextContains() and paste in the rest of the test.
         */
        $button = $crawler->selectButton('Register');
        $form = $button->form();
        /*
            Let's see: this goes to /register,
            finds the Register button by its text,
            and then fills out all the form fields.
            These funny-looking values are literally the name attributes of each element
            if you looked at the source HTML.
         */
        $form['user_registration_form[firstName]']->setValue('Ryan');
        $form['user_registration_form[email]']->setValue(sprintf('foo%s@example.com', rand()));
        $form['user_registration_form[plainPassword]']->setValue('space_rocks');
        $form['user_registration_form[agreeTerms]']->tick();
        $client->submit($form);
        /*
            After submitting the form,
            we assert that the response is a redirect
            which is an easy way to assert that the form submit was successful.

            If there's a validation error,
            it re-renders without redirecting.

            We've used the registration form on this site about 100 times.
            So we know it works and so this test should pass.

            Whenever you say that something "should" work in programming,
            do you ever get the sinking feeling
            that you're about to eat your words?
            Ah, I'm sure nothing bad will happen in this case.
            Let's try it!
         */
        $this->assertResponseRedirects();
        /*
            At your terminal, run just this test with:
            php bin/phpunit tests/Controller/SecurityControllerTest.php
            Deprecation notices of course and woh!
            It failed!
            And dumped some giant HTML which is impossible to read
            unless you go all the way to the top.
            Ah! “Failed asserting that the Response is redirected: 500 internal server error.
            And down in the HTML:
            “Connection could not be established with host tcp://localhost:25”
         */
    }
}

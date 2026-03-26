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
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Hello World');
    }
}

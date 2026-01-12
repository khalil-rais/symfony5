<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImagePostControllerTest extends WebTestCase
{
    public function testCreate()
    {
        $client = static::createClient();
        $uploadedFile = new UploadedFile(
            __DIR__.'/../fixtures/ryan-fabien.png',
            'ryan-fabien.png'
        );
        $client->request('POST', '/api/images', [], [
            'file' => $uploadedFile
        ]);
        $this->assertResponseIsSuccessful();
    }
}
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
            __DIR__.'/../fixtures/ryan-fabien.jpg',
            'ryan-fabien.jpg'
        );
        $client->request('POST', '/api/images', [], [
            'file' => $uploadedFile
        ]);
        dd($client->getResponse()->getContent());
    }
}
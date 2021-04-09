<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Repository\UserRepository;

class AuthControllerTest extends WebTestCase
{
	/** @test */
    public function registration()
    {
        $client = static::createClient();
        $permitted_chars = 'abcdefghijklmnopqrstuvwxyz';
        $client->request('POST', '/register', [
        	'email' => substr(str_shuffle($permitted_chars), 0, 10).'@gmail.com', 
        	'password'=>'1234567',
        	'password_confirmation'=>'1234567',
        	'name'=>'user123',
        	'age'=>22
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}


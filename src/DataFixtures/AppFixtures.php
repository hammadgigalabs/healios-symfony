<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
// use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use App\Entity\User;

class AppFixtures extends Fixture
{
	private $encoder;

    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    public function load(ObjectManager $manager)
    {
    	$user = new User();
    	$encodedPassword = $this->encoder->encodePassword($user, 'pass_1234');
	    $user->setEmail('admin@gmail.com');
	    $user->setPassword($encodedPassword);
	    $user->setRoles(['ROLE_ADMIN']);
        $user->setName('Admin');
        $user->setAge('18');
	    // $user->setCreatedBy(1);
	    $manager->persist($user);
        $manager->flush();
    }
}

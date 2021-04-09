<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use App\Entity\Auth;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

class AuthController extends AbstractController
{    
    /**
     * @Route("/register", name="api_register", methods={"POST"})
     */
    public function register(EntityManagerInterface $om, UserPasswordEncoderInterface $passwordEncoder, Request $request, ValidatorInterface $validator)
    {
        $user = new User();
        $email                  = $request->request->get("email");
        $password               = $request->request->get("password");
        $passwordConfirmation   = $request->request->get("password_confirmation");
        $name                   = $request->request->get('name');
        $age                    = $request->request->get('age');
        $input = [
          'email'=>$request->request->get("email"), 
          'password'=>$request->request->get("password")
        ];
        
        $constraints = new Assert\Collection([
            'email' => [new Assert\Email(), new Assert\NotBlank],
            'password' => [  
                new Assert\IdenticalTo([
                  'value'=>$request->request->get("password_confirmation"),
                  'message'=>'Password should match confirm password'
                ]), 
                new Assert\NotBlank,
                new Assert\Length(['min' => 6])
            ]
          ]
        );

        $violations = $validator->validate($input, $constraints);

        if (count($violations) > 0) {
            $accessor = PropertyAccess::createPropertyAccessor();
            $errorMessages = [];
            foreach ($violations as $violation) {
                $accessor->setValue($errorMessages,
                $violation->getPropertyPath(),
                $violation->getMessage());
            }
            return $this->json([
               'errors' => $errorMessages
           ], 400);
        }

        $encodedPassword = $passwordEncoder->encodePassword($user, $password);
        $user->setEmail($email);
        $user->setPassword($encodedPassword);
        $user->setRoles(['ROLE_USER']);
        $user->setName($name);
        $user->setAge($age);
        $om->persist($user);
        $om->flush();
        return $this->json([
           'user' => $user
        ]);
       
    }

    /**
     * @Route("/login", name="api_login", methods={"POST"})
     */
    public function login(Request $request, EntityManagerInterface $om)
    {
      return $this->json(['result' => true]);
    }

    /**
     * @Route("/profile", name="api_profile")
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_USER')")
     */
    public function profile()
    {
      return $this->json([
          'user' => $this->getUser()
      ], 200, [], [
          'groups' => ['api']
      ]);
    }

    /**
     * @Route("/", name="api_home")
     */
    public function home()
    {
       return $this->json(['result' => true]);
    }
}

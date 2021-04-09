<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use App\Entity\User;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;


class UserController extends AbstractController
{
    /**
     * @Route("/user", name="user")
     */
    public function index(): Response
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/UserController.php',
        ]);
    }


    /**
     * @Route("/create", name="api_user_create", methods={"POST"})
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function create(EntityManagerInterface $om, UserPasswordEncoderInterface $passwordEncoder, Request $request)
    {
        $user = new User();
        $email                  = $request->request->get("email");
        $password               = $request->request->get("password");
        $passwordConfirmation   = $request->request->get("password_confirmation");
        $roles                  = $request->request->get("roles");
        $input = [
          'email'=>$request->request->get("email"), 
          'password'=>$request->request->get("password"),
          'roles'=>$request->request->get("roles"),
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
            ],
            'roles' => [
              new Assert\Type('array'),
              new Assert\Count(['min'=>1]),

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
        $user->setRoles($roles);
        $om->persist($user);
        $om->flush();
        return $this->json([
           'user' => $user
        ]);
    }
    
    /**
     * @Route("/getUser/{id?}", name="api_get_user")
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_USER')")
     */
    public function getUserProfile($id)
    {
        if (!in_array('ROLE_ADMIN', $this->getUser()->getRoles(), true))
            $id = $this->getUser()->getId();

        $user = $this->getDoctrine()->getRepository(User::class)->find($id);

        return $this->json([
            'user' => $user
        ], 200, [], [
            'groups' => ['api']
        ]);
    }

    /**
     * @Route("/updateUser/{id?}", name="api_update_user", methods={"POST"})
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_USER')")
     */
    public function updateUser($id, EntityManagerInterface $om, Request $request) {
        if (!in_array('ROLE_ADMIN', $this->getUser()->getRoles(), true))
            $id = $this->getUser()->getId();    

        $user = $this->getDoctrine()->getRepository(User::class)->find($id);

        $name   = $request->request->get("name");
        $age    = $request->request->get("age");

        $user->setName($name);
        $user->setAge($age);
        try
        {
           $om->persist($user);
           $om->flush();
            return $this->json([
                'User'=>$user,
                'Message' =>'User update successfully' 
            ], 200, [], [
                'groups' => ['api']
            ]);
        }
        catch(\Exception $e)
        {
            $errors[] = "Unable to update user at this time.";
        }
        
        return $this->json([
           'errors' => $errors
        ], 400);

    }

    /**
     * @Route("/deleteUser/{id?}", name="api_delete_user")
     * @Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_USER')")
     */
    public function deleteUser($id, TokenStorageInterface $tokenStorage, EntityManagerInterface $om)
    {
        $logout = false;
        if (!in_array('ROLE_ADMIN', $this->getUser()->getRoles(), true)){
            $id = $this->getUser()->getId();
            $logout = true;
        }
        $user = $this->getDoctrine()->getRepository(User::class)->find($id);
        $om->remove($user);
        $om->flush();
        if ($logout) {
            $tokenStorage->setToken();
            $response = new Response();
            $response->headers->clearCookie('jwt');
        }
        return $this->json([
            'user' => "delete user {$id}"
        ], 200, [], [
            'groups' => ['api']
        ]);
    }
}

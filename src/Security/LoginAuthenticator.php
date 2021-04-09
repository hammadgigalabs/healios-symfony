<?php
namespace App\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Firebase\JWT\JWT;
use App\Entity\Auth;
use Doctrine\ORM\EntityManagerInterface;

class LoginAuthenticator extends AbstractGuardAuthenticator
{
   private $passwordEncoder;
   private $em;
   public function __construct(UserPasswordEncoderInterface $passwordEncoder, EntityManagerInterface $om)
   {
       $this->passwordEncoder = $passwordEncoder;
       $this->em = $om;
   }
   public function supports(Request $request)
   {
       return $request->get("_route") === "api_login" && $request->isMethod("POST");
   }
   public function getCredentials(Request $request)
   {
       return [
           'email' => $request->request->get("email"),
           'password' => $request->request->get("password")
       ];
   }
   public function getUser($credentials, UserProviderInterface $userProvider)
   {
        // var_dump($credentials);die;
        return $userProvider->loadUserByUsername($credentials['email']);
   }
   public function checkCredentials($credentials, UserInterface $user)
   {
        
       return $this->passwordEncoder->isPasswordValid($user, $credentials['password']);
   }
   public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
   {
       return new JsonResponse([
           'error' => $exception->getMessageKey()
       ], 400);
   }
    
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        // var_dump('1');
        // die;
        $expireTime = time() + 3600;
        $tokenPayload = [
            'user_id' => $token->getUser()->getId(),
            'email'   => $token->getUser()->getEmail(),
            'exp'     => $expireTime
        ];

        $jwt = JWT::encode($tokenPayload, $_ENV['JWT_SECRET']);

        // If you are developing on a non-https server, you will need to set
        // the $useHttps variable to false
        // var_dump($jwt);die;
        $useHttps = false;
        setcookie("jwt", $jwt, $expireTime, "", "127.0.0.1", $useHttps, true);

        // $om = $this->getDoctrine()->getManager();
        $auth = new Auth();
        $auth->setUserId($token->getUser()->getId());
        $auth->setToken($jwt);
        $this->em->persist($auth);
        $this->em->flush();

        // return new JsonResponse([
        //     'result' => true
        // ]);
    }

   public function start(Request $request, AuthenticationException $authException = null)
   {
       return new JsonResponse([
           'error' => 'Access Denied'
       ]);
   }
   public function supportsRememberMe()
   {
       return false;
   }
}